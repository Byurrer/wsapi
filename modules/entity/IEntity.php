<?php

interface IEntity extends IBaseObject
{
	//! возвращает имя таблицы (в БД) сущности
	public static function getTableName();

	//************************************************************************

	/*! существует ли свойство класса
		@param sProp название свойства
		@param canOnlyPublic нужно ли проверять только среди публичных
		@return true/false
	*/
	public static function existsProp($sProp, $canOnlyPublic=false);

	//************************************************************************

	/*! добавить обработчик для преобразования значений при передачи в/из БД
		@param type строковое предаствление типа
		@param fnInDB функция преобразования в БД
		@param fnOutDB функция преобразования из БД
		@note функции вида function(&$data) { }
	*/
	public static function addCastHandler($type, $fnInDB, $fnOutDB);

	//########################################################################
	// CRUD
	//########################################################################

	/*! создание обьекта сущности
		@param aData ассоциативный массив данных, где ключи свойства класса
		@note из aData берет только определенные данные для каждой сущности
	*/
	public function create($aData);

	//************************************************************************

	/*! инициализация объекта из массива 
		@param aData ассоциативный массив данных, где ключи свойства класса
		@param isFromDB данные из бд?
	*/
	public function fromArray($aData, $isFromDB = true);

	//************************************************************************

	/*! обновление данных объекта
		@param aData ассоциативный массив, где ключи свойства класса
		@param useOnlyPublic устанавливать только публичные данные
		@param useOnlySet устанавливать только то что разрешено изменять
	*/
	public function updateData($aData, $useOnlyPublic=false, $useOnlySet=false);

	//************************************************************************

	/*! загрузка из бд
		@param aWhere ассоциативный массив с данными для идентиикации, например ["id" => 5]
		@return возвращает объект класса либо null
	*/
	public function load($aWhere);

	//************************************************************************

	/*! сохранение данных сущности
		@note метод сам определяет когда нужно insert, а когда update
	*/
	public function save();

	//************************************************************************

	//! удаление объекта из бд и обнуление данных объекта
	public function delete();

	//************************************************************************

	//! загружен ли обьект, true - загружен, false - создан
	public function isLoaded();

	//########################################################################
	// СВОЙСТВА ОБЪЕКТА
	//########################################################################

	/*! установка данных, с проверками на возможность записи
		@param sKey имя ключа (переменной)
		@param sValue значение 
	*/
	public function set($sKey, $sValue);

	/*! метод для принудительной установки данных, без проверок
		@param sKey имя ключа (переменной)
		@param sValue значение 
	*/
	public function _set($sKey, $sValue);

	//************************************************************************

	/*! прибавление к переменной (с проверками на возможность записи)
		@param sKey имя ключа (переменной)
		@param sAddValue прибавляемое значение 
	*/
	public function add($sKey, $sAddValue);

	//! #add без проверок
	public function _add($sKey, $sAddValue);

	//************************************************************************

	/*! вычитание из переменной (с проверками на возможность записи)
		@param sKey имя ключа (переменной)
		@param sSubstractValue прибавляемое значение 
	*/
	public function substract($sKey, $sSubstractValue);

	//! #substract без проверок
	public function _substract($sKey, $sSubstractValue);

	//************************************************************************

	/*! возвращает значение переменной (без проверок прав доступа)
		@param sKey имя ключа (переменной)
	*/
	public function get($sKey);

	//************************************************************************

	/*! возвращает объект как массив
		@param canCheckPublic нужно ли проверять данные на публичность, если да то не публичные данные не попадут в итоговый массив
	*/
	public function asArray($canCheckPublic=true);

	//########################################################################
	// ПРЕОБРАЗОВАНИЕ СВОЙСТВ ОБЪЕКТА
	//########################################################################

	/*! преобразование свойств сущности в ранее определенные типы, т.к. бд возвращает строковые значения, для передачи дальше
		@param aProps адрес массива свойств (или массив массивов свойств), значения свойств будут перезаписаны
		@return aProps
	*/
	public static function castOut(&$aProps);

	//************************************************************************

	/*! преобразование типов свойств сущности для записи в бд
		@doc #castOut
	*/
	public static function castIn(&$aProps);

	//########################################################################
	// ДОПОЛНИТЕЛЬНО
	//########################################################################

	//! возвращает meta сущность текущей сущности 
	public function getMeta();
};

ExCore::regInterface("IEntity", ExCore::INTERFACE_MULTI);