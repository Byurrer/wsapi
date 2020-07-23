<?php

/** сырой запрос, SQL с плейсхолдерами
	@note внутренняя реализация не должна проверять корректность вводимых данных
*/
interface IQueryRaw extends IQuery
{
	//! установить sql запрос
	public function setSQL($sSQL);

	//! привязка данных для плейсхолдера, где sKey имя плейсхолдера, а sValue значение
	public function bindData($sKey, $sValue);
}

ExCore::regInterface("IQueryRaw", ExCore::INTERFACE_MULTI);
