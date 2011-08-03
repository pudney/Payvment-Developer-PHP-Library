<?php
/**
 * Payvment tests
 */
class PayvmentTest extends PHPUnit_Framework_TestCase
{
    public $oauth;
    public $request = array(
             'code' => 'abcXYZ'
           );
    public $sandbox = false;
    public $redirectUrl = 'http://www.test.com/';
    public $mockPayvment;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->oauth = new Payvment($this->request);
        
        $this->mockPayvment = $this->getMock(
            'Payvment',
            array(
                "getOrdersUrl",
                "generateTokenUrl"
            ),
            array(),
            '',
            false
        );
        
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated TokensTest::tearDown()
        $this->oauth = null;
        parent::tearDown();
    }
    
    public function testGenerateAuthorizationUrl()
    {
        /* should look like:
            https://api.payvment.com/oauth/authorize?
            client_id=727274
            &redirect_uri=http%3A%2F%2Fwww.test.com%2F
            &state=59a9b46c62facbc38adc9bc79b08330
        */
        $this->oauth->setRedirectUrl($this->redirectUrl);
        $actual = $this->oauth->generateAuthorizationUrl(false);
        
        $this->assertRegExp('/https:\/\/api.payvment.com\/oauth\/authorize\?client_id=\d+\&redirect_uri=.*\&state=\w+/', $actual);
        
             
    }
    
    public function testGenerateAuthorizationUrlSandbox()
    {
        /* should look like:
            https://api-sandbox.payvment.com/oauth/authorize?
            client_id=727274
            &redirect_uri=http%3A%2F%2Fwww.test.com%2F
            &state=59a9b46c62facbc38adc9bc79b08330
        */
        $this->request['sandbox'] = true;
        $this->oauth = new Payvment($this->request);
        $this->oauth->setRedirectUrl($this->redirectUrl);
        $actual = $this->oauth->generateAuthorizationUrl(false);
        
        $this->assertRegExp('/https:\/\/api-sandbox.payvment.com\/oauth\/authorize\?client_id=\d+\&redirect_uri=.*\&state=\w+/', $actual);
             
    }
    
    public function testGenerateTokenUrl()
    {
        /* should look like:
            https://api.payvment.com/?
            client_id=727274
            &client_secret=asjdflkajsfAS121
            &code=59a9b46c62facbc38adc9bc79b08330
        */
        
        $actual = $this->oauth->generateTokenUrl();
        
        $this->assertRegExp('/https:\/\/api.payvment.com\/oauth\/accesstoken\?client_id=\d+\&client_secret=.*\&code=\w+/', $actual);
        
             
    }
    
    /*public function testgenerateAuthorizationUrlRedirects()
    {
        $authUrl = $this->oauth->generateAuthorizationUrl(false);
        
        override_function('header', 'foo', 'return "foo"');
        
        $this->oauth->setRedirectUrl($this->redirectUrl);
        $actual = $this->oauth->generateAuthorizationUrl();
        
        $this->assertRegExp('/https:\/\/api.payvment.com\/oauth\/authorize\?client_id=\d+\&redirect_uri=.*\&state=\w+/', $actual);
    }*/
    
    public function testSetRedirectUrl()
    {
        $this->oauth->setRedirectUrl($this->redirectUrl);
        $expected = $this->oauth->getRedirectUrl();
        $this->assertEquals($expected, $this->redirectUrl);
    }
    
    public function testGetOrdersUrlWith()
    {
        //$this->oauth = new Payvment($this->request);
        $this->oauth->setPayvmentToken('abc123');
        $expectedUrl = 'https://api.payvment.com/rest/orders/?access_token=' . $this->oauth->getPayvmentToken();
        
        // test with no params sent
        $actual = $this->oauth->getOrdersUrl();
        $this->assertEquals($expectedUrl, $actual);
        
        
        // test with command param
        $params = array('command' => 'pullOrders');
        $expectedUrl .= "&command=pullOrders";
        $actual = $this->oauth->getOrdersUrl($params);
        $this->assertEquals($expectedUrl, $actual);
    }
    
    public function testMagicFunctions()
    {
        $expected = 'testVal';
        $oauth = new Payvment($this->request);
        $oauth->testKey = $expected;
        $this->assertEquals($expected, $oauth->testKey);
        $actual = isset($oauth->testKey);
        
        $this->assertTrue($actual);
    }
    
    public function testIsUserAuthenticated()
    {
        $this->oauth = new Payvment($this->request);
        $this->oauth->setPayvmentId(1234);
        $this->oauth->setPayvmentToken('abc123');
        $actual = $this->oauth->isUserAuthenticated();
        
        $this->assertTrue($actual);
        
    }
    
    public function testIsUserAuthenticatedNOT()
    {
        $this->oauth = new Payvment($this->request);
        $this->oauth->payvmentId = 'xyz';
        $actual = $this->oauth->isUserAuthenticated();
        
        $this->assertFalse($actual);
    }
    
    public function testLoadOrders()
    {
        $file = dirname(__FILE__) . '/xml/testOrders.xml';
        $this->mockPayvment->expects($this->any())
                ->method("getOrdersUrl")
                ->will($this->returnValue($file));
        $this->mockPayvment->orders();
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testLoadOrdersInvalidOrFalse()
    {
        $this->mockPayvment->expects($this->any())
                ->method("getOrdersUrl")
                ->will($this->returnValue('bad.xml'));
        $actual = $this->mockPayvment->orders();
        $this->assertFalse($actual);
    }
    
    public function testLoadOrdersInvalidFormat()
    {
        $this->mockPayvment->expects($this->any())
                ->method("getOrdersUrl")
                ->will($this->returnValue('bad.xml'));
        $actual = $this->mockPayvment->orders(false, 'fooFormat');
        $this->assertEquals($actual,'Invalid format passed.');
        
    }
    
    public function testGenerateToken()
    {
        $expectedPayvmentId = 561011;
        $expectedPayvmentToken = 'abc123';
        
        $this->request['state'] = $_SESSION['state'] = '123xyz';
        //$this->mockPayvment = new Payvment($this->request);
        
        $file = dirname(__FILE__) . '/xml/token.xml';
        
        $this->mockPayvment = $this->getMock(
            'Payvment',
            array(
                "generateTokenUrl"
            ),
            array($this->request),
            '',
            true
        );
        
        $this->mockPayvment->expects($this->any())
                ->method("generateTokenUrl")
                ->will($this->returnValue($file));
        try {
            $this->mockPayvment->generateToken();
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        
        $payvmentId = intval($this->mockPayvment->getPayvmentId());
        $payvmentToken = $this->mockPayvment->getPayvmentToken();
        echo (intval($payvmentId));
        $this->assertEquals($expectedPayvmentId, $payvmentId);
        //$this->assertEquals($expectedPayvmentToken, $payvmentToken);
    }
    
    public function testGenerateTokenThrowsFaultyXMLException()
    {
        $fileMalformed = dirname(__FILE__) . '/xml/tokenMalformed.xml';
        $this->request['state'] = $_SESSION['state'] = '123xyz';
        
        $this->mockPayvment = $this->getMock(
            'Payvment',
            array(
                "generateTokenUrl"
            ),
            array($this->request),
            '',
            true
        );
        
        // test faulty xml
        $this->mockPayvment->expects($this->any())
                ->method("generateTokenUrl")
                ->will($this->returnValue($fileMalformed));
        try {
            $this->mockPayvment->generateToken();
        } catch (Exception $exc) {
            $this->assertTrue($exc instanceof Exception);
            $this->assertEquals($exc->getMessage(), 'Token and/or xml document not returned.');
            return true;
        }
        
        $this->fail('Exception not thrown for testGenerateTokenThrowsFaultyXMLException()');
    }
    public function testGenerateTokenThrowsCSRFException()
    {
        $file = dirname(__FILE__) . '/xml/token.xml';
        
        $this->request['state'] = '123xyz';
        $_SESSION['state'] = '122222';
        
        $this->mockPayvment = $this->getMock(
            'Payvment',
            array(
                "generateTokenUrl"
            ),
            array($this->request),
            '',
            true
        );
        
        // test faulty xml
        $this->mockPayvment->expects($this->any())
                ->method("generateTokenUrl")
                ->will($this->returnValue($file));
        try {
            $this->mockPayvment->generateToken();
        } catch (Exception $exc) {
            $this->assertTrue($exc instanceof Exception);
            $this->assertEquals($exc->getMessage(), 'The state does not match. You may be a victim of CSRF.');
            return true;
        }
        
        $this->fail('Exception not thrown for testGenerateTokenThrowsCSRFException()');
    }
}