<?php

interface IPubSub extends IBaseObject
{
	public function subscribe($aEvent, $aCond, $handler);

	public function publish($aEvent, $aArgs=null);
};

ExCore::regInterface("IPubSub", ExCore::INTERFACE_SINGLE);
