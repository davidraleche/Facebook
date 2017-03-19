<?php

require_once __DIR__ . '/global.php';
require_once __DIR__ . '/class.ugp_client.php';

/**
 * This is the Tracking Event Class
 * =============================================
 *
 * PHP version 5
 * Managing All Events on Client side
 *
 * Use CURL class to execute the web services
 *
 * @author     Code implementation : David Raleche <david.raleche@high5games.com>
 * @version    September 29th 2015
 *
 */

class TrackingEvent
{
    public  $UGPRequest = null;
    public  $parameters = null;
    public  $actionCode = '';
    public  $errorMessage = null;
    public  $trackingEventObjects;
    public  $jsonTrackingEventConfiguration;
    public  $fbAppId;
    private $client;

    /**
     * Constructor
     *
     */
    public function __construct($queryStringParameters)
    {
        //FB QA 1 APP ID = 646095282164633
        $this->fbAppId = $this->ifVariableSet($queryStringParameters['fb_app_id'], 646095282164633); 
        try {
            //Set AuthenticationToken from User
            if(!isset($queryStringParameters['userProfile']['applicationToken'])
                OR ($queryStringParameters['userProfile']['applicationToken'] === NULL))
            {
                $this->applicationToken = null;
            }
            else
            {
                $this->applicationToken = $queryStringParameters['userProfile']['applicationToken'];
            }
            $this->client = new UgpClient($this->applicationToken);
            //additionalParameter and actionCode Objects
            $this->jsonTrackingEventConfiguration = $this->openFile('../data/trackingEvent.json');
            $this->trackingEventObjects = $this->jsonDecode($this->jsonTrackingEventConfiguration);
            // Match FB trackingName to an actionCode
            $this->parameters = $this->ifVariableSet($queryStringParameters['parameters'], null);
            $this->userProfile = $this->ifVariableSet($queryStringParameters['userProfile'], null);
            $this->actionCode = $this->setActionCode();
        }
        catch (Exception $e)
        {
            err($this->errorMessage = 'Constructor Error '.$e);
        }
    }

    /**
     * Helper function if variable SET
     *
     * @return variable
     */
    public function ifVariableSet(&$variable, $defaultValue = null)
    {
        if(isset($variable)){
            return $variable;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Helper openFile
     *
     * @throws Exception
     * @return variable
     *
     */
    public function openFile($file)
    {
        $fileContent = file_get_contents($file);
        if($fileContent === false){
            throw new Exception('File cannot be opened :'.$file);
        }
        return $fileContent;
    }

    /**
     * Helper jsonDecode
     *
     * @return variable
     * @throws Exception
     */
    public function jsonDecode($json)
    {
        $decodedJson = json_decode($json);
        if($decodedJson === false){
            throw new Exception('Json cannot be decoded :'.$json);
        }
        return $decodedJson;
    }

    /**
     * Set Action Code to the object from [tracking Parameter]
     *
     * @throws Exception
     * @return actionCode
     */
    private function setActionCode()
    {
        $actionCode = null;
        $trackingName = $this->ifVariableSet($this->parameters['tracking'],null);
        if($trackingName != null){
            $actionCode = $this->findActionCode($trackingName);
        } else {
            throw new Exception('No action Code from findActionCode TrackingName:NULL');
        }
        return $actionCode;
    }

    /**
     * Match trackingName and ActionCode
     * @param trackingName
     *
     * @throws Exception
     * @return actionCode
     */
    public function findActionCode($trackingName){
        foreach($this->trackingEventObjects->actionCode as $key => $value){
            if ($key === $trackingName){
                return $value;
                break;
            }
        }
        throw new Exception( 'No action Code from findActionCode() - TrackingName:'.$trackingName);
        return null;
    }

    /**
     * Send Facebook App Event
     *
     * @return TrackingEvent
     */
    public function initializeFacebookEvent()
    {
        $this->trackingName = $this->ifVariableSet($this->parameters['tracking'],null);
        $this->numberGiftEarned = $this->ifVariableSet($this->parameters['numberGiftEarned'],0);
        $this->numberGiftSent = $this->ifVariableSet($this->parameters['numberGiftSent'],0);
        $this->numberGiftReceived = $this->ifVariableSet($this->parameters['numberGiftReceived'],0);
        $this->fbAccessToken =  $this->ifVariableSet($this->userProfile['fbAccessToken'],0);
        $this->casinoUserId =  $this->ifVariableSet($this->userProfile['casinoUserId'],0);
        $this->FBUserId =  $this->ifVariableSet($this->userProfile['FBUserId'],0);
        $this->customEventsFields = array(
            '_appVersion' => urlencode('DIGIPRESENCE'),
            'casinoUserId' => urlencode($this->casinoUserId),
            'FBUserId' => urlencode($this->FBUserId),
            'numberGiftSent' => urlencode($this->numberGiftSent),
            'numberGiftReceived' => urlencode($this->numberGiftReceived),
            'numberGiftEarned' => urlencode($this->numberGiftEarned),
            '_eventName' => urlencode($this->trackingName)
        );
    }

    /**
     * Customized error message
     *
     * void
     */
    public function customizedErrorMessage($customizedErrorMessage, $eventFields, $resultSendFBAppEvents)
    {
        err($customizedErrorMessage.'  '
        .print_r($eventFields,1).'  '
        .$resultSendFBAppEvents['response']
        .print_r($resultSendFBAppEvents,1));
    }

    /**
     * Attempt send FbApp events
     *
     * void
     */
    public function attemptToSendFacebookAppEvent($eventFields)
    {
        global $ALLOW_DEBUG;
        //Execute FB app events
        $urlSendFBAppEvent = "https://graph.facebook.com/$this->fbAppId/activities";
        $resultSendFBAppEvents = $this->sendFBAppEvents($urlSendFBAppEvent ,$eventFields);
        if($ALLOW_DEBUG === true)
            $this->customizedErrorMessage('Facebook App Events  ',$eventFields,$resultSendFBAppEvents );
        if ( $resultSendFBAppEvents['info']['http_code'] > 200 || $resultSendFBAppEvents['info']['http_code'] == 0){
            $this->customizedErrorMessage('ERROR  Facebook App Events  ',$eventFields,$resultSendFBAppEvents );
            sleep(5);        
           $resultSendFBAppEvents = $this->sendFBAppEvents($urlSendFBAppEvent ,$eventFields);
           if ( $resultSendFBAppEvents['info']['http_code'] > 200 || $resultSendFBAppEvents['info']['http_code'] == 0){
            $this->customizedErrorMessage('ERROR [2] Facebook App Events  ',$eventFields, $resultSendFBAppEvents );
             sleep(20);
            $resultSendFBAppEvents = $this->sendFBAppEvents($urlSendFBAppEvent ,$eventFields);
            if ( $resultSendFBAppEvents['info']['http_code'] > 200 || $resultSendFBAppEvents['info']['http_code'] == 0)
                $this->customizedErrorMessage('ERROR [3] Facebook App Events  ',$eventFields,$resultSendFBAppEvents );
           }
        } 
    }

    /**
     * Send Facebook App Event
     *
     * @return TrackingEvent
     */
    public function sendFacebookAppEvent()
    {
        $this->initializeFacebookEvent();
        $eventFields = array(
            'event' => urlencode('CUSTOM_APP_EVENTS'),
            'access_token' => urlencode($this->fbAccessToken),
            'application_tracking_enabled' => 1,
            'advertiser_tracking_enabled' => 1,
            //"advertiser_id=1383-6676-5853-8751", 
            'custom_events' => '['.json_encode($this->customEventsFields).']'
        );
        $this->attemptToSendFacebookAppEvent($eventFields);
    }

    public function sendFBAppEvents($web_service_url, $fields)
    {
        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $web_service_url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        //Execute POST
        $output['response'] = curl_exec($ch);
        $output['info'] = curl_getinfo($ch);
        $output['error'] =  curl_error($ch);
        //close connection
        curl_close($ch);
        return($output);
    }

    /**
     * Attempt Sent actionCode
     *
     * @return TrackingEvent
     */
    public function sendActionCode()
    {
        if($this->errorMessage === null){
            //Array for additional parameters to the Action Code
            $arrayParameters = $this->prepareDataArrayToSend($this->parameters);
            //Prepare Request to UGP Analytics
            $this->UGPRequest = $this->prepareRequestUGP($arrayParameters);
            if($this->UGPRequest !== null) //Execute UGP Request
                $this->client->disableLogging()->request('loggingService/v2/clientActions/', 'post', null, $this->UGPRequest);  
            if(isset($this->parameters['activeFacebookAppEvent']))
                if($this->parameters['activeFacebookAppEvent'] == '1')
                    $this->sendFacebookAppEvent();
            $response = $this->prepareResponseData(); ///Prepare JSON Response
            // CATCH ERRORS
            $data = json_decode($response);
            if($data->http_code > 300)
                err('Action Code Error '.print_r($response,1));
            //Return Object
            return $response;
        }
    }

    /**
     * WIKI - http://high5-wiki.high5.local/display/BD/Game+Action+Codes
     *
     * @return TrackingEvent
     * //Parameters[3] = [key,value,valueType]
     */
    private function prepareDataArrayToSend($parameters)
    {
        $newParameters = array();
        foreach($parameters as $key => $value){
            $keyfound = $this->assignKeyNumberToParameterName($key);
            if($keyfound != null){
                array_push($newParameters,$keyfound['keyNumber'].','. $value.','.$keyfound['valueType']);
            }
        }
        return $newParameters;
    }

    /**
     * Attempt Sent actionCode
     *
     * @return TrackingEvent
     *
     * 'storyForm':'ImplicitShare',
     *  "ShareType":[{"key":"9","valueType":"String"}],
     */
    private function assignKeyNumberToParameterName($TheKEY){
        foreach($this->trackingEventObjects->additionalParameters as $key => $value){
            if ($key === $TheKEY){
                return array('keyName'=>$TheKEY, 'keyNumber'=>$value[0]->key, "valueType"=>$value[0]->valueType);
                break;
            }
        }
        return null;
    }

    /**
     * Get analytics data
     * //actionCode
     * //Parameters[] = [key0,value1,valueType2]
     *
     * @return array
     */
    private function prepareRequestUGP($arrayParameters)
    {
        $data =  array('event' => $this->actionCode); //Set Core Action Code
        $parameters_array = array();
        foreach($arrayParameters as $iterator => $iteratorValue)
        {
            $parseIteratorValue = $this->explodeString(',',$iteratorValue);
            $key =  $this->ifVariableSet($parseIteratorValue[0],null);
            $value = $this->ifVariableSet($parseIteratorValue[1],null);
            $valueType = $this->ifVariableSet($parseIteratorValue[2],null);
            if($valueType === 'long'){ // Cast LONG Value or UGP analytic
                $value =  intval($value);
            }
            $parameters = //SET parameters
                array(
                    'key'       =>"$key",
                    "value"     => $value,
                    "isNull"    => is_null($value),
                    "dataType"  => "$valueType");
            $parameters_array[] = $parameters ;
        }
        $data['parameters'] = $parameters_array;  //Add additional value to the action Code
        if($this->actionCode === null){$data = null;} //If no ActionCode do not sent request RETURN NULL
        return $data;
    }

    /**
     * Helper explodeString
     *
     * @return variable
     */
    public function explodeString($separator, $string)
    {
        return explode($separator, $string);;
    }

    /**
     * Handle response
     *
     * @return Response
     */
    protected function prepareResponseData()
    {
        $response = array();
        $response['http_code'] = (null !== ($this->client->getInfo('http_code'))) ?
            $this->client->getInfo('http_code') : "Request Not Sent";
        $response['url'] = (null !== ($this->client->getInfo('url')))? $this->client->getInfo('url') : "No Url";
        $response['request'] = $this->ifVariableSet($this->UGPRequest, $this->errorMessage);
        $response['message'] = $this->ifVariableSet($this->client->getData()->message , '');
        return json_encode($response);
    }

    /**
     * Helper arrayPush
     *
     * @return variable
     */
    public function arrayPush($array, $newValue){
        return array_push($array, $newValue);
    }
}
