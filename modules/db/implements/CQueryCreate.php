<?php

class CQueryCreate extends CPropContainer implements IQueryCreate
{
	public function init($aData=null)
	{
		parent::init($aData);

		if(!$this->exists("ignore"))
			$this->set("ignore", false);

		if($this->exists("data"))
			$this->setData($this->get("data"));
	}

	//************************************************************************

	public function setData($aData)
	{
		$this->setColumns(array_keys($aData));
		$this->addString(array_values($aData));
	}

	//************************************************************************

	public function setColumns($aCols)
	{
		$this->m_aColumns = $aCols;
	}

	//************************************************************************

	public function addString($aString)
	{
		__eassert(count($this->m_aColumns) == count($aString), "count columns does not match");
		$this->m_aStrings[] = $aString;
	}

	//************************************************************************

	public function getSQL()
	{
		$sTable = $this->get("table");
		$useIgnore = $this->get("ignore");
		$sNames = "";
		foreach($this->m_aColumns as $value)
		{
			if(strlen($sNames) > 0)
				$sNames .= ', ';
			$sNames .= "`" . $value . "`";
		}
		$sSQL = "INSERT ".($useIgnore ? "IGNORE" : "")." INTO " . $sTable . ' (' . $sNames . ") VALUES ";

		$this->m_aBindData = [];
		$aStrings = [];
		$iNum = 0;
		foreach($this->m_aStrings as $value)
		{
			$aString = [];
			for($i=0, $il=count($this->m_aColumns); $i<$il; ++$i)
			{
				$sCol = $this->m_aColumns[$i];
				$sKey = ":{$sCol}_{$iNum}";
				$this->m_aBindData[$sKey] = $value[$i];
				$aString[] = $sKey;
			}

			$aStrings[] = implode(", ", $aString);
			++$iNum;
		}

		for($i=0, $il=count($aStrings); $i<$il; ++$i)
		{
			$sSQL .= "(".$aStrings[$i].")";
			if($i<$il-1)
				$sSQL .= ", ";
		}
		
		return $sSQL;
	}

	//************************************************************************

	public function getBindData()
	{
		return $this->m_aBindData;
	}

	//########################################################################

	protected $m_aColumns = [];
	protected $m_aStrings = [];
	protected $m_aBindData = [];
}

ExCore::regClassEx("CQueryCreate", "IQueryCreate");
