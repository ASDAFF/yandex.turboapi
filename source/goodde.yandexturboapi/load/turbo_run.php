<?
$mgu = memory_get_usage(true);
$start      = microtime(true);
$strftime   = '%d.%m.%Y %H:%M:%S';
$LAST_START = new \Bitrix\Main\Type\DateTime();
$logName    = strftime('%Y-%m-%d_%H-%M-%S') . '__' . $arFeed['ID'];

\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_FEED'), $arFeed['ID'], $logName);
\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_PROCESS'), getmypid(), $logName);
\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_MEMORY'), ini_get('memory_limit'), $logName);
\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_LAST_START'), strftime($strftime), $logName);

$step = 1;	
$path = $turboFeed->getPath().'/'.$arFeed['ID'].'/';
if($arFeed['IS_NOT_UPLOAD_FEED'] == 'Y')
{
	$resultUpload = $turboFeed->uploadFeed($path, $arFeed);
	
	\Goodde\YandexTurbo\Log::write(
		\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_UPLOAD_LAST_END', array('#STEP#' => $step, '#MEMORY#' => \Goodde\YandexTurbo\Log::getMemoryUsage())),
		sprintf('%.2F', (microtime(true) - $start)),
		$logName
	);
}
else
{	
	global $runError;
	$exported = 0;
	$numberRss = 1;
	$numberItem = 1;
	$bytesWritten = 0;
	$fp = $turboFeed->rssHeader($path.'turbo_'.$numberRss.'.xml', $bytesWritten, array('ID' => $arFeed['ID'], 'TITLE' => $arFeed['NAME'], 'LINK' => $arFeed['SERVER_ADDRESS'], 'DESCRIPTION' => $arFeed['DESCRIPTION']));
	if(strlen($runError) <= 0)
	{ 
		while($exported < $totalItems) 
		{
			$arResult = $turboFeed->execute($parameters);
			$exported += count($arResult['ITEMS']);
			
			$fp = $turboFeed->rssBody($fp, '', $arResult, $arFeed, $bytesWritten, $numberRss, $numberItem);
			
			$parameters = array('LAST_ID' => $arResult['LAST_ID']);
			\Goodde\YandexTurbo\Log::write(
				\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_STEP', array('#STEP#' => $step, '#ITEMS#' => $exported, '#MEMORY#' => \Goodde\YandexTurbo\Log::getMemoryUsage())),
				sprintf('%.2F', (microtime(true) - $start)),
				$logName
			);
			$step++;
			if($exported == $totalItems)
			{
				$turboFeed->rssFooter($fp);
				\Goodde\YandexTurbo\FeedTable::update($arFeed['ID'], array('DATE_ADD_FEED' => new \Bitrix\Main\Type\DateTime(), 'ALL_FEED' => 'N'));
				break;
			}
			
		}
		
		$resultUpload = $turboFeed->uploadFeed($path, $arFeed);
		
		\Goodde\YandexTurbo\Log::write(
			 \Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_UPLOAD_LAST_END', array('#STEP#' => $step, '#MEMORY#' => \Goodde\YandexTurbo\Log::getMemoryUsage())),
			 sprintf('%.2F', (microtime(true) - $start)),
			 $logName
		);
	}
}

$end  = microtime(true);
$lastRunTime = sprintf('%.2F', $end - $start);
$totalMemory = \Goodde\YandexTurbo\Log::getMemoryUsage($mgu);

\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_LAST_END'), strftime($strftime), $logName);
if($arFeed['IS_NOT_UPLOAD_FEED'] != 'Y')
	\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_TOTAL_ELEMENTS'), $totalItems, $logName);
\Goodde\YandexTurbo\Log::write(
	\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_UPLOAD_RESULT', 
	array(
		'#TOTAL_FILE#' => $resultUpload['TOTAL_FILE'], 
		'#PROCESSED#' => $resultUpload['PROCESSED'],
		'#ADD#' => $resultUpload['ADD'],
		'#ERROR#' => $resultUpload['ERROR'],
	)),
	'',
	$logName
);
\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_TOTAL_RUN_TIME'), $lastRunTime, $logName);
\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_TOTAL_MEMORY'), $totalMemory, $logName);

unset($start, $end, $strftime, $exported, $numberRss, $numberItem, $bytesWritten, $lastRunTime, $totalMemory, $step, $mgu);
?>