<?php

/** Контейнер свойств
	@note Свойства могут быть многоуровневые, в методах set/get/exists/remove/ параметр var может быть строкой (именем свойства) или линейным массивом с последовательным доступом к свойству. Например ["body", "status"]
*/
interface IPropContainer extends IBaseObject
{
	//! Установка значения переменной, если не было то создаст
	public function set($var, $value);

	//! Возвращает значение переменной, если не было вернет null
	public function get($var);

	//! Существует ли переменная с именем (true/false)
	public function exists($var);

	/** Удаляет переменную по имени
		@param var имя удаляемой переменной, если null тогда удаляет все переменные
	*/
	public function remove($var=null);

	//! Возвращает массив где в ключах имена переменных, а в значения значения
	public function asArray();
};

ExCore::regInterface("IPropContainer", ExCore::INTERFACE_MULTI);

/*class_alias("IPropContainer", "IError");
ExCore::regInterface("IError", ExCore::INTERFACE_MULTI);*/
