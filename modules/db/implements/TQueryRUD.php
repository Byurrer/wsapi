<?php

trait TQueryRUD
{
	use TQueryOpWhere, TQueryOpJoin, TQueryOpLimit;

	//########################################################################
	
	public function tqInit($aData=null) 
	{
		$this->m_idQuery = static::$m_idQueryNext++;

		if(!$aData)
			return;

		//echo "tqInit = ".print_r($aData, true);

		if(array_key_exists("where", $aData) && $aData["where"])
			$this->where($aData["where"]);

		if(array_key_exists("limit", $aData) && $aData["limit"])
			$this->limit($aData["limit"]);

		if(array_key_exists("joins", $aData) && $aData["joins"])
			$this->joins($aData["joins"]);
	}

	//************************************************************************

	public function tqGetSQL()
	{
		$sTable = $this->get("table");
		
		$sWhere = $this->getWhereSQL();
		$sJoin = $this->getJoinSQL();
		$sLimit = $this->getLimitSQL();
		$sSQL = " $sJoin $sWhere $sLimit";
		$sSQL = str_replace("  ", " ", $sSQL);
		$sSQL = str_replace("  ", " ", $sSQL);
		return $sSQL;
	}

	//************************************************************************

	public function getBindData()
	{
		return $this->m_aBindData;
	}
};
