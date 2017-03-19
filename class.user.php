<?php
/**	
* 		Class USER
* 		@author David Raleche <david@raleche.com>
*/

class user 
{
		private 	$user_name = "";
		private		$email  = '';

		// APP DEFAULTS
		private	$originId = 2; // 2 = h5c.net, 3 = facebook.com
		private	$accountTypeId = 2; // 2 = facebook, 1 = playreal
		private	$accountId;
		public	$total_languages;
		public	$last_language;
		public 	$lang; //User Locale
		public 	$envLang; //User Env Locale
		
	
	/********************************************************************************************************************************/	
	// CLASS CONSTRUCTOR
	public  function __construct($accountTypeId, $originId )
	{
		global $LANGUAGES_NEW;
		$this->total_languages = count($LANGUAGES_NEW);
		end($LANGUAGES_NEW);
		$key = key($LANGUAGES_NEW);
		$this->last_language = substr($key,0,2);
		
		
		//Set accountTypeId 
		$this->accountTypeId = $accountTypeId;

		//Set originId 
		$this->originId = $originId;
		
		//Set AccountId
		$this->set_accountId();
		
		//Set User EnvLocale Object
		$this->setEnvLocale();
		
		//Set User Locale Object
		$this->setLocale();
		
		//Check Cookie
		$this->set_cookie();


	}

	
	/********************************************************************************************************************************/	
	// Private Function
	// SET ENVLOCALE - LangID
	
	public function	setEnvLocale()
	{
		global $USER, $_SERVER;
		
		// populate from FACEBOOK
		if(isset($USER['locale']))
		{
			//echo "FACEBOOK";
			$array_lang	= Language::is_supported_locale($USER['locale']);
			if($array_lang['status']  == true)
			{
				$this->envLang 			= new Language(array("locale" => $array_lang['locale'], "languageId" => $array_lang['languageId'], "englishName" => $array_lang['englishName']));			
				return;
			}
		}
			
		// populate FROM BROWSER 
		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$array_lang	= Language::is_supported_locale($_SERVER['HTTP_ACCEPT_LANGUAGE']);
			if($array_lang['status']  == true)
			{
				$this->envLang 			= new Language(array("locale" => $array_lang['locale'], "languageId" => $array_lang['languageId'], "englishName" => $array_lang['englishName']));

				return;
			}
		}
	}
	

	/********************************************************
	SETLOCALE - LangID
	********************************************************/
	public function setLocale()
	{
	
		#1 - GET PARAMETER
		if(isset($_GET['langId']) && isset($_GET['lang']))
		{
			$supported =  Language::is_supported_locale($_GET['lang']) ;
			if($supported ['status'] == true)
			{			
				$this->lang 			= new Language(array("locale" => $supported['locale'], "languageId" => $supported['languageId'], "englishName" => $supported['englishName']));
				return;
			}
			else
			{
				$this->lang->locale = 'en';
				$this->lang->languageId = 1;
			}
			return;
		}
			
		#2 - NEW COOKIE PARAMETER
		if(isset($_COOKIE['newLang']) && isset($_COOKIE['newLangId']))
		{
			$supported = Language::is_supported_locale($_COOKIE['newLang']) ;
			if($supported ['status'] == true)
			{
					$this->lang 			= new Language(array("locale" => $supported['locale'], "languageId" => $supported['languageId'],"englishName" => $supported['englishName']));
					return;	
			}
		}

		#3 - OLD COOKIE PARAMETER
		if(isset($_COOKIE['lang']) && isset($_COOKIE['langId']))
		{
			$supported = Language::is_supported_locale($_COOKIE['lang']) ;
			if($supported['status'] == true)
			{
				$this->lang 			= new Language(array("locale" => $supported['locale'], "languageId" => $supported['languageId'],"englishName" => $supported['englishName']));
				return;
			}
		}

        //Default to Environment Lang
        if (isset($this->envLang))
        {
            $this->lang = $this->envLang;
        }
        else
        {
            //Default to Environment Lang
            $this->lang = new Language(array(
                'locale'      => 'en',
                'languageId'  => 1,
                'englishName' => 'English',
            ));

            $lang_not_supported = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'Not Identified';

            err('Class User - Not Supported Language default English - Lang Not SUpported : '.$lang_not_supported);
        }

		return;
	}
	

	
	/********************************************************
	SET_COOKIE
	********************************************************/
	public function set_cookie()
	{
		global $domain_public;
		
		//SPECIAL CASE - GET VARIABLE
		if(isset($_GET['lang']) && isset($_GET['langId']))
		{
			set_session_cookie("totalLanguages", 	$this->total_languages);
			set_session_cookie("lastLanguage", 		$this->last_language);
			set_session_cookie("newLang", 			$_GET['lang']);
			set_session_cookie("newLangId", 		$_GET['langId']);

			return;
		}

		//New USER
		if(!isset($_COOKIE['lang']) && !isset($_COOKIE['newLang']))
		{
			
			set_session_cookie("totalLanguages"	, $this->total_languages);
			set_session_cookie("lastLanguage"	, $this->last_language);
			set_session_cookie("newLang"		, $this->lang->locale);
			set_session_cookie("newLangId"		, $this->lang->languageId);
			//echo "<!-- CASE 1 - New USER -->";
		}

		//Existing USER - CONVERTED
		if(isset($_COOKIE['newLang']) && isset($_COOKIE['newLangId']))
		{
			// get user's previous language total if available
			$userTotalLanguages = isset($_COOKIE['totalLanguages']) ? (int) $_COOKIE['totalLanguages'] : 0;

			//New Language has been added
			if($this->total_languages != $userTotalLanguages)
				if(($this->last_language ==  $this->envLang->locale) )
						$this->triggerMessage();
				
			//Regular process
			set_session_cookie("totalLanguages"	, $this->total_languages);
			set_session_cookie("lastLanguage"	, $this->last_language);
			set_session_cookie("newLang"		, $_COOKIE['newLang']);
			set_session_cookie("newLangId"		, $_COOKIE['newLangId']);
			
			delete_session_cookie("langId");
			delete_session_cookie("lang");
			//echo "<!-- CASE 2 Existing USER - CONVERTED -->";
			return;
		}

		
		//EXISTING USER - NOT CONVERTED
		if(isset($_COOKIE['lang']) && !isset($_COOKIE['newLang']))
		{
			//-- HAS MATCHING LANGUAGES (no need to show message)
			if(($this->envLang->locale == 'en') OR ($_COOKIE['lang'] ==  $this->envLang->locale))
//			if($_COOKIE['lang'] ==  $this->envLang->locale)
			{
				
				set_session_cookie("totalLanguages"	, $this->total_languages);
				set_session_cookie("lastLanguage"	, $this->last_language);
				set_session_cookie("newLang"		, $_COOKIE['lang']);
				set_session_cookie("newLangId"		, $_COOKIE['langId']);	

				//Return OldCookie LangID (2.1c)
				$lang->locale 		= $_COOKIE['lang'];
				$lang->languageId 	= $_COOKIE['langId']; 
			
				delete_session_cookie("langId");
				delete_session_cookie("lang");
				//echo "<!-- CASE 3 - EXISTING USER - NOT CONVERTED HAS MATCHING LANGUAGES-->";

				return;
			}
		
			//-- HAS MISMATCHED LANGUAGES (show message)
			if(!isset($_GET['lang']) && !isset($_GET['langId']))
			{
				if(($this->lang->locale != 'en') OR ($_COOKIE['lang'] !=  $this->envLang->locale) )
//				if(($_COOKIE['lang'] !=  $this->envLang->locale) )
				{
					
					$this->triggerMessage();
				}
			}
		}
		
		//NEW LANGUAGES HAS BEEN ADDED
		set_session_cookie("totalLanguages", $this->total_languages);
		set_session_cookie("lastLanguage", $this->last_language);
	}

	public function triggerMessage()
	{
		global $domain_public, $LANGUAGES_NEW;
		//Bug - no <Span> - no javascript triggerred - need to review html/javascript convention
		//echo "<!-- CASE TRIGGER MESSAGE --> <div></div>";
		$langId 	= (isset($_COOKIE['langId']))?$_COOKIE['langId']:1;
		$lang 		= (isset($_COOKIE['lang']))?$_COOKIE['lang']:'en';
		set_session_cookie("totalLanguages", $this->total_languages);
		set_session_cookie("lastLanguage", $this->last_language);
		set_session_cookie("newLang", $lang);
		set_session_cookie("newLangId", $langId);
		delete_session_cookie("langId");
		delete_session_cookie("lang");
	?>

<script type="text/javascript" language="javascript">
	// there is an if in Social/functions.js which looks for this
	var doWeFancyConfirm 			= {};
	doWeFancyConfirm.message 		= "High5Casino is now Available in <?php echo $this->envLang->englishName; ?>. Do you want to play High5Casino in <?php echo $this->envLang->englishName; ?> ? <br><br>  <?php echo $this->envLang->translator->get('sitecopy.lang_modal_instruct'); ?>  ";
	doWeFancyConfirm.yes			= "Yes/<?php echo $this->envLang->translator->get('button.text_yes');  ?> ";
	doWeFancyConfirm.no 			= "No Thanks/<?php echo $this->envLang->translator->get('button.text_no_thanks'); ?>";
	doWeFancyConfirm.successVal 	= "https://<?php echo $domain_public; ?>/Social/index.php?langId=<?=$this->envLang->languageId;?>&lang=<?=$this->envLang->locale;?>";
	doWeFancyConfirm.failVal 		= "https://<?php echo $domain_public; ?>/Social/index.php?langId=<?=$langId; ?>&lang=<?=$lang;?>";
	doWeFancyConfirm.newLanguage	=  "<?php echo $this->envLang->englishName; ?> ";
</script>

		<?php


	}

	
	public function set_accountId()
	{
		global $USER;
		// Play Real
		if($this->accountTypeId == 1)
		{
		
		}
		
		// Facebook
		if($this->accountTypeId == 2)
		{
			$this->accountId =    $user_id = $USER['id']  ;
		}
	}
	
	// set/delete session cookie
		public function set_session_cookie($name, $value){
			delete_session_cookie($name);
			setcookie($name, $value, 0, '/', '');
		}

		public function delete_session_cookie($name){
			setcookie($name, null, time() - 3600, '/', '');
		}
	

	/********************************************************************************************************************************/	
	// Getter - Setter	
	public function get_langId()
	{
		return $this->langId;
	}

	public function get_lang()
	{
		return $this->lang;	
	}
}
