<?php

if (!function_exists('array_key_first')) 
{
	function array_key_first(array $arr) 
	{
		foreach($arr as $key => $unused) 
			return $key;
		return NULL;
	}
}

//##########################################################################

function &__get($sInterface, $sName = null)
{
	return ExCore::getObject($sInterface, null, $sName);
}

//**************************************************************************

function &__getv($sInterface, $sVariant, $sName = null)
{
	return ExCore::getObject($sInterface, $sVariant, $sName);
}

//**************************************************************************

function __new($sInterface, $aData = null)
{
	return ExCore::newObject($sInterface, null, $aData);
}

//**************************************************************************

function __newv($sInterface, $sVariant, $aData = null)
{
	return ExCore::newObject($sInterface, $sVariant, $aData);
}

//##########################################################################

function __log($sStr)
{
	__get("ILog")->log($sStr);
}

//##########################################################################

function __eassert($b, $sError)
{
	if(!$b)
	{
		$aCallInfo = debug_backtrace(0, 1);
		UserErrorHandler(E_USER_ERROR, $sError, $aCallInfo[0]["file"], $aCallInfo[0]["line"]);
		//trigger_error($sError, E_USER_ERROR);
	}
}
