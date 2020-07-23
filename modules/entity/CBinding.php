<?php

class CBinding implements IBinding
{
	protected static $m_aEntities = [
		"entity1" => "col1", 
		"entity2" => "col2", 
	];

	//########################################################################

	public function init($aData=null) { }
	public function release() { }

	public static function getTableName()
	{
		return "";
	}
};

ExCore::regClass("CBinding");
