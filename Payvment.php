<?php

/**
 * This module implements the basic authentication methods needed
 * to interface w/ Payvment's Application
 * 
 * version: 1.0
 * 
 *
 * @author mjelks
 */

require_once('BasePayvment.php');

define('PRODUCTION_APPLICATION_ID', '<YOUR_APP_ID>');
define('PRODUCTION_APPLICATION_SECRET', '<YOUR_APP_SECRET>');
define('PRODUCTION_API_CALLBACK', 'https://api.payvment.com');

define('SANDBOX_APPLICATION_ID', '<YOUR_SANDBOX_APP_ID>');
define('SANDBOX_APPLICATION_SECRET', '<YOUR_SANDBOX_APP_SECRET>');
define('SANDBOX_API_CALLBACK', 'https://api-sandbox.payvment.com');

class Payvment extends BasePayvment {
    
    private $_request;
    private $_sandbox;
    
    protected $_callbackUrl;
    protected $_applicationId;
    protected $_redirectUrl;
    protected $_payvmentId;
    protected $_payvmentToken;

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
    
    public function __construct($request=false)
    {
        // we pass in the request varible to allow for dependency injection
        // http://www.richardcastera.com/blog/php-convert-array-to-object-with-stdclass
        $this->_request = (!empty($request)) ? (object) $request : (object) $_REQUEST;
        $this->_sandbox = (isset($this->_request->sandbox)) ? true : false;
        
        $this->_callbackUrl = ($this->_sandbox) ? SANDBOX_API_CALLBACK : PRODUCTION_API_CALLBACK;
        
        $this->_applicationId = ($this->_sandbox) ? SANDBOX_APPLICATION_ID : PRODUCTION_APPLICATION_ID;
        $this->_applicationSecret = ($this->_sandbox) ? SANDBOX_APPLICATION_SECRET : PRODUCTION_APPLICATION_SECRET;
        
    }
    
    public function generateAuthorizationUrl($redirect=true)
    {
        $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
        $this->_redirectUrl .= ($this->_sandbox) ? '?sandbox=1' : '';
        
        $authorizeUrl = 
            $this->_callbackUrl . 
            "/oauth/authorize?" .
            "client_id={$this->_applicationId}&" .
            "redirect_uri=" . urlencode($this->_redirectUrl) . "&" .
            "state=" . $_SESSION['state'];
            
        // @codeCoverageIgnoreStart
        if ($redirect) {
            header('Location: ' . $authorizeUrl);
        }
        // @codeCoverageIgnoreEnd
        else {
            return $authorizeUrl;
        } 
    // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd
    
    public function generateTokenUrl()
    {
        $tokenUrl = 
            $this->_callbackUrl . "/oauth/accesstoken?" . 
            "client_id={$this->_applicationId}" . 
            "&client_secret={$this->_applicationSecret}" . 
            "&code=" . $this->_request->code;
            
        return $tokenUrl;
    }
    
    
    /**
     * Passing in a url or file resource,
     * return the xml document
     * NOTE: simplexml_load_file returns false if invalid or no xml 
     * 
     * @param string $url 
     * @return mixed (boolean/xml) 
     */
    public function getXml($url)
    {
        return simplexml_load_file($url);        
    }
    
    
    public function isUserAuthenticated()
    {
        $authenticated = false;
        
        if (
            isset($this->_payvmentId) && is_int($this->_payvmentId) && 
            isset($this->_payvmentToken) && !empty($this->_payvmentToken)
        ) 
        {
            $authenticated = true;
        }
        
        return $authenticated;
    }
    
    public function generateToken()
    {
        if($this->_request->state == $_SESSION['state']) 
        {
            
            //Make request for access token and Payvment ID
            $xml = $this->getXml($this->generateTokenUrl());
            if (isset($xml->payvment_userid) && isset($xml->token)) {
                $this->setPayvmentId($xml->payvment_userid); //Store Payvment ID to your DB
                $this->setPayvmentToken($xml->token); //Store access token to your DB
            } else {
                throw new Exception('Token and/or xml document not returned.');
            }
        } 
        else
        {
            throw new Exception('The state does not match. You may be a victim of CSRF.');
        }
        
        return true;
    }
    
    /* Payvment API Support */
    
    /**
     * This is the REST call for Payvment's orders API
     * the default command will pull all orders for a given retailer
     * 
     * @param string $command
     * @return string $url
     * 
     */
    public function getOrdersUrl($command="pullOrders")
    {
        $url = $this->_callbackUrl . "/rest/orders/?access_token=" . 
                $this->_payvmentToken . "&command=" . $command;
        
        return $url;
    }
    
    /**
     *
     * Return all orders for a given retailer -- 
     * 
     * @param string $format
     * @return string $result
     */
    public function orders($command='pullOrders', $format='xml')
    {
        $result = false;

        switch ($format) {
            case 'xml':
                $result = $this->getXml($this->getOrdersUrl($command));
                break;

            default:
                $result = 'Invalid format passed.';
                break;
        }
        
        return $result;
    }
    
}
