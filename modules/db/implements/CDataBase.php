<?php

class CDataBase implements IDataBase
{
	public function __construct() {}
	public function init($aData=null) {}
	public function release() {}

	//########################################################################

	public function connect($sHost, $sDBname, $sUser, $sPassword, $sCharset="utf8")
	{
		$this->m_DB = null;
		$this->m_aStackSqlQuery = [];
		$this->m_iErrorCode = 0;
		$this->m_sErrorText = "";
		$this->m_sTraceStack = "";
		
		$aOpt  = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => TRUE,
		];

		$sDsn = 'mysql:host='.$sHost.';dbname='.$sDBname.';charset='.$sCharset;
		
		try
		{
			$this->m_DB = new PDO($sDsn, $sUser, $sPassword, $aOpt);
		}
		catch(PDOException $oException) 
		{
			$this->_report($oException);
		}
	}

	//************************************************************************
	
	public function getQueries()
	{
		return $this->m_aStackSqlQuery;
	}

	//************************************************************************
	
	public function getQueriesDetail()
	{
		return $this->m_aStackQueryDet;
	}

	//************************************************************************
	
	public function getCountQueries()
	{
		return count($this->m_aStackSqlQuery);
	}

	//************************************************************************
	
	public function getLast()
	{
		if(count($this->m_aStackSqlQuery) > 0)
			return $this->m_aStackSqlQuery[count($this->m_aStackSqlQuery) - 1];
		return "";
	}

	//************************************************************************
	
	public function isConnect()
	{
		return boolval($this->m_DB);
	}

	//************************************************************************

	public function getErrorCode()
	{
		return $this->m_iErrorCode;
	}

	//************************************************************************

	public function getErrorText()
	{
		return $this->m_sErrorText;
	}

	//************************************************************************

	public function getStackTrace()
	{
		return $this->m_sTraceStack;
	}

	//************************************************************************

	public function getLastInsertId()
	{
		return $this->m_DB->lastInsertId();
	}

	//************************************************************************

	public function report($sError)
	{
		__log($sError);
		trigger_error("Database problems", E_USER_ERROR);
	}

	//**********************************************************************
	
	public function query($oQuery, $iRetArr=DB_QUERY_RETURN_STATUS, $isFetchPreTableName=false)
	{
		try
		{
			$sSQL = $oQuery->getSQL();
			$this->m_aStackSqlQuery[] = $sSQL;
			$aBindData = $oQuery->getBindData();

			$aQuery = [];
			$aQuery["sql"] = $sSQL;
			$aQuery["data"] = [];

			$aQuery["stack"] = [];
			$aStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

			for($i=0, $il=count($aStack); $i<$il; ++$i)
				$aQuery["stack"][] = (array_key_exists("class", $aStack[$i]) ? $aStack[$i]["class"] : "").$aStack[$i]["type"].$aStack[$i]["function"]." - ".$aStack[$i]["file"].":".$aStack[$i]["line"];
			

			$this->m_DB->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, $isFetchPreTableName);
			$oStatement = $this->m_DB->prepare($sSQL);

			foreach($aBindData as $key => $value)
			{
				$oStatement->bindValue($key, $value);
				$aQuery["data"][$key] = $value;
			}

			$this->m_aStackQueryDet[] = $aQuery;

			//$oStatement->debugDumpParams();
				
			$iRes = $oStatement->execute();
			//echo "errorInfo: ".print_r($oStatement->errorInfo(), true)."\n";

			if($iRetArr != DB_QUERY_RETURN_STATUS)
			{
				if(!$iRes || $oStatement->rowCount() == 0)
					return null;

				return ($iRetArr == DB_QUERY_RETURN_ONE ? $oStatement->fetch() : $oStatement->fetchAll());
			}

			return $iRes;
		}
		catch(PDOException $oException) 
		{
			$this->_report($oException);
		}
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	//! генерация сообщения об ошибке
	protected function _report(PDOException $oException)
	{
		$this->m_iErrorCode = $oException->getCode();
		$this->m_sErrorText = $oException->getMessage();
		$this->m_sTraceStack = $oException->getTraceAsString();

		$sError = "DB ERROR: ".$oException->getMessage() . "\n";
		$sError .= "SQLs: ".print_r($this->m_aStackSqlQuery, true) . "\n";
		$sError .= "Stack trace: ".print_r($this->m_aStackSqlQuery, true) . "\n";

		$aError = explode("#", $oException->getTraceAsString());
		for($i=1, $il = count($aError); $i<$il; ++$i)
			$sError .= " - " . $aError[$i];

		$this->report($sError);
	}

	//########################################################################

	//! объект PDO
	protected $m_DB = null;
	
	//! массив запросов
	protected $m_aStackSqlQuery = [];

	protected $m_aStackQueryDet = [];

	//! код ошибки (последней)
	protected $m_iErrorCode = 0;

	//! текст ошибки (последней)
	protected $m_sErrorText = "";

	//! строка с трассировкой стека, после ошибки (последней)
	protected $m_sTraceStack = "";

	protected $m_isFetchPreTableName = false;
};

ExCore::regClass("CDataBase");
