<?php

/** Интерфейс системы сущности
*/
interface IEntitySystem extends IBaseObject
{
	//! регистрация класса сущности интерфейс #IEntity
	public function regEntity($sClassEntity);

	//! регистрация класса связи интерфейс #IBinding
	public function regBinding($sClassBinding);

	//########################################################################

	/** возвращает как массив
		@param data может быть обьектом #IEntity или массивом обьектов #IEntity
		@note к кажджому обьекту будет применен метод asArray()
	*/
	public function asArray($data);

	/** возвращает обьект из массива
		@param sEntity имя класса сущности или таблицы
		@param aTarget ассоциативный массив с данными обьекта или массив ассоциативных массивов
		@return is_assoc(aTarget) => object, is_array(aTarget) и внутри ассоциативные массивы => массив обьектов, иначе null
	*/
	public function asObject($sEntity, $aTarget);

	//########################################################################

	/** создание новой сущности
		@param sEntity имя класса сущности или таблицы
		@param aData ассоциативный массив свойств обьекта
		@param canSave нужно ли сохранять обьект в бд
		@return созданный обьект
	*/
	public function newEntity($sEntity, $aData, $canSave=false);

	/** загрузка обьекта сущности
		@param sEntity имя класса сущности или таблицы
		@param aWhere условие-массив с ключами-именами столбцов и значениями - свойствами обьекта
		@param sRelated строка подгружаемых сущностей перечисленние через запятую, вложенность (неограниченно) через точку
		@param canReport нужно ли сообщать в случае неудачной загрузки
		@return если есть sRelated тогда вернет [target => , related => ] (подробнее #loadEntityList), иначе загруженный обьект, или null в случае провала
	*/
	public function loadEntity($sEntity, $aWhere, $sRelated=null, $canReport=true);

	/** загрузка обьекта сущности как массив (ассоциативный)
		@doc #loadEntity
	*/
	public function loadEntityAsArr($sEntity, $aWhere, $sRelated=null, $canReport=true);

	//########################################################################

	/** загрузка списка сущностей (как обьекты или массивы)
		@param sEntity имя класса сущности или таблицы
		@param aWhere условие-массив с ключами-именами столбцов и значениями - свойствами обьекта
		//@param aJoins массивы подгружаемых сущностей, в ключе имя таблицы/класса в значенииимя ключа для идентификации sEntity
		@param sRelated строка подгружаемых сущностей перечисленние через запятую, вложенность (неограниченно) через точку
		@param iStart смещение
		@param iCount количество
		@param sSortBy поле сортировки
		@param sSortDir направление сортировки ASC/DESC
		@param asObject преобразовывать ли в обьект
		@return aJoins === null => линейный массив, иначе [target => массив целевых обьектов/массивов, related => массив подгружаемых сущностей, где ключ имя таблицы, а значение массивы/обьекты]
	*/
	public function loadEntityList($sEntity, $aWhere, $sRelated=null, $iStart=0, $iCount=0, $sSortBy="id", $sSortDir="DESC", $asObject=true);

	/** загрузка списка сущностей как массивы
		@doc #loadEntityList
	*/
	public function loadEntityListArr($sEntity, $aWhere, $sRelated=null, $iStart=0, $iCount=0, $sSortBy="id", $sSortDir="DESC");

	/** загрузка списка значений ключа sKey
		@doc #loadEntityList
		@return возвращает линейный массив значений sKey
	*/
	public function loadEntityListKey($sEntity, $sKey, $aWhere, $sRelated=null);

	//########################################################################

	/** агрегатная функция (или несколько), например (count, sum и другие)
		@param sEntity имя класса сущности или таблицы
		@param aFuncKey ассоциативный массив где в ключах название функции, в значении имя столбца из БД
		@param aWhere условие-массив с ключами-именами столбцов и значениями - свойствами обьекта
		@param aJoins массивы подгружаемых сущностей, в ключе имя таблицы/класса в значенииимя ключа для идентификации sEntity
		@return count(aFuncKey) == 1 => значение, иначе ассоциативный массив где ключи имена функций
	*/
	public function aggregate($sEntity, $aFuncKey, $aWhere, $aJoins=null);

	//########################################################################

	/** создание/удаление связей
		@param sBinding имя класса биндинга или таблицы
		@param aDataBasis значения к которым добавляются aDataBasis, массив с ключами - именами столбцов
		@param aDataAdd добавляемые значения к aDataBasis, массив с ключами - именами столбцов
		@param isBind true - нужно установить связь, false - удалить указанные связи
		@param isForce true - устанавливать только эти связи удаля все предыдущие, false - добавить эти связи к существующим
		@note если isForce == true тогда будут удалены все связи с aDataBasis
		@note ключи aDataBasis и aDataAdd в значениях могут содержать линейные массивы данных, например aDataBasis["company" => [1,23,3]]
		@note массивы aDataBasis aDataAdd обьединяются и на основании них выстраивается массив строк аналогичный строкам в итоговой таблице
		@sample Привязать юзеров к магазину: binding("binding_store_user", ["store" => 1], ["user" => [1,2,3]], true, true);
	*/
	public function binding($sBinding, $aDataBasis, $aDataAdd, $isBind=true, $isForce=false);

	/** существует ли связь
		@param sBinding имя класса биндинга или таблицы
		@param aWhere условие - массив с ключами-именами столбцов
		@return true/false
	*/
	public function binded($sBinding, $aWhere);

	/** список связей
		@param sBinding имя класса биндинга или таблицы
		@param aColumns линейный массив выбираемых столбцов или строка с выбираемым столбцом
		@param aWhere условие - массив с ключами-именами столбцов
		@return если is_string(aColumns) - линейный массив со значениями, иначе линейный массив с ассоциативными массивами
	*/
	public function bindedList($sBinding, $aColumns, $aWhere);

	//########################################################################

	/** получить класс сущности
		@param sEntity класс или имя таблицы сущности
		@param canReport нужно ли сообщать об ошибке в случае если не удалось найти
		@return если найдено - класс сущности, иначе null
	*/
	public function getEntClass($sEntity, $canReport=true);

	/** получить имя таблицы сущности
		@doc #getEntClass
	*/
	public function getEntTable($sEntity, $canReport=true);

	//########################################################################

	//! сообщение об ошибке
	public function report($sError);
};

ExCore::regInterface("IEntitySystem", ExCore::INTERFACE_SINGLE);
