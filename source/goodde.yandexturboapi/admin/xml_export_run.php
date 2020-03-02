<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main;
use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Text\Converter;
use Bitrix\Main\Localization\Loc;
use Goodde\YandexTurbo\Turbo;
use Goodde\Export\TurboProfileTable;

Loc::loadMessages(dirname(__FILE__).'/feed_run.php');
$POST_RIGHT = $APPLICATION->GetGroupRight("goodde.yandexturboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if(!Main\Loader::includeModule('goodde.yandexturboapi'))
{
	\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_MODULE'));
	die();
}

$bIBlock = Main\Loader::includeModule('iblock');

$ID = intval($_REQUEST['ID']);
$taskId = 0;
$NS = isset($_REQUEST['NS']) && is_array($_REQUEST['NS']) ? $_REQUEST['NS'] : array();

$arProfile = null;
if($ID > 0)
{
	$arProfile = TurboProfileTable::getById($ID)->fetch();
}
if(!is_array($arProfile))
{
	\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_PROFILE_NOT_FOUND'));
	die();
}

if($_REQUEST['action'] == 'xml_export_run' && check_bitrix_sessid())
{
	$arValueSteps = array(
		'init' => 0,
		'step' => 1,
		'step_80' => 80,
		'step_90' => 90,
		'index' => 100,
	);
	
	$v = intval($_REQUEST['value']);
	$NS['STEP'] = $NS['STEP'] ? $NS['STEP'] : 1;
	$NS['ALL_ELEMENTS_COUNT'] = $profileExport->arResult['ALL_ELEMENTS_COUNT'] ? $profileExport->arResult['ALL_ELEMENTS_COUNT'] : 0;
	$NS['ITEMS_COUNT'] = $NS['ITEMS_COUNT'] ? $NS['ITEMS_COUNT'] : 0;
	$NS['ELEMENTS_COUNT'] = $NS['ELEMENTS_COUNT'] ? $NS['ELEMENTS_COUNT'] : 0;
	$NS['OFFERS_COUNT'] = $NS['OFFERS_COUNT'] ? $NS['OFFERS_COUNT'] : 0;
	$NS['LAST_ID'] = $NS['LAST_ID'] ? $NS['LAST_ID'] : 0;
	
	$profileExport = new \Goodde\Export\ProfileExport($arProfile['ID'], array('TMP_FILE_PATH' => $NS['TMP_FILE_PATH']));
	$profileExport->feed['LAST_ELEMENT_ID'] = $NS['LAST_ID'];
	if($NS['STEP'] <= 1 || $NS['ALL_ELEMENTS_COUNT'] <= 0)
	{
		$profileExport->arResult['ALL_ELEMENTS_COUNT'] = $profileExport->SelectedRowsCount();
		$NS['ALL_ELEMENTS_COUNT'] = $profileExport->arResult['ALL_ELEMENTS_COUNT'];
	}
	else
	{
		$profileExport->arResult['ALL_ELEMENTS_COUNT'] = intval($NS['ALL_ELEMENTS_COUNT']);
	}	
	
	if($profileExport->arResult['ALL_ELEMENTS_COUNT'] <= 0)
	{
		\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_FILE_NOT_ELEMENTS'));
		die();
	}
	
	if($v == $arValueSteps['init'])
	{
		if($NS['ELEMENTS_COUNT'] == 0)
		{
			$NS['TMP_FILE_PATH'] = $profileExport->feed['TMP_FILE_PATH'];
			$tmpDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp';
			if(!is_dir($tmpDir))
			{
				if(!mkdir($tmpDir, 0755, true))
				{
					\CAdminMessage::ShowMessage('Error! Can\'t make tmp folder');
					die();
				}
			}
			$lockFile = $tmpDir . '/xml_export_' . $profileExport->feed['ID'] . '.lock';
			$NS['LOCK_FP'] = $lockFp   = fopen($lockFile, 'w');
			if(!flock($lockFp, LOCK_EX | LOCK_NB))
			{
				\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_RUN_EXPORT_FLOCK'));
				die();
			}
			
			$NS['TIME_START'] = microtime(true);
			$NS['LOG_NAME'] = $logName = strftime('%Y-%m-%d_%H-%M-%S') . '__' . $profileExport->feed['ID'];
			\Goodde\Export\TurboProfileTable::update($profileExport->feed['ID'], array(
				'LAST_START' => new \Bitrix\Main\Type\DateTime(),
			));

			\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_EXPORT_PROFILE'), $profileExport->feed['ID'], $logName);
			\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_PROCESS'), getmypid(), $logName);
			\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_MEMORY'), ini_get('memory_limit'), $logName);
			\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_LAST_START'), strftime('%d.%m.%Y %H:%M:%S'), $logName);
			
			$profileExport->writeHeader();
		}
		$v = $arValueSteps['step'];
	}
	elseif($v < $arValueSteps['step_80'])
	{
		if($NS['ELEMENTS_COUNT'] < $profileExport->arResult['ALL_ELEMENTS_COUNT']) 
		{
			$arResult = $profileExport->execute();
		
			\Goodde\YandexTurbo\Log::write(
				\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_EXPORT_STEP', array('#STEP#' => $NS['STEP'], '#ITEMS#' => $arResult['LAST_ITEMS_COUNT'], '#MEMORY#' => \Goodde\YandexTurbo\Log::getMemoryUsage())),
				sprintf('%.2F', (microtime(true) - $NS['TIME_START'])),
				$NS['LOG_NAME']
			);

			$NS['STEP']++;
			$NS['ITEMS_COUNT'] += $arResult['LAST_ITEMS_COUNT'];
			$NS['ELEMENTS_COUNT'] += $arResult['LAST_ELEMENTS_COUNT'];
			$NS['OFFERS_COUNT'] += $arResult['LAST_OFFERS_COUNT'];
			$NS['LAST_ID'] = $arResult['LAST_ID'];
			$v = $arValueSteps['step'];
		}
		
		$e = floor(($NS['ELEMENTS_COUNT']*$arValueSteps['index'])/$NS['ALL_ELEMENTS_COUNT']);
		
		if($e == $arValueSteps['index'])
		{
			$profileExport->writeFooter();
			$profileExport->saveXML();
			$v = $arValueSteps['step_80'];
		}
		
		$msg = Loc::getMessage('GOODDE_RUN_RSS', array(
			"#EXPORTED#" => $NS['ELEMENTS_COUNT'],
			"#TOTAL_ITEMS#" => $profileExport->arResult['ALL_ELEMENTS_COUNT'],
		));
	}
	elseif($v < $arValueSteps['step_90'])
	{
		$end  = microtime(true);
		$lastRunTime = sprintf('%.2F', $end - $NS['TIME_START']);
		
		\Goodde\Export\TurboProfileTable::update($profileExport->feed['ID'], array(
			'LAST_END' => new \Bitrix\Main\Type\DateTime(),
			'TOTAL_ITEMS' => $NS['ITEMS_COUNT'],
			'TOTAL_ELEMENTS' => $NS['ELEMENTS_COUNT'],
			'TOTAL_OFFERS' => $NS['OFFERS_COUNT'],
			'TOTAL_RUN_TIME' => $lastRunTime,
			'TOTAL_MEMORY' => 0,
		));
		$logName = $NS['LOG_NAME'];
		\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_LAST_END'), strftime('%d.%m.%Y %H:%M:%S'), $logName);
		\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_EXPORT_TOTAL_ELEMENTS'), $NS['ITEMS_COUNT'], $logName);
		\Goodde\YandexTurbo\Log::write(\Bitrix\Main\Localization\Loc::getMessage('GOODDE_TYRBO_API_LOG_TOTAL_RUN_TIME'), $lastRunTime, $logName);
		
		$tmpDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp';
		$lockFile = $tmpDir . '/xml_export_' . $profileExport->feed['ID'] . '.lock';
		$lockFp = $NS['LOCK_FP'];
			
		register_shutdown_function(function () use ($lockFp, $lockFile){
			flock($lockFp, LOCK_UN);
			@unlink($lockFile);
		});
	
		$v = $arValueSteps['index'];
	}
	else
	{
		$v = $arValueSteps['index'];
	}
	
	if($v == $arValueSteps['index'] || $profileExport->arErrors)
	{
		if($profileExport->arErrors && is_array($profileExport->arErrors))
		{
			if($msg)
			{
				echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE_XML'), $v);
			}
			foreach($profileExport->arErrors as $k => $error)
			{
				 \CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_RUN', array(
					"#ERROR_CODE#" => 'PROFILE_NO_ACTIVE',
					"#ERROR_MESSAGE#" => Converter::getHtmlConverter()->encode($error),
				)));
			}
			die();
		}
		else
		{
			$msg = Loc::getMessage('GOODDE_RUN_FINISH');
		}
	}
	
	if(isset($e))
	{
		echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE_XML'), $e);
		if($e == $arValueSteps['index'])
		{
			unset($e);
		}
	}
	else
	{
		echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE_XML'), $v);
	}
	
	if($v < $arValueSteps['index'])
	{
		?>
		<script>
		top.BX.runFeed(<?=$ID?>, 'xml_export_run', <?=$v?>, '<?=$ID?>', <?=CUtil::PhpToJsObject($NS)?>);
		</script>
		<?
	}
	else
	{
		?>
		<script>
		top.BX.finishTurboFeed(0);
		</script>
		<?	
	}
}
?>