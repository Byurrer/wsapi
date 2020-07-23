<?php

include_once("simple-wiki-markup/simple-wiki-markup.php");

/* пример дефайнов 
	@имя значение

В дальнейшем в документации @имя будет заменено на значение
*/
/**defines 
	@api_url https://api.uppleseen.com
	@returnOK "status": "successs"
	@returnERROR {"status": "failed", "value": ""}
*/

/* В документации могут быть глобальные секции,которые будут идти первыми на вывод в списке документации
	Пример:
/**gsection имя_секции Заголовок секции
текстсекции

*/

/** Пример документации запроса
	@type POST
	@paramGET имя_get_параметра описание параметра
	@paramPOST имя_post_параметра описание параметра
	@returnOK описание удачного ответа
	@returnERR описание неудачного ответа
	@sample пример запроса
*/
/*__get("IRouter")->addHandler(
	["company", "create"],	//из этого будет сформирован url
	"Ctl_CompanyCreate",		//контроллер тоже будет считан
	[
		"type" => "post",
		"get" => ["token" => "string"],
		"post" => ["name" => "string"],
		"admin" => true
	]
);*/

//##########################################################################

function markup_code($sString)
{
	return swm::codeEnd(swm::codeStart($sString));
}

//##########################################################################

/** Класс сборки документации по контроллерам
*/
class CAutoDoc
{
	/** Генерация документации
		@param sPath путь до директории откуда брать файлы для сборки документации
		@return ["docs" => массив с документацией, "sections" => документация секций]
	*/
	public function genDoc($sPath)
	{
		$aDocs = [];
		$aControllersDir = scandir($sPath);
		$aDoc2 = $this->genDocFile($sPath."ctl.php");
		foreach($aControllersDir as $key => $value)
		{
			if($value == "." || $value == ".." || $value[0] == '-')
				continue;

			$aDoc2 = $this->genDocFile($sPath.$value);
			if($aDoc2)
				$aDocs[$value] = $aDoc2;
		}

		//callback функция сортировки массива 
		uasort($aDocs, function(&$a, &$b){
			return ($a["header"]["order"] >= $b["header"]["order"]);
		});

		//обработка дефайнов
		array_walk_recursive($aDocs, function(&$item, $key){
			if(is_array($item))
				return;

			foreach($this->m_aDefines as $key => $value)
				$item = str_ireplace("%".$key."%", $value, $item);
		});

		//расстановка ссылок на секции
		array_walk_recursive($aDocs, function(&$item, $key){
			if(is_array($item))
				return;

			foreach($this->m_aGlobalSections as $key => $value)
			{
				$sLink = '<a href="#'.$key.'">'.$value["header"].'</a>';
				$item = str_ireplace("#".$key."#", $sLink, $item);
			}
		});

		return ["docs" => $aDocs, "sections" => $this->m_aGlobalSections];
	}

	//************************************************************************

	/** Генерация документации из файла
		@param sFile путь до файла
		@return массив документации
	*/
	public function genDocFile($sFile)
	{
		$aGenDoc = [];
		$sColor = sprintf('#%06X', crc32($sFile) & 0xFFFFFF);
		$sText = file_get_contents($sFile);
		$sText = str_replace("\r\n", "\n", $sText);

		$aHeader = $this->parseHeader($sFile, $sText);
		$this->parseDefines($sText);
		$aSections = $this->parseSections($sText);
		$aDocs = $this->parseCode($sText);

		//если документации нет, тогда null
		if(count($aDocs) == 0)
			return null;

		foreach($aDocs as $value)
		{
			$data = $this->parseComment($value["comment"]);
			
			$aControllers = [];
			$aControllers["desc"] = $data['comment'][0];
			$aControllers["type"] = (array_key_exists('@type', $data) ? $data['@type'][0] : '');
			
			
			$aParams = [];
			foreach($data as $k => $a)
			{
				if(substr($k, 0, 6) == '@param')
				{
					foreach($a as $v)
					{
						$v = explode(' ', str_replace("\n", ' ', trim(substr($k, 6).' '.$v)));
						$kk = $v[0];
						$k2 = $v[1];
						unset($v[0]);
						unset($v[1]);
						$v = implode(' ', $v);
						$aParams[] = [
							"type" => $kk, 
							"arg" => $k2, 
							"desc" => $v,
						];
					}
				}
			}
			$aControllers["params"] = $aParams;
			
			$aReturn = [];
			foreach($data as $k => $a)
			{
				if(substr($k, 0, 7) == '@return')
				{
					foreach($a as $v)
					{
						$v = explode(' ', trim(substr($k, 7).' '.$v));
						$kk = $v[0];
						unset($v[0]);
						$v = implode(' ', $v);
						$aReturn[] = [
							"type" => $kk, 
							"desc" => markup_code('[code lang="js"]'.$v.'[/code]'),
						];
					}
				}
			}
			$aControllers["return"] = $aReturn;
			$aControllers["controller"] = $value["ctl"];
			$aControllers["url"] = [$value["url"], $value["line"]];
			$aControllers["file"] = pathinfo($sFile, PATHINFO_FILENAME);
			$aControllers["sample"] = isset($data['@sample']) ? markup_code(nl2br(implode("\n",$data['@sample']))) : '';
			$aControllers["note"] = isset($data['@note']) ? nl2br(implode("\n", $data['@note'])) : '';
			$aControllers["todo"] = isset($data['@todo']) ? $data['@todo'] : [];
			$aGenDoc[] = $aControllers;
		}

		return([
			'docs' => $aGenDoc,
			'color' => $sColor,
			"header" => $aHeader,
			"sections" => $aSections
		]);
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	protected $m_aDefines = [];				//!< дефайны
	protected $m_aGlobalSections = [];//!< секции

	//########################################################################

	/** Парсинг кода
		@param sCode текст кода
		@return [
			comment => текст комментария,
			line => строка где addHandler
			url => относительный url
			ctl => контроллер (функция, класс, класс-метод)
		]
	*/
	protected function parseCode($sCode)
	{
		/* берем все токены token_get_all и проходимся по ним извлекая нужное*/

		$aOut = [];
		$aTokens = token_get_all($sCode);

		$aCurrComment = [];
		$isInHandler = false;
		$foundUrl = false;
		$foundCtl = false;
		$foundType = false;

		for($i = 0, $l = count($aTokens); $i < $l; ++$i)
		{
			$aToken = $aTokens[$i];

			switch($aToken[0])
			{
			case T_DOC_COMMENT:
			{
				//если массив заполнен, тогда записываем его в aOut и обнуляем данные
				if(count($aCurrComment) > 0 && array_key_exists("comment", $aCurrComment))
				{
					$aOut[] = $aCurrComment;
					$isInHandler = false;
					$foundUrl = false;
					$foundCtl = false;
					$foundType = false;
				}
				$aCurrComment = ["comment" => substr(substr($aToken[1], 3), 0, -2), "line" => $aToken[2]];

				break;
			}

			case T_STRING:
			{
				//если есть какие-то данные по комментарию и еще не вошли в обработчик и это функция addHandler
				if(count($aCurrComment) > 0 && !$isInHandler && $aToken[1] == "addHandler")
				{
					$aCurrComment["line"] = $aToken[2];
					$isInHandler = true;
				}
				
				break;
			}

			case '[': 
			{
				//если внутри добавления обработчика
				if($isInHandler)
				{
					//echo "isInHandler";
					//если url не найден тогда ищем
					if(!$foundUrl)
					{
						$aURL = [];
						while($aTokens[$i] != "]")
						{
							$aToken = $aTokens[$i];
							if($aToken[0] == T_CONSTANT_ENCAPSED_STRING)
								$aURL[] = str_replace("\"", "", str_replace("'", "", $aToken[1]));

							++$i;
						}
						//print_r($aURL);
						$aCurrComment["url"] = implode("/", $aURL);
						$foundUrl = true;

						//если не найден контроллер тогда ищем
						if(!$foundCtl)
						{
							while($aTokens[$i][0] != T_CONSTANT_ENCAPSED_STRING)
								++$i;
							
							$aCurrComment["ctl"] = str_replace("\"", "", str_replace("'", "", $aTokens[$i][1]));;
							$foundCtl = true;
						}
					}
				}

				break;
			}

			case T_WHITESPACE:
			case T_CONSTANT_ENCAPSED_STRING:
			case ']':
			default:
				break;
			}
		}

		//если есть данные по последнему комментарию тогда добавляем в массив
		if(count($aCurrComment) > 0 && array_key_exists("comment", $aCurrComment))
			$aOut[] = $aCurrComment;

		return $aOut;
	}

	//************************************************************************

	/** Парсинг заголовка файла
		@param sFile путь до файла
		@param sText текст файла
		@return [
			category => название категории или имя файла (если не задана категория),
			order => порядок сортировки (вывода на странице)
		]
	*/
	protected function parseHeader($sFile, $sText)
	{
		$aHeader = [
			"category" => pathinfo($sFile, PATHINFO_BASENAME),
			"order" => 0
		];

		if(preg_match("/\/*\*header(.*?)\*\/\\n+/ims", $sText, $aMatches))
		{
			if(preg_match("/@category\s+(.[^@]*)/ims", $aMatches[1], $aMatches2))
				$aHeader["category"] = $aMatches2[1];

			if(preg_match("/@order\s+(\d+)/ims", $aMatches[1], $aMatches2))
				$aHeader["order"] = intval($aMatches2[1]);
		}

		return $aHeader;
	}

	//************************************************************************

	/** Парсинг комментария
		@param sStr текст комментария с симовалми комментария
		@return [
			@имя_ключа => значнеие (может быть многострочным)
		]
	*/
	protected function parseComment($sStr)
	{
		$aOut = [];
		
		$sKey = 'comment';
		$aText = [];
		
		$aLines = explode("\n", $sStr);
		foreach($aLines as $sLine)
		{
			$sTrimmed = trim($sLine);
			if(strlen($sTrimmed) > 1 && $sTrimmed[0] == '@')
			{
				if(!isset($aOut[$sKey]))
				{
					$aOut[$sKey] = [];
				}
				$aOut[$sKey][] = implode("\n", $aText);
				$sKey = explode(' ', str_replace(["\n", "\t"], ' ', $sTrimmed))[0];
				$aText = [trim(substr($sTrimmed, strlen($sKey)))];
			}
			else
			{
				$aText[] = $sLine;
			}
		}
		if(!isset($aOut[$sKey]))
		{
			$aOut[$sKey] = [];
		}
		$aOut[$sKey][] = implode("\n", $aText);
		
		return($aOut);
	}

	//************************************************************************

	/* Парсинг дефайнов
		@param sText текст файла
	*/
	protected function parseDefines($sText)
	{
		if(preg_match("/\/*\*defines(.*?)\*\/\\n+/ims", $sText, $aMatches))
		{
			preg_match_all('/@(\w+)\s(.*?)\n/ms', $aMatches[1], $aMatches, PREG_SET_ORDER);

			foreach($aMatches as $key => $value)
				$this->m_aDefines[$value[1]] = $value[2];
		}
	}

	//************************************************************************

	/** Парсинг секций (локальных и глобальных)
		@param sText текст файла
		@return локльные секции [
			link => ссылка
			header => заголовок
			text => текст секции
		]
	*/
	protected function parseSections($sText)
	{
		$aSections = [];
		$iResCount = preg_match_all("/\/*\*section\s+(\w+)\s+(.*?)\n+(.*?)\*\/\n/ms", $sText, $aMatches, PREG_SET_ORDER);
		if($iResCount > 0)
		{
			foreach($aMatches as $key => $value)
			{
				$value[3] = swm::markup($value[3]);
				$aSections[] = [
					"link" => $value[1],
					"header" => $value[2],
					"text" => $value[3]
				];
			}
		}

		$iResCount = preg_match_all("/\/*\*gsection\s+(\w+)\s+(.*?)\n+(.*?)\*\/\n/ms", $sText, $aMatches, PREG_SET_ORDER);
		if($iResCount > 0)
		{
			foreach($aMatches as $key => $value)
			{
				$value[3] = swm::markup($value[3]);
				$this->m_aGlobalSections[$value[1]] = [
					"header" => $value[2],
					"text" => $value[3]
				];
			}
		}

		return $aSections;
	}
};

//##########################################################################
