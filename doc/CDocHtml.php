<?php

/* Класс генерации HTML для документации
*/
class CDocHtml
{
	/** Сборка документации 
		@param aDocs массив документации
		@param aGlobalSection секции
		@return HTML текст
	*/
	public function build($aDocs, $aGlobalSection)
	{
		$sText = file_get_contents("index.html");
		$sText = str_replace("[[title]]", PAGE_TITLE, $sText);
		$sText = str_replace("[[description]]", PAGE_DESC, $sText);

		$sContent = $this->buildTOC($aDocs, $aGlobalSection).$this->buildContent($aDocs, $aGlobalSection);

		$sCurrYear = date("Y");
		$sCopyLink = '<a href="'.COPYRIGHTS_LINK.'" target="_blank">'.COPYRIGHTS_CAPTION.'</a>';
		$sContent .= '<div class="str pad-0 footer flex-iva-center">
      <div class="col-1-1 pad-5px talign-center">
        <div class="cell">
          Copyrights © '.$sCurrYear.' '.$sCopyLink.'
        </div>
      </div>
    </div>';
		
		$sText = str_replace("[[content]]", $sContent, $sText);
		return $sText;
	}

	//########################################################################
	// PROTECTED
	//########################################################################

	//! Сборка оглавления
	protected function buildTOC($aDocs, $aGlobalSection)
	{
		$html = [];
		$html[] = '<div class="cell toc" id="left_pan">';

		$html[] = '<div class="col-1-1" style="background: '.(sprintf('#%06X', crc32("doc.php") & 0xFFFFFF)).'33; padding-left: 10px;justify-content: left;">';
		$html[] = '<a class="hlink" href="#gsection">Введение</a>';
		$html[] = '<ul>';
		foreach($aGlobalSection as $key => $value)
			$html[] = '<li><a href="#'.$key.'">'.$value['header'].'</a></li>';
		$html[] = '</ul></div>';

		foreach($aDocs as $sFile => $aDoc)
		{
			if(!$aDoc['docs'] && !$aDoc['sections'])
				continue;
			
			$html[] = '<div class="col-1-1 block" style="background: '.$aDoc['color'].'33;">';
			$html[] = '<a class="hlink" href="#'.htmlspecialchars($sFile).'">'.htmlspecialchars($aDoc["header"]["category"]).'</a>';
			$html[] = '<ul>';
			foreach($aDoc['sections'] as $key => $value)
				$html[] = '<li><a href="#'.htmlspecialchars($value['link']).'">'.$value['header'].'</a></li>';
			$html[] = '</ul>';
			
			if($aDoc['docs'])
			{
				$html[] = '<div class="col-1-1" style="justify-content: left;">Запросы:</div>';
				$html[] = '<ul>';
				foreach($aDoc['docs'] as $key => $value)
				{
					$html[] = '<li><a href="#'.htmlspecialchars($value['url'][0]).'">'.htmlspecialchars($value['desc']).'</a></li>';
				}
				$html[] = '</ul>';
			}
			$html[] = '</div>';
		}
		$html[] = "</div>";

		return implode("\n", $html);
	}

	//************************************************************************

	//! Сборка контентной части
	protected function buildContent($aDocs, $aGlobalSection)
	{
		$html = [];
		//контент
		$html[] = '<div class="str flex-iva-top doc" id="pan_right">';

		//глобальные секции
		$html[] = '<div class="section" style="background: '.(sprintf('#%06X', crc32("doc.php") & 0xFFFFFF)).'33;">';
		$html[] = '<div class="str pad-10px"><h1><a style="color: #000;" name="gsection" href="#gsection">Введение</a></h1></div>';
		foreach($aGlobalSection as $key => $value)
		{
			$html[] = '<div class="str pad-10px flex-iva-top">';
			$html[] = '<div class="col-1-1 talign-center" style="background: '.(sprintf('#%06X', crc32("doc.php") & 0xFFFFFF)).';">
					<h2><a class="h2link" name="'.$key.'" href="#'.$key.'">'.$value['header'].'</a></h2>
				</div>';
			$html[] = '<div class="cell">'.$value['text'].'</div>';
			$html[] = '</div>';
		}
		$html[] = '</div>';

		//документация по файлам
		foreach($aDocs as $sFile => $aDoc)
		{
			if(!$aDoc['docs'] && !$aDoc['sections'])
				continue;
			
			$html[] = '<div class="section" style="background: '.$aDoc['color'].'33;">';
			$html[] = '<div class="str pad-10px"><h1><a class="h1link" name="'.htmlspecialchars($sFile).'" href="#'.htmlspecialchars($sFile).'">'.htmlspecialchars($aDoc["header"]["category"]).'</a></h1></div>';
			
			//секции
			foreach($aDoc['sections'] as $key => $value)
			{
				$sQ = ['<div class="str pad-10px flex-iva-top">'];
				$sQ[] = '<div class="col-1-1 talign-center" style="background: '.$aDoc['color'].';">
					<h2><a class="h2link" name="'.($value['link']).'" href="#'.($value['link']).'">'.($value['header']).'</a></h2>
				</div>';

				$sQ[] = '<div class="cell">'.$value['text'].'</div>';

				$sQ[] = '</div>';
				$html[] = implode('', $sQ);
			}

			//запросы
			if($aDoc['docs'])
			{
				$html[] = '<div class="str pad-10px flex-iva-top"><h3>Запросы</h3></div>';
				foreach($aDoc['docs'] as $key => $value)
				{
					$sQ = ['<div class="str pad-5px flex-iva-top">'];
					$sQ[] = '<div class="col-1-1 talign-center" style="background: '.$aDoc['color'].';"><h2><a class="h2link" name="'.htmlspecialchars($value['url'][0]).'" href="#'.htmlspecialchars($value['url'][0]).'">'.htmlspecialchars($value['desc']).'</a></h2></div>';
					
					$sQ[] = '<div class="cell talign-right meta"><b>Type: </b>'.$value['type'].' | <b>File: </b>'.$value['file'].':'.$value['url'][1].' | <b>Ctl: </b>'.htmlspecialchars($value['controller']).' | <b>Query:</b> '.$value['url'][0].'</div>';

					$sQ[] = '<div class="col-1-2 flex-iva-top pad-5px query-part">';
					$sQ[] = '<div class="col-1-1 flex-iha-left"><h3>Параметры</h3></div>';
					foreach($value['params'] as $key2 => $value2)
					{
						$sQ[] = '<div class="col-1-3 flex-iha-left arg-name"><span>'.$value2['arg'].' ('.$value2['type'].')</span></div>';
						$sQ[] = '<div class="col-2-3 flex-iha-left arg-desc">'.$value2['desc'].'</div>';
					}
					$sQ[] = '</div>';

					$sQ[] = '<div class="col-1-2 flex-iva-top pad-5px query-part">';
					$sQ[] = '<div class="col-1-1 flex-iha-left"><h3>Ответ</h3></div>';
					$sQ[] = '<div class="cell" style="padding-left: 25px;">'.$value['return'][0]['desc'].'</div>';
					//$sQ[] = '<div class="cell" style="padding-left: 25px;"><b>'.$value['return'][0]['type'].'</b>: '.$value['return'][0]['desc'].'</div>';
					//$sQ .= '<div class="cell" style="padding-left: 25px;"><b>'.$value['return'][1]['type'].'</b>: '.$value['return'][1]['desc'].'</div>';

					$sQ[] = '<div class="col-1-1 flex-iha-left"><h3>Пример</h3></div>';
					$sQ[] = '<div class="cell" style="padding-left: 25px;">'.$value['sample'].'</div>';
					$sQ[] = "</div>";
					if($value['note'])
					{
						$sQ[] = '<div class="cell note"><h3>Заметки</h3><pre style="white-space: pre-wrap;">'.$value['note'].'</pre></div>';
					}
					if($value['todo'])
					{
						$sQ[] = '<div class="cell todo"><h3>Todo:</h3><ul>';
						foreach($value['todo'] as $todo)
						{
							$sQ[] = '<li style="white-space: pre-wrap;">'.htmlspecialchars($todo).'</li>';
						}
						$sQ[] = '</ul></div>';
					}

					$sQ[] = '</div>';
					
					
					$html[] = implode('', $sQ);
				}
			}
			$html[] = '</div>';
		}
		$html[] = "</div>";

		return implode("\n", $html);
	}
};
