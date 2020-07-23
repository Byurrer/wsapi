<?php

interface IRequest extends IPropContainer
{
	public function send();
};

ExCore::regInterface("IRequest", ExCore::INTERFACE_MIXED);
