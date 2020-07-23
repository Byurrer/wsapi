<?php

/** Интерфейс ответа на запрос
	@note может использоваться для отправки ответа на входящий запрос и получения ответа на исходящий
*/
interface IResponse extends IPropContainer
{
	/** добавить данные к ответу
		@param var имя переменной либо линейный массив со вложенными именами
		@param data значение ключа
		@note кроме существующих ключей можно создавать новые
		@note если var = ["body", "status"] тогда в переменной body будет создан ключ status если не было, иначе произойдет добавление данных к значению ключа, если переменная body существовала и не была массивом, тогда она будет заменена на массив
	*/
	public function add($var, $data);

	//! редирект
	public function redirect($sURL, $iCode=301);

	//! отправка заголовков
	public function sendHeaders();

	//! отправка тела запроса
	public function sendBody();

	//! отправка заголовков и тела (sendHeaders и sendBody)
	public function send();

	//! обработчик для content-type исходящего (out) ответа
	public static function addOutCTypeHandler($ctype, $fnHandler);
};

ExCore::regInterface("IResponse", ExCore::INTERFACE_MIXED);
