<?php

//! обработчик
class CHandler
{
	//! массив адресов (следюущих друг за другом) при котором сработает обработчик
	public $m_aQuery;

	//! обработчик (функция или класс)
	public $m_handler;

	//! массив требований для работы обработчика
	public $m_aRequirements;

	//! массив передаваемых данных в обработчик
	public $m_aArgs;
	
	public function __construct($aQuery, $handler, $aRequirements)
	{
		$this->m_aQuery = $aQuery;
		$this->m_handler = $handler;
		$this->m_aRequirements = $aRequirements;
	}
};

//##########################################################################

/** Интерфейса роутера
	@note адрес запрос берется из $_SERVER["REQUEST_URI"]
*/
interface IRouter extends IBaseObject
{
	/** возвращает строку запроса
		@param needGet нужны ли get параметры
	*/
	public function getQuery($needGet=false);

	/** сшивает между собой (через /) элементы запроса, начиная с iStart количеством iCount
		@param iStart позиция старта (в элементах)
		@param iCount количество (в элементах), если 0 то до конца
		@return часть URl строки
	*/
	public function veld($iStart=0, $iCount=0);
	
	/** добавить обработчик 
		@note Для добавления контроллера на главную страницу в $aQuery необходимо записать ["/"]
	*/
	public function addHandler($aQuery, $handler, $aRequirements=null);
	
	//! добавить обработчик, который будет вызываться в случае если ни один другой не подошел
	public function addLostHandler($fnHandler);

	/** Вызывается в exec перед выполнением контроллера
		@return true если можно продолжать, false если произошла ошибка
	*/
	public function preExec($oHandler);

	/** Вызывается в конце exec после основной логики
		@param oHandler обьект обработчика
		@return true/false результат работы exec
	*/
	public function postExec($oHandler);

	//! возвращает обработчик для текущего запроса
	public function getHandler();
	
	/** выполнение обработки запроса (preExec, getHandler, postExec)
		@return IResponse
	*/
	public function exec();
};

ExCore::regInterface("IRouter", ExCore::INTERFACE_SINGLE);
