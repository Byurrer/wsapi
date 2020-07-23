<?php

class CResponse extends CPropContainer implements IResponse 
{
	public function __construct()
	{
		$this->init();
	}

	//************************************************************************

	public function init($aData=null)
	{
		parent::init($aData);

		if(!array_key_exists("headers", $this->m_aProps))
			$this->m_aProps["headers"] = [];

		//если тип контента не определен, значит по умолчанию будет text/plain
		if(!array_key_exists("Content-type", $this->m_aProps["headers"]))
			$this->m_aProps["headers"]["Content-type"] = "text/plain";


		//регистрирация обработчика тела ответа для text/plain и text/html
		static::addOutCTypeHandler(["text/plain", "text/html"], function($oResponse){ return $oResponse->get("body"); });


		if(!$this->exists("code"))
			$this->set("code", 200);

		//если передан сырой массив заголовков в строке вида "key: val\n ..." то разбираем его
		if($this->exists("headers_raw"))
		{
			$aHeaders = explode("\n", $this->m_aProps["headers_raw"]);
			foreach($aHeaders as $value)
			{
				$aHeader = explode(":", $value, 2);

				if(count($aHeader) == 2)
					$this->m_aProps["headers"][trim($aHeader[0])] = trim($aHeader[1]);
			}
		}
	}

	public function release() {}

	//########################################################################

	public function add($key, $data)
	{
		$vard = &$this->__getd($key);

		if(is_null($vard) && is_array($data))
			$vard = [];
		
		if(is_array($vard))
			$vard[] = $data;
		else
			$vard .= $data;
	}

	//########################################################################

	public function redirect($sURL, $iCode=301)
	{
		$this->set(["headers", "Location"], $sURL);
		$this->set("code", $iCode);
	}

	//########################################################################

	public function sendHeaders()
	{
		foreach($this->m_aProps["headers"] as $key => $value)
			header("$key: $value");

		$iCode = $this->get("code");
		header("HTTP/1.1 $iCode", true, $iCode);
	}

	//************************************************************************

	public function sendBody()
	{
		if(!array_key_exists("body", $this->m_aProps))
			$this->m_aProps["body"] = "";

		//exit(print_r($this->m_aCTypeHandlers, true));

		$sCType = $this->get(["headers", "Content-type"]);
		foreach(static::$m_aOutCTypeHandlers as $sKey => $fn)
		{
			if(strcasecmp($sCType, $sKey) == 0)
			{
				$response = $fn($this);
				print_r($response);
				return $response;
			}
		}
		//print_r($this->m_aProps["body"]);
		__eassert(false, "Not found handler for response with Content-type [$sCType]");
	}

	//************************************************************************

	public function send()
	{
		$this->sendHeaders();
		$this->sendBody();
	}

	//************************************************************************

	public static function addOutCTypeHandler($ctype, $fnHandler)
	{
		if(is_array($ctype))
		{
			foreach($ctype as $sCType)
				static::addOutCTypeHandler($sCType, $fnHandler);

			return;
		}
		static::$m_aOutCTypeHandlers[$ctype] = $fnHandler;
	}

	//########################################################################
	//########################################################################
	//########################################################################

	protected static $m_aOutCTypeHandlers = [];
}

ExCore::regClassEx("CResponse", "IResponse");
