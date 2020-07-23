<?php

interface ILog extends IBaseObject
{
	//! файл на каждый день
	const TYPE_PER_DAY = 0;

	//! файл на каждый час
	const TYPE_PER_HOUR = 1;

	//########################################################################

	//! установить разрешение логирования
	public function setEnable($isEnable);

	//! разрешено ли логирование
	public function getEnable();

	/** установить текущий лог
		@note если не добавлен такой лог тогда trigger_error
	*/
	public function setCurrLog($sName);

	//! получить текущий лог
	public function getCurrLog();

	/** добавить лог
		@param sName название лога
		@param sDir директория кудабудут сохранятся логи
		@param iType тип лога из ILog::TYPE_PER_
	*/
	public function addLog($sName, $sDir, $iType);

	//! вывод дебаг информации в буфер
	public function log($sStr);

	//! вывод дебаг информации в буфер с трассировкой стека
	public function stacktrace($sStr);

	//! возвращает текущую строку с дебаг инфой
	public function getLog();

	//! сохранить дебаг информацию в файл
	public function release();
};

ExCore::regInterface("ILog", ExCore::INTERFACE_SINGLE);
