<?php
/**
 * This is the Facebook User Class
 *
 * Managing Facebook Session and Facebook User profile Info
 *
 * @author  David Raleche <david.raleche@high5games.com> / Nathan Stokes <nathan.stokes@high5games.com> /
 *
 * @since v1.0 - Jan 28 2015
 * Time: 1:51 PM
 */

use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;

class FacebookUser {

    private $userId = null;
    private $session = null;
    private $accessToken = null;
    private $appId = null;
    private $secret = null;
    private $originId = 2;

    /**
     * Constructor
     */
    public function __construct($appId, $secret, $originId = 2)
    {
        $this->originId = $originId;
        $this->appId = $appId;
        $this->secret = $secret;

        try {
            //Initialize Facebook Session
            FacebookSession::setDefaultApplication($this->appId, $this->secret);

            $this->initializeFacebookSessionBasedOnOriginId();

            $this->throwExceptionIfAccessTokenNotValid();

        }
        catch (\Exception $e) {
            $this->secret = null; //protect from the logs
            err("Class FacebookUser [__construct]: Exception : {$e->getMessage()}  Object: ".print_r($this,1));
            $this->resetAllObjectParameters();

        }
    }


    /**
     * Get Facebook User information
     *
     * @param 	string   	$facebookUserProfileFields
     * @return 	object or null
     *
     */
    public function getProfile($facebookUserProfileFields)
    {
        try
        {
            if ( ! ($this->session instanceof FacebookSession))
            {
                err("Class FacebookUser [getProfile()]: No FacebookSession instance");

                return null;
            }

            $request = new FacebookRequest($this->session, 'GET', "/{$this->userId}?{$facebookUserProfileFields}");
            $user    = $request->execute()->getGraphObject(GraphUser::className());

            return $user;
        }
        catch (FacebookRequestException $e)
        {
            err("Class FacebookUser [getProfile()]: Request Exception: {$e->getMessage()}")   ;
        }
        catch (\Exception $e)
        {
            err("Class FacebookUser [getProfile()]: User Profile Exception: {$e->getMessage()}");
        }

        return null;
    }


    /**
     * getter Long Lived Token
     *
     * Set long Live Token
     */
    public function getFBLongLivedToken()
    {
        try
        {
            // Set URL for GET GRAPH API CALL
            $url = "https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token"
                ."&client_id=".$this->appId
                ."&client_secret=".$this->secret
                ."&fb_exchange_token=".$this->accessToken;

            // Execute Graph APi Call
            $responseFBLiveToken = file_get_contents($url);
            if($responseFBLiveToken === false)
                throw new Exception('FAILING file_get_contents  '.$url);

            // Verify Response Headers
            if(strpos($http_response_header[0],' 200 ') === false) {
                throw new Exception('Access LONG LIVE Token Not Valid  '.$url.' '
                    .print_r($http_response_header,1)
                    .print_r( $url ));
            }

            // Parse Response from Facebook to get [access_token] & [expires] values
            $arrayResponse = array();
            parse_str( $responseFBLiveToken, $arrayResponse);
            // SET FB ACCESS TOKEN
            $this->accessToken = $arrayResponse['access_token'];

        } catch (Exception $e) {
            err("FAILURE getFBLongLivedToken ".$e);
            throw new Exception('getFBLongLivedToken  '.$e);
        }
    }

    /**
     * getter Session
     *
     * @return session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     *  isValidSession
     *
     * @return boolean
     */
    public function isValidSession()
    {
        if($this->session === null){
            return false;
        }else{
            return true;
        }
    }

    /**
     * getter userId
     *
     * @return userId
     */
    public function getUserId()
    {
        return $this->userId;
    }


    /**
     * getter accessToken
     *
     * @return accessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }


    /**
     * throwExceptionIfAccessTokenNotValid
     *
     * @return void
     */
    public function throwExceptionIfAccessTokenNotValid()
    {
        if(!isset($this->accessToken))
            throw new Exception('Access Token is NOT SET  Facebook Canvas originId :'.$this->originId);

        $url = "https://graph.facebook.com/me?access_token=";
        $headers = @get_headers($url.$this->accessToken);
        if(strpos($headers[0],' 200 ') === false) {
            throw new Exception('Access Token Not Valid  '.$url.$this->accessToken.' '.print_r($headers,1) );
        }
    }

    /**
     * resetObject
     *
     * @return boolean
     */
    public function resetAllObjectParameters(){
        $this->userId = null;
        $this->accessToken = null;
        $this->session = null;
        $this->appId = null;
        $this->secret = null;
        $this->originId = null;
    }

    /**
     * setAccessTokenFromCookies
     *
     * @return boolean
     */
    public function setAccessTokenFromCookies(){
        //If no accessToken - Retrieve from Cookies (if cookies exist)
        if(!isset($this->accessToken) && isset($_COOKIE['FB_accessToken'])){
            $this->accessToken = $_COOKIE['FB_accessToken'];
        } else {
            $this->accessToken = null;
        }
    }

    /**
     * initializeObjectBasedOnOriginId
     *
     * @return boolean
     */
    public function initializeFacebookSessionBasedOnOriginId(){
        // coming from Facebook Canvas == 3
        if ($this->originId === 3) {
            $facebookHelper = new FacebookCanvasLoginHelper($this->appId, $this->secret);
            $this->session = $facebookHelper->getSession();
            $this->userId = $facebookHelper->getUserId();

            if ($this->session instanceof FacebookSession) {
                $this->accessToken = $this->session->getToken();
            }
            else
                throw new Exception('No instanceof FacebookSession  '.print_r($this->session,1).'  originId : '.$this->originId);
        } else {
        //coming high5casino.net PlayReal
            $this->session = FacebookSession::newAppSession();
            $this->userId = (new FacebookJavaScriptLoginHelper())->getUserId();
            $this->setAccessTokenFromCookies();
        }
    }
}
