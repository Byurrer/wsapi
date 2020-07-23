<?php

/** реализация #IRequestChecker
*/
class CRequestChecker implements IRequestChecker
{
	public function __construct()	
	{	
		$this->init();
	}

	//************************************************************************

	public function init($aData=null)	{	}

	public function release() {}

	//########################################################################

	public function checkType($sTypeRequest, $canReport=true)
	{
		if(strcasecmp($sTypeRequest, $_SERVER["REQUEST_METHOD"]) != 0)
		{
			if($canReport)
			$this->logError("Expected {$sTypeRequest} request, but come " . $_SERVER["REQUEST_METHOD"]);
			return false;
		}

		return true;
	}

	//************************************************************************

	public function existsData($sTypeStorage, $sKey)
	{
		$aArr = &$this->getStorage($sTypeStorage);
		return array_key_exists($sKey, $aArr);
	}

	//************************************************************************

	public function checkData($sTypeStorage, $sKey, $sTypeArg, $canReport=true)
	{
		$sTypeArg = strtolower($sTypeArg);
		$data = null;
		$aStorage = &$this->getStorage($sTypeStorage);

		//если ключ не найден
		if(!array_key_exists($sKey, $aStorage))
		{
			if($canReport)
				$this->logErrorArg($sKey, ["message" => "Параметр является обязательным", "code" => "required"]);
			return false;
		}
		else
			$data = $aStorage[$sKey];

		//если тип данных не соответствует ожидаемому
		if(!$this->checkTypeData($sTypeArg, $data, $sError))
		{
			if($canReport)
				$this->logErrorArg($sKey, ["message" => $sError, "code" => "invalid"]);
			return false;
		}

		//возможно надо привести к определенному (правильнмоу) значению
		$this->castParam($aStorage[$sKey], $sTypeArg);

		return true;
	}

	//************************************************************************

	public function checkExistsData($sTypeStorage, $sKey, $sTypeArg)
	{
		$aStorage = &$this->getStorage($sTypeStorage);

		if(array_key_exists($sKey, $aStorage))
			return $this->checkData($sTypeStorage, $sKey, $sTypeArg, true);

		return false;
	}

	//************************************************************************

	public function checkDataLeast($sTypeStorage, $aKeys, $canReport=true)
	{
		$aStorage = &$this->getStorage($sTypeStorage);

		$existsLeast = false;
		foreach($aKeys as $key => $type)
		{
			if(array_key_exists($key, $aStorage))
			{
				if($this->checkTypeData($type, $aStorage[$key], $sError) == false)
				{
					if($canReport)
						$this->logErrorArg($sKey, ["message" => $sError, "code" => "invalid"]);
					return false;
				}
				$this->castParam($aStorage[$key], $type);
				$existsLeast = true;
			}
		}

		if($canReport && !$existsLeast)
			$this->logError("required parameters not found: ".implode(", ", array_keys($aKeys)));

		return $existsLeast;
	}

	//************************************************************************

	public function getData($sTypeStorage, $sKey, $default=null)
	{
		$aStorage = &$this->getStorage($sTypeStorage);

		if(!array_key_exists($sKey, $aStorage))
			return $default;
		else
			return $aStorage[$sKey];
	}

	//************************************************************************

	public function unsetData($sTypeStorage, $sKey)
	{
		$aStorage = &$this->getStorage($sTypeStorage);

		if(array_key_exists($sKey, $aStorage))
			unset($aStorage[$sKey]);
	}

	//########################################################################

	public static function addCast($sType, $fnCast)
	{
		static::$m_aCast[$sType] = $fnCast;
	}

	//************************************************************************

	public static function addCheckerTypeData($type, $fnChecker, $sError)
	{
		if(is_array($type))
		{
			foreach($type as $sType)
				static::addCheckerTypeData($sType, $fnChecker, $sError);

			return;
		}

		static::$m_aCheckerType[$type] = [
			"fn" => $fnChecker,
			"error" => $sError
		];
	}

	//########################################################################

	public function logError($sError)
	{
		__get("IResponse")->add(["body", "error"], $sError);
	}

	//************************************************************************

	public function logErrorArg($sArg, $sError)
	{
		__get("IResponse")->add(["body", "error_arg", $sArg], $sError);
	}

	//########################################################################
	//########################################################################
	//########################################################################

	//! обработчики преобразования типов
	protected static $m_aCast = [];

	//! обработчики проверки типов
	protected static $m_aCheckerType = [];

	//************************************************************************

	/** возвращает ссылку на хранилище запроса _GET/_POST
		@return если sTypeStorage == "get" тогда _GET, иначе _POST
	*/
	protected function &getStorage($sTypeStorage)
	{
		if(strcasecmp($sTypeStorage, "get") == 0)
			return $_GET;
		else 
			return $_POST;
	}

	//************************************************************************

	/** приведение данных в data в соответсвии с типом sType
		@note не все данные будут изменены этой функцией, но все данные надо через нее проводить
	*/
	protected function castParam(&$data, $sType)
	{
		if(!array_key_exists($sType, static::$m_aCast))
			return;

		static::$m_aCast[$sType]($data);
	}

	//************************************************************************

	/** проверка: соответствует ли значение data указанному типу sType
		@param sType тип из набора
		@param data переменная с проверяемым значением
		@return true/false
	*/
	protected function checkTypeData($sType, $data, &$sError)
	{
		if(!array_key_exists($sType, static::$m_aCheckerType))
		{
			$sError = static::$m_aCheckerType[$sType]["error"];
			return false;
		}

		$sError = static::$m_aCheckerType[$sType]["error"];
		return call_user_func(static::$m_aCheckerType[$sType]["fn"], $data);
	}
};

ExCore::regClass("CRequestChecker");

//##########################################################################

//регистрация string
CRequestChecker::addCheckerTypeData(
	"string", 
	function($data) { return is_string($data); },
	"Введите строку"
);

//регистрация int
CRequestChecker::addCheckerTypeData(
	"int", 
	function($data) { return (filter_var($data, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_OCTAL) !== false); },
	"Введите целое число"
);

//регистрация bool
CRequestChecker::addCheckerTypeData(
	"bool", 
	function($data) { return (
		$data === 0 || $data === 1 || 
		strcasecmp($data, "0") == 0 || strcasecmp($data, "1") == 0 || 
		strcasecmp($data, "false") == 0 || strcasecmp($data, "true") == 0 || 
		strcasecmp($data, "on") == 0 || strcasecmp($data, "off") == 0
	);},
	"Введите логическое значение (true/false 1/0 on/off)"
);

//**********************************************************************

//регистрация преобразования bool значения
CRequestChecker::addCast("bool", 
	function(&$data) {$data = ($data == 1 || strcasecmp($data, "true") == 0 || strcasecmp($data, "on") == 0); }
);
