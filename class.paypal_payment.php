<?php 
//require_once 'class.ugp_client.php';
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/class.ugp_client.php');

 /**
  * This is the Paypal Payment Class
  * =============================================
  * 
  * PHP version 5
  * Managing Paypal payments 
  * 
  * Use CURL class to execute the web services 
  * 
  * @author     Code implementation : David Raleche <david.raleche@high5games.com>
  * @version    August 25th 2014 
  * 
  */


class PaypalPayment extends UgpClient  
{
    /**
     * ugpClient
     * 
     * @var object
     */

    public $ugpClient;

    /**
     * accountTypeId
     * 
     * @var interger
     */

    private $accountTypeId          = 2; //Facebook, PlayReal

    /**
     * Casino Id
     * 
     * @var interger
     */

    private $casinoId               = 2; //High5Casino

    /**
     * ReturnUrl
     * 
     * @var string
     */

	private $returnUrl;

	/*
     * CancelUrl
     * 
     * @var string
     */

	private $cancelUrl;



  /**
   *
   * Constructor Paypal Class
   *
   * @param 	integer   	$casinoId 			{Casino Id (2 high5casino, 3 ShakeTheSky)}
   * @param 	integer   	$accountTypeId 		{Facebook, PlayReal, GoogleAccount}
   * @return 	boolean 		
   *
   * Instanciate a UGPClient Object and set variables casinoId & accountTypeId
   *
   */
	public function __construct()
	{
		//Instanciate Ugp Client Object
		$this->ugpClient    	= new UgpClient();
  }

  /**
   *
   * Initiate Paypal Paypal
   *
   * @param 	integer   	$accountId 		{Facebook, PlayReal, GoogleAccount}
   * @param 	integer   	$packageToken 	
   * @return 	Json object or False
   *
   * Instanciate a UGPClient Object and set variables casinoId & accountTypeId
   *
   */    
  	public function initiatePayment($initialiationParameterJsonObject)
    {	
  		//Check If json object
  		if(is_object(json_decode($initialiationParameterJsonObject)) === false)
  			// throw new Exception('Unable to load - No Json Object');
  			 return false;

  		//Decode Json Object
  		$paypalParameterJsonObject = json_decode($initialiationParameterJsonObject);
  		     		   
  		 //Initialize Variable
  		$this->casinoId			   = $paypalParameterJsonObject->casinoId;
  		$this->accountTypeId	 = $paypalParameterJsonObject->accountTypeId;
  		$this->returnUrl 		   = $paypalParameterJsonObject->returnUrl; 
  		$this->cancelUrl 		   = $paypalParameterJsonObject->cancelUrl;
  		$this->accountId 		   = $paypalParameterJsonObject->accountId;
  		$this->packageToken		 = $paypalParameterJsonObject->packageToken;
      $this->purchaseToken   = $paypalParameterJsonObject->purchaseToken;

      	//HTTP METHOD
  		$httpMethod 	= 'POST';

  		//Parameters for UPG
  		$ugpService 	= 'storeService/v1/orders/'.$this->accountId .'/5';

  		//BUILD the URL for UGP
  		$webServiceUrl 	= $ugpService;

  		$body 			= array(
       // "packageToken"	=> $this->packageToken,
  			"returnUrl"		=> $this->returnUrl,
  			"cancelUrl"		=> $this->cancelUrl,
        "purchaseToken"    => $this->purchaseToken,
  			"casinoId"  	=> $this->casinoId);

  		// CURL EXECUTION
  		$this->ugpClient  = $this->ugpClient->request($webServiceUrl, $httpMethod, null, $body);

  		if($this->ugpClient->isCode(200)) 		
  			return $this->ugpClient->getData();
  		else
  			return false;
    }


  /**
   *
   * Initiate Paypal Paypal
   *
   * @param 	integer   	$accountId 		{Facebook, PlayReal, GoogleAccount}
   * @param 	integer   	$orderId 	
   * @return 	Json object or False
   *
   * Instanciate a UGPClient Object and set variables casinoId & accountTypeId
   *
   */    
    public function getInformation($accountId, $orderId)
    {
  		//HTTP METHOD
  		$httpMethod = 'GET';

  		//Parameters for UPG
  		$ugpService 		= "storeService/v1/orders/$accountId/order/$orderId";

  		//BUILD the URL for UGP
  		$webServiceUrl 	= $ugpService;

  		// CURL EXECUTION
  		$this->ugpClient  = $this->ugpClient->request($webServiceUrl, $httpMethod, null, null);

          //$this->ugpClient->handleResponse($body);

  		if($this->ugpClient->isCode(200)) 		
  			return true;
  		else
  			return false;
	}


  /**
   *
   * Complete Paypal Payment
   *
   * @param 	integer   	$accountId 		{Facebook, PlayReal, GoogleAccount}
   * @param 	integer   	$paymentId 	
   * @return 	Json object or False
   *
   * Instanciate a UGPClient Object and set variables casinoId & accountTypeId
   *
   */        
  public function completePayment($accountId, $paymentId, $payerId, $cancelUrl ,$accountTypeId)
    {
  		//HTTP METHOD
  		$httpMethod = 'POST';

  		//Parameters for UPG
  		$ugpService 		= "storeService/v1/orders/$accountId/5/$paymentId";

  		//BUILD the URL for UGP
  		$webServiceUrl 	= $ugpService;

  		//Body parameter
  		$body = array( 
      "paymentId"         => $paymentId, 
  		"payerId"           => $payerId, 
  		"accountTypeId" 		=> $accountTypeId,
  		"cancelUrl" 		    => $cancelUrl,
  		"externalStatus"		=> "APPROVED",
  		"casinoId"          => $this->casinoId);

  		// CURL EXECUTION
  		$this->ugpClient  = $this->ugpClient->request($webServiceUrl, $httpMethod, null, $body);

          //$this->ugpClient->handleResponse($body);

  		if($this->ugpClient->isCode(200)) 		
  			return true;
  		else
  			return false;
	}
}
?>