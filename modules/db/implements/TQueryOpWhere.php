<?php

define("SQL_AND", "AND");
define("SQL_OR", "OR");

define("IN_FULL_BIND", true);

//##########################################################################

trait TQueryOpWhere
{
	//########################################################################

	public function where($aWhere, $sOpCond=SQL_AND)
	{
		__eassert(($sOpCond == SQL_AND || $sOpCond == SQL_OR), "Unresolved logic operation");
		__eassert(is_array($aWhere) || is_object($aWhere), "Unresolved where data");
		
		if(count($this->m_aWhere) == 0)
		{
			foreach($aWhere as $key => $value)
			{
				if(is_assoc($value))
				{
					$this->m_aWhere["__cond".($this->m_idCond++)] = $sOpCond;
					$this->m_aWhere["__expr".($this->m_idExpr++)] = $value;
				}
				else
					$this->m_aWhere[$key] = $value;
			}
		}
		else
		{
			$this->m_aWhere["__cond".($this->m_idCond++)] = $sOpCond;
			$this->m_aWhere["__expr".($this->m_idExpr++)] = $aWhere;
		}
	}

	//************************************************************************

	public function getWhereSQL()
	{
		__eassert($this->exists("table"), "Variable [table] not found");
		$sTable = $this->get("table");
		$sWhere = $this->parseWhere($this->m_aWhere, $sTable);

		if(strlen($sWhere) > 0)
			$sWhere = " WHERE $sWhere ";

		return $sWhere;
	}

	//************************************************************************

	public function getRawWhere()
	{
		return $this->m_aWhere;
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	protected $m_aWhere = [];

	//! идентификатор логического оператора (чтобы уникализировать ключи в массиве)
	protected $m_idCond = 0;

	//! идентификатор выражения (чтобы уникализировать ключи в массиве)
	protected $m_idExpr = 0;

	//! идентификатор следующего запроса
	protected static $m_idQueryNext = 0;

	//! идентификатор текущего запроса
	protected $m_idQuery = 0;

	//########################################################################

	//! возвращает краткое строковое описание операции
	protected function getShortStrOp($sOp)
	{
		switch($sOp)
		{
		case ">": return "bt";
		case ">=": return "bte";
		case "<": return "lt";
		case "<=": return "lte";
		case "!=": return "ne";
		case "!": return "n";
		case "%": return "like";
		case "!%": return "nlike";
		case "in": return "in";
		case "!in": return "nin";

		default: return "e";
		}
	}

	//************************************************************************

	//! возвращает SQL аналог операции
	protected function getOpSQL($sOp)
	{
		switch($sOp)
		{
		case "%": return " LIKE ";
		case "!%": return " NOT LIKE ";
		case "in": return " IN ";
		case "!in": return " NOT IN ";

		default: return $sOp;
		}
	}

	//************************************************************************

	//! конвертирует значение на основании операции, если надо, иначе вернет $value без изменений
	protected function сonvVal($sOp, $value)
	{
		switch($sOp)
		{
		case "%": 
		case "!%": 
			return "%$value%";
		case "=":
		case "!=":
		case "in": 
		case "!in":
		{
			if(!IN_FULL_BIND && is_array($value))
			{
				$aVal = [];
				foreach($value as $val)
					$aVal[] = escape_sql($val);
				
				return "'".implode("', '", $aVal)."'";
			}
		}
		default: return $value;
		}
	}

	//************************************************************************

	protected function needValBinding($sOp)
	{
		return !($sOp == "in" || $sOp == "!in");
	}

	//************************************************************************

	//! (если надо) конвертирует операцию на основании значения (иначе вернет неизменным)
	protected function convOpByVal($sOp, $value)
	{
		if(is_array($value))
		{
			if($sOp == "=")
				return "in";
			else if($sOp == "!=")
			return "!in";
		}

		return $sOp;
	}

	//************************************************************************

	/*! парсинг ключа (левого операнда)
		@param sKey ключ
		@param value значение ключа (правый операнд), от этого значения может поменятся логическая операция
		@param sTable название таблицы, в которой используется этот ключ
	*/
	protected function parseKey($sKey, $value, $sTable = null)
	{
		$aKey = explode(":", $sKey);
		$sKey = ""; $sCond = "and"; $sOpRaw = "";

		if(strcasecmp($aKey[0], "or") == 0 || strcasecmp($aKey[0], "and") == 0)
		{
			$sCond = $aKey[0];
			$sKey = $aKey[1];
			$sOpRaw = (count($aKey) > 2 ? $aKey[2] : "=");
		}
		else
		{
			$sKey = $aKey[0];
			$sOpRaw = (count($aKey) > 1 ? $aKey[1] : "=");
		}

		$sKeyVal = str_replace(".", "__", $sKey);
		$sValPostfix = $this->getShortStrOp($sOpRaw);

		$sOpRaw = $this->convOpByVal($sOpRaw, $value);

		if(strpos($sKey, ".") === false && $sTable && strlen($sTable) > 0)
			$sKey = $sTable.".".$sKey;

		return ["key" => $sKey, "cond" => $sCond, "op_raw" => $sOpRaw, "op" => $this->getOpSQL($sOpRaw), "val" => "idq".$this->m_idQuery."_".$sKeyVal."_".$sValPostfix];
	}

	//************************************************************************

	//! парсит aData как where sub expression
	protected function parseWhereSub($aData, $sTable="")
	{
		if(is_assoc($aData))
			return "(".$this->parseWhere($aData, $sTable).")";
		else if(is_object($aData))
			return "(".$aData->getSQL().")";
		return "";
	}

	//************************************************************************

	//! парсинг where таблицы
	protected function parseWhere($aData, $sTable="")
	{
		if($aData === null || count($aData) == 0)
			return "";

		$sSQL = "";

		//можно ли вставлять логический оператор (после первой вставки любого выражения - можно)
		$canCond = false;

		foreach($aData as $key => $value)
		{
			//если логический оператор, то просто вставляем
			if(strpos($key, "__cond") === 0)
				$sSQL .= " ".strtoupper($value)." ";

			//иначе логическое выражение
			else
			{
				//парсим подвыражение (получаем SQL)
				$sSubSQL = $this->parseWhereSub($value, $sTable);

				//если выражение (сложное)
				if(strpos($key, "__expr") !== 0)
				{
					//парсим ключ
					$aKey = $this->parseKey($key, $value, $sTable);
					$needBinding = $this->needValBinding($aKey["op_raw"]);

					$sCond = ($canCond ? (" ".strtoupper($aKey["cond"]). " ") : "");
					
					//если подвыражения нет значит это простое выражение (типа литерала или массива для IN)
					if(strlen($sSubSQL) == 0)
					{
						//$final_value = $this->сonvVal($aKey["op_raw"], $value);
						$needBind = $this->needValBinding($aKey["op_raw"]);
						$sBindKey = ":".$aKey["val"];
						
						//если биндить не надо
						if(!$needBind)
						{
							if(IN_FULL_BIND)
							{
								$aFinalValue = [];
								
								foreach($value as $key => $val)
								{
									$sBindKey2 = $sBindKey."_IN".$key;
									$this->m_aBindData[$sBindKey2] = $val;
									$aFinalValue[] = $sBindKey2;
								}
								$sSubSQL = implode(", ", $aFinalValue);
							}
							else
								$sSubSQL = $this->сonvVal($aKey["op_raw"], $value);

							$sSubSQL = "($sSubSQL)";
						}
						else
						{
							$sSubSQL = $sBindKey;
							$this->m_aBindData[$sBindKey] = $value;
						}

						//echo "sSubSQL = $sSubSQL\n";
					}

					//иначе подвыражение было, если значение является обьектом, значит это подзапрос - достаем из него bind и обьединяем с главным bind
					else if(is_object($value))
						$this->m_aBindData = array_merge($this->m_aBindData, $value->getBindData());

					$sSQL .= $sCond.$aKey["key"].$aKey["op"].$sSubSQL;
				}
				else
					$sSQL .= $sSubSQL;
			}

			//разрешаем вставлять логический оператор
			$canCond = true;
		}

		return $sSQL;
	}
};
