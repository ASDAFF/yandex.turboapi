<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$arFilter = array('=ACTIVE' => 'Y');
if($profileId > 0)
{
	$arFilter['=ID'] = $profileId;
}
$resProfile = \Yandex\Export\TurboProfileTable::getList(array(
	'select' => array('ID'),
	'filter' => $arFilter,
	'order' => array('ID' => 'ASC'),
));
while($arProfile = $resProfile->fetch())
{
	$profileExport = new \Yandex\Export\ProfileExport($arProfile['ID']);
	$profileExport->arResult['ALL_ELEMENTS_COUNT'] = $profileExport->SelectedRowsCount();
	if($profileExport->arResult['ALL_ELEMENTS_COUNT'] <= 0)
	{
		continue;
	}

	$tmpDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp';
	if(!is_dir($tmpDir))
	{
		if(!mkdir($tmpDir, 0755, true))
		{
			die('Error! Can\'t make tmp folder');
		}
	}
	$lockFile = $tmpDir . '/xml_export_' . $arProfile['ID'] . '.lock';
	$lockFp   = fopen($lockFile, 'w');
	if(!flock($lockFp, LOCK_EX | LOCK_NB))
	{
		continue;
	}
	
	$mgu = memory_get_usage(true);
	$start      = microtime(true);
	$strftime   = '%d.%m.%Y %H:%M:%S';
	$lastStart = new \Bitrix\Main\Type\DateTime();
	$logName    = strftime('%Y-%m-%d_%H-%M-%S') . '__' . $arProfile['ID'];

	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_EXPORT_PROFILE'), $arProfile['ID'], $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_PROCESS'), getmypid(), $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_MEMORY'), ini_get('memory_limit'), $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_LAST_START'), strftime($strftime), $logName);
	
	$profileExport->writeHeader();

	$step = 1;	
	$itemsCount = 0;
	$elementsCount = 0;
	$offersCount = 0;

	while($elementsCount < $profileExport->arResult['ALL_ELEMENTS_COUNT']) 
	{
		$arResult = $profileExport->execute();
		
		\Yandex\TurboAPI\Log::write(
			\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_EXPORT_STEP', array('#STEP#' => $step, '#ITEMS#' => $arResult['LAST_ITEMS_COUNT'], '#MEMORY#' => \Yandex\TurboAPI\Log::getMemoryUsage())),
			sprintf('%.2F', (microtime(true) - $start)),
			$logName
		);

		$step++;
		$itemsCount += $arResult['LAST_ITEMS_COUNT'];
		$elementsCount += $arResult['LAST_ELEMENTS_COUNT'];
		$offersCount += $arResult['LAST_OFFERS_COUNT'];
		
		if($elementsCount == $profileExport->arResult['ALL_ELEMENTS_COUNT'])
		{
			break;
		}
	}
	
	$profileExport->writeFooter();
	$profileExport->saveXML();
			
	$end  = microtime(true);
	$lastRunTime = sprintf('%.2F', $end - $start);
	$totalMemory = \Yandex\TurboAPI\Log::getMemoryUsage($mgu);
	
	\Yandex\Export\TurboProfileTable::update($arProfile['ID'], array(
		'LAST_START' => $lastStart,
		'LAST_END' => new \Bitrix\Main\Type\DateTime(),
		'TOTAL_ITEMS' => $itemsCount,
		'TOTAL_ELEMENTS' => $elementsCount,
		'TOTAL_OFFERS' => $offersCount,
		'TOTAL_RUN_TIME' => $lastRunTime,
		'TOTAL_MEMORY' => $totalMemory,
	));

	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_LAST_END'), strftime($strftime), $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_EXPORT_TOTAL_ELEMENTS'), $itemsCount, $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_TOTAL_RUN_TIME'), $lastRunTime, $logName);
	\Yandex\TurboAPI\Log::write(\Bitrix\Main\Localization\Loc::getMessage('YANDEX_TYRBO_API_LOG_TOTAL_MEMORY'), $totalMemory, $logName);
	
	unset($profileExport, $arResult, $lastStart, $start, $end, $strftime, $itemsCount, $elementsCount, $offersCount, $lastRunTime, $totalMemory, $step, $mgu);
	register_shutdown_function(function () use ($lockFp, $lockFile){
		flock($lockFp, LOCK_UN);
		@unlink($lockFile);
	});
}
?>