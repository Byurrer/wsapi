<?php

class CRequest extends CPropContainer implements IRequest
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

		$this->set("type", "get");
		$this->set("cookie", "");
	}

	public function release() {}

	//########################################################################

	public function send()
	{
		$hCurl = curl_init();

		curl_setopt($hCurl, CURLOPT_URL, $this->get("url"));
		curl_setopt($hCurl, CURLOPT_HEADER, TRUE);
		curl_setopt($hCurl, CURLOPT_FOLLOWLOCATION, 2);

		if($this->get("type") == "post")
			curl_setopt($hCurl, CURLOPT_POST, TRUE);

		if($this->get("referer"))
			curl_setopt($hCurl, CURLOPT_REFERER, $this->get("referer"));

		if($this->get("useragent"))
			curl_setopt($hCurl, CURLOPT_USERAGENT, $this->get("useragent"));
		
		curl_setopt($hCurl, CURLOPT_TIMEOUT, 10);

		if($this->get("headers"))
		{
			$aHeaders = [];
			foreach($this->get("headers") as $sKey => $sValue)
				$aHeaders[] = "$sKey: $sValue";

			curl_setopt($hCurl, CURLOPT_HTTPHEADER, $aHeaders);
		}

		curl_setopt($hCurl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($hCurl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, 1);

		$sResponse = curl_exec($hCurl);

		$sResponse = str_replace("\r\n", "\n", $sResponse);
		$aResponse = explode("\n\n", $sResponse);

		$sHeaders = $aResponse[0];
		$sBody = $aResponse[1];

		return __new("IResponse", [
			"headers_raw" => $sHeaders,
			"code" => curl_getinfo($hCurl, CURLINFO_RESPONSE_CODE),
			"body" => $sBody,
		]);
	}
};

ExCore::regClassEx("CRequest", "IRequest");
