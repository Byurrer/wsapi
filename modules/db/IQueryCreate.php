<?php

/** интерфейс Create запроса (insert)
*/
interface IQueryCreate extends IQuery
{
	/** установка названия столбцов в таблице
		@param aCols линейный массив имен столбцов
	*/
	public function setColumns($aCols);

	//************************************************************************

	/** добавление строки на запись
		@param aString массив значений для каждого столбца указанного в #setColumns
	*/
	public function addString($aString);

	//************************************************************************

	/** установить обьект (ассоциативный массив) для записи
		@param aData ассоциативный массив где ключи это столбцы, занчения данные для строки
	*/
	public function setData($aData);
}

ExCore::regInterface("IQueryCreate", ExCore::INTERFACE_MULTI);
