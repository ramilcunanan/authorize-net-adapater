<?php
ini_set("display_errors", true);
require '../authorize/sdk/autoload.php';
require 'authnet_config.php';
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

  define("AUTHORIZENET_LOG_FILE", "phplog");

class AuthorizeNetAdapter
{
    private $logFile = "debug.txt";
    private $myreturn = array();
    /*
    public function __construct(AnetApiRequestType $request)
    {
        $responseType = 'net\authorize\api\contract\v1\ARBGetSubscriptionResponse';
        parent::__construct($request, $responseType);
    }

    protected function validateRequest()
    {
        //validate required fields of $this->apiRequest->

        //validate non-required fields of $this->apiRequest->
    }
    */

    public function __construct()
    {
        $this->_cleanLog();
    }

    private function _cleanLog()
    {
        if (file_exists( $this->logFile )) {
          unlink( $this->logFile );
        }
    }

    private function _logMe( $str = "", $customLogFile = null )
    {
        /*
        for ($ctr = 1; $ctr <= $tab; $ctr++) {
            $str .= "\t" . $str;
        }
        */
        $logFile = $this->logFile;
        if ( !is_null($customLogFile) ) {
            $logFile = $customLogFile;
        }

        file_put_contents( $logFile, $str . PHP_EOL, FILE_APPEND );
    }

    /*   Set up for API credentials
     *   @param customerId (required) - from customer_id field of the database
     */
    private function _setAPICredentials()
    {
        $this->_logMe("\t" . __FUNCTION__);
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(\AuthNet\Config::MERCHANT_LOGIN_ID);
        $merchantAuthentication->setTransactionKey(\AuthNet\Config::MERCHANT_TRANSACTION_KEY);

        return $merchantAuthentication;
    }

    private function _handleError()
    {
        // TODO: create error handler here
    }

    /**
     * @param mainClass
     * @param callbacks
     *
     */
    private function _traverseCallbacks( $mainClass, $callbacks )
    {
        foreach ( $callbacks as $key => $value ) {
            $test = $mainClass;
            if ( is_array($value) ) {
                $test = call_user_func( array($test, 'get'.$key) );

                $this->_traverseCallbacks($test, $value);
            } else {
                if ( $value == 'count' ) {
                    $this->myreturn[$key] = $value( call_user_func( array($test, 'get'.$key) ) );
                } else {
                    $this->myreturn[$key] = call_user_func( array($test, 'get'.$key) );
                }
            }
        }

        return $callbacks;
    }

    /*   Send action and param to API server
     *   @param target (required) - the target namespace to instantiate
     *   @param request (required) - the data to be sent to the API server
     *   @param callbackId (required) - the method to be called to return the generated ID
     */
    private function _sendAction( $target, $request, $callbacks )
    {
        $targetController = '\\net\\authorize\\api\\controller\\' . $target;
        $this->_logMe("\t" . __FUNCTION__);
        $controller = new $targetController($request);
        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

        $responseObj = new stdClass();
        $this->_logMe(print_r($response, true), "response.txt");
        if ( ($response != null) && ($response->getMessages()->getResultCode() == "Ok") ) {
            $responseObj->success = 1;

            if ( is_array($callbacks) ) {
                $test = $response;

                $this->_traverseCallbacks($test, $callbacks);

                $responseObj->info = $this->myreturn;
            } else {
                $responseObj->id = $response->{$callbacks}();
            }
        } else {
            $responseObj->success = 0;
            $responseObj->code = $response->getMessages()->getMessage()[0]->getCode();
            $responseObj->text = $response->getMessages()->getMessage()[0]->getText();
        }

        return json_encode($responseObj);
    }

    /*   Set up for API credentials
     *   @param customer_info(required) - from customer_id field of the database
     *   @param customerId (required) - from customer_id field of the database
     *   @param customerId (required) - from customer_id field of the database
     */
    private function _setCustomerProfile( $profileInfo, $profileId = null )
    {
        $customerProfile = new AnetAPI\CustomerProfileExType();
        $customerProfile->setMerchantCustomerId($profileInfo->merchantId);
        $customerProfile->setDescription($profileInfo->description);
        $customerProfile->setEmail($profileInfo->email);

        if ( !is_null($profileId) ) {
            $customerProfile->setCustomerProfileId($profileId);
        }

        return $customerProfile;
    }

    /**
     * Create a new plain customer profile
     * @param customerId (required) - from customer_id field of the database
     * @param description (optional) - from label field of the database
     * @param email (optional) - email address of the contact
     * @return response - response object
     */
    public function addCustomerProfile( $profileInfo )
    {
        $customerProfile = $this->_setCustomerProfile((object)$profileInfo);

        $request = new AnetAPI\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setProfile($customerProfile);

        $controller = "CreateCustomerProfileController";

        $callbacks = array(
            'CustomerProfileId' => '',
            'CustomerPaymentProfileIdList' => 'count',
            'customerShippingAddressIdList' => 'count'
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function getCustomerProfile( $profileId )
    {
        // TODO what do we do next with the info we got from API?
        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);

        $controller = "GetCustomerProfileController";
        $callbacks = array(
            'Profile' => array (
                'CustomerProfileId' => '',
                'MerchantCustomerId' => '',
                'Description' => '',
                'Email' => '',
                'PaymentProfiles' => 'count',
                'ShipToList' => 'count'
            )
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function updateCustomerProfile( $profileInfo, $profileId )
    {
        $customerProfile = $this->_setCustomerProfile((object)$profileInfo, $profileId);

        $request = new AnetAPI\UpdateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setProfile($customerProfile);

        $controller = "UpdateCustomerProfileController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function deleteCustomerProfile( $profileId )
    {
        $request = new AnetAPI\DeleteCustomerProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);

        $controller = "DeleteCustomerProfileController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    private function _setCreditCard( $ccInfo )
    {
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($ccInfo->number);
        $creditCard->setExpirationDate($ccInfo->expiration);
        $creditCard->setCardCode($ccInfo->code);

        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);

        return $paymentCreditCard;
    }

    // used by payment info
    // used by shipping info
    private function _setAddressInfo( $addressInfo, $addressId = null )
    {
        $customerAddress = new AnetAPI\CustomerAddressExType();
        $customerAddress->setFirstName($addressInfo->firstname);
        $customerAddress->setLastName($addressInfo->lastname);
        $customerAddress->setCompany($addressInfo->company);
        $customerAddress->setAddress($addressInfo->address);
        $customerAddress->setCity($addressInfo->city);
        $customerAddress->setState($addressInfo->state);
        $customerAddress->setZip($addressInfo->zip);
        $customerAddress->setPhoneNumber($addressInfo->phone);
        $customerAddress->setfaxNumber($addressInfo->fax);
        $customerAddress->setCountry($addressInfo->country);

        if ( !is_null($addressId) ) {
            $customerAddress->setCustomerAddressId($addressId);
        }

        return $customerAddress;
    }

    /*   Set up for API credentials
     *
     */
    public function addCustomerPaymentProfile( $profileId, $ccInfo, $billingInfo )
    {
        $addressInfo = $this->_setAddressInfo( (object)$billingInfo );
        $creditCard = $this->_setCreditCard( (object)$ccInfo );

        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($addressInfo);
        $paymentProfile->setPayment($creditCard);

        $paymentProfiles[] = $paymentProfile;

        $request = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setPaymentProfile($paymentProfiles);
        $request->setValidationMode(\SampleCode\Config::VALIDATION_MODE);

        $controller = "CreateCustomerPaymentProfileController";
        $callbacks = array(
            'CustomerProfileId' => '',
            'CustomerPaymentProfileId' => ''
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function getCustomerPaymentProfile( $profileId, $paymentId )
    {
        $request = new AnetAPI\GetCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
    	$request->setCustomerPaymentProfileId((int)$paymentId);

        $controller = "GetCustomerPaymentProfileController";
        $callbacks = array(
            'PaymentProfile' => array (
                'CustomerProfileId' => '',
                'CustomerPaymentProfileId' => '',
                'CustomerType' => '',
                'Payment' => array (
                    'CreditCard' => array(
                        'CardNumber' => '',
                        'ExpirationDate' => '',
                        'CardType' => ''
                    )
                ),
                'BillTo' => array(
                    'FirstName' => '',
                    'LastName' => '',
                    'Company' => '',
                    'Email' => '',
                    'Address' => '',
                    'City' => '',
                    'State' => '',
                    'Zip' => '',
                    'PhoneNumber' => '',
                    'FaxNumber' => '',
                    'Country' => ''
                )
            )
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function updateCustomerPaymentProfile( $profileId, $paymentId, $ccInfo, $billingInfo )
    {
        $addressInfo = $this->_setAddressInfo( (object)$billingInfo );
        $creditCard = $this->_setCreditCard( (object)$ccInfo );

        $paymentProfile = new AnetAPI\CustomerPaymentProfileExType();
        $paymentProfile->setCustomerPaymentProfileId((int)$paymentId);
        $paymentProfile->setBillTo($addressInfo);
        $paymentProfile->setPayment($creditCard);

        $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setPaymentProfile($paymentProfile);
        $request->setValidationMode(\SampleCode\Config::VALIDATION_MODE);

        $controller = "UpdateCustomerPaymentProfileController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function deleteCustomerPaymentProfile( $profileId, $paymentId )
    {
        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setCustomerPaymentProfileId((int)$paymentId);

        $controller = "DeleteCustomerPaymentProfileController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    /*
     *
     */
    public function addCustomerShippingProfile( $profileId, $shippingInfo )
    {
        $addressInfo = $this->_setAddressInfo( (object)$shippingInfo );

        $request = new AnetAPI\CreateCustomerShippingAddressRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setAddress($addressInfo);

        $controller = "CreateCustomerShippingAddressController";
        $callbacks = array(
            'customerProfileId' => '',
            'customerAddressId' => ''
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function getCustomerShippingProfile( $profileId, $shippingId )
    {
        $request = new AnetAPI\GetCustomerShippingAddressRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
    	$request->setCustomerAddressId((int)$shippingId);

        $controller = "GetCustomerShippingAddressController";
        $callbacks = array(
            'Address' => array(
                'CustomerAddressId' => '',
                'FirstName' => '',
                'LastName' => '',
                'Company' => '',
                'Email' => '',
                'Address' => '',
                'City' => '',
                'State' => '',
                'Zip' => '',
                'PhoneNumber' => '',
                'FaxNumber' => '',
                'Country' => ''
            )
        );

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public function updateCustomerShippingProfile( $profileId, $shippingId, $shippingInfo )
    {
        $addressInfo = $this->_setAddressInfo( (object)$shippingInfo, $shippingId );

        $request = new AnetAPI\UpdateCustomerShippingAddressRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setAddress($addressInfo);

        $controller = "UpdateCustomerShippingAddressController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }

    public  function deleteCustomerShippingProfile( $profileId, $shippingId )
    {
        $request = new AnetAPI\DeleteCustomerShippingAddressRequest();
        $request->setMerchantAuthentication($this->_setAPICredentials());
        $request->setCustomerProfileId((int)$profileId);
        $request->setCustomerAddressId((int)$shippingId);

        $controller = "DeleteCustomerShippingAddressController";
        $callbacks = array();

        $response = $this->_sendAction($controller, $request, $callbacks);

        return $response;
    }
}
