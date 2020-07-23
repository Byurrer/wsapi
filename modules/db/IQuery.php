<?php

/** интерфейс SQL запроса
	@note контейнерные свойства:
		* table - имя таблицы
*/
interface IQuery extends IPropContainer
{
	//! возвращает сформированный SQL запрос для обращения к БД, может содержать плейсхолдеры, которые будут заменены на значения их #getBindData
	public function getSQL();

	//! возвращает привязанные данные к запросу
	public function getBindData();
}
