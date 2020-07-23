<?php

trait TQueryOpLimit
{
	public function limit($iLimit, $iOffset = 0)
	{
		if(is_array($iLimit))
		{
			$this->limit($iLimit[0], $iLimit[1]);
			return;
		}

		__eassert($iLimit >= 0, "Unresolved limit");
		__eassert($iOffset >= 0, "Unresolved offset");

		$this->m_iLimit = $iLimit;
		$this->m_iOffset = $iOffset;
	}

	//************************************************************************

	//! возвращает SQL код WHERE (без WHERE)
	public function getLimitSQL()
	{
		$sLimit = "";
		if($this->m_iLimit > 0 && $this->m_iOffset >= 0)
			$sLimit = " LIMIT " . ($this->m_iOffset > 0 ? $this->m_iOffset."," : "") . " " . $this->m_iLimit;

		return $sLimit;
	}

	//########################################################################

	protected $m_iLimit = 0;	//!< ограничение на количество выбираемых строк
	protected $m_iOffset = 0;	//!< смещение выборки
}
