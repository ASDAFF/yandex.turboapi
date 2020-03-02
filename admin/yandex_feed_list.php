<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Yandex\TurboAPI\FeedTable;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/yandex.turboapi/admin/tools.php");

$moduleId = 'yandex.turboapi';
Loc::loadMessages(__FILE__);

if(CModule::IncludeModuleEx($moduleId) == 3)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("YANDEX_TYRBO_API_ERROR_MODULE_DEMO_EXPIRED"),
		"TYPE" => "ERROR",
	));
	return;
}
elseif(!Loader::IncludeModule($moduleId))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("YANDEX_TYRBO_API_ERROR_MODULE"),
		"TYPE" => "ERROR",
	));
	return;
}

$POST_RIGHT = $APPLICATION->GetGroupRight("yandex.turboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$strErrorMessage = '';
$FEED_ID = intval($_REQUEST['FEED_ID']);
if ($_REQUEST["ACTION"]=="AGENT" && $USER->CanDoOperation('edit_php') && check_bitrix_sessid())
{
	if($FEED_ID > 0)
	{
		$arFeed = FeedTable::getById($FEED_ID)->fetch();
		if($arFeed)
		{
			$FEED_ID = (int)$arFeed['ID'];
		}
		else
		{
			$FEED_ID = 0;
		}
	}

	if($FEED_ID > 0)
	{
		$agentPeriod = \Yandex\TurboAPI\Turbo::checkTypeCount($_REQUEST['agent_period']);
		if($agentPeriod<=0)
			$agentPeriod = 24;

		if($arFeed['IN_AGENT'] == 'Y')
			CAgent::RemoveAgent("\Yandex\TurboAPI\Turbo::preGenerateExport(".$FEED_ID.");", $moduleId);
		else
			CAgent::AddAgent("\Yandex\TurboAPI\Turbo::preGenerateExport(".$FEED_ID.");", $moduleId, "N", $agentPeriod*60*60, "", "Y");

		\Yandex\TurboAPI\FeedTable::update($FEED_ID, array(
			'IN_AGENT' => ($arFeed['IN_AGENT'] == 'Y' ? 'N' : 'Y')
		));
	}

	if (strlen($strErrorMessage)<=0)
	{
		$redirectUrl = "/bitrix/admin/yandex_feed_list.php?lang=".urlencode(LANGUAGE_ID)."&success_export=Y";
		LocalRedirect($redirectUrl);
	}
}
			
$sTableId = "yandex_yandex_turbo_feed";
$oSort = new CAdminSorting($sTableId, "ID", "asc");
$lAdmin = new CAdminList($sTableId, $oSort);
if($lAdmin->EditAction())
{
  foreach($FIELDS as $ID=>$arFields)
  {
	if(!$lAdmin->IsUpdated($ID))
      continue;
    
    $DB->StartTransaction();
    $ID = IntVal($ID);
    $res = FeedTable::getById($ID);
	if(!$arData = $res->fetch()){
		foreach($arFields as $key=>$value)
        	$arData[$key]=$value;
 		$result = FeedTable::update($ID, $arData);
 		
		if(!$result->isSuccess())
		{
			if($e = $result->getErrorMessages())
				$lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_SAVE_ERROR")." ".$e, $ID);
			$DB->Rollback();
		}
	}
    else
    {
      $lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_SAVE_ERROR")." ".Loc::getMessage("YANDEX_TYRBO_API_NO_ELEMENT"), $ID);
      $DB->Rollback();
    }
    $DB->Commit();
  }
}

if(($arID = $lAdmin->GroupAction()))
{
  if($_REQUEST['action_target']=='selected')
  {
    $rsData = FeedTable::getList(
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
		  $result = FeedTable::delete($ID);
		  if(!$result->isSuccess())
		  {
			  $DB->Rollback();
			  $lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_DELETE_ERROR"), $ID);
		  }
		  $DB->Commit();
		  break;
		
		case "activate":
		case "deactivate":
		  
		  if(($rsData = FeedTable::getById($ID)) && ($arFields = $rsData->fetch()))
		  {
			$arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");
			$result = FeedTable::update($ID, $arFields);
			if(!$result->isSuccess())
				if($e = $result->getErrorMessages())
					$lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_SAVE_ERROR").$e, $ID);
		  }
		  else
			$lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_SAVE_ERROR")." ".Loc::getMessage("YANDEX_TYRBO_API_NO_ELEMENT"), $ID);
		  break;
    }
  }
}

$APPLICATION->SetTitle(Loc::getMessage("YANDEX_TYRBO_API_ADMIN_TITLE"));

$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));
$arFilterFields = array(
	'filter_name',
	'filter_active',
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array('!IS_SECTION' => true);
if(strlen($filter_name) > 0) $arFilter['%NAME'] = Trim($filter_name);
if(strlen($filter_active) > 0) $arFilter['ACTIVE'] = Trim($filter_active);

$myData = FeedTable::getList(
	array(
		'filter' => $arFilter,
		'order' => $arOrder
	)
);

$myData = new CAdminResult($myData, $sTableId);
$myData->NavStart();

$lAdmin->NavText($myData->GetNavPrint(Loc::getMessage("YANDEX_TYRBO_API_ADMIN_NAV")));

$cols = FeedTable::getMap();
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
$arUsersCache = array();
$arElementCache = array();
while ($arRes = $myData->GetNext())
{
	$arActions = array();
	$MODIFIED_BY = $arRes['MODIFIED_BY'];
	$CREATED_BY = $arRes['CREATED_BY'];
	$el_edit_url = htmlspecialcharsbx(\Yandex\TurboAPI\CYandexTurboAPITools::GetAdminElementEditLink($arRes["ID"], 'yandex_feed_edit.php?ID='));
	
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);
	if (in_array("ACTIVE", $visibleHeaderColumns))
	{
		$row->AddViewField("ACTIVE", $arRes['ACTIVE'] == 'Y'?Loc::getMessage("YANDEX_TYRBO_API_YES"):Loc::getMessage("YANDEX_TYRBO_API_NO"));
	}
	
	if (in_array("SERVER_ADDRESS", $visibleHeaderColumns))
	{
		$row->AddViewField("IN_AGENT", $arRes['IN_AGENT'] == 'Y'?Loc::getMessage("YANDEX_TYRBO_API_YES"):Loc::getMessage("YANDEX_TYRBO_API_NO"));
	}
	
	if (in_array("IN_AGENT", $visibleHeaderColumns))
	{
		if($arRes['FIELDS']['IS_SUBDOMAIN'] == 'Y' && $arRes['FIELDS']['HOST_ID_SUBDOMAIN'])
		{
			$row->AddViewField("SERVER_ADDRESS", \Yandex\TurboAPI\Model\Request::getHostNamebyYandexHostId($arRes['FIELDS']['HOST_ID_SUBDOMAIN']));
		}
	}
	
	if(in_array("ID", $visibleHeaderColumns) && intval($arRes["ID"]) > 0)
	{
		$row->AddViewField("ID", '<a href="'.$el_edit_url.'" title="ID">'.$arRes["ID"].'</a>');
	}
	
	if(in_array('MODIFIED_BY', $visibleHeaderColumns) && intval($MODIFIED_BY) > 0)
	{
		if(!array_key_exists($MODIFIED_BY, $arUsersCache))
		{
			$rsUser = CUser::GetByID($MODIFIED_BY);
			$arUsersCache[$MODIFIED_BY] = $rsUser->Fetch();
		}
		if($arUser = $arUsersCache[$MODIFIED_BY])
			$row->AddViewField("MODIFIED_BY", '[<a href="user_edit.php?lang='.LANGUAGE_ID.'&ID='.$MODIFIED_BY.'" title="'.GetMessage("IBLIST_A_USERINFO").'">'.$MODIFIED_BY."</a>]&nbsp;(".htmlspecialcharsEx($arUser["LOGIN"]).") ".htmlspecialcharsEx($arUser["NAME"]." ".$arUser["LAST_NAME"]));
	}

	if(in_array("CREATED_BY", $visibleHeaderColumns) && intval($CREATED_BY) > 0)
	{
		if(!array_key_exists($CREATED_BY, $arUsersCache))
		{
			$rsUser = CUser::GetByID($CREATED_BY);
			$arUsersCache[$CREATED_BY] = $rsUser->Fetch();
		}
		if($arUser = $arUsersCache[$CREATED_BY])
			$row->AddViewField("CREATED_BY", '[<a href="user_edit.php?lang='.LANGUAGE_ID.'&ID='.$CREATED_BY.'" title="'.GetMessage("IBLIST_A_USERINFO").'">'.$CREATED_BY."</a>]&nbsp;(".htmlspecialcharsEx($arUser["LOGIN"]).") ".htmlspecialcharsEx($arUser["NAME"]." ".$arUser["LAST_NAME"]));
	}
	
	$arActions[] = array("ICON" => "edit", "TEXT" => Loc::getMessage("YANDEX_TYRBO_API_EDIT"), "ACTION" => $lAdmin->ActionRedirect($el_edit_url), "DEFAULT" => true,);
	if ('Y' == $arRes['IN_AGENT'])
	{
		$arActions[] = array(
			"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DEL"),
			"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DESCR_DEL"),
			"ACTION" => $lAdmin->ActionRedirect("/bitrix/admin/yandex_feed_list.php?lang=".LANGUAGE_ID."&".bitrix_sessid_get()."&ACTION=AGENT&FEED_ID=".$arRes['ID']),
		);
	}
	else
	{
		$arActions[] = array(
			"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_AGENT"),
			"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DESCR"),
			"ACTION" => "ShowAgentForm('".$APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID."&".bitrix_sessid_get()."&ACTION=AGENT&FEED_ID=".$arRes['ID']."');",
		);
	}
	if($POST_RIGHT >= "W")
		$arActions[] = array("ICON" => "delete", "TEXT" => Loc::getMessage("YANDEX_TYRBO_API_DELETE"), "ACTION" => "if(confirm('" . GetMessageJS("YANDEX_TYRBO_API_DEL_CONF") . "')) " . $lAdmin->ActionDoGroup($arRes["ID"], "delete"),);
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
  "activate"=>Loc::getMessage("MAIN_ADMIN_LIST_ACTIVATE"),
  "deactivate"=>Loc::getMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
));

$aContext = array();

if (empty($aContext))
{
	$aContext[] = array(
			"ICON" => "btn_new",
			"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_ADD"),
			"LINK" => \Yandex\TurboAPI\CYandexTurboAPITools::GetAdminElementEditLink(0, 'yandex_feed_edit.php?ID='),
			"LINK_PARAM" => "",
			"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_ADD")
	);
}

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
	
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div id="form_shadow" style="display:none;" class="float-form-shadow">&nbsp;</div>
<div id="agent_form" style="display:none;" class="float-form">
<form name="agentform" id="agentform" action="" method="post">
	<table class="edit-table">
		<tbody>
	<tr>
		<td style="white-space: nowrap; font-size: 12px;"><? echo Loc::getMessage("YANDEX_TYRBO_API_RUN_INTERVAL"); ?></td>
		<td><input type="text" name="agent_period" value="" size="10"></td>
	</tr>
		</tbody>
		<tfoot>
	<tr>
		<td colspan="2" style="text-align: center;">
			<input type="submit" value="<? echo Loc::getMessage("YANDEX_TYRBO_API_SET"); ?>">&nbsp;&nbsp;<input type="button" value="<? echo Loc::getMessage("YANDEX_TYRBO_API_CLOSE"); ?>" onclick="HideAgentForm();">
		</td>
	</tr>
		</tfoot>
	</table>
</form>
</div>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPageParam()?>?">
<?
$oFilter = new CAdminFilter(
	$sTableId."_filter",
	array(
		Loc::getMessage("YANDEX_TYRBO_API_FILTER_NAME"),
		Loc::getMessage("YANDEX_TYRBO_API_FILTER_ACTIVE"),
	)
);

$oFilter->Begin();
?>
<tr>
	<td><?=Loc::getMessage("YANDEX_TYRBO_API_FILTER_NAME")?>:</td>
	<td>
		<input type="text" name="filter_name" value="<?=htmlspecialcharsbx($filter_name)?>">
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("YANDEX_TYRBO_API_FILTER_ACTIVE")?>:</td>
	<td>
		<select name="filter_active">
			<option value=""><?=Loc::getMessage("YANDEX_TYRBO_API_ALL")?></option>
			<option value="Y"<?if ($filter_active=="Y") echo " selected"?>><?=Loc::getMessage("YANDEX_TYRBO_API_YES")?></option>
			<option value="N"<?if ($filter_active=="N") echo " selected"?>><?=Loc::getMessage("YANDEX_TYRBO_API_NO")?></option>
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
if($_GET["success_export"] == "Y")
{
	CAdminMessage::ShowNote(Loc::getMessage("YANDEX_TYRBO_API_SUCCESS"));
}

$lAdmin->DisplayList();

echo BeginNote();
	echo Loc::getMessage("YANDEX_TYRBO_API_EXPORT_SETUP_CAT")?> /bitrix/php_interface/include/yandex_turbo/<br><br>
	<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES1");?><br><br>
	<?if ($bWindowsHosting):?>
		<b><?=Loc::getMessage("YANDEX_TYRBO_API_NOTES2");?></b>
	<?else:?>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES3");?>
		<b><?echo $_SERVER["DOCUMENT_ROOT"];?>/bitrix/crontab/crontab.cfg</b>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES4");?><br>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES5");?><br>
		<b>crontab <?echo $_SERVER["DOCUMENT_ROOT"];?>/bitrix/crontab/crontab.cfg</b><br>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES6");?><br>
		<b>crontab -l</b><br>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES7");?><br>
		<b>crontab -r</b><br><br>
		<?
		$arRetval = array();
		@exec("crontab -l", $arRetval);
		if (is_array($arRetval) && !empty($arRetval))
		{
			echo Loc::getMessage("YANDEX_TYRBO_API_NOTES8");?><br>
			<textarea name="crontasks" cols="70" rows="5" readonly>
			<?
			echo htmlspecialcharsbx(implode("\n", $arRetval))."\n";
			?>
			</textarea><br>
			<?
		}
		echo Loc::getMessage("YANDEX_TYRBO_API_NOTES10");?><br><br>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES11_EXT", array('#FILE#' => '/bitrix/php_interface/include/yandex_turbo/cron_frame.php'));?><br>
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES12_EXT");?><br>
		<?=Loc::getMessage('YANDEX_TYRBO_API_NOTES13_EXT', array('#FOLDER#' => '/bitrix/modules/'.$moduleId.'/load/'));
	endif;

echo EndNote();
?>
<script type="text/javascript">
function ShowDiv(div, shadow)
{
	var obDiv = BX(div);
	var obShadow = BX(shadow);
	if (!!obDiv && !!obShadow)
	{
		var obCoord = BX.GetWindowSize();
		BX.style(obDiv, 'display', 'block');
		BX.style(obShadow, 'display', 'block');

		var l = parseInt(obCoord.scrollLeft + obCoord.innerWidth/2 - obDiv.offsetWidth/2);
		var t = parseInt(obCoord.scrollTop + obCoord.innerHeight/2 - obDiv.offsetHeight/2);

		BX.adjust(obDiv, {style: {left: l + "px", top: t + "px"}});
		BX.adjust(obShadow, {style: {left: (l+4) + "px", top: (t+4) + "px", width: obDiv.offsetWidth + 'px', height: obDiv.offsetHeight + 'px'}});
	}
}

function HideDiv(div, shadow)
{
	var obDiv = BX(div);
	var obShadow = BX(shadow);
	if (!!obDiv && !!obShadow)
	{
		BX.style(obDiv, 'display', 'none');
		BX.style(obShadow, 'display', 'none');
	}
}

function SetForm(form, strAction)
{
	var obForm = BX(form);
	if (!!obForm)
	{
		obForm.action = strAction;
		var obTbl = BX.findChild(obForm, {tag: 'table', className: 'edit-table'}, false, false);
		if (!!obTbl)
		{
			var n = obTbl.tBodies[0].rows.length;
			for (var i=0; i<n; i++)
			{
				if (obTbl.tBodies[0].rows[i].cells.length > 1)
				{
					BX.addClass(obTbl.rows[i].cells[0], 'adm-detail-content-cell-l');
					BX.addClass(obTbl.rows[i].cells[1], 'adm-detail-content-cell-r');
				}
			}
		}
		BX.adminFormTools.modifyFormElements(obTbl);
		return true;
	}
	return false;
}

function ShowAgentForm(strAction)
{
	if (SetForm('agentform', strAction))
	{
		ShowDiv('agent_form', 'form_shadow');
	}
}

function HideAgentForm()
{
	HideDiv('agent_form', 'form_shadow');
}
</script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>