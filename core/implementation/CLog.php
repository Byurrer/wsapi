<?php

//! признак окончания отчета по текущем запросу
define("DBG_EOQ", "############################################\n");

class CLog implements ILog
{
	public function __construct()
	{
		$this->init();
	}

	public function init($aData=null)
	{	}

	//************************************************************************

	public function setEnable($isEnable)
	{
		$this->m_isEnable = $isEnable;
	}

	//************************************************************************

	public function getEnable()
	{
		return $this->m_isEnable;
	}

	//************************************************************************

	public function setCurrLog($sName)
	{
		if(!array_key_exists($sName, $this->m_aLogs))
			trigger_error("Not found key [$sName] in m_aLogs", E_USER_ERROR);

		$this->m_sCurrLog = $sName;
	}

	//************************************************************************

	public function getCurrLog()
	{
		return $this->m_sCurrLog;
	}

	//************************************************************************

	public function addLog($sName, $sDir, $iType)
	{
		$this->m_aLogs[$sName] = [
			"dir" => $sDir,
			"type" => $iType
		];
	}

	//************************************************************************

	public function log($sStr)
	{
		$this->m_sDebug .= "[" . date("Y-m-d H:i:s") . "]: " . $sStr . "\n";
	}

	//************************************************************************

	public function stacktrace($sStr)
	{ 
		$sTraceStack = "";
		$aStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		foreach($aStack as $key => $value)
			$sTraceStack .= "  " . $value["file"] . ": " .  $value["line"] . " - " .  $value["function"] . "\n";

		$sLog = "USERDATA, debug_backtrace: \n" . $sTraceStack . "====================\n" . $sStr;

		$this->log($sLog);
	}

	//************************************************************************

	public function getLog()
	{
		return $this->m_sDebug;
	}

	//************************************************************************

	public function release($aData=null)
	{
		if(!$this->m_isEnable)
			return;

		$aCurrLog = $this->m_aLogs[$this->m_sCurrLog];
		
		if(!file_exists($aCurrLog["dir"]))
			mkdir($aCurrLog["dir"], 0700, true);

		$sPath = "";
		if($aCurrLog["type"] == ILog::TYPE_PER_DAY)
			$sPath = $aCurrLog["dir"] . "/" . date("Y-m-d") . ".txt";
		else
		{
			$sPath = $aCurrLog["dir"] . "/" . date("Y-m-d");

			if(!file_exists($sPath))
				mkdir($sPath, 0700, true);

			$sPath = $sPath . "/" . date("H") . ".txt";
		}

		file_put_contents($sPath, $this->m_sDebug . DBG_EOQ, FILE_APPEND);
	}

	//########################################################################
	//########################################################################
	//########################################################################

	//! строка для накопления информации об отладке
	protected $m_sDebug = "";

	protected $m_isEnable = true;

	protected $m_sCurrLog = "default";

	protected $m_aLogs = [
		"default" => [
			"dir" => "__default", 
			"type" => ILog::TYPE_PER_DAY
		],
	];
};

ExCore::regClass("CLog");
