<?php

//! реализация Read операции
class CQueryRead extends CPropContainer implements IQueryRead
{
	use TQueryRUD;

	//########################################################################

	public function __construct() { parent::__construct(); }
	public function init($aData=null) { parent::init($aData); $this->tqInit($aData); }

	//########################################################################

	public function getSQL()
	{
		$sTable = $this->get("table");
		$sColumns = $this->getSelectColumnsSQL();
		$sAddedSQL = $this->tqGetSQL();
		$sSQL ="SELECT $sColumns FROM ".$sTable." $sAddedSQL";
		return $sSQL;
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	protected $m_aBindData = [];

	//########################################################################

	protected function getSelectColumnsSQL()
	{
		if(!$this->exists("columns"))
			return "*";

		$col = $this->get("columns");
		$sColumns = (is_array($col) ? implode(", ", $col) : $col);
		return (strlen($sColumns) > 0 ? $sColumns : "*");
	}
};

ExCore::regClassEx("CQueryRead", "IQueryRead");
