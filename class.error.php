<?php
/**
* ERROR CLASS 
*
* @author  David Raleche <david@raleche.com>
* PHP version 5
*
* Manages and handles Error Logs 
*/

Class Error {


		
	public static function err($msg, $important = 0)
	{
		set_error_handler(array('Error', 'customError')); 

		if(is_array($msg)) {$temp = print_r($msg, true); $msg = $temp;}

		trigger_error($msg);

	}
	
	
	
	
// Custom error function, writes all errors in dev environments.  In live only writes to file in case of important error
// Usage: to use manually trigger: err( (string) $error_message, (optional number) $important ).  $important flag means error will be logged to LIVE server log if on live server.  It does nothing different on other environments, merely is a way to conserve error log filesize on live environments
	public static function customError($errno, $errstr, $errfile, $errline) 
	{
		global $FILES, $USER, $serverLocation, $displayErrorsOnScreen, $accountTypeId, $buildId, $DB_CONN;
		if (error_reporting() === 0) return; // suppress @ errors

		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$important = isset($important) && !is_array($important) ? $important : 0;
		$errstr = (strip_tags($errstr, '<b><i><u><br><ul><ol><li>'));
//		$errstr = "UGP (" . gethostname(). ")\n" . $errstr;
		$errfile = str_replace( '/data', '', $errfile);
		$errString = "\n<b>" . date("m/d/Y h:i:sa T") . "</b>:  [$errno] $errstr <i>$errfile:$errline</i> <span = 'basic'>" . get_ip_address() . ' ' . $userAgent . "</span><br />\n";

		$debug_back = debug_backtrace();
	//	$errstr .= "\n\nDEBUG BACKTRACE:\n" . print_r($debug_back[1], true);

		// if on dev or staging, log the error to the screen (if display errors is true)
		if(ALLOW_DEBUG) if($displayErrorsOnScreen) echo $errString;

		// log to database
		$DB_CONN['LOGS']->log_to_db($errno, $errstr, $errfile, $errline);
	
		return true;
	}
	
	
	public static function fatalErrorHandler() { 
	$error = error_get_last();
	if( ($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR) ) { 
		extract($error);
		customError($type, $message, $file, $line);
	} 
}   
	
}	
	?>