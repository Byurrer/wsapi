<?php

/** интерфейс Read запроса (select)
*/
interface IQueryRead extends IQueryRUD 
{
	
}

ExCore::regInterface("IQueryRead", ExCore::INTERFACE_MULTI);

