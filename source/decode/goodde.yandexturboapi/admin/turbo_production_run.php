<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main;
use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Text\Converter;
use Bitrix\Main\Localization\Loc;
use Goodde\YandexTurbo\Turbo;
use Goodde\YandexTurbo\TaskTable;
use Goodde\YandexTurbo\FeedTable;
use Goodde\YandexTurbo\Model\Request;

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

$arFeed = null;
if($ID > 0)
{
	$arFeed = FeedTable::getById($ID)->fetch();
}
if(!is_array($arFeed))
{
	\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_FILE_NOT_FOUND'));
	die();
}

if($_REQUEST['action'] == 'production_run' && check_bitrix_sessid())
{
	global $arErrors;
	$arValueSteps = array(
		'init' => 0,
		'feed' => 70,
		'upload_address' => 80,
		'index' => 100,
	);
	
	$v = intval($_REQUEST['value']);
	$PID = $ID;
	$NS['TOTAL_ITEMS'] = null;
	$path = Turbo::getPath().'/'.$arFeed['ID'].'/';
	$turboFeed = new \Goodde\YandexTurbo\TurboFeed($arFeed['ID']);
	
	if($v == $arValueSteps['init'])
	{
		if($NS['TOTAL_ITEMS'] == null)
		{
			$NS['TOTAL_ITEMS'] = intVal($turboFeed->SelectedRowsCount());
		}
		if($NS['TOTAL_ITEMS'] <= 0 && $arFeed['IS_NOT_UPLOAD_FEED'] == 'N')
		{
			\CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_FILE_NOT_ELEMENTS'));
			die();
		}
		
			
		if($arFeed['IS_NOT_UPLOAD_FEED'] == 'Y')
		{
			$arResult = $turboFeed->uploadFeed($path, $arFeed);
			$msg = Loc::getMessage('GOODDE_RUN_UPLOAD_FEED', array(
				"#TOTAL_FILE#" => $arResult['TOTAL_FILE'],
				"#PROCESSED#" => $arResult['PROCESSED'],
				"#ADD#" => $arResult['ADD'],
				"#ERROR#" => $arResult['ERROR'],
			));
			$v = $arValueSteps['index'];
		}
		else
		{
			global $runError;
			$NS['EXPORTED'] = $NS['EXPORTED'] ? $NS['EXPORTED'] : 0;
			$NS['NUMBER_RSS'] = $NS['NUMBER_RSS'] ? $NS['NUMBER_RSS'] : 1;
			$NS['NUMBER_ITEM'] = $NS['NUMBER_ITEM'] ? $NS['NUMBER_ITEM'] : 1;
			$NS['BYTES_WRITTEN'] = $NS['BYTES_WRITTEN'] ? $NS['BYTES_WRITTEN'] : 0;
			$NS['LAST_ID'] = $NS['LAST_ID'] ? $NS['LAST_ID'] : 0;
			
			$pathToAction = $path.'turbo_'.$NS['NUMBER_RSS'].'.xml';
			if($NS['EXPORTED'] == 0)
			{
				$turboFeed->rssHeader($pathToAction, $NS['BYTES_WRITTEN'], array('ID' => $arFeed['ID'], 'TITLE' => $arFeed['NAME'], 'LINK' => $arFeed['SERVER_ADDRESS'], 'DESCRIPTION' => $arFeed['DESCRIPTION']));
				if(strlen($runError) > 0)
				{ 
					CAdminMessage::ShowMessage($runError);
					die();
				}
			}

			if($NS['EXPORTED'] < $NS['TOTAL_ITEMS']) 
			{
				$arParams = array();
				if($NS['LAST_ID'] > 0)
				{
					$arParams = array('LAST_ID' => $NS['LAST_ID']);
				}
				$arResult = $turboFeed->execute($arParams);
				$NS['EXPORTED'] += count($arResult['ITEMS']);
				
				$turboFeed->rssBody('', $pathToAction, $arResult, $arFeed, $NS['BYTES_WRITTEN'], $NS['NUMBER_RSS'], $NS['NUMBER_ITEM']);
				
				$NS['LAST_ID'] = $arResult['LAST_ID'];
				$v = $arValueSteps['init'];
			}
			
			$e = floor(($NS['EXPORTED']*$arValueSteps['index'])/$NS['TOTAL_ITEMS']);
			
			if($e == $arValueSteps['index'])
			{
				$fp = fopen($pathToAction, "ab");
				$turboFeed->rssFooter($fp);
				FeedTable::update($arFeed['ID'], array('DATE_ADD_FEED' => new \Bitrix\Main\Type\DateTime(), 'ALL_FEED' => 'N'));
				$v = $arValueSteps['feed'];
			}
			
			$msg = Loc::getMessage('GOODDE_RUN_RSS', array(
				"#EXPORTED#" => $NS['EXPORTED'],
				"#TOTAL_ITEMS#" => $NS['TOTAL_ITEMS'],
			));
		}
	}
	elseif($v < $arValueSteps['upload_address'])
	{
		$arResult = $turboFeed->uploadFeed($path, $arFeed);
		$msg = Loc::getMessage('GOODDE_RUN_UPLOAD_FEED', array(
			"#TOTAL_FILE#" => $arResult['TOTAL_FILE'],
			"#PROCESSED#" => $arResult['PROCESSED'],
			"#ADD#" => $arResult['ADD'],
			"#ERROR#" => $arResult['ERROR'],
		));
		$v = $arValueSteps['upload_address'];
	}
	else
	{
		$v = $arValueSteps['index'];
	}
	
	if($v == $arValueSteps['index'] || $arErrors)
	{
		if($arErrors && is_array($arErrors))
		{
			if($msg)
			{
				echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE'), $v);
			}
			foreach($arErrors as $k => $arError)
			{
				 \CAdminMessage::ShowMessage(Loc::getMessage('GOODDE_ERROR_UPLOAD_ADDRESS_P', array(
					"#FILE#" => $k,
					"#ERROR_CODE#" => $arError['ERROR_CODE'],
					"#ERROR_MESSAGE#" => Converter::getHtmlConverter()->encode($arError['ERROR_MESSAGE']),
				)));
			}
			die();
		}
		elseif($arFeed['IS_NOT_UPLOAD_FEED'] != 'Y')
		{
			$msg = Loc::getMessage('GOODDE_RUN_FINISH');
		}
	}
	
	if(isset($e))
	{
		echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE'), $e);
		if($e == $arValueSteps['index'])
		{
			unset($e);
		}
	}
	else
	{
		echo Turbo::showProgress($msg, Loc::getMessage('GOODDE_RUN_TITLE'), $v);
	}
	
	if($v < $arValueSteps['index'])
	{
		?>
		<script>
		top.BX.runFeed(<?=$ID?>, 'production_run', <?=$v?>, '<?=$PID?>', <?=CUtil::PhpToJsObject($NS)?>);
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