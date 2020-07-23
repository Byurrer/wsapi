<?php

class CPubSub implements IPubSub
{
	public function __construct()
	{
		$this->init();
	}

	public function init($aData=null) {}
	public function release() {}

	//************************************************************************

	public function subscribe($aEvent, $aCond, $handler)
	{
		$sEvent = implode("-", $aEvent);
		__log("PUBSUB: subscribe event '$sEvent'");
		if(!array_key_exists($sEvent, $this->m_aSubs))
			$this->m_aSubs[$sEvent] = [];

		$this->m_aSubs[$sEvent][] = [
			"cond" => $aCond,
			"handler" => $handler
		];
	}

	//************************************************************************

	public function publish($aEvent, $aArgs=null)
	{
		$sEvent = implode("-", $aEvent);
		__log("PUBSUB: publish event '{$sEvent}'");
		if(!array_key_exists($sEvent, $this->m_aSubs))
			return null;

		$aResult = [];

		foreach($this->m_aSubs[$sEvent] as $subscriber)
		{
			$needCall = false;
			if($subscriber["cond"] === null && $aArgs === null)
				$needCall = true;

			if($subscriber["cond"] !== null && $aArgs !== null)
			{
				$needCall = true;
				foreach($subscriber["cond"] as $key => $value)
				{
					if(!array_key_exists($key, $aArgs) || $aArgs[$key] != $value)
					{
						$needCall = false;
						break;
					}
				}
			}

			$handler = $subscriber["handler"];
			if($needCall)
			{
				$sHandler = (is_string($handler) ? $handler : "type: ".gettype($handler));
				__log("PUBSUB: call handler {'$sHandler'} for event '{$sEvent}'");
				$res = $this->call($handler, $aArgs);
				if(is_array($res))
					$aResult = array_merge($aResult, $res);
			}
			else
				__log("PUBSUB: not found handler for event '{$sEvent}'");
		}

		return $aResult;
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	//! 
	protected $m_aSubs = [];

	//########################################################################

	protected function call($handler, $aArgs)
	{
		if(is_callable($handler))
			return call_user_func($handler, $aArgs);
		else
		{
			$aClassMethod = explode("@", $handler);
			$sClass = $aClassMethod[0];
			$sMethod = (count($aClassMethod) == 1 ? "exec" : $aClassMethod[1]);

			$oClassHandler = new $sClass();
			return $oClassHandler->$sMethod($aArgs);
		}
	}
};

ExCore::regClass("CPubSub");
