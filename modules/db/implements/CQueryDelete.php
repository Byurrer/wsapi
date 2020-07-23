<?php

//! реализация Delete операции
class CQueryDelete extends CPropContainer implements IQueryDelete
{
	use TQueryRUD;

	//########################################################################

	public function __construct() { parent::__construct(); }
	public function init($aData=null) { parent::init($aData); $this->tqInit($aData); }

	//########################################################################

	public function getSQL()
	{
		$sTable = $this->get("table");
		$sAddedSQL = $this->tqGetSQL();
		$sSQL ="DELETE FROM $sTable $sAddedSQL";
		return $sSQL;
	}

};

ExCore::regClassEx("CQueryDelete", "IQueryDelete");
