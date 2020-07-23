<?php

define('PATH_CORE', dirname(__FILE__));

include("std.php");
include("ex.php");
include("__fn.php");

//##########################################################################

/*

*/
class ExCore
{
	//! тип интерфейса, обьект которого может быть создан только в единственном экземпляре
	const INTERFACE_SINGLE = 0;

	//! тип интерфейса, обьект которого может быть создан неограниченное количество раз
	const INTERFACE_MULTI = 1;

	//! тип интерфейса, обьект которого может быть одиночным и создан неограниченное количество раз
	const INTERFACE_MIXED = 2;

	//########################################################################

	/** Иницализация ядра
		@param aPluginPathes массив с путями до директорий файлов с модулями
	*/
	static public function init($aPluginPathes)
	{
		if(static::$m_isInit)
			return;

		static::loadModules([PATH_CORE."/interfaces/"]);
		static::loadModules([PATH_CORE."/implementation/"]);
		static::loadModules($aPluginPathes);
		static::$m_isInit = true;
		static::$m_fTime = microtime(true);
	}

	//************************************************************************

	/** Регистрация интерфейса
		@param sInterface имя интерфейса
		@param iType тип интерфейса из констант ExCore::INTERFACE_
	*/
	static public function regInterface($sInterface, $iType, $isImplement=false)
	{
		if(array_key_exists($sInterface, static::$m_mapInterfaces))
			static::error("interface [$sInterface] $iType already registered");

		static::log("[$sInterface] $iType");

		static::$m_mapInterfaces[$sInterface] = $iType;

		if($isImplement)
			static::regClassEx(null, $sInterface);
	}

	//************************************************************************

	/** Регистрация производного класса 
		@param sClassDerivative имя производного класса
		@param sVariant имя варианта
		@note Базовый класс выбирается автоматически (последний элемент из дерева), сначала class_parents, если нет тогда class_implements
	*/
	static public function regClass($sClassDerivative, $sVariant = null)
	{
		$aBases = class_parents($sClassDerivative);
		if(count($aBases) == 0)
		{
			$aBases = class_implements($sClassDerivative);
			if(count($aBases) == 0)
				static::error("class [$sClassDerivative] has not parents");
		}

		$sClassBase = $aBases[array_key_first($aBases)];

		static::regClassEx($sClassDerivative, $sClassBase, $sVariant, true);
	}

	//*****************

	/** Расширенная регистрация производного класса с реализацией
		@param sClassDerivative имя производного класса
		@param sClassBase имя базового класса, от которого происходит наследование
		@param sVariant имя варианта
		@param isInternal вызывается ли эта функция внутри ядра (для правильного логирования), для внешнего вызова указывать не надо
	*/
	static public function regClassEx($sClassDerivative, $sClassBase, $sVariant = null, $isInternal = false)
	{
		if(!$sClassDerivative)
		{
			$sClassDerivative = $sClassBase; 
			$sClassDerivative[0] = 'C';
			//$sClassDerivative .= "__";

			class_alias($sClassBase, $sClassDerivative);
		}
		else
		{
			$aBases = array_merge(class_parents($sClassDerivative), class_implements($sClassDerivative));
			if(!array_key_exists($sClassBase, $aBases))
				static::error("class [$sClassDerivative] has not parent [$sClassBase]");
		}
		

		//если класс уже есть в массиве
		if(array_key_exists($sClassBase, static::$m_mapInherits))
		{
			foreach(static::$m_mapObjects as $sKey => $value)
			{
				if(stripos($sKey, $sClassBase) !== 0)
					static::error("object of class [$sClassBase] already exists, undefined behavior");
			}
			//если это массив (с вариантами)
			if(is_array(static::$m_mapInherits[$sClassBase]))
				static::$m_mapInherits[$sClassBase][$sClassDerivative] = $sVariant;
			//иначе уже существует запись без вариантов, ошибка
			else
			{
				$sClassAlready = static::$m_mapInherits[$sClassBase];
				static::error("base class [$sClassBase] already has derivative [$sClassAlready], fagot add new derivative [$sClassDerivative]");
			}
		}
		//иначе записи нет, добавляем
		else
		{
			if($sVariant)
				static::$m_mapInherits[$sClassBase] = [$sClassDerivative => $sVariant];
			else
				static::$m_mapInherits[$sClassBase] = $sClassDerivative;
		}

		static::log("derivative [$sClassDerivative] of base [$sClassBase]", intval($isInternal));
	}

	//************************************************************************

	/** Возвращает последний производный класс базового класса
		@param sClassBase название базового класса/интерфейса
		@param sVariant вариант использования если есть
		@return строку с названием производного класса
	*/
	static public function getClass($sClassBase, $sVariant = null)
	{
		//если класса не существует, пытаемся загрузить
		if(!array_key_exists($sClassBase, static::$m_mapInherits))
		{
			$sClassDerivative = $sClassBase;
			$sClassDerivative[0] = 'C';
			spl_autoload_call($sClassDerivative);

			//если не удалось найти, значит ошибка
			if(!array_key_exists($sClassBase, static::$m_mapInherits))
				static::error("not found derivative class for base class [$sClassBase]");
		}

		$sClassDerivative = static::getDerivative($sClassBase, $sVariant);

		//static::log("base [$sClassBase], derivative [$sClassDerivative], variant [".strval($sVariant)."]");

		return $sClassDerivative;
	}

	//########################################################################

	/** Возвращает single обьект (если не было тогда создаст)
		@param sInterface интерфейс обьекта
		@param aVariants варианты использования, может быть null если у интерфейса нет вариантов, строка - если несколько один вариант, или массив строк, если несколько вариантов у промежуточных производных классов
		@param sName имя обьекта, если null тогда обьект будет существовать в единственном экземпляре
		@return обьект указанного базового класса
	*/
	static public function &getObject($sInterface, $aVariants = null, $sName = null)
	{
		if(static::getInterfaceType($sInterface) == self::INTERFACE_MULTI)
			static::error("fagot create SINGLE object of MULTI interface [$sInterface]");

		//получаем финальный производный класс (если была ошибка, то этот вызов все остановит)
		$sClassDerivative = static::getClass($sInterface, $aVariants);

		//склейка вариантов для имени
		$sVariant = (is_array($aVariants) ? implode("/", $aVariants) : $aVariants);

		//генерация имени ключа массива
		$sKey = "$sInterface/$sVariant/$sName";

		//если обьекта с указанным ключом еще нет, тогда создаем ()
		if(!array_key_exists($sKey, static::$m_mapObjects))
			static::$m_mapObjects[$sKey] = new $sClassDerivative();

		return static::$m_mapObjects[$sKey];
	}

	//************************************************************************

	/** Создает и возвращает новый multi обьект
		@param sInterface интерфейс обьекта
		@param sVariant вариант использования, может быть null если у интерфейса нет вариантов
		@return обьект указанного базового класса
	*/
	static public function newObject($sInterface, $sVariant = null, $aData)
	{
		if(static::getInterfaceType($sInterface) == self::INTERFACE_SINGLE)
			static::error("fagot create MULTI object of SINGLE interface [$sInterface]");

		$sClassDerivative = static::getClass($sInterface, $sVariant);

		$oObj = new $sClassDerivative();
		$oObj->init($aData);
		return $oObj;
	}

	//########################################################################

	static public function release()
	{
		if(static::$m_isRelease)
			return;

		static::$m_isRelease = true;
		__get("IResponse")->send();
		__log("core time: ".(microtime(true) - static::$m_fTime));
		__get("ILog")->release();
		exit();
	}

	//########################################################################

	static public function dbg($sData, $needReturn = false)
	{
		$sStr = "";
		switch($sData)
		{
		case "inherits":
			$sStr = print_r(static::$m_mapInherits, true);
			break;
		case "interfaces":
			$sStr = print_r(static::$m_mapInterfaces, true);
			break;
		case "objects":
			$sStr = print_r(static::$m_mapObjects, true);
			break;
		case "log":
			$sStr = print_r(static::$m_aLog, true);
			break;
		default:
			break;
		}

		if($needReturn)
			return $sStr;

		print_r($sStr);
	}

	//########################################################################
	//########################################################################
	//########################################################################

	/** Загрузка модулей по указанным путям
		@param aPathes линейный массив путей до директорий или файлов
		@note в директории все php файлы будут подключены
		@note все вложенные директории будут просканированы
	*/
	static protected function loadModules($aPathes)
	{
		if(is_string($aPathes))
			$aPathes = [$aPathes];

		foreach($aPathes as $sPath)
		{
			$sDir = (is_file($sPath) ? dirname($sPath) : $sPath);
			spl_autoload_register(function ($sClass) use($sDir) {
				$sPath2 = $sDir . "/" . $sClass . '.php';
				if(file_exists($sPath2))
					include_once($sPath2);
			});

			if($sPath == "." || $sPath == "..")
					continue;

			//echo $sPath . "\n";
			$aFiles = [];
			if(is_file($sPath))
			{
				if(static::isPHPfile($sPath))
					include_once($sPath);
				continue;
			}
			else
				$aFiles = scandir($sPath);

			$aDirs = [];

			foreach($aFiles as $sFile)
			{
				if($sFile == "." || $sFile == "..")
					continue;

				$sFullPath = $sPath . "/" . $sFile;
				if(is_file($sFullPath))
				{
					if(static::isPHPfile($sFullPath))
						include_once($sFullPath);
				}
				else
					$aDirs[] = $sFullPath;
			}

			if(count($aDirs) > 0)
				static::loadModules($aDirs);
		}
	}

	//************************************************************************

	//! Возвращает true если sPath файл и имеет расширение php
	static protected function isPHPfile($sPath)
	{
		return (is_file($sPath) && strcasecmp(substr(strrchr($sPath, '.'), 1), "php") == 0);
	}

	//########################################################################

	/** Возвращает производный класс
		@param sInterface базовый класс
		@param aVariants варианты использования
		@return конечный производный класс
	*/
	static protected function getDerivative($sInterface, $aVariants = null)
	{
		/*if($sInterface == "IQueryRead")
		{
			exit_print_r(static::$m_mapInherits);
		}*/

		if(is_string($aVariants))
			$aVariants = [$aVariants];

		$sVariant = null;
		if(is_array($aVariants))
			$sVariant = array_shift($aVariants);

		$sClassDerivative = $sInterface;

		while(array_key_exists($sClassDerivative, static::$m_mapInherits))
		{
			if(is_array(static::$m_mapInherits[$sClassDerivative]))
			{
				if(!$sVariant)
					static::error("derivative [$sClassDerivative] of interface [$sInterface] contains variants, but requested without variant");
				
				if(!($sKey = array_search($sVariant, static::$m_mapInherits[$sClassDerivative])))
					static::error("derivative [$sClassDerivative] of interface [$sInterface] not found variant [$sVariant]");

				$sClassDerivative = $sKey;

				$sVariant = null;
				if(count($aVariants))
					$sVariant = array_shift($aVariants);
			}
			else
				$sClassDerivative = static::$m_mapInherits[$sClassDerivative];
		}

		if($sVariant)
			static::error("derivative [$sClassDerivative] of interface [$sInterface] not found variants");

		return $sClassDerivative;
	}

	//************************************************************************

	//! Возвращает самый базовый класс класса
	static protected function getInterface($sClass)
	{
		$sInterface = $sClass;
		$found = true;
		while($found)
		{
			$found = false;
			foreach(static::$m_mapInherits as $sBase => $derivative)
			{
				if(is_string($derivative))
				{
					if($derivative == $sInterface)
					{
						$sInterface = $sBase;
						$found = true;
					}
				}
				else
				{
					foreach($derivative as $sDerivative => $sVariant)
					{
						if($sDerivative == $sInterface)
						{
							$sInterface = $sDerivative;
							$found = true;
						}
					}
				}
			}
		}

		return $sInterface;
	}

	//************************************************************************

	/** Возвращает тип (из констант ExCore::INTERFACE_) интерфейса sInterface
		@note Если интерфейса не существует, тогда будет произведена попытка загрузки файла по имени интерфейса, а затем по имени класса (замена первого символа на C)
	*/
	static protected function getInterfaceType($sInterface)
	{
		if(!array_key_exists($sInterface, static::$m_mapInterfaces))
		{
			spl_autoload_call($sInterface);
			$sClass = $sInterface;
			$sClass[0] = 'C';
			spl_autoload_call($sClass);

			if(!array_key_exists($sInterface, static::$m_mapInterfaces))
				static::error("interface [$sInterface] not registered");
		}

		return static::$m_mapInterfaces[$sInterface];
	}

	//########################################################################

	/** Запись в лог
		@param sText записываемый текст
		@param iDepth глубина трассировки стека
	*/
	static protected function log($sText, $iDepth = 0)
	{
		$aCallInfo = debug_backtrace(0, $iDepth+2);
		$iKey = $iDepth + 1;
		static::$m_aLog[] = "LOG ExCore::".$aCallInfo[$iKey]["function"] . ", " . $aCallInfo[$iKey]["file"].":".$aCallInfo[$iKey]["line"]." - $sText\n";
	}

	//************************************************************************

	//! Генерация ошибки
	static protected function error($sText)
	{
		/*
		$aCallInfo = debug_backtrace();
		static::error(print_r($aCallInfo, true));
		*/

		header("Content-type: text/plain");
		$aCallInfo = debug_backtrace(0, 10);
		echo "ERROR: ExCore::".$aCallInfo[1]["function"] . ", " . $aCallInfo[1]["file"].":".$aCallInfo[1]["line"]." - $sText\n";
		for($i=2, $il=count($aCallInfo); $i<$il; ++$i)
			echo "\t".(array_key_exists("class", $aCallInfo[$i]) ? $aCallInfo[$i]["class"] : "")."::".$aCallInfo[$i]["function"]." - ".$aCallInfo[$i]["file"].":".$aCallInfo[$i]["line"]."\n";
		exit();
	}

	//########################################################################
	//########################################################################
	//########################################################################

	/** Ассоциативный массив обьектов где:
		* ключ - ИмяКласса/Вариант/ИмяОбьекта, Вариант и ИмяОбьекта могут быть пустыми
		* значения - обьекты
	*/
	static protected $m_mapObjects = [];

	/** Ассоциативный массив наследования где:
		* ключ - имя базового класса
		* значение - имя производного класса, или массив вариантов где:
		** ключ - имя производного класса
		** значение - имя варианта
	*/
	static protected $m_mapInherits = [];

	/** Ассоциативный массив зарегистрированных интерфейсов где:
		* ключ - имя интерфейса
		* значение - тип интерфейса из констант ExCore::INTERFACE_
	*/
	static protected $m_mapInterfaces = [];

	//! Была ли проведена инициализация
	static protected $m_isInit = false;

	//! Лог
	static protected $m_aLog = [];

	static protected $m_fTime = 0;

	static protected $m_isRelease = false;
};
