<?php

//! реализация Update операции
class CQueryUpdate extends CPropContainer implements IQueryUpdate 
{
	use TQueryRUD;

	//########################################################################
	
	public function __construct() { parent::__construct(); }
	public function init($aData=null) { parent::init($aData); $this->tqInit($aData); }

	//########################################################################

	public function getSQL()
	{
		$sTable = $this->get("table");
		$sUpdateSQL = $this->geColumnsSQL();
		$sAddedSQL = $this->tqGetSQL();
		$sSQL ="UPDATE $sTable SET $sUpdateSQL $sAddedSQL";
		return $sSQL;
	}

	//########################################################################

	protected function geColumnsSQL()
	{
		__eassert($this->exists("columns"), "Not found columns for update");
		__eassert(is_array($this->get("columns")), "Not found array columns for update");

		$aUpdate = $this->get("columns");
		$sUpdate = "";
		foreach($aUpdate as $key => $value)
		{
			if(strlen($sUpdate) > 0)
				$sUpdate .= ", ";

			$sKey = ":{$key}_u";
			$sUpdate .= "$key=$sKey";
			$this->m_aBindData[$sKey] = $value;
		}

		return $sUpdate;
	}
};

ExCore::regClassEx("CQueryUpdate", "IQueryUpdate");
