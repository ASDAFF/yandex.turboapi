<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Goodde\YandexTurbo\TaskTable;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/goodde.yandexturboapi/admin/tools.php");

$moduleId = 'goodde.yandexturboapi';
Loc::loadMessages(__FILE__);

if(CModule::IncludeModuleEx($moduleId) == 3)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("GOODDE_TYRBO_API_ERROR_MODULE_DEMO_EXPIRED"),
		"TYPE" => "ERROR",
	));
	return;
}
elseif(!Loader::IncludeModule($moduleId))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("GOODDE_TYRBO_API_ERROR_MODULE"),
		"TYPE" => "ERROR",
	));
	return;
}

$POST_RIGHT = $APPLICATION->GetGroupRight("goodde.yandexturboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$sTableId = "goodde_yandex_turbo_task";
$oSort = new CAdminSorting($sTableId, "DATE_CREATE", "desc");
$lAdmin = new CAdminList($sTableId, $oSort);

if(($arID = $lAdmin->GroupAction()))
{
  if($_REQUEST['action_target']=='selected')
  {
    $rsData = TaskTable::getList(
		array(
			"filter" => $arFilter,
			'order' => array($by=>$order)
		)
	);
    while($arRes = $rsData->fetch())
      $arID[] = $arRes['ID'];
  }

  foreach($arID as $ID)
  {
    if(strlen($ID)<=0)
      continue;
       $ID = IntVal($ID);
    
    switch($_REQUEST['action'])
    {
		case "delete":
		  set_time_limit(0);
		  $DB->StartTransaction();
		  $result = TaskTable::delete($ID);
		  if(!$result->isSuccess())
		  {
			  $DB->Rollback();
			  $lAdmin->AddGroupError(Loc::getMessage("GOODDE_TYRBO_API_DELETE_ERROR"), $ID);
		  }
		  $DB->Commit();
		  break;
    }
  }
}

$APPLICATION->SetTitle(Loc::getMessage("GOODDE_TYRBO_API_ADMIN_TITLE"));

$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));
$arFilterFields = array(
	'filter_name',
	'filter_feed_id',
	'filter_lid',
	'filter_mode',
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array();
if(strlen($filter_name) > 0) $arFilter['%NAME'] = Trim($filter_name);
if(strlen($filter_feed_id) > 0) $arFilter['FEED_ID'] = Trim($filter_feed_id);
if(strlen($filter_lid) > 0) $arFilter['LID'] = Trim($filter_lid);
if(strlen($filter_mode) > 0) $arFilter['MODE'] = Trim($filter_mode);

$myData = TaskTable::getList(
	array(
		'filter' => $arFilter,
		'order' => $arOrder
	)
);

$myData = new CAdminResult($myData, $sTableId);
$myData->NavStart();

$lAdmin->NavText($myData->GetNavPrint(Loc::getMessage("GOODDE_TYRBO_API_ADMIN_NAV")));

$cols = TaskTable::getMap();
$colHeaders = array();
foreach ($cols as $colId => $col)
{
	if(is_object($col))
	{
		$colHeaders[] = array(
			"id" => $colId,
			"content" => $col->getTitle(),
			"sort" => $colId,
			"default" => true,
		);
	}
	else
	{
		if($col['hidden']){
			continue;
		}
		$colHeaders[] = array(
			"id" => $colId,
			"content" => $col["title"],
			"sort" => $colId,
			"default" => true,
		);
	}
}
$lAdmin->AddHeaders($colHeaders);

$visibleHeaderColumns = $lAdmin->GetVisibleHeaderColumns();
while ($arRes = $myData->GetNext())
{
	$arActions = array();
	$el_edit_url = htmlspecialcharsbx(\Goodde\YandexTurbo\CGooddeYandexTurboTools::GetAdminElementEditLink($arRes["ID"], 'goodde_task_edit.php?ID='));
	
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);
	if(in_array("ID", $visibleHeaderColumns) && intval($arRes["ID"]) > 0)
	{
		$row->AddViewField("ID", '<a href="'.$el_edit_url.'" title="ID">'.$arRes["ID"].'</a>');
	}	
	
	$arActions[] = array("ICON" => "edit", "TEXT" => Loc::getMessage("GOODDE_TYRBO_API_OPEN"), "ACTION" => $lAdmin->ActionRedirect($el_edit_url), "DEFAULT" => true,);
	if($POST_RIGHT >= "W")
		$arActions[] = array("ICON" => "delete", "TEXT" => Loc::getMessage("GOODDE_TYRBO_API_DELETE"), "ACTION" => "if(confirm('" . GetMessageJS("GOODDE_TYRBO_API_DEL_CONF") . "')) " . $lAdmin->ActionDoGroup($arRes["ID"], "delete"),);
	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $myData->SelectedRowsCount()
		),
		array(
			"counter" => true,
			"title" => Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => "0"
		),
	)
);

$lAdmin->AddGroupActionTable(Array(
  "delete"=>Loc::getMessage("MAIN_ADMIN_LIST_DELETE"),
));

$lAdmin->CheckListMode();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPageParam()?>?">
<?
$oFilter = new CAdminFilter(
	$sTableId."_filter",
	array(
		Loc::getMessage("GOODDE_TYRBO_API_FILTER_NAME"),
		Loc::getMessage("GOODDE_TYRBO_API_FILTER_FEED_ID"),
		Loc::getMessage("GOODDE_TYRBO_API_FILTER_LID"),
		Loc::getMessage("GOODDE_TYRBO_API_FILTER_MODE"),
	)
);

$oFilter->Begin();
?>
<tr>
	<td><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_NAME")?>:</td>
	<td>
		<input type="text" name="filter_name" value="<?=htmlspecialcharsbx($filter_name)?>">
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_FEED_ID")?>:</td>
	<td>
		<input type="text" name="filter_feed_id" value="<?=htmlspecialcharsbx($filter_feed_id)?>">
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_LID")?>:</td>
	<td>
		<?\Goodde\YandexTurbo\CGooddeYandexTurboTools::ShowLidField('filter_lid', false, true);?>
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_MODE")?>:</td>
	<td>
		<select name="filter_mode">
			<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_ALL")?></option>
			<option value="DEBUG"<?if ($filter_mode=="DEBUG") echo " selected"?>><?=Loc::getMessage("GOODDE_TYRBO_API_DEBUG")?></option>
			<option value="PRODUCTION"<?if ($filter_mode=="PRODUCTION") echo " selected"?>><?=Loc::getMessage("GOODDE_TYRBO_API_PRODUCTION")?></option>
		</select>
	</td>
</tr>
<?
$oFilter->Buttons(
	array(
		"table_id" => $sTableId,
		"url" => $APPLICATION->GetCurPageParam("", $arFilterFields),
		"form" => "find_form"
	)
);
$oFilter->End();
?>
</form>
<?
$lAdmin->DisplayList();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>