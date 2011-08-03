<?php
/*
    This example demonstrates how one can use Payvment's Oauth 2.0 mechanism to authenticate
    and pull orders. The sample code below is a Drupal example and uses some of the conventions
    from that platform. For this example, assume the following table used to store Payvment's
    credentials on the Drupal site:

    CREATE TABLE payvment_integration (
        uid int(10) unsigned NOT NULL,
        payvment_id INT(11) UNSIGNED NOT NULL, 
        token varchar(255), PRIMARY KEY(`payvment_id`)
    );
*/

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// load in the necessary Payvment library
require_once('Payvment/Payvment.php');
$payvment = new Payvment();

session_start();

// 1. get payvmentUserToken/payvmentUserToken (third-party dependent)
$result = db_query("SELECT * FROM {payvment_integration} WHERE uid = %d LIMIT 1", $user->uid);
while ($column = db_fetch_array($result)) {
    if (isset($column['payvment_id']) && isset($column['payvment_id'])) {
        $payvment->setPayvmentId(intval($column['payvment_id']));
        $payvment->setPayvmentToken($column['token']);
    }
}

// 2a. if we have an access token, we can start making REST calls to Payvment APIs
if ($payvment->isUserAuthenticated()) 
{
    // you can now run Payvment API calls
    // additional order 'commands' reference can be found here:
    // http://www.payvment.com/developers/docs_ordermanagement.php
    
    // get all orders
    print_r($payvment->orders());
    
    // get all orders, but limit to 2 and 'new' orders
    $params = array(
        'command'=> 'pullOrders', 
        'limit' => 2,
        'state' => 'new'
    );
    print_r($payvment->orders($params));
    
    // get specific order
    $params = array('orderId' => '16028');
    print_r($payvment->orders($params));
    

} 
//  2b. else, we need to generate a token and authenticate with Payvment
else 
{
    // 3. the first time through we set the redirect url so Payvment knows where to go after 
    // heading over to Payvment to acquire an access token
    if (empty($_REQUEST['code']))
    {
        // set your redirect url
        $payvment->setRedirectUrl('http://<my-test-domain.com>/drupal/ipn.php');
        // the default behavior of this method is to do a 'header' redirect for you in PHP
        // if you set to false, it will simply return the url and you can handle it yourself
        $payvment->generateAuthorizationUrl(); // user gets redirected out to Payvment Token Generator
    } 
    
    // 4.   assuming you redirect to the same php file (this one, for instance), 
    //      generate a token
    //      the token generation process will set the appropriate payvmentId and payvmentToken
    //      use the getter function to grab these values and insert into your database
    try {
        $payvment->generateToken();

        // store this info into our integration table:
        $result = db_query(
            "INSERT INTO {payvment_integration} 
            (payvment_id, token, uid) 
            VALUES 
            ('%s', '%s', %d)", 
            $payvment->getPayvmentId(), $payvment->getPayvmentToken(), $user->uid
        );
        // now that we have the token properly stored, refresh the page 
        header("Location: <my-test-domain.com>" );
    } catch (Exception $exception) {
        echo $exception->getMessage() . "<br/>" . $exception->getTraceAsString();
    }


}

//$txn_id = db_result(db_query("SELECT stock FROM {uc_product_stock} WHERE order_id = %d ORDER BY received ASC", $arg1->order_id));



?>
