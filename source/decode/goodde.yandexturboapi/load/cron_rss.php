<?
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_CRONTAB", true);
define('NO_AGENT_CHECK', true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

set_time_limit(0);
ignore_user_abort(true);

$feedId = 0;
if (isset($argv[1]))
	$feedId = (int)$argv[1];
if ($feedId <= 0)
	die('No feed id');

if(!\Bitrix\Main\Loader::includeModule('goodde.yandexturboapi'))
{
	die('No module goodde.yandexturboapi');
}

if(!\Bitrix\Main\Loader::includeModule('iblock'))
{
	die('No iblock');
}

$arFeed = \Goodde\YandexTurbo\FeedTable::getById($feedId)->fetch();
if(!$arFeed)
{
	die('No feed');
}

$turboFeed = new \Goodde\YandexTurbo\TurboFeed($arFeed['ID']);

$totalItems = intVal($turboFeed->SelectedRowsCount());
if($totalItems <= 0 && $arFeed['IS_NOT_UPLOAD_FEED'] == 'N')
{
	die('No elements');
}

$path = $turboFeed->getPath().$arFeed['ID'].'/';

if($arFeed['IS_NOT_UPLOAD_FEED'] == 'Y')
{
	$turboFeed->uploadFeed($path, $arFeed);
}
else
{	
	global $runError;
	$exported = 0;
	$numberRss = 1;
	$numberItem = 1;
	$bytesWritten = 0;
	$fp = $turboFeed->rssHeader($path.'turbo_'.$numberRss.'.xml', $bytesWritten, array('ID' => $arFeed['ID'], 'TITLE' => $arFeed['NAME'], 'LINK' => $arFeed['SERVER_ADDRESS'], 'DESCRIPTION' => $arFeed['DESCRIPTION']));
	if(strlen($runError) > 0)
	{ 
		die($runError);
	}
	
	while($exported < $totalItems) 
	{
		$arResult = $turboFeed->execute($parameters);
		$exported += count($arResult['ITEMS']);
		
		$fp = $turboFeed->rssBody($fp, '', $arResult, $arFeed, $bytesWritten, $numberRss, $numberItem);
		
		$parameters = array('LAST_ID' => $arResult['LAST_ID']);
		if($exported == $totalItems)
		{
			$turboFeed->rssFooter($fp);
			\Goodde\YandexTurbo\FeedTable::update($arFeed['ID'], array('DATE_ADD_FEED' => new \Bitrix\Main\Type\DateTime(), 'ALL_FEED' => 'N'));
			break;
		}
	}
	
	$turboFeed->uploadFeed($path, $arFeed);
}
?>
