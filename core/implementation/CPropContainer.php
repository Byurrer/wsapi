<?php

//##########################################################################

/* Реализация интерфейса IPropContainer
*/
class CPropContainer implements IPropContainer
{
	public function __construct()
	{
		$this->init();
	}

	//************************************************************************

	public function init($aData=null)
	{
		if(!$aData)
			return;

		foreach($aData as $key => $value)
			$this->m_aProps[$key] = $value;
	}

	public function release() {}

	//########################################################################

	public function set($var, $value)
	{
		$var = &$this->__getd($var);
		$var = $value;
		
	}

	//************************************************************************

	public function get($var)
	{
		$var = $this->__getd($var);
		return $var;
	}

	//************************************************************************

	public function exists($var)
	{
		$aKeys = $aKeys = $this->__getvar($var);
		$aArr = &$this->m_aProps;
		foreach($aKeys as $sKey)
		{
			if(!is_array($aArr) || !array_key_exists($sKey, $aArr))
				return false;

			$aArr = &$aArr[$sKey];
		}

		return true;
	}

	//************************************************************************

	public function remove($var=null)
	{
		if($var === null)
		{
			$this->m_aProps = [];
			return;
		}

		$aKeys = $aKeys = $this->__getvar($var);
		$aArr = &$this->m_aProps;
		for($i=0, $il=count($aKeys); $i<$il; ++$i)
		{
			$sKey = $aKeys[$i];
			if(!is_array($aArr) || !array_key_exists($sKey, $aArr))
				return;

			if($i == $il-1)
			{
				unset($aArr[$sKey]);
				return;
			}

			$aArr = &$aArr[$sKey];
		}
	}

	//########################################################################

	public function asArray()
	{
		return $this->m_aProps;
	}

	//########################################################################
	//########################################################################
	//########################################################################

	/** Возвращает ссылку значения ключа
		@param var линейный массив вложений
		@param needCreate надо ли создавать данные если их не было
		@return если вложения не существовала и needCreate == false, тогда вернет null, иначе вернет ссылку на значение
	*/
	protected function &__getd($var)
	{
		$aKeys = $this->__getvar($var);
		$aArr = &$this->m_aProps;
		foreach($aKeys as $sKey)
		{
			if(!is_array($aArr))
				$aArr = [];

			if(!array_key_exists($sKey, $aArr))
				$aArr[$sKey] = null;

			$aArr = &$aArr[$sKey];
		}

		return $aArr;
	}

	//************************************************************************

	/** Возвращает линейный массив (последовательного доступа к свойству)
		@param var массив (вернетэтот же массив) или строка
	*/
	protected function __getvar($var)
	{
		if(is_array($var))
			return $var;
		else
			return [$var];
	}

	//########################################################################

	//! массив свойств
	protected $m_aProps = [];
};

ExCore::regClass("CPropContainer");
