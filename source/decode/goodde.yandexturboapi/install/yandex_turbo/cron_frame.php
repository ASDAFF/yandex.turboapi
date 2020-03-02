#!#PHP_PATH# -q
<?
/* replace #PHP_PATH# to real path of php binary
For example:
/user/bin/php
/usr/bin/perl
/usr/bin/env python
*/
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

$strFile = '/bitrix/modules/goodde.yandexturboapi/load/turbo_run.php';
if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
{
	die('No export script');
}

$arFeed = \Goodde\YandexTurbo\FeedTable::getById($feedId)->fetch();
if(!$arFeed)
{
	die('No feed');
}
if($arFeed['IS_SECTION'] == 'Y')
{
	$turboFeed = new \Goodde\YandexTurbo\TurboFeedSections($arFeed['ID']);
}
else
{
	$turboFeed = new \Goodde\YandexTurbo\TurboFeed($arFeed['ID']);
}
$totalItems = intVal($turboFeed->SelectedRowsCount());
if($totalItems <= 0 && $arFeed['IS_NOT_UPLOAD_FEED'] == 'N')
{
	die('No elements');
}

include($_SERVER["DOCUMENT_ROOT"].$strFile);
?>