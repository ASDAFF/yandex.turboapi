<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	\Yandex\Export\TurboProfileTable;
	
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
	
$sTableId = "yandex_yandex_turbo_profile";
$oSort = new CAdminSorting($sTableId, "ID", "asc");
$lAdmin = new CAdminUiList($sTableId, $oSort);

$arFilter = array();

$isRedirect = false;
$PROFILE_ID = intval($_REQUEST['PROFILE_ID'] ? $_REQUEST['PROFILE_ID'] : $_REQUEST['ID']);
if($USER->CanDoOperation('edit_php') && check_bitrix_sessid())
{
	if($PROFILE_ID > 0)
	{
		$arProfile = TurboProfileTable::getRow(array(
			'select' => array('ID', 'IN_AGENT'),
			'filter' => array('=ID' => $PROFILE_ID),
			'limit' => 1,
		));
		if($arProfile)
		{
			$PROFILE_ID = (int)$arProfile['ID'];
		}
		else
		{
			$PROFILE_ID = 0;
		}
	}

	if($PROFILE_ID > 0)
	{
		if($_REQUEST['action'] === 'agent')
		{
			$agentPeriod = \Yandex\TurboAPI\Turbo::checkTypeCount($_REQUEST['agent_period']);
			if($agentPeriod<=0)
				$agentPeriod = 24;

			if($arProfile['IN_AGENT'] == 'Y')
				CAgent::RemoveAgent("\Yandex\Export\ProfileTools::preGenerateExport(".$PROFILE_ID.");", $moduleId);
			else
				CAgent::AddAgent("\Yandex\Export\ProfileTools::preGenerateExport(".$PROFILE_ID.");", $moduleId, "N", $agentPeriod*60*60, "", "Y");

			TurboProfileTable::update($PROFILE_ID, array(
				'IN_AGENT' => ($arProfile['IN_AGENT'] == 'Y' ? 'N' : 'Y')
			));
			$isRedirect = true;;
		}
		elseif($_REQUEST['action_button_yandex_yandex_turbo_profile'] === 'delete')
		{
			$DB->StartTransaction();
			$result = TurboProfileTable::delete($PROFILE_ID);
			if(!$result->isSuccess())
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(Loc::getMessage("YANDEX_TYRBO_API_DELETE_ERROR"), $PROFILE_ID);
			}
			$DB->Commit();
		}
	}

	if($isRedirect)
	{
		$redirectUrl = "/bitrix/admin/yandex_profile_list.php?lang=".urlencode(LANGUAGE_ID)."&success_export=Y";
		LocalRedirect($redirectUrl);
	}
}
$colHeaders = array();
$arSelectFields = array(
	'ID', 'NAME', 'ACTIVE', 'SORT', 'TIMESTAMP_X', 'MODIFIED_BY', 'DATE_CREATE', 'CREATED_BY', 'LID', 'LAST_START', 'LAST_END',
	'TOTAL_ITEMS', 'TOTAL_ELEMENTS', 'TOTAL_OFFERS', 'TOTAL_SECTIONS', 'TOTAL_RUN_TIME', 'TOTAL_MEMORY', 'IN_AGENT'
);

$query = \Yandex\Export\TurboProfileTable::query();
$query->setSelect($arSelectFields);
$query->addOrder($by, $order);

$dbResultList = new CAdminUiResult($query->exec(), $sTableId);
$dbResultList->NavStart();

$lAdmin->SetNavigationParams($dbResultList, array("BASE_LINK" => "/bitrix/admin/yandex_profile_list.php"));

$columns = \Yandex\Export\TurboProfileTable::getEntity()->getFields();
foreach($columns as $code => $column) 
{
	if(in_array($column->getName(), $arSelectFields))
	{
		$colHeaders[] = array(
			"id" => $column->getName(),
			"content" => $column->getTitle(),
			"default" => true,
		);
	}
}

$lAdmin->AddHeaders($colHeaders);

$arUserList = array();
$strNameFormat = CSite::GetNameFormat(true);

while($arProfile = $dbResultList->NavNext(false))
{
	$el_edit_url = htmlspecialcharsbx(\Yandex\TurboAPI\CYandexTurboAPITools::GetAdminElementEditLink($arProfile["ID"], 'yandex_profile_edit.php?ID='));
	
	$row = &$lAdmin->AddRow($arProfile['ID'], $arProfile, $el_edit_url);

	$row->AddViewField('ID', '<a href="'.$el_edit_url.'" title="ID">'.htmlspecialcharsbx($arProfile['ID']).'</a>');
	$row->AddViewField("NAME", '<a href="'.$el_edit_url.'" title="ID">'.htmlspecialcharsbx($arProfile['NAME']).'</a>');
	$row->AddCheckField("ACTIVE", false);
	$row->AddCheckField("IN_AGENT", false);
		
	$strCreatedBy = '';
	$strModifiedBy = '';
	$arProfile['CREATED_BY'] = (int)$arProfile['CREATED_BY'];
	if (0 < $arProfile['CREATED_BY'])
	{
		if (!isset($arUserList[$arProfile['CREATED_BY']]))
		{
			$byUser = 'ID';
			$byOrder = 'ASC';
			$rsUsers = CUser::GetList(
				$byUser,
				$byOrder,
				array('ID_EQUAL_EXACT' => $arProfile['CREATED_BY']),
				array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL'))
			);
			if ($arOneUser = $rsUsers->Fetch())
			{
				$arOneUser['ID'] = (int)$arOneUser['ID'];
				if ($publicMode)
				{
					$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
				}
				else
				{
					$arUserList[$arOneUser['ID']] = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$arProfile['MODIFIED_BY'].'">'.CUser::FormatName($strNameFormat, $arOneUser).'</a>';
				}
			}
		}
		if (isset($arUserList[$arProfile['CREATED_BY']]))
			$strCreatedBy = $arUserList[$arProfile['CREATED_BY']];
	}
	$arProfile['MODIFIED_BY'] = (int)$arProfile['MODIFIED_BY'];
	if (0 < $arProfile['MODIFIED_BY'])
	{
		if (!isset($arUserList[$arProfile['MODIFIED_BY']]))
		{
			$byUser = 'ID';
			$byOrder = 'ASC';
			$rsUsers = CUser::GetList(
				$byUser,
				$byOrder,
				array('ID_EQUAL_EXACT' => $arProfile['MODIFIED_BY']),
				array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL'))
			);
			if ($arOneUser = $rsUsers->Fetch())
			{
				$arOneUser['ID'] = (int)$arOneUser['ID'];
				if ($publicMode)
				{
					$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
				}
				else
				{
					$arUserList[$arOneUser['ID']] = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$arProfile['MODIFIED_BY'].'">'.CUser::FormatName($strNameFormat, $arOneUser).'</a>';
				}
			}
		}
		if (isset($arUserList[$arProfile['MODIFIED_BY']]))
			$strModifiedBy = $arUserList[$arProfile['MODIFIED_BY']];
	}

	$row->AddViewField("CREATED_BY", $strCreatedBy);
	$row->AddCalendarField("DATE_CREATE", false);
	$row->AddViewField("MODIFIED_BY", $strModifiedBy);
	$row->AddCalendarField("TIMESTAMP_X", false);
	
	$arActions = Array();
	$arActions[] = array(
		"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_EDIT"),
		"ACTION" => $lAdmin->ActionRedirect($el_edit_url), 
		"DEFAULT" => true
	);
	$arActions[] = array(
		"TEXT" => GetMessage("YANDEX_TYRBO_API_COPY_PROFILE"),
		"TITLE" => GetMessage("YANDEX_TYRBO_API_COPY_PROFILE"),
		"ACTION" => $lAdmin->ActionRedirect($el_edit_url."&action=copy&".bitrix_sessid_get()),
	);
	if ('Y' == $arProfile['IN_AGENT'])
	{
		$arActions[] = array(
			"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DEL"),
			"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DESCR_DEL"),
			"ACTION" => $lAdmin->ActionRedirect("/bitrix/admin/yandex_profile_list.php?lang=".LANGUAGE_ID."&".bitrix_sessid_get()."&action=agent&PROFILE_ID=".$arProfile['ID']),
		);
	}
	else
	{
		$arActions[] = array(
			"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_AGENT"),
			"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_AGENT_DESCR"),
			"ACTION" => "ShowAgentForm('".$APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID."&".bitrix_sessid_get()."&action=agent&PROFILE_ID=".$arProfile['ID']."');",
		);
	}
	if($POST_RIGHT >= "W")
		$arActions[] = array("ICON" => "delete", "TEXT" => Loc::getMessage("YANDEX_TYRBO_API_DELETE"), "ACTION" => "if(confirm('" . GetMessageJS("YANDEX_TYRBO_API_DEL_CONF") . "')) " . $lAdmin->ActionDoGroup($arProfile["ID"], "delete"));
	
	$row->AddActions($arActions);
}

$arContext = array();
$arContext[] = array(
	"ICON" => "btn_new",
	"TEXT" => Loc::getMessage("YANDEX_TYRBO_API_ADD"),
	"TITLE" => Loc::getMessage("YANDEX_TYRBO_API_ADD"),
	"LINK" => \Yandex\TurboAPI\CYandexTurboAPITools::GetAdminElementEditLink(0, 'yandex_profile_edit.php?ID='),
);

$lAdmin->AddAdminContextMenu($arContext, false);
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("YANDEX_TYRBO_API_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($_GET["success_export"] == "Y")
{
	CAdminMessage::ShowNote(Loc::getMessage("YANDEX_TYRBO_API_SUCCESS"));
}
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
<?
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
		<?=Loc::getMessage("YANDEX_TYRBO_API_NOTES11_EXT", array('#FILE#' => '/bitrix/php_interface/include/yandex_turbo/cron_frame_xml.php'));?><br>
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