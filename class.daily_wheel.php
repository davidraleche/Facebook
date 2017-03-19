<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/class.ugp_client.php';

/**
* This is the Daily WHeel Class
* =============================================
* 
* PHP version 5
* Managing Daily Wheel Flow
* 
* Use CURL class to execute web services endpoints
* 
* @author     Code implementation : David Raleche
* @version    April 21st 2016 
* 
*/

class DailyWheel
{
  public $userAppToken;
  public $accountTypeId = 2;
  public $applicationID;
  public $queryStringParam;
  public $extId;
  public $casinoUserId = 0;    
  public $fbUserProfile;
  public $platformSoundEnabled;
  public $ungivenGiftQuantity = 0;
  public $unacceptedGiftArray = array();
  public $unacceptedGiftQuantity = 0;
  public $externalFriends;
  public $sizeAward;
  public $earning ;
  public $earnedToday = 0;
  public $maxGifts = 0;
  public $balanceBeforeGift = 0;
  public $balanceAfterGift = 0;
  public $giftAmount = 0;
  public $getThankYous;
  public $http_code = 200;

  /**
   * Gifting Constructor
   *
   * @return void
   */
  public function __construct($queryStringParam, $applicationId = 2, $accountTypeId = 2)
  {
      $this->queryStringParam = $queryStringParam;
      $this->applicationID = $applicationId;
      $this->accountTypeId = $accountTypeId;
      $this->extId = isset($queryStringParam['FBuserId'])?$queryStringParam['FBuserId']:'NoFBUserId';
      $this->senderId = isset($queryStringParam['senderId'])?$queryStringParam['senderId']:0;
      $this->userId = isset($queryStringParam['userId'])?$queryStringParam['userId']:null;
      $this->casinoUserId = isset($queryStringParam['casinoUserId'])?$queryStringParam['casinoUserId']:$this->senderId;
      $this->userAppToken = isset($queryStringParam['appLoginToken'])?$queryStringParam['appLoginToken']:'NoUserTOKEN';
      $this->platformSoundEnabled = isset($queryStringParam['platformSoundEnabled'])?$queryStringParam['platformSoundEnabled']:'true';
      $this->clientServiceAppToken = new UgpClient();
      $this->clientUserAppToken = new UgpClient($this->userAppToken);

      $this->getDailyBonusInformation();
  }

  /**
   * Execute API Protocol;
   *
   *
   * @return array
   */    
  public function executeApi()
  {
      switch ($this->queryStringParam['action']){
          case 'acceptDailyWheel':
              return $this->acceptDailyWheel($this->queryStringParam['casinoUserId'],$this->queryStringParam['dailyBonusExtId']);
          break;
          case 'resetDailyWheel':
              return $this->resetDailyWheel($this->queryStringParam['casinoUserId'],$this->queryStringParam['dailyBonusExtId']);
          break;
          case 'setGamePlayedtoTrue':
              return $this->setGamePlayedtoTrue($this->queryStringParam['casinoUserId'],$this->queryStringParam['dailyBonusExtId']);
          break;
          default:
              return 'NO ACTION IDENTIFIED';
          break;
      }
  }

  /**
   *   Accept the thank yous for the given user.
   *
   * @return array
   */    
  public function acceptDailyWheel($userId,$dailyBonusExtId)
  {
      $httpMethod = 'POST';
      $ugpRequest = "/dailyService/v1/bonus/app/{$this->applicationID}/user/{$userId}/accept";
      $body = array("dailyBonusExtId"=>$dailyBonusExtId);
      $this->clientUserAppToken->request($ugpRequest, $httpMethod, null, $body);
      $response['http_code'] = $this->http_code = $this->clientUserAppToken->getCode();
      $response['amount'] = $this->clientUserAppToken->getData()->amount;
      $response['balanceBeforeBonus'] = $this->clientUserAppToken->getData()->balanceBeforeBonus;
      $response['balanceAfterBonus'] = $this->clientUserAppToken->getData()->balanceAfterBonus;
      return json_encode($response);
  }

  /**
   *   Accept the thank yous for the given user.
   *
   * @return array
   */    
  public function resetDailyWheel($userId,$dailyBonusExtId)
  {
      $httpMethod = 'POST';
      $ugpRequest = "/dailyService/v1/bonus/app/{$this->applicationID}/user/{$userId}/gaffe";            
      $body = array("dailyBonusExtId"=>$userId);
      $this->clientUserAppToken->request($ugpRequest, $httpMethod, null, $body);
      $response['http_code'] = $this->http_code = $this->clientUserAppToken->getCode();
      return json_encode($response);
  }

  /**
   *   Set the game played flag to true on the given daily bonus.
   *
   * @return array
   */    
  public function setGamePlayedtoTrue($userId,$dailyBonusExtId)
  {
      $httpMethod = 'POST';
      $ugpRequest = "/dailyService/v1/bonus/app/{$this->applicationID}/user/{$userId}/gamePlayed";      
      $body = array("dailyBonusExtId"=>$dailyBonusExtId);
      $this->clientUserAppToken->request($ugpRequest, $httpMethod, null, $body);
      $response['http_code'] = $this->http_code = $this->clientUserAppToken->getCode();
      return json_encode($response);
  }

 /**
   *      Get Daily Bonus Information by userId
   *
   *      @return array
   */
 public function getDailyBonusInformation()
  {
      $response['response'] = null;
      if($this->http_code > 300){
          err('DAILYWHEEL ERROR 1 [getDailyBonusInformation()] : http_code: '.$this->http_code.'   <br>'.print_r($this->clientUserAppToken,1));
          $response['clientUserAppToken'] = $this->clientUserAppToken;
          return json_encode($response);         
      }

      $ugpRequest = "/dailyService/v1/bonus/app/{$this->applicationID}/user/{$this->casinoUserId}";
      $this->clientUserAppToken->request($ugpRequest);
      if($this->clientUserAppToken->getCode() >= 200 and $this->clientUserAppToken->getCode() < 300) {
          $response['response'] = $this->getDailyWheelInformation = $this->clientUserAppToken->getData();
          return json_encode($response);
      }
      $this->http_code = $this->clientUserAppToken->getCode();
      err('DAILYWHEEL ERROR 2 [getDailyBonusInformation()] :<br>'
        . json_encode($response)." User App Token:".$this->userAppToken.print_r($this->clientUserAppToken,1) );
      $response['clientUserAppToken'] = $this->clientUserAppToken;
      return json_encode($response); ;
  }
}
