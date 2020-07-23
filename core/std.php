<?php

if (!function_exists('array_key_first')) {
	function array_key_first(array $arr) {
		foreach($arr as $key => $unused) {
			return $key;
		}
		return NULL;
	}
}

//##########################################################################

//! является ли arr ассоциативным массивом
function is_assoc($arr)
{
	if(!is_array($arr) || count($arr) == 0) 
		return false;

  return (array_keys($arr) !== range(0, count($arr) - 1));
}

//##########################################################################

//! экранирование sql запроса
function escape_sql($sSQL)
{
	return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $sSQL);
}

//##########################################################################

//! вывод data на страницу и завершение рабоыт скрипта
function exit_print_r($data)
{
	header("Content-type: text/plain; charset: utf8;");
	exit(print_r($data, true));
}

//##########################################################################
// ARRAYS/OBJECTS

//! удалить ключ из массива по значению
function array_unset_value(&$aArr, $val)
{
	foreach($aArr as $sKey => $value)
	{
		if($value == $val)
			unset($aArr[$sKey]);
	}
}

//**************************************************************************

/** извлекает из массива (массивов/обьектов) ключи sKey, возвращает линейный массив
	@note обьекты должны быть контейнерами свойств с методом get (например #IPropContainer)
*/
function extrude($aArr, $sKey, $onlyUniq=true)
{
	if(!is_array($aArr) || count($aArr) == 0)
		return [];
		
	$aArr2 = [];
	if(is_array($aArr[array_key_first($aArr)]))
	{
		foreach($aArr as $key => $value)
		{
			if(!$onlyUniq || ($onlyUniq && !in_array($value[$sKey], $aArr2)))
				$aArr2[] = $value[$sKey];
		}
	}
	else if(is_object($aArr[array_key_first($aArr)]))
	{
		foreach($aArr as $key => $oEnt)
		{
			if(!$onlyUniq || ($onlyUniq && !in_array($oEnt->get($sKey), $aArr2)))
				$aArr2[] = $oEnt->get($sKey);
		}
	}

	return $aArr2;
}

//**************************************************************************

/** ассоциировать массивы/обьекты внутри массива по ключу sKey в этом массиве
	@note обьекты должны быть контейнерами свойств с методом get (например #IPropContainer)
*/
function mapping($aArr, $sKey)
{
	if(!is_array($aArr) || count($aArr) == 0)
		return [];

	$aArr2 = [];
	if(is_array($aArr[array_key_first($aArr)]))
	{
		foreach($aArr as $key => $value)
			$aArr2[$value[$sKey]] = $value;
	}
	else if(is_object($aArr[array_key_first($aArr)]))
	{
		foreach($aArr as $key => $value)
			$aArr2[$value->get($sKey)] = $value;
	}

	return $aArr2;
}

//**************************************************************************

//! извлекает ключи со значениями (aKeyArr) из aSrcArr в новый массив
function extract_keys($aSrcArr, $aKeyArr)
{
	$aExtract = [];
	foreach($aKeyArr as $key)
	{
		if(array_key_exists($key, $aSrcArr))
			$aExtract[$key] = $aSrcArr[$key];
	}
	return $aExtract;
}

//**************************************************************************

//! извлекает ключи со значением sSubKey в имени ключа до sDelimeter из aSrcArr в новый массив
function extract_subkeys($aSrcArr, $sSubKey, $sDelimeter=".")
{
	$aExtract = [];
	foreach($aSrcArr as $key => $value)
	{
		$aKey = explode($sDelimeter, $key, 2);
		if(count($aKey) == 1)
			continue;

		if(strcasecmp($aKey[0], $sSubKey) == 0)
			$aExtract[$aKey[1]] = $value;
	}
	return $aExtract;
}

//**************************************************************************

//! разделение массива по субключам (ключ разделен $sDelimeter), вернет ассоциативный массив
function split_by_subkeys($aSrcArr, $sDelimeter=".")
{
	$aArr = [];

	foreach($aSrcArr as $key => $value)
	{
		$aKey = explode($sDelimeter, $key, 2);
		if(count($aKey) == 1)
			continue;
		
		if(!array_key_exists($aKey[0], $aArr))
			$aArr[$aKey[0]] = [];

		$aArr[$aKey[0]][$aKey[1]] = $value;
	}

	return $aArr;
}

//##########################################################################
// PASSWORD

//! кодирование пароля (для записи в бд)
function pwd_code($sPassword)
{
	return password_hash($sPassword, PASSWORD_DEFAULT);
}

//**************************************************************************

//! проверка кодированного пароля sPasswordCode с некодированным sPasswordRaw
function pwd_verify($sPasswordRaw, $sPasswordCode)
{
	return password_verify($sPasswordRaw, $sPasswordCode);
}

//**************************************************************************

//! генерация пароля длиной iLen символов
function pwd_gen($iLen)
{
	$aChars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
	$iCharsSize = strlen($aChars)-1;
	$sPassword = "";

	for($i=0; $i<$iLen; ++$i)
		$sPassword .= $aChars[random_int(0, $iCharsSize)];

	return $sPassword;
}

//##########################################################################
// STRING (CHAR) <=> STRING (HEX)

//! преобразовать строку с char в строку с hex
function str2hex($string)
{
	$hex = '';
	for ($i=0; $i<strlen($string); ++$i)
	{
		$ord = ord($string[$i]);
		$hexCode = dechex($ord);
		$hex .= substr('0'.$hexCode, -2);
	}
	return strToUpper($hex);
}

//**************************************************************************

//! преобразовать строку с hex в строку с char
function hex2str($hex)
{
	$string='';
	for ($i=0; $i < strlen($hex)-1; $i+=2)
		$string .= chr(hexdec($hex[$i].$hex[$i+1]));
	return $string;
}

//##########################################################################
// IP <=> BIN

//! преобразовать строку с ip в бинарную строку
function ip2bin($ip)
{
	$sIp = str2hex(inet_pton($ip));
	if(strlen($sIp) < 32)
	{
		for($i=0, $il=32-strlen($sIp); $i<$il; ++$i)
			$sIp .= "0";
	}
	return hex2str($sIp);
}

//**************************************************************************

//! преобразовать бинарную строку в строку с ip
function bin2ip($ip)
{
	$sIp = str2hex($ip);
	if(substr($sIp, 8) == "000000000000000000000000")
		$sIp = substr($sIp, 0, 8);
	return inet_ntop(hex2str($sIp));
}

//##########################################################################
