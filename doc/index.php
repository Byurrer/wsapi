<?php

define('PATH_ROOT', dirname(dirname(__FILE__)));

include("config.php");
include("CAutoDoc.php");
include("CDocHtml.php");

$oDoc = new CAutoDoc();
$aContent = $oDoc->genDoc(PATH_ROOT."/controllers/");

if(array_key_exists("debug", $_GET))
{
	header("Content-type: text/plain");
	print_r($aContent);
	exit();
}
/*header("Content-type: text/plain");
exit(print_r($aContent, true));*/

$oHtml = new CDocHtml();
$sHtml = $oHtml->build($aContent["docs"], $aContent["sections"]);
echo $sHtml;
