<?php

/** интерфейс проверки данных входящего запроса
	ошибки записывает в текущий IResponse
	методы #IRequestChecker::logError и #IRequestChecker::logErrorArg надо переопределять в конечном использовании
*/
interface IRequestChecker extends IBaseObject
{
	/** Проверка типа текущего запроса
		@param sTypeRequest тип запроса (get/post), регистронезависимое сравнение
		@param canReport надо ли сообщать о несоответсвии
		@return true/false
	*/
	public function checkType($sTypeRequest, $canReport=true);

	//************************************************************************

	/** Существует ли ключ sKey среди ключей хранилища sTypeStorage
		@param sTypeStorage тип хранилища (get/post), регистронезависимое сравнение
		@param sKey ключ массива
		@return true/false
	*/
	public function existsData($sTypeStorage, $sKey);

	//************************************************************************

	/** Проверка содержимого ключа sKey в хранилище sTypeStorage
		@param sTypeStorage тип хранилища (get/post), регистронезависимое сравнение
		@param sKey проверяемый ключ
		@param sTypeArg ожидаемый тип ключа
		@param canReport надо ли сообщать об ошибке
		@return true/false
	*/
	public function checkData($sTypeStorage, $sKey, $sTypeArg, $canReport=true);

	//************************************************************************

	/** Проверка содержимого ключа sKey в хранилище sTypeStorage (только если он есть)
		@param sTypeStorage тип хранилища (get/post), регистронезависимое сравнение
		@param sKey проверяемый ключ
		@param sTypeArg ожидаемый тип ключа
		@return true/false
	*/
	public function checkExistsData($sTypeStorage, $sKey, $sTypeArg);

	//************************************************************************

	/** Проверка ключей aKeys (есть ли хотя бы один) в хранилище sTypeStorage
		@param sTypeStorage тип хранилища (get/post), регистронезависимое сравнение
		@param aKeys ассоциативный массив ключ => ожидаемый тип (аналогично #checkData)
		@param canReport надо ли сообщать об ошибке
		@return true/false
	*/
	public function checkDataLeast($sTypeStorage, $aKeys, $canReport=true);

	//************************************************************************

	/** Извлечение данных ключа sKey из хранилища sTypeStorage
		@param sType тип хранилища (get/post), регистронезависимое сравнение
		@param sKey проверяемый ключ
		@param default значение по умолчанию, если данных в массиве нет
		@return если ключ в массиве есть то значение из хранилища, иначе default
	*/
	public function getData($sTypeStorage, $sKey, $default=null);

	//************************************************************************

	/** Удаление ключ sKey из хранилища sTypeStorage
		@param sTypeStorage тип хранилища (get/post), регистронезависимое сравнение
		@param sKey проверяемый ключ
	*/
	public function unsetData($sTypeStorage, $sKey);

	//########################################################################

	/** добавить функция для преобразования данных
		@param sType тип который надо преобразовать
		@param fnCast функция преобразования function($data){return $data;}
	*/
	public static function addCast($sType, $fnCast);

	/** добавить функцию для проверки типа данных
		@param type тип для проверки, строка или массив строк
		@param fnChecker функция проверки соответсвия типу function($data){return true/false;}
		@param sError текст ошибки 
	*/
	public static function addCheckerTypeData($type, $fnChecker, $sError);

	/** запись общей ошибки проверки
		@note надо переопределить в конечном использовании
	*/
	public function logError($sError);

	/** запись ошибки аргумента
		@note надо переопределить в конечном использовании
		@param sArg имя параметра
		@param sError данные ошибки, подразумевается строка, но поступать может что угодно
	*/
	public function logErrorArg($sArg, $sError);
};

ExCore::regInterface("IRequestChecker", ExCore::INTERFACE_SINGLE);
