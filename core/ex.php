<?php

function UserErrorHandler($errno, $errstr, $errfile, $errline)
{
	//std handler
	if (!(error_reporting() & $errno))
		return false;

	static $aError = [];
		
	$sError = "USER_ERROR [$errno]: $errstr at $errfile:$errline";
	$aError[] = $sError;
	__log($sError);

	if($errno == E_USER_ERROR)
	{
		//__get("IResponse")->set(["headers", "Content-type"], "text/plain");
		__get("IResponse")->set("error", $sError . "\n\n Stack trace: ".debug_print_backtrace());
		__get("IResponse")->set("code", 500);
		ExCore::release();
	}

  return true;
}

$fnOldErrorHandler = set_error_handler("UserErrorHandler");

