<?php

/** интерфейс Read запроса (select)
*/
interface IQueryRUD extends IQuery 
{
	/** условия запроса
		@param aWhere массив условий где ключ это левый операнд условия, а значение левый операнд
		@param sOpCond логический оператор для присоединения aWhere к уже существующему (внутри) массиву условий или если нет (внутри) существующего массива условий, тогда это логический оператор для соединения подмассивов массива aWhere, иначе не учитывается

		@note вложенность массивов может быть любая
		@note в ключе может быть задан логический оператор в начале имени ключа до двоеточия, допустимые значения or and
		@note в ключе может быть задан оператор сравнения - в конце имени ключа после двоеточия (по умолчанию =), допустимые значения = != > >= < <= % !% in !in

		@samples:
			$oQuery->where(["id" => [2,1,3,4,5]]); => WHERE id IN(2,1,3,4,5)
			$oQuery->where(["id" => 4, ["qwe" => 15, "or:asd" => 45]], SQL_OR); => WHERE id=4 OR (qwe=15 OR asd = 45)
			$oQuery->where(["val:in" => $oSubQuery]); => WHERE val IN (sql subquery)
	*/
	public function where($aWhere, $sOpCond=SQL_AND);

	//! FOR DEBUG: возвращает where массив сформированный через where метод
	public function getRawWhere();

	//! лимит и смещение
	public function limit($iLimit, $iOffset = 0);

	/** соединение
		@param sTable таблица, с которой происходит соединение
		@param aCond условия соединения
		@param sSide left/right
		@param bOuter использовать outer (true), или inner (false)
	*/
	public function join($sTable, $aCond, $sSide="LEFT", $bOuter=false);

	/* соединения
		@param aJoins ассоциативный массив где ключи это имена таблиц, а значения ассоциативные массивы где ключи:
			cond - условие
			side - left/right (по умолчанию left)
			outer - использовать outer (true), или inner (false)
	*/
	public function joins($aJoins);
}
