<?php

class CEntitySystem implements IEntitySystem
{
	public function init($aData=null) { }
	public function release() { }

	//########################################################################

	public function regEntity($sClassEntity)
	{
		if(array_key_exists($sClassEntity, $this->m_aEntities))
			return;

		$this->m_aEntities[$sClassEntity] = $sClassEntity::getTableName();
	}

	//************************************************************************

	public function regBinding($sClassBinding)
	{
		if(array_key_exists($sClassBinding, $this->m_aBindings))
			return;

		$this->m_aBindings[$sClassBinding] = $sClassBinding::getTableName();
	}

	//########################################################################

	public function asArray($data)
	{
		if(is_array($data))
		{
			$aData2 = [];
			foreach($data as $key => $value)
			{
				if(is_array($value))
				{
					$aData2[$key] = [];
					foreach($data[$key] as $key2 => $value2)
					{
						if(!array_key_exists($key2, $aData2[$key]))
							$aData2[$key][$key2] = [];

						$aData2[$key][$key2] = $value2->asArray();
					}
				}
				else if(is_object($value))
					$aData2[$key] = $value->asArray();
			}

			return $aData2;
		}
		else if(is_object($data))
			return $data->asArray();

		return null;
	}

	//************************************************************************

	public function asObject($sEntity, $aTarget)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);
		//exit_print_r($sClassEntity);

		if(!is_array($aTarget))
			return null;

		if(is_assoc($aTarget))
		{
			$oObject = new $sClassEntity();
			$oObject->fromArray($aTarget, true);
			return $oObject;
		}

		if(!is_assoc($aTarget[0]))
			return null;

		$aObjects = [];

		foreach($aTarget as $key => $value)
		{
			$oObject = new $sClassEntity();
			//exit_print_r($value);
			$oObject->fromArray($value, true);
			
			$aObjects[] = $oObject;
		}

		return $aObjects;
	}

	//########################################################################

	public function newEntity($sEntity, $aData, $canSave=false)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);

		$oObject = new $sClassEntity();
		$oObject->create($aData);

		if($canSave)
			$oObject->save();

		return $oObject;
	}

	//************************************************************************

	public function loadEntity($sEntity, $aWhere, $sRelated=null, $canReport=true)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);
		$aJoins2 = $this->related2Joins($sClassEntity, $sRelated);

		if(!$aWhere) $aWhere = [];
		if($sClassEntity::existsProp("deleted") && !array_key_exists("deleted", $aWhere))
			$aWhere["deleted"] = 0;

		$oRead = __new("IQueryRead", ["table" => $sClassEntity::getTableName(), "where" => $aWhere, "joins" => $aJoins2, "limit" => 1]);
		//print_r($oRead->getSQL());
		$aData = __get("IDataBase")->query($oRead, DB_QUERY_RETURN_ONE, ($sRelated ? true : false));
		if(!$aData)
		{
			if($canReport)
				$this->report("object {{$sClassEntity}} not found");
			return null;
		}

		if($sRelated)
		{
			$aData = $this->splitRawResult($sClassEntity, $aData, true);
			$aRelated = [];
			foreach($aData["related"] as $key => $value)
				$aRelated[$key] = array_shift($value);
			return ["target" => $aData["target"][0], "related" => $aRelated];
		}
		
		$oObject = new $sClassEntity();
		$oObject->fromArray($aData, true);

		return $oObject;
	}

	//************************************************************************

	public function loadEntityAsArr($sEntity, $aWhere, $sRelated=null, $canReport=true)
	{
		$result = $this->loadEntity($sEntity, $aWhere, $sRelated, $canReport);
		if(is_array($result))
		{
			$result["target"] = $result["target"]->asArray();

			foreach($result["related"] as $key => &$value)
				$value = $value->asArray();

			return $result;
		}

		if($result)
			return $result->asArray();

		return null;
	}

	//########################################################################

	public function loadEntityList($sEntity, $aWhere, $sRelated=null, $iStart=0, $iCount=0, $sSortBy="id", $sSortDir="DESC", $asObject=true)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);
		$aJoins2 = $this->related2Joins($sClassEntity, $sRelated);//$this->getJoinsArr($sClassEntity, $aJoins);

		$oRead = __new("IQueryRead", ["table" => $sClassEntity::getTableName(), "where" => $aWhere, "joins" => $aJoins2, "limit" => [$iCount, $iStart], "sort" => [$sSortBy, $sSortDir]]);
		//print_r($oRead->getSQL());
		$aResult = __get("IDataBase")->query($oRead, DB_QUERY_RETURN_ALL, ($sRelated ? true : false));

		if(!$sRelated)
			return ($asObject ? $this->asObject($sClassEntity, $aResult) : $sClassEntity::castOut($aResult));

		$aResult = $this->splitRawResult($sClassEntity, $aResult, $asObject);
		return $aResult;
	}

	//************************************************************************

	public function loadEntityListArr($sEntity, $aWhere, $sRelated=null, $iStart=0, $iCount=0, $sSortBy="id", $sSortDir="DESC")
	{
		$aObjects = loadEntityList($sEntity, $aWhere, $sRelated, $iStart, $iCount, $sSortBy, $sSortDir, true);
		return $aObjects;
	}

	//************************************************************************

	public function loadEntityListKey($sEntity, $sKey, $aWhere, $sRelated=null)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);
		$aJoins2 = $this->related2Joins($sClassEntity, $sRelated);

		$oRead = __new("IQueryRead", ["table" => $sClassEntity::getTableName(), "columns" => [$sKey], "where" => $aWhere, "joins" => $aJoins2]);
		//print_r($oRead->getSQL());
		$aResult = __get("IDataBase")->query($oRead, DB_QUERY_RETURN_ALL, ($sRelated ? true : false));
		return extrude($aResult, $sKey, true);
	}

	//########################################################################

	public function aggregate($sEntity, $aFuncKey, $aWhere, $sRelated=null)
	{
		$sClassEntity = $this->getEntClass($sEntity, true);
		$aJoins2 = $this->related2Joins($sClassEntity, $sRelated);

		$aColumns = [];
		foreach($aFuncKey as $sFunc => $sKey)
			$aColumns[] = "$sFunc($sKey) as $sFunc";

		$oRead = __new("IQueryRead", ["table" => $sClassEntity::getTableName(), "where" => $aWhere, "joins" => $aJoins2, "columns" => $aColumns]);
		//print_r($oRead->getSQL());
		$aResult = __get("IDataBase")->query($oRead, DB_QUERY_RETURN_ONE, ($sRelated ? true : false));
		//print_r($aResult);

		$aResult2 = [];
		foreach($aResult as $key => $value)
		{
			if($key[0] == ".")
				$key = substr($key, 1);
			$aResult2[$key] = intval($value);
		}

		if(count($aColumns) == 1)
			return array_shift($aResult2);

		return $aResult2;
	}

	//########################################################################

	public function binding($sBinding, $aDataBasis, $aDataAdd, $isBind=true, $isForce=false)
	{
		$sClassBinding = $this->getBindingClass($sBinding, true);

		$aAllData = array_merge($aData, $aDataAdd);
		$aCounters = [];	//массив общего количества по каждому ключу
		$aCurrPos = [];		//текущая позиция для каждого ключа
		$iAllCount = 0;		//общее количество данных
		$aStrings = [];		//массив строк

		//инициализация aCounters aCurrPos и iAllCount
		foreach($aAllData as $sCol => $value)
		{
			$aCounters[$sCol] = 1;

			if(is_array($value))
				$aCounters[$sCol] = count($value);

			$aCurrPos[$sCol] = 0;
			$iAllCount += $aCounters[$sCol];
		}

		//создание строк для добавления в бд
		while($iAllCount > 0)
		{
			$aString = [];
			foreach($aAllData as $sCol => $value)
			{
				if(is_array($value))
				{
					$aString[$sCol] = $value[$aCurrPos[$sCol]];
					++$aCurrPos[$sCol];

					if($aCurrPos[$sCol] == $aCounters[$sCol])
						$aCurrPos[$sCol] = 0;
				}
				else
					$aString[$sCol] = $value;

				--$iAllCount;
			}

			$oInsert->addString($aString);
		}


		if($isBind)
		{
			if($isForce)
			{
				$oQueryDelete = __new("IQueryDelete", ["table" => $sClassBinding::getTableName(), "where" => $aDataBasis]);
				__get("IDataBase")->query($oQueryDelete);
			}

			$oInsert = __new("IQueryCreate", ["table" => $sClassEntity::getTableName(), "columns" => array_keys($aAllData), "strings" => $aStrings]);
			//print_r($oRead->getSQL());
			$bRes = __get("IDataBase")->query($oInsert);
		}
		else
		{
			foreach($aStrings as $aString)
			{
				$oQueryDelete = __new("IQueryDelete", ["table" => $sClassBinding::getTableName(), "where" => $aString, "limit" => 1]);
				//print_r($oQueryDelete->getSQL());
				__get("IDataBase")->query($oQueryDelete);
			}
		}
	}

	//************************************************************************

	public function binded($sBinding, $aWhere)
	{
		$sClassBinding = $this->getBindingClass($sBinding, true);

		$oRead = __new("IQueryRead", ["table" => $sClassBinding::getTableName(), "where" => $aWhere]);
		//print_r($oRead->getSQL());
		$aResult = __get("IDataBase")->query($oRead, true);
		return boolval(count($aResult));
	}

	//************************************************************************

	public function bindedList($sBinding, $aColumns, $aWhere)
	{
		$sClassBinding = $this->getBindingClass($sBinding, true);

		$isSimple = false;
		if(is_string($aColumns))
		{
			$aColumns = [$aColumns];
			$isSimple = true;
		}

		$oRead = __new("IQueryRead", ["table" => $sClassBinding::getTableName(), "where" => $aWhere, "columns" => $aColumns]);
		$aResult = __get("IDataBase")->query($oRead, true);
		if($aResult)
			return ($isSimple ? $aResult : extract($aColumns[0], $aResult));

		return null;
	}

	//########################################################################

	public function getEntClass($sEntity, $canReport=true)
	{
		if(array_key_exists($sEntity, $this->m_aEntities))
			return $sEntity;

		$sClassEntity = array_search($sEntity, $this->m_aEntities);

		if($canReport)
			__eassert($sClassEntity, "Not found entity [$sEntity]");

		return ($sClassEntity ? $sClassEntity : null);
	}

	//************************************************************************

	public function getEntTable($sEntity, $canReport=true)
	{
		$sClassEntity = $this->getEntClass($sEntity, $canReport);
		return $sClassEntity::getTableName();
	}

	//************************************************************************

	public function report($sError)
	{

	}

	//########################################################################
	// PROTECTED
	//########################################################################

	protected $m_aEntities = [];
	protected $m_aBindings = [];

	//########################################################################

	protected function getBindingClass($sBinding, $canReport=true)
	{
		if(array_key_exists($sBinding, $this->m_aBindings))
			return $sBinding;

		$sClassBinding = array_search($sBinding, $this->m_aBindings);

		__eassert($sClassBinding, "Not found binding [$sBinding]");

		return $sClassBinding;
	}

	//************************************************************************

	protected function getJoinsArr($sClassEntity, $aPreJoins)
	{
		$aJoins2 = null;
		if($aPreJoins)
		{
			$aJoins2 = [];
			foreach($aPreJoins as $sEntity2 => $sField)
			{
				$sClassEntity2 = $this->getEntClass($sEntity2, true);
				$aJoins2[$sClassEntity2::getTablename()] = [
					"cond" => [$sClassEntity2::getTablename().".$sField" => $sClassEntity::getTablename().".id"],
					"side" => "left",
					"outer" => false
				];
			}
		}

		return $aJoins2;
	}

	//************************************************************************

	protected function splitRawResult($sClassEntity, $aResult, $asObject=true)
	{
		$aTargetsId = [];
		$aTargets = [];
		$aRelateds = [];

		foreach($aResult as $value)
		{
			$aDiff = split_by_subkeys($value, ".");
			$aTarget = $aDiff[$sClassEntity::getTableName()];

			if(!in_array($aTarget["id"], $aTargetsId))
			{
				$aTargetsId[] = $aTarget["id"];
				$aTargets[] = ($asObject ? $this->asObject($sClassEntity, $aTarget) : $sClassEntity::castOut($aTarget));
			}

			unset($aDiff[$sClassEntity::getTableName()]);

			foreach($aDiff as $key2 => $value2)
			{
				if($value2["id"] === null)
					continue;

				if(!array_key_exists($key2, $aRelateds))
					$aRelateds[$key2] = [];

				$sClassEntity2 = $this->getEntClass($key2, true);
				$aRelateds[$key2][] = ($asObject ? $this->asObject($key2, $value2) : $sClassEntity2::castOut($value2));
			}
		}

		return ["target" => $aTargets, "related" => $aRelateds];
	}

	//************************************************************************

	protected function related2Joins($sClassName, $aRelated)
	{
		if(!$aRelated)
			return null;

		$sJoins = [];

		$aEnt = explode(",", $aRelated);
		$aEnt = array_map("trim", $aEnt);

		foreach($aEnt as $sEnt)
		{
			$aEnt2 = explode(".", $sEnt);

			$sPreClass = $sClassName;
			$sPreField = $sClassName::getTableName();
			foreach($aEnt2 as $sEnt2)
			{
				if(!array_key_exists($sEnt2, $sJoins))
					$sJoins[$sEnt2] = ["cond" => [], "side" => "left", "outer" => false];

				$aCond = ["$sPreField.$sEnt2" => "$sEnt2.id"];
				$sJoins[$sEnt2]["cond"] = array_merge($sJoins[$sEnt2]["cond"], $aCond);

				$sPreClass = $this->getEntClass($sEnt2, true);
				$sPreField = $sPreClass::getTableName();
			}
		}

		//exit_print_r($sJoins);
		return $sJoins;
	}
};

ExCore::regClass("CEntitySystem");
