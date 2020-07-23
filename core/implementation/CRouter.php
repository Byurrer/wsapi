<?php

class CRouter implements IRouter
{
	private $m_sQuery = "";

	//! распарсенная строка запроса в массив, токен парсинга / (адрес не входит сюда)
	private $m_aURL;

	//! массив обработчиков CHandler
	private $m_aHandlers = [];

	//! обработчик который вызывается в случае если ни одного подходящего не найдено
	private $m_handlerLost;

	//************************************************************************
	
	public function __construct()
	{
		$this->init();
	}

	//************************************************************************

	public function init($aData=null)
	{
		$sQuery = explode("?", $_SERVER["REQUEST_URI"])[0];
		//если строка запроса пустая, значит на главной
		if($_SERVER["REQUEST_URI"] == "/")
		{
			//создаем массив с единственным ключом /
			$this->m_aURL = ["/"];
		}
		else
			$this->m_aURL = explode("/", substr($sQuery, 1));
		
		$this->m_sQuery = $sQuery;
		$this->m_handlerLost = null;
	}

	public function release() {}

	//************************************************************************

	public function getQuery($needGet = false)
	{
		return ($needGet ? $_SERVER["REQUEST_URI"] : $this->m_sQuery);
	}

	//************************************************************************

	public function veld($iStart=0, $iCount=0)
	{
		//print_r($iStart . " " . $iCount . " " . $this->m_aURL[$iStart] . "\n");
		$sStr = "";
		/*if($iCount > count($this->m_aURL) || $iCount <= 0)
			$iCount = count($this-$m_aURL);*/

		if($iStart < 0)
			$iStart = 0;

		$iEnd = $iStart + $iCount;

		if($iEnd > count($this->m_aURL) || $iCount <= 0)
			$iEnd = count($this->m_aURL);

		for($i=$iStart; $i < $iEnd; ++$i)
		{
			if(strlen($sStr) > 0)
				$sStr .= "/";

			$sStr .= $this->m_aURL[$i];
		}

		return $sStr;
	}

	//************************************************************************
	
	public function addHandler($aQuery, $handler, $aRequirements=null)
	{
		$this->m_aHandlers[] = new CHandler($aQuery, $handler, $aRequirements);
	}
	
	//************************************************************************

	public function addLostHandler($handler)
	{
		$this->m_handlerLost = $handler;
	}

	//************************************************************************

	public function preExec($oHandler){ return true; }
	public function postExec($oHandler){ return true; }

	//************************************************************************

	public function getHandler()
	{
		if(count($this->m_aHandlers) == 0)
			return;
		
		$iCountCmp = 0;
		$oHandler = null;
		$aMatches = [];
		
		// выбор наиболее подходящего URL из контроллеров
		foreach($this->m_aHandlers as $oCurrHandler)
		{
			if(count($this->m_aURL) != count($oCurrHandler->m_aQuery))
				continue;

			$iCountCmp = 0;
			for($i = 0, $il = count($oCurrHandler->m_aQuery); $i < $il; ++$i)
			{
				$match = false;
				if(substr_count($oCurrHandler->m_aQuery[$i], "/") > 1)
				{
					$match = preg_match_all($oCurrHandler->m_aQuery[$i], $this->m_aURL[$i], $aMatches, PREG_PATTERN_ORDER);
					if($match)
						$aMatches = $aMatches[1];
				}
				else
					$match = (strcasecmp($oCurrHandler->m_aQuery[$i], $this->m_aURL[$i]) == 0);

				//если хотя бы одна из строк не подходит, тогда контроллер отбрасывается
				if($match)
				{
					++$iCountCmp;
					$oHandler = $oCurrHandler;
				}
				else
				{
					$oHandler = null;
					break;
				}
			}

			//если URL контроллера полностью подходит, тогда останавливаем цикл перебора
			if($iCountCmp == count($oCurrHandler->m_aQuery))
			{
				__log("Controller found!");
				break;
			}
		}

		if($oHandler)
			$oHandler->m_aArgs["matches"] = $aMatches;

		return $oHandler;
	}
	
	//************************************************************************
	
	public function exec()
	{
		$oHandler = $this->getHandler();
		$bResult = true;
		$fnHandler = null;

		//если главный обработчик есть, тогда вызываем
		if($oHandler)
		{
			if(!$this->preExec($oHandler))
				return false;

			if(is_callable($oHandler->m_handler))
			{
				if(is_string($oHandler->m_handler) && strpos($oHandler->m_handler, "::"))
				{
					$aClassMethod = explode("::", $oHandler->m_handler);
					$sClass = $aClassMethod[0];
					$sClass::init($oHandler->m_aArgs);
				}
				$bResult = call_user_func($oHandler->m_handler, $oHandler->m_aArgs);
			}
			else
			{
				$aClassMethod = explode("@", $oHandler->m_handler);
				$sClass = $aClassMethod[0];
				$sMethod = (count($aClassMethod) == 1 ? "exec" : $aClassMethod[1]);

				$oClassHandler = new $sClass();
				$oClassHandler->init($oHandler->m_aArgs);
				$bResult = $oClassHandler->$sMethod();
			}
		}
		//иначе, если есть специальный обработчик на случай отсутсвия главного обработчика, то вызываем его, иначе 404
		else
		{
			$handler = $this->m_handlerLost;
			
			if($handler != null)
			{
				if(is_callable($handler))
					$bResult = call_user_func($handler, null);
				else
				{
					$oClassHandler = new $handler();
					$bResult = $oClassHandler->exec();
				}
			}
			else
			{
				__new("IResponse")->set("code", 404);
				$bResult = false;
			}
		}

		//echo $fnHandler;
		
		if($bResult)
			return $this->postExec($oHandler);
		
		return $bResult;
	}
};

ExCore::regClass("CRouter");
