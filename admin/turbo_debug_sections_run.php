<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main;
use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Text\Converter;
use Bitrix\Main\Localization\Loc;
use Yandex\TurboAPI\Turbo;
use Yandex\TurboAPI\TaskTable;
use Yandex\TurboAPI\FeedTable;
use Yandex\TurboAPI\Model\Request;

Loc::loadMessages(dirname(__FILE__).'/feed_run.php');
$POST_RIGHT = $APPLICATION->GetGroupRight("yandex.turboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if(!Main\Loader::includeModule('yandex.turboapi'))
{
	\CAdminMessage::ShowMessage(Loc::getMessage('YANDEX_ERROR_MODULE'));
	die();
}

$bIBlock = Main\Loader::includeModule('iblock');

$ID = intval($_REQUEST['ID']);
$taskId = 0;
$NS = isset($_REQUEST['NS']) && is_array($_REQUEST['NS']) ? $_REQUEST['NS'] : array();

$arFeed = null;
if($ID > 0)
{
	$arFeed = FeedTable::getById($ID)->fetch();
}
if(!is_array($arFeed))
{
	\CAdminMessage::ShowMessage(Loc::getMessage('YANDEX_ERROR_FILE_NOT_FOUND'));
	die();
}
else
{
	$turboFeed = new \Yandex\TurboAPI\TurboFeedSections($arFeed['ID']);
	$turboFeed->modeDebug = true;
	// is subdomain
	if($arFeed['FIELDS']['IS_SUBDOMAIN'] == 'Y' && $arFeed['FIELDS']['HOST_ID_SUBDOMAIN'])
	{
		$arFeed['SERVER_ADDRESS'] = \Yandex\TurboAPI\Model\Request::getHostNamebyYandexHostId($arFeed['FIELDS']['HOST_ID_SUBDOMAIN']);
	}
}
if($_REQUEST['action'] == 'debug_sections_run' && check_bitrix_sessid())
{
	$path = $turboFeed->getPath().'/'.$arFeed['ID'].'/debug_turbo.xml';
	$arValueSteps = array(
		'init' => 0,
		'upload_address' => 50,
		'index' => 100,
	);
	
	$v = intval($_REQUEST['value']);
	$PID = $ID;
	
	if($v == $arValueSteps['init'])
	{
		global $runError;
		$fp = $turboFeed->rssHeader($path, $bytesWritten = 0, array('ID' => $arFeed['ID'], 'TITLE' => $arFeed['NAME'], 'LINK' => $arFeed['SERVER_ADDRESS'], 'DESCRIPTION' => $arFeed['DESCRIPTION']));
		if(strlen($runError) > 0)
		{
			\CAdminMessage::ShowMessage($runError); 
			die();
		}
		$arResult = $turboFeed->execute();
		$fp = $turboFeed->rssBody($fp, '', $arResult, $arFeed);
		$turboFeed->rssFooter($fp);

		$v++;
	}
	elseif($v < $arValueSteps['upload_address'])
	{
		if(file_exists($path))
		{
			$data = \Bitrix\Main\IO\File::getFileContents($path);
			/*bind events*/
			foreach(GetModuleEvents("yandex.turboapi", "OnBeforeContentAdd", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arFields, &$data));
			}
			
			if($turboFeed->isGzip)
			{
				$data = gzencode($data, 9);
			}
			
			$result = Request::addFeed($arFeed['LID'], 'debug', $data, $turboFeed->isGzip, $arFeed['FIELDS']['HOST_ID_SUBDOMAIN']);
			$NS['result'] = $result;
			$v = $arValueSteps['upload_address'];
		}
		else
		{
			\CAdminMessage::ShowMessage(Loc::getMessage('YANDEX_ERROR_UPLOAD_ADDRESS_D')); 
			die();
		}
	}
	else
	{
		$v = $arValueSteps['index'];
	}
	
	if($v == $arValueSteps['index'])
	{
		$msg = Loc::getMessage('YANDEX_RUN_FINISH');
		if(is_array($NS['result']) && isset($NS['result']['task_id']))
		{
			$result = TaskTable::add(array('FEED_ID' => $arFeed['ID'], 'LID' => $arFeed['LID'], 'TASK_ID' => $NS['result']['task_id'], 'MODE' => 'DEBUG'));
			if($result->isSuccess())
			{
				$taskId = $result->getId();
				$newName = $turboFeed->getPath().'/reports/debug_turbo_'.$taskId.'.xml';
				if(rename($path, $newName))
				{
					if($turboFeed->isGzip)
					{
						$fileName = $turboFeed->addArchive($newName);
						if(strlen($fileName) > 0)
						{
							$newName = $fileName;
						}
						unset($fileName);
					}
					TaskTable::update($taskId, array('NAME' => $newName));
				}
			}
		}
		else
		{
			 \CAdminMessage::ShowMessage(Loc::getMessage('YANDEX_ERROR_RUN', array(
				"#ERROR_CODE#" => $NS['result']['error_code'],
				"#ERROR_MESSAGE#" => Converter::getHtmlConverter()->encode($NS['result']['error_message']),
			))); 
			die();
		}
	}

	echo Turbo::showProgress($msg, Loc::getMessage('YANDEX_RUN_TITLE'), $v);
	if($v < $arValueSteps['index'])
	{
		?>
		<script>
		top.BX.runFeed(<?=$ID?>, 'debug_sections_run', <?=$v?>, '<?=$PID?>', <?=CUtil::PhpToJsObject($NS)?>);
		</script>
		<?
	}
	else
	{
		?>
		<script>
		top.BX.finishTurboFeed('<?=$taskId?>');
		</script>
		<?
	}
}
?>