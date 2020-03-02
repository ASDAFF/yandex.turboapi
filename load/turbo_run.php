<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


$mgu = memory_get_usage(true);
$start      = microtime(true);
$strftime   = '%d.%m.%Y %H:%M:%S';
$LAST_START = new \Bitrix\Main\Type\DateTime();
$logName    = strftime('%Y-%m-%d_%H-%M-%S') . '__' . $arFeed['ID'];

\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_FEED'), $arFeed['ID'], $logName);
\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_PROCESS'), getmypid(), $logName);
\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_MEMORY'), ini_get('memory_limit'), $logName);
\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_LAST_START'), strftime($strftime), $logName);

$step = 1;	
$path = $turboFeed->getPath().'/'.$arFeed['ID'].'/';
if($arFeed['IS_NOT_UPLOAD_FEED'] == 'Y')
{
	$resultUpload = $turboFeed->uploadFeed($path, $arFeed);
	
	\Yandex\TurboAPI\Log::write(
		\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_UPLOAD_LAST_END', array('#STEP#' => $step, '#MEMORY#' => \Yandex\TurboAPI\Log::getMemoryUsage())),
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
			\Yandex\TurboAPI\Log::write(
				\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_STEP', array('#STEP#' => $step, '#ITEMS#' => $exported, '#MEMORY#' => \Yandex\TurboAPI\Log::getMemoryUsage())),
				sprintf('%.2F', (microtime(true) - $start)),
				$logName
			);
			$step++;
			if($exported == $totalItems)
			{
				$turboFeed->rssFooter($fp);
				\Yandex\TurboAPI\FeedTable::update($arFeed['ID'], array('DATE_ADD_FEED' => new \Bitrix\Main\Type\DateTime(), 'ALL_FEED' => 'N'));
				break;
			}
			
		}
		
		$resultUpload = $turboFeed->uploadFeed($path, $arFeed);
		
		\Yandex\TurboAPI\Log::write(
			 \Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_UPLOAD_LAST_END', array('#STEP#' => $step, '#MEMORY#' => \Yandex\TurboAPI\Log::getMemoryUsage())),
			 sprintf('%.2F', (microtime(true) - $start)),
			 $logName
		);
	}
}

$end  = microtime(true);
$lastRunTime = sprintf('%.2F', $end - $start);
$totalMemory = \Yandex\TurboAPI\Log::getMemoryUsage($mgu);

\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_LAST_END'), strftime($strftime), $logName);
if($arFeed['IS_NOT_UPLOAD_FEED'] != 'Y')
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_TOTAL_ELEMENTS'), $totalItems, $logName);
\Yandex\TurboAPI\Log::write(
	\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_UPLOAD_RESULT', 
	array(
		'#TOTAL_FILE#' => $resultUpload['TOTAL_FILE'], 
		'#PROCESSED#' => $resultUpload['PROCESSED'],
		'#ADD#' => $resultUpload['ADD'],
		'#ERROR#' => $resultUpload['ERROR'],
	)),
	'',
	$logName
);
\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_TOTAL_RUN_TIME'), $lastRunTime, $logName);
\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_TOTAL_MEMORY'), $totalMemory, $logName);

unset($start, $end, $strftime, $exported, $numberRss, $numberItem, $bytesWritten, $lastRunTime, $totalMemory, $step, $mgu);
?>