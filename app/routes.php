<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

use PayPal\Api\Agreement;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Payer;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

Route::get('/', function()
{
	return View::make('hello');
});

Route::get('paypal', function () {

    // Replace these values by entering your own ClientId and Secret by visiting https://developer.paypal.com/webapps/developer/applications/myapps
    $clientId = 'AWYo0-KqPiTnqr5WJKH0dHXa4mq161o4ZzK1WQ3v93CVuBTRVjTmh_57Z_cyUUfrFWT1ogYSZdIvLzwP';
    $clientSecret = 'EO3SEwlcf4J5ZaLJ4VZUdzDTMYVheLrgzXOl8SzYxh9_xGTUrTIcdTdmJhv9Ci9yKtxcL6iCxS4jmKBd';

    /** @var \Paypal\Rest\ApiContext $apiContext */
    $apiContext = getApiContext($clientId, $clientSecret);


    // Create a new instance of Plan object
    $plan = new Plan();

    // # Basic Information
    // Fill up the basic information that is required for the plan
    $plan->setName('T-Shirt of the Month Club Plan')
        ->setDescription('Template creation.')
        ->setType('fixed');

// # Payment definitions for this billing plan.
    $paymentDefinitionTrial = new PaymentDefinition();
    $paymentDefinitionRegular = new PaymentDefinition();

// The possible values for such setters are mentioned in the setter method documentation.
// Just open the class file. e.g. lib/PayPal/Api/PaymentDefinition.php and look for setFrequency method.
// You should be able to see the acceptable values in the comments.
    $paymentDefinitionTrial->setName('Regular Payments')
        ->setType('REGULAR')
        ->setFrequency('Month')
        ->setFrequencyInterval("1")
        ->setCycles('11')
        ->setAmount(new Currency(array('value' => 1, 'currency' => 'USD')));

    $paymentDefinitionRegular->setName('Trial Payments')
        ->setType('TRIAL')
        ->setFrequency('Month')
        ->setFrequencyInterval("1")
        ->setCycles("1")
        ->setAmount(new Currency(array('value' => 60, 'currency' => 'USD')));

    var_dump($paymentDefinitionTrial);

    $merchantPreferences = new MerchantPreferences();
    $baseUrl = 'http://payment-paypal.app';
    // ReturnURL and CancelURL are not required and used when creating billing agreement with payment_method as "credit_card".
    // However, it is generally a good idea to set these values, in case you plan to create billing agreements which accepts "paypal" as payment_method.
    // This will keep your plan compatible with both the possible scenarios on how it is being used in agreement.
    $merchantPreferences->setReturnUrl("$baseUrl/payment-success")
        ->setCancelUrl("$baseUrl/payment-cancel")
        ->setAutoBillAmount("yes")
        ->setInitialFailAmountAction("CONTINUE")
        ->setMaxFailAttempts("0")
        ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));


    $plan->setPaymentDefinitions(array($paymentDefinitionTrial, $paymentDefinitionRegular));
    $plan->setMerchantPreferences($merchantPreferences);

// For Sample Purposes Only.
    $request = clone $plan;

// ### Create Plan
    try {
        $output = $plan->create($apiContext);
    } catch (Exception $ex) {
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        dd("error", $ex);
        exit(1);
    }

// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    dd("Created Plan", "Plan", $output->getId(), $output);

//    return $output;
});

Route::get('active-plan', function () {

});

Route::get('billing', function () {
    $agreement = new Agreement();

    $agreement->setName('Base Agreement')
        ->setDescription('Basic Agreement')
        ->setStartDate('2019-06-17T9:45:04Z');

    // Add Plan ID
    // Please note that the plan Id should be only set in this case.
    $plan = new Plan();
    $plan->setId('P-4HB678111E604820NCDSSYCA');
    $agreement->setPlan($plan);

    // Add Payer
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');
    $agreement->setPayer($payer);

    // For Sample Purposes Only.
    $request = clone $agreement;

    $clientId = 'AWYo0-KqPiTnqr5WJKH0dHXa4mq161o4ZzK1WQ3v93CVuBTRVjTmh_57Z_cyUUfrFWT1ogYSZdIvLzwP';
    $clientSecret = 'EO3SEwlcf4J5ZaLJ4VZUdzDTMYVheLrgzXOl8SzYxh9_xGTUrTIcdTdmJhv9Ci9yKtxcL6iCxS4jmKBd';
    $apiContext = getApiContext($clientId, $clientSecret);

    // ### Create Agreement
    try {
        // Please note that as the agreement has not yet activated, we wont be receiving the ID just yet.
        $agreement = $agreement->create($apiContext);

        // ### Get redirect url
        // The API response provides the url that you must redirect
        // the buyer to. Retrieve the url from the $agreement->getApprovalLink()
        // method
        $approvalUrl = $agreement->getApprovalLink();
    } catch (Exception $ex) {
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        dd("error.", $ex);
        exit(1);
    }

// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    dd("Created Billing Agreement. Please visit the URL to Approve.", "Agreement", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $agreement);
});


/**
 * Helper method for getting an APIContext for all calls
 * @param string $clientId Client ID
 * @param string $clientSecret Client Secret
 * @return PayPal\Rest\ApiContext
 */
function getApiContext($clientId, $clientSecret)
{

    // #### SDK configuration
    // Register the sdk_config.ini file in current directory
    // as the configuration source.
    /*
    if(!defined("PP_CONFIG_PATH")) {
        define("PP_CONFIG_PATH", __DIR__);
    }
    */


    // ### Api context
    // Use an ApiContext object to authenticate
    // API calls. The clientId and clientSecret for the
    // OAuthTokenCredential class can be retrieved from
    // developer.paypal.com

    $apiContext = new ApiContext(
        new OAuthTokenCredential(
            $clientId,
            $clientSecret
        )
    );

    // Comment this line out and uncomment the PP_CONFIG_PATH
    // 'define' block if you want to use static file
    // based configuration

    $apiContext->setConfig(
        array(
            'mode' => 'sandbox',
            'log.LogEnabled' => true,
            'log.FileName' => '../PayPal.log',
            'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
            'cache.enabled' => true,
            // 'http.CURLOPT_CONNECTTIMEOUT' => 30
            // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            //'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
        )
    );

    // Partner Attribution Id
    // Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
    // To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
    // $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');

    return $apiContext;
}