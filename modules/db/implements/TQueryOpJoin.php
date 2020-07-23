<?php

trait TQueryOpJoin
{
	public function join($sTable, $aCond, $sSide="LEFT", $bOuter=false)
	{
		$this->m_aJoins[$sTable] = [
			"cond" => $aCond, 
			"side" => $sSide, 
			"outer"=> $bOuter
		];
	}

	//************************************************************************
	
	public function joins($aJoins)
	{
		$this->m_aJoins = array_merge($this->m_aJoins, $aJoins);
	}

	//************************************************************************

	public function getJoinSQL()
	{
		$sSql = "";
		foreach($this->m_aJoins as $key => $value)
		{
			$Side = strtoupper($value["side"]);
			$useOuter = (array_key_exists("outer", $value) ? $value["outer"] : false);
			$sSqlJoin = " $Side ".($useOuter ? "OUTER" : "")." JOIN $key ON ";
			
			$sSqlOn = "";
			foreach($value["cond"] as $key2 => $value2)
			{
				if(strlen($sSqlOn) > 0)
					$sSqlOn .= " AND ";
					
				$sSqlOn .= " ".$key2."=".$value2." ";
			}
			$sSql .= $sSqlJoin.$sSqlOn;
		}

		return $sSql;
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	protected $m_aJoins = [];

};
