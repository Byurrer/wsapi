<?php

class CQueryRaw extends CPropContainer implements IQueryRaw
{
	public function init($aData=null) 
	{
		//parent::init($aData);

		if(!$aData)
			return;

		if(array_key_exists("sql", $aData))
			$this->setSQL($aData["sql"]);

		if(array_key_exists("bind", $aData))
			$this->m_aBindData = $aData["bind"];
	}

	//************************************************************************

	public function setSQL($sSQL)
	{
		$this->m_sSQL = $sSQL;
	}

	//************************************************************************

	public function bindData($sKey, $sValue)
	{
		$this->m_aBindData[$sKey] = $sValue;
	}

	//************************************************************************

	public function getSQL()
	{
		return $this->m_sSQL;
	}

	//************************************************************************

	public function getBindData()
	{
		return $this->m_aBindData;
	}

	//########################################################################

	protected $m_sSQL = "";
	protected $m_aBindData = [];
}

ExCore::regClassEx("CQueryRaw", "IQueryRaw");
