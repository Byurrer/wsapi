<?php

define("DB_QUERY_RETURN_STATUS", 0);	//!< возвращать статус ответа
define("DB_QUERY_RETURN_ONE", 1);		//!< возвращать только нулевой обьект
define("DB_QUERY_RETURN_ALL", 2);		//!< возвращать все обьекты в линейном массиве

//##########################################################################

//! класс для работы с базой данных
interface IDataBase extends IBaseObject
{
	public function connect($sHost, $sDBname, $sUser, $sPassword, $sCharset="utf8");
	
	//! возвращает массив осуществленных запросов (за всю жизнь экземпляра класса)
	public function getQueries();

	public function getQueriesDetail();
	
	//! возвращает количество запросов (за всю жизнь экземпляра класса)
	public function getCountQueries();
	
	//! возвращает строку последнего запроса
	public function getLast();
	
	//! установлено ли соединение с бд?
	public function isConnect();

	//! возвращает код ошибки (последней)
	public function getErrorCode();

	//! возвращает текст ошибки (последней)
	public function getErrorText();

	//! возвращает строку трассировки стека после ошибки (последней)
	public function getStackTrace();

	//! возвращает последний вставленный id базы данных
	public function getLastInsertId();

	public function report($sError);

	//**********************************************************************
	
	/** запрос 
		@param oQuery обьект запроса #IQuery
		@param iRetArr как возвращать ответ из дефайнов DB_QUERY_RETURN_
		@param isFetchPreTableName добавлять ли название таблицы к имени ключа, название таблицы от названия ключа отделяется десятичной запятой
		@return зависит от iRetArr, если iRetArr != DB_QUERY_RETURN_STATUS и запрос завершился провалом или нет обьектов, тогда null
	*/
	public function query($oQuery, $iRetArr=DB_QUERY_RETURN_STATUS, $isFetchPreTableName=false);
};

ExCore::regInterface("IDataBase", ExCore::INTERFACE_MIXED);
