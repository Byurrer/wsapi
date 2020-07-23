<?php

class CEntity implements IEntity
{
	/*! статичный ассоциативный массив свойств/переменных сущности, каждое поле должно называться так же как и в таблице бд
		@note каждый дочерний класс должен переопределять это
	*/
	protected static $m_aPropsStatic = [
		"id" => [
			"type"		=> "int",	//!< тип, при загрузке из бд будет преобразовано
			"set"			=> false,	//!< можно ли использовать set метод для изменения значения
			"default" => -1,		//!< значение по умолчанию
			"public"	=> true,	//!< публичное поле? если да тогда asArray(true) включит это поле в итоговый массив
		]
	];

	//########################################################################
	// СТАТИЧНЫЕ ПУБЛИЧНЫЕ МЕТОДЫ
	//########################################################################

	public static function getTableName()
	{
		return "";
	}

	//************************************************************************

	public static function existsProp($sProp, $canOnlyPublic=false)
	{
		return (array_key_exists($sProp, static::$m_aPropsStatic) && (!$canOnlyPublic || static::$m_aPropsStatic[$sProp]["public"]));
	}

	//************************************************************************

	public static function addCastHandler($type, $fnInDB, $fnOutDB)
	{
		if(is_array($type))
		{
			foreach($type as $sType)
				static::addCastHandler($sType, $fnInDB, $fnOutDB);

			return;
		}

		static::$m_aCastHandlers[$type] = ["in" => $fnInDB, "out" => $fnOutDB];
	}

	//########################################################################

	public function __construct()
	{
		$this->m_aProps = [];
		$this->nulling();
	}

	public function release() { }

	//########################################################################
	// CRUD
	//########################################################################

	public function create($aData)
	{
		$this->verifyCreateData($aData);
		if($aData)
			$this->updateData($aData, true, true);
	}

	//************************************************************************

	public function init($aData=null)
	{
		if($aData)
			$this->fromArray($aData, (array_key_exists("__from_db", $aData) ? $aData["__from_db"] : false));
	}

	//************************************************************************

	public function fromArray($aData, $isFromDB = true)
	{
		$this->m_aProps = [];
		foreach(static::$m_aPropsStatic as $key => $value)
			$this->m_aProps[$key] = (array_key_exists($key, $aData) ? $aData[$key] : $value["default"]);

		foreach($this->m_aChangedProps as $key => &$value)
			$value = false;

		static::castOut($this->m_aProps);
		
		$this->m_isFromDB = $isFromDB;
	}

	//************************************************************************

	public function updateData($aData, $useOnlyPublic=false, $useOnlySet=false)
	{
		foreach(static::$m_aPropsStatic as $key => $value)
		{
			if(
				array_key_exists($key, $aData) && 
				(!$useOnlyPublic || $value["public"]) && 
				(!$useOnlySet || $value["set"])
			)
			{
				$this->m_aProps[$key] = $aData[$key];
				$this->m_aChangedProps[$key] = true;
			}
		}

		static::castOut($this->m_aProps);
	}

	//************************************************************************

	public function load($aWhere)
	{
		if($aWhere)
		{
			$oRead = __new("IQueryRead", ["table" => static::getTableName(), "where" => $aWhere, "limit" => 1]);
			//print_r($aWhere);
			print_r($oRead->getSQL());
			//print_r($oRead->getRawWhere());
			$aData = __get("IDataBase")->query($oRead, DB_QUERY_RETURN_ONE);

			if($aData && count($aData) > 0)
			{
				$this->fromArray($aData, true);
				return true;
			}
		}
		return false;
	}

	//************************************************************************

	public function save()
	{
		if($this->m_isFromDB)
			$this->_update();
		else
			$this->_insert();
	}

	//************************************************************************

	public function delete()
	{
		if($this->m_isFromDB)
		{
			$this->deleteBinding();
			if(static::existsProp("deleted"))
			{
				$this->_set("deleted", 1);
				$this->save();
			}
			else
			{
				$oQueryDelete = __new("IQueryDelete", ["table" => static::getTableName(), "where" => ["id" => $this->get("id")], "limit" => 1]);
				//print_r($oQueryDelete->getSQL());
				__get("IDataBase")->query($oQueryDelete);
			}
		}

		$this->nulling();
	}

	//************************************************************************

	public function isLoaded()
	{
		return ($this->get("id") >= 0);
	}

	//########################################################################
	// СВОЙСТВА КЛАССА
	//########################################################################

	public function set($sKey, $sValue)
	{
		if($this->canSet($sKey))
		{
			$this->m_aProps[$sKey] = $sValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	public function _set($sKey, $sValue)
	{
		if(array_key_exists($sKey, $this->m_aProps))
		{
			$this->m_aProps[$sKey] = $sValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	//************************************************************************

	public function add($sKey, $sAddValue)
	{
		if($this->canSet($sKey))
		{
			$this->m_aProps[$sKey] += $sAddValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	public function _add($sKey, $sAddValue)
	{
		if(array_key_exists($sKey, $this->m_aProps))
		{
			$this->m_aProps[$sKey] += $sAddValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	//************************************************************************

	public function substract($sKey, $sSubstractValue)
	{
		if($this->canSet($sKey))
		{
			$this->m_aProps[$sKey] -= $sSubstractValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	public function _substract($sKey, $sSubstractValue)
	{
		if(array_key_exists($sKey, $this->m_aProps))
		{
			$this->m_aProps[$sKey] -= $sSubstractValue;
			$this->m_aChangedProps[$sKey] = true;
		}
	}

	//************************************************************************

	public function get($sKey)
	{
		if(array_key_exists($sKey, $this->m_aProps))
			return $this->m_aProps[$sKey];

		return null;
	}

	//************************************************************************

	public function asArray($canCheckPublic=true)
	{
		$aData = [];
		foreach($this->m_aProps as $key => $value)
		{
			if(($canCheckPublic && static::$m_aPropsStatic[$key]["public"]) || !$canCheckPublic)
				$aData[$key] = $this->m_aProps[$key];
		}

		static::castOut($this->m_aProps);
		
		return $aData;
	}

	//########################################################################

	public function getMeta()
	{
		if($this->m_oMeta)
			return $this->m_oMeta;

		if($sClassMeta = __get("IEntitySystem")->getEntClass(static::getTableName()."_meta", false))
		{
			$this->m_oMeta = __get("IEntitySystem")->loadEntity($sClassMeta, [static::getTableName() => $this->get("id")], null, false);
			return $this->m_oMeta;
		}

		return null;
	}

	//########################################################################

	public static function castOut(&$aProps)
	{
		foreach($aProps as $sName => &$value)
		{
			if(is_array($value))
				static::castOut($value);
			else
				static::castValue("out", $sName, $value);
		}

		return $aProps;
	}

	//************************************************************************

	public static function castIn(&$aProps)
	{
		foreach($aProps as $sName => &$value)
		{
			if(is_array($value))
				static::castIn($value);
			else
				static::castValue("in", $sName, $value);
		}

		return $aProps;
	}

	//########################################################################
	// ЗАЩИЩЕННЫЕ/ВНУТРЕННИЕ МЕТОДЫ
	//########################################################################

	protected $m_aProps = [];					//!< свойства обьекта сущности
	protected $m_aChangedProps = [];	//!< изменено ли свойство
	protected $m_isFromDB = false;		//!< взята ли сущность из бд
	protected static $m_aCastHandlers = [];	//!< массив обработчиков преобразователей значений к нужному виду
	protected $m_oMeta = null;

	//########################################################################

	protected function createMeta()
	{
		if(!($sClassMeta = __get("IEntitySystem")->getEntClass(static::getTableName()."_meta", false)))
			return;

		$this->m_oMeta = __get("IEntitySystem")->newEntity($sClassMeta, [static::getTableName() => $this->get("id")], true);
		return $this->m_oMeta;
	}

	//************************************************************************

	protected function deleteBinding()
	{

	}

	//************************************************************************

	protected function verifyCreateData($aData)
	{
		foreach(static::$m_aPropsStatic as $key => $value)
			__eassert(!array_key_exists("required", $value) || !$value["required"] || array_key_exists($key, $aData), "Not found required data [$key] for create object intity");
	}

	//************************************************************************

	/*! можно ли записывать данные в переменную/ключ
		@param sKey имя ключа (переменной)
	*/
	protected function canSet($sKey)
	{
		return (array_key_exists($sKey, $this->m_aProps) && static::$m_aPropsStatic[$sKey]["set"]);
	}

	//************************************************************************

	//! возвращает обьект сущности как массив со всеми его свойствами независимо от прав доступа
	protected function asArr($needOnlyChanged=false)
	{
		$aData = [];
		foreach($this->m_aProps as $key => $value)
		{
			if(!$needOnlyChanged || $this->m_aChangedProps[$key])
				$aData[$key] = $value;
		}

		static::castIn($aData);
		
		return $aData;
	}

	//************************************************************************

	//! обновить строку в таблице
	protected function _update()
	{
		$aWriteData = $this->asArr(true);
		if(array_key_exists("id", $aWriteData) && $aWriteData["id"] <= 0)
			unset($aWriteData["id"]);

		$oQueryUpdate = __new("IQueryUpdate", ["table" => static::getTableName(), "columns" => $aWriteData, "where" => ["id" => $this->get("id")], "limit" => 1]);
		//print_r($oQueryUpdate->getSQL());
		__get("IDataBase")->query($oQueryUpdate);
	}

	//************************************************************************

	//! вставить строку в таблицу
	protected function _insert()
	{
		$aWriteData = $this->asArr(false);
		if(array_key_exists("id", $aWriteData) && $aWriteData["id"] <= 0)
			unset($aWriteData["id"]);

		$oInsert = __new("IQueryCreate", ["table" => static::getTableName(), "data" => $aWriteData]);
		$this->m_isFromDB = __get("IDataBase")->query($oInsert);
		$this->_set("id", __get("IDataBase")->getLastInsertId());

		$this->createMeta();
	}

	//************************************************************************

	//! обнуление данных объекта
	protected function nulling()
	{
		$this->m_aProps = [];
		$this->m_aChangedProps = [];
		foreach(static::$m_aPropsStatic as $key => $value)
		{
			$this->m_aProps[$key] = $value["default"];
			$this->m_aChangedProps[$key] = true;
		}
		
		$this->m_isFromDB = false;
	}

	//************************************************************************

	/*! преобразование значения
		@param sTypeConv тип преобразования "out" для выходных данных из БД, "in" дляотправки в БД
		@param sName имя переменной
		@param value адрес значения переменной (если будет найден обработчик - перезапишет value)
		@return возвращает значение перенной
	*/
	protected static function castValue($sTypeConv, $sName, &$value)
	{
		if(
			array_key_exists($sName, static::$m_aPropsStatic) && 
			array_key_exists("type", static::$m_aPropsStatic[$sName]) && 
			array_key_exists(static::$m_aPropsStatic[$sName]["type"], static::$m_aCastHandlers)
			)
		{
			$sType = static::$m_aPropsStatic[$sName]["type"];
			call_user_func_array(static::$m_aCastHandlers[$sType][$sTypeConv], [&$value]);
		}

		return $value;
	}
};

ExCore::regClass("CEntity");

//##########################################################################

$fnCastHandlerInt10 = function(&$data) { $data = intval($data); };
CEntity::addCastHandler(
	["int", "int10"], 
	$fnCastHandlerInt10, 
	$fnCastHandlerInt10
);

//**************************************************************************

$fnCastHandlerInt2 = function(&$data) { $data = intval($data, 2); };
CEntity::addCastHandler(
	"int2", 
	$fnCastHandlerInt2, 
	$fnCastHandlerInt2
);

//**************************************************************************

$fnCastHandlerInt16 = function(&$data) { $data = intval($data, 16); };
CEntity::addCastHandler(
	"int16", 
	$fnCastHandlerInt16, 
	$fnCastHandlerInt16
);

//**************************************************************************

$fnCastHandlerFloat = function(&$data) { $data = floatval($data); };
CEntity::addCastHandler(
	"float", 
	$fnCastHandlerFloat, 
	$fnCastHandlerFloat
);

//**************************************************************************

CEntity::addCastHandler(
	"bool", 
	function(&$data) { $data = intval($data); }, 
	function(&$data) { $data = boolval($data); }
);
