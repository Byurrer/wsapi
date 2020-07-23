<?php

header("Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, X-File-Name");

define("FILE_PROTECTED", 1);
error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ob_start();

include("core/ExCore.php");
include("config.php");

ExCore::init(["mods", "controllers", "ex"]);

$oRouter = __get("IRouter");

$oResponse = &__get("IResponse");
$oResponse->set("success", $oRouter->exec());
$sEcho = ob_get_contents();
ob_end_clean();

$oResponse->set("echo", $sEcho);
//exit_print_r($oResponse);

///$oResponse->send();
ExCore::release();
