<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


use Bitrix\Main\Loader,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Currency,
	Bitrix\Main\Web\Json,
	Bitrix\Main\Application,
	 Bitrix\Main\Type\DateTime,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Yandex\TurboAPI\Turbo,
	Yandex\Export\TurboProfileTable,
	Yandex\TurboAPI\Model\Request;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/yandex.turboapi/admin/tools.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

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

CJSCore::Init(array('yandex_turboapi'));
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/iblock/iblock_edit.js');

$arFieldTitle = array();

$columns = TurboProfileTable::getEntity()->getFields();
/** @var \Bitrix\Main\Entity\Field $field */
foreach($columns as $code => $column) 
{
	$arFieldTitle[$column->getName()] = $column->getTitle();
}

$context = Application::getInstance()->getContext();
$documentRoot = Application::getDocumentRoot();
$request = $context->getRequest();
$lang = $context->getLanguage();

$save = trim($request->get('save'));
$apply = trim($request->get('apply'));
$action = trim($request->get('action'));
$update = trim($request->get('update'));

$id = intval($request->get('ID'));
$bCopy = ($action == "copy");
$bUpdate = ($update == "Y");
$bSale = Loader::includeModule('sale');
$catalogIncluded = Loader::includeModule('catalog');
$bIblock = Loader::includeModule('iblock');
$bCurrency = Loader::includeModule('currency');


$aTabs = array(
	array("DIV" => "edit1", "TAB" => Loc::getMessage("YANDEX_TYRBO_API_TAB_MAIN"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_TAB_MAIN")),
	array("DIV" => "edit2", "TAB" => Loc::getMessage("YANDEX_TYRBO_API_TAB_MAIN_PROFILE"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_TAB_MAIN_PROFILE")),
	array("DIV" => "edit3", "TAB" => Loc::getMessage("YANDEX_TYRBO_API_TAB_FILTER"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_TAB_FILTER_TITLE")), 
	array("DIV" => "edit4", "TAB" => Loc::getMessage("YANDEX_TYRBO_API_TAB_OFFER"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_TAB_OFFER_TITLE")),
	array("DIV" => "edit5", "TAB" => Loc::getMessage("YANDEX_TYRBO_API_TAB_RUN"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_TAB_RUN_TITLE")),
);
$tabControl = new CAdminForm("feed_edit", $aTabs);

$message = null;
$bVarsFromForm = false;

if($request->isPost() && ($save!='' || $apply!='') && check_bitrix_sessid())
{
	$arFields = array();
	$POST = $_POST['FEED'];
	$FILTER = $_POST['FILTER'];
	
	if($POST['CURRENCY'])
	{
		foreach($POST['CURRENCY'] as $key => $arCurrency)
		{
			if($arCurrency['ACTIVE'] != 'Y')
				unset($POST['CURRENCY'][$key]);
		}
	}
	if($FILTER['ELEMENTS'])
		$POST['ELEMENTS_FILTER'] = $FILTER['ELEMENTS'];
	else
		$POST['ELEMENTS_FILTER'] = array();
	
	if($FILTER['OFFERS'])
		$POST['OFFERS_FILTER'] = $FILTER['OFFERS'];
	else
		$POST['OFFERS_FILTER'] = array();
	
	if($catalogIncluded && $FILTER['ELEMENTS_CONDITION']) 
	{
		$obCond = new CCatalogCondTree();
		$boolCond = $obCond->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, array());
		$POST['ELEMENTS_CONDITION'] = $obCond->Parse($FILTER['ELEMENTS_CONDITION']);
	}
	else 
	{
		$POST['ELEMENTS_CONDITION'] = array();
	}

	if($catalogIncluded && $FILTER['OFFERS_CONDITION']) 
	{
		$obCond = new CCatalogCondTree();
		$boolCond = $obCond->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, array());
		$POST['OFFERS_CONDITION'] = $obCond->Parse($FILTER['OFFERS_CONDITION']);
	}
	else 
	{
		$POST['OFFERS_CONDITION'] = array();
	}
	
    $POST['LIMIT'] = min(intval($POST['LIMIT']), 1000);
	if(!isset($POST['DELIVERY']))
		$POST['DELIVERY'] = array();

	if(!isset($POST['UTM_TAGS']))
		$POST['UTM_TAGS'] = array();
	if(serialize($POST['UTM_TAGS']) == 'a:2:{s:4:"NAME";a:1:{i:0;s:0:"";}s:5:"VALUE";a:1:{i:0;s:0:"";}}')
		$POST['UTM_TAGS'] = array();

	if(!isset($POST['DIMENSIONS']))
		$POST['DIMENSIONS'] = '';
	
	TurboProfileTable::encodeFields($POST);	
	if($id > 0 && !$bCopy)
	{
		$POST['MODIFIED_BY'] = $GLOBALS['USER']->GetID();
		$result = TurboProfileTable::update($id, $POST);
	}
	else
	{
		$POST['CREATED_BY'] = $GLOBALS['USER']->GetID();
		$result = TurboProfileTable::add($POST);
		if($result->isSuccess())
		{
			$id = $result->getId();
		}
	}

	if($result->isSuccess())
	{
		if ($apply != "")
		  LocalRedirect("/bitrix/admin/yandex_profile_edit.php?ID=".$id."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
		else
		  LocalRedirect("/bitrix/admin/yandex_profile_list.php?lang=".LANG);
	}
	else
	{
		if($e = $result->getErrorMessages())
			$message = new CAdminMessage(Loc::getMessage("YANDEX_TYRBO_API_ERROR").implode("; ",$e));
		$bVarsFromForm = true;
	}
}
//res
if($id > 0) 
{
    if($arFields = TurboProfileTable::getById($id)->fetch())
	{
		TurboProfileTable::decodeFields($arFields);
	}
	else
	{
		$id = 0;
	}
} 

if(empty($arFields)) 
{
	$arFields = \Yandex\TurboAPI\CYandexTurboAPITools::getProfileDefaults();
}

$arFields['FILE_PATH'] = \Yandex\TurboAPI\CYandexTurboAPITools::getHttpFilePath($arFields['FILE_PATH']);
if($catalogIncluded)
{
	$arPriceTypes = CCatalogIBlockParameters::getPriceTypesList();
	$arCurrency = \Yandex\TurboAPI\CYandexTurboAPITools::getCurrency();
	$arCurrencyRates = \Yandex\TurboAPI\CYandexTurboAPITools::getCurrencyRates();
}
$arCatalogs = \Yandex\TurboAPI\CYandexTurboAPITools::getCatalogs($arFields['USE_CATALOG'] == 'Y');
$arCatalogSections = \Yandex\TurboAPI\CYandexTurboAPITools::getCatalogSections($arFields['IBLOCK_ID'], $arFields['USE_SUBSECTIONS'] == 'Y');
$arTypes = \Yandex\TurboAPI\CYandexTurboAPITools::getOfferTypes();

if($bVarsFromForm)
  $DB->InitTableVarsForEdit("yandex_yandex_turbo_profile", "", "str_");

$APPLICATION->SetTitle(($id>0 ? Loc::getMessage("YANDEX_TYRBO_API_EDIT_TITLE").$id : Loc::getMessage("YANDEX_TYRBO_API_ADD_TITLE")));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>Loc::getMessage("YANDEX_TYRBO_API_LIST_XML"),
		"TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_LIST_XML"),
		"LINK"=>"yandex_profile_list.php?lang=".LANG,
		"ICON"=>"btn_list",
	)
);

if($id>0 && !$bCopy)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("YANDEX_TYRBO_API_ADD"),
		"TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_ADD"),
		"LINK"=>"yandex_profile_edit.php?lang=".LANG,
		"ICON"=>"btn_new",
	);
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("YANDEX_TYRBO_API_DELETE"),
		"TITLE"=>Loc::getMessage("YANDEX_TYRBO_API_DELETE"),
		"LINK"=>"javascript:if(confirm('".Loc::getMessage("YANDEX_TYRBO_API_DELETE_CONF")."'))window.location='yandex_profile_list.php?ID=".$id."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
		"ICON"=>"btn_delete",
	);
}
$context = new CAdminContextMenu($aMenu);
$context->Show();

$arItems = CIBlockParameters::GetPathTemplateMenuItems("DETAIL", "__SetUrlVar", "mnu_DETAIL_URL", "DETAIL_URL");
$u = new CAdminPopupEx(
	"mnu_DETAIL_URL",
	$arItems,
	array("zIndex" => 2000)
);
$u->Show();

if($_REQUEST["mess"] == "ok" && $id>0)
  CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("YANDEX_TYRBO_API_SAVED"), "TYPE"=>"OK"));

if($message)
  echo $message->Show();
elseif($redirectElement->LAST_ERROR!="")
  CAdminMessage::ShowMessage($redirectElement->LAST_ERROR);

$tabControl->BeginPrologContent();
?>
<script type="text/javascript">
$(function () {
	//tab2
	$(document).on('click', '.copy_inner .adm-btn-add', function () {
		var curRow = $(this).closest('.copy_row');
		var cloneRow = curRow.clone();
		cloneRow.find('input, select').val('');
		cloneRow.insertAfter(curRow);
	});
	$(document).on('click', '.copy_inner .adm-btn-delete', function () {
		var inner = $(this).closest('.copy_inner');
		var row = $(this).closest('.copy_row');
		if($(inner).find('.copy_row').length > 1){
			$(row).remove();
		}
	});
	//iblock
	$(document).on('change', 'input[name="FEED[USE_CATALOG]"]', function () {
		execAjax('changeUseCatalog');
	});
	$(document).on('change', 'input[name="FEED[USE_SUBSECTIONS]"]', function () {
		execAjax('changeUseSubsections');
	});
	$('#api_iblock_type_id').on('change', 'select', function () {
		$('#api_iblock_id select, #api_iblock_section_id select').hide();
		execAjax('changeIblockTypeId');
	});
	$('#api_iblock_id').on('change', 'select', function () {
		$('#api_iblock_section_id select').hide();
		execAjax('changeIblockId');
	});
	//tab4
	$(document).on('change', 'select[name="FEED[TYPE]"]', function () {
		execAjax('changeXmlOfferType');
		if($('[data-custom-field-add]').length){
			var type = $(this).val();
			if (type === 'ym_simple' || type === 'ym_vendor_model' ) {
				$('[data-custom-field-add]').show();
			} else {
				$('[data-custom-field-add]').hide();
			}
		}
	});
	
	$(document).on('click', '#feed_fields_table .controls .adm-btn-add', function () {
		var curRow = $(this).closest('.field-row');
		var cloneRow = curRow.clone();
		var type = cloneRow.find('.type_row select').val();
		var typeValue = cloneRow.find('.value_row select').val();
		var ipropertyValue = cloneRow.find('[data-useIpropertyValue]');
		var input = $('input', ipropertyValue);
		ipropertyValue.hide();
		input.attr('disabled', 'disabled');	
		if(type === 'IPROPERTY'){
			if (typeValue) {
				ipropertyValue.hide();
				input.attr('disabled', 'disabled');
			} else {
				ipropertyValue.show();
				input.removeAttr('disabled', 'disabled');
			}
		}
		cloneRow.find('.adm-btn[disabled]').removeAttr('disabled');
		cloneRow.insertAfter(curRow);
	});	
});

function changeUseIproperty(_this) {
	var holder = _this.parentNode;
	var ipropertyValue = $('[data-useIpropertyValue]', holder);
	var input = $('input', ipropertyValue);
	var parent = holder.parentNode;
	var typeRow = $('.type_row', parent);
	var type = typeRow.find('select').val();
	
	input.attr('disabled', 'disabled');
	ipropertyValue.hide();
	
	if(type === 'IPROPERTY'){
		if (_this.value) {
			ipropertyValue.hide();
			input.attr('disabled', 'disabled');
		} else {
			ipropertyValue.show();
			input.removeAttr('disabled');
		}
	}
}

$(window).on('load',function(){
	$('select[name="FEED[TYPE]"]').trigger('change');
});

function getDefaultData() {
	var obDefaultData = {
		'FEED[ID]': $('input[type="hidden"][name="ID"]').val(),
		'FEED[IBLOCK_TYPE_ID]': $('select[name="FEED[IBLOCK_TYPE_ID]"]').val(),
		'FEED[IBLOCK_ID]': $('select[name="FEED[IBLOCK_ID]"]').val(),
		'FEED[SECTION_ID][]': $('select[name="FEED[SECTION_ID][]"]').val(),
		'FEED[TYPE]': $('select[name="FEED[TYPE]"]').val(),
		'FEED[USE_CATALOG]': typeof $('input[name="FEED[USE_CATALOG]"]:checked').val() === "undefined" ? '' : 'Y',
		'FEED[USE_SUBSECTIONS]': typeof $('input[name="FEED[USE_SUBSECTIONS]"]:checked').val() === "undefined" ? '' : 'Y',
		'sessid': BX.bitrix_sessid()
	};
	return obDefaultData;
}
	
function customXmlFieldAdd(_this) {
	var customId = ($('#feed_fields_table > tr').length);

	var data = getDefaultData();
	data['isCustom'] = 1;
	data['customId'] = customId;
	data['exec_action'] = 'changeXmlOfferType';

	BX.showWait('wait1');
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: '/bitrix/admin/yandex_turbo_ajax.php',
		data: data,
		async: true,
		error: function (request, error) {
			if (error.length)
				alert('Error ' + error);
		},
		success: function (data) {
			BX.closeWait('wait1');

			if (data.result == 'ok') {
				$('#feed_fields_table tr:last').after(data.html);
			}
			else {
				alert('Error create custom field');
			}
		}
	});

	return false;
}

function __SetUrlVar(id, mnu_id, el_id)
{
	var obj_ta = BX(el_id);
	//IE
	if (document.selection)
	{
		obj_ta.focus();
		var sel = document.selection.createRange();
		sel.text = id;
	}
	//FF
	else if (obj_ta.selectionStart || obj_ta.selectionStart == '0')
	{
		var startPos = obj_ta.selectionStart;
		var endPos = obj_ta.selectionEnd;
		var caretPos = startPos + id.length;
		obj_ta.value = obj_ta.value.substring(0, startPos) + id + obj_ta.value.substring(endPos, obj_ta.value.length);
		obj_ta.setSelectionRange(caretPos, caretPos);
		obj_ta.focus();
	}
	else
	{
		obj_ta.value += id;
		obj_ta.focus();
	}

	BX.fireEvent(obj_ta, 'change');
	obj_ta.focus();
}

function generateTurboFeed(ID, action)
{
	var node = BX('xml_export_run');

	node.style.display = 'block';

	var windowPos = BX.GetWindowSize();
	var pos = BX.pos(node);

	if(pos.top > windowPos.scrollTop + windowPos.innerHeight)
	{
		window.scrollTo(windowPos.scrollLeft, pos.top + 150 - windowPos.innerHeight);
	}

	BX.runFeed(ID, action, 0, '', '');
}

BX.runFeed = function(ID, action, value, pid, NS)
{
	BX.adminPanel.showWait(BX(action+'_button_' + ID));
	BX.ajax.post('/bitrix/admin/yandex_'+action+'.php', {
		lang:'<?=LANGUAGE_ID?>',
		action: action,
		ID: ID,
		value: value,
		pid: pid,
		NS: NS,
		sessid: BX.bitrix_sessid()
	}, function(data)
	{
		BX.adminPanel.closeWait(BX(action+'_button_' + ID));
		BX('xml_export_run_progress').innerHTML = data;
	});
};

BX.finishTurboFeed = function(ID)
{
	tabControl.SelectTab("edit5");
};
BX.hint_replace(BX('hint_elements_catalog_available'), '<?=CUtil::JSEscape(Loc::getMessage('YANDEX_TYRBO_API_FILTER_CATALOG_AVAILABLE_HINT')); ?>');
BX.hint_replace(BX('hint_offers_catalog_available'), '<?=CUtil::JSEscape(Loc::getMessage('YANDEX_TYRBO_API_FILTER_CATALOG_AVAILABLE_HINT')); ?>');
</script>
<div id="wait1" style="position: fixed; float: right; width: 100%; right: 0;"></div>
<?
$tabControl->EndPrologContent();

$tabControl->BeginEpilogContent();
?>
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="update" value="Y">
	<input type="hidden" name="lang" value="<?=$lang;?>">
<? if($id>0 && !$bCopy): ?>
	<input type="hidden" name="ID" value="<?=$id;?>">
<? endif ?>
<?
$tabControl->EndEpilogContent();
//
$tabControl->Begin(array('FORM_ACTION' => $APPLICATION->GetCurPage() . "?lang=" . $lang));
	
	$tabControl->BeginNextFormTab();//tab1
	if(!$bCopy)
		$tabControl->AddViewField('FEED[ID]', $arFieldTitle['ID'] . ':', $arFields['ID']);
	$tabControl->AddCheckBoxField('FEED[ACTIVE]', $arFieldTitle['ACTIVE'], false, array('Y','N'), $arFields['ACTIVE'] != 'N');
	$tabControl->AddEditField('FEED[SORT]', $arFieldTitle['SORT'], true, array('size' => 5), $arFields['SORT']);

	if($arFields['DATE_CREATE'])
		$tabControl->AddViewField('FEED[DATE_CREATE]', $arFieldTitle['DATE_CREATE'] . ':', $arFields['DATE_CREATE']);
	if($arFields['TIMESTAMP_X'])
		$tabControl->AddViewField('FEED[TIMESTAMP_X]', $arFieldTitle['TIMESTAMP_X'] . ':', $arFields['TIMESTAMP_X']);

	$tabControl->AddEditField('FEED[NAME]', $arFieldTitle['NAME'], true, array('size' => 60), $arFields['NAME']);
	?>
	<? $tabControl->BeginCustomField('FEED[LID]', $arFieldTitle['LID'], true); ?>
		<tr>
			<td><?=$tabControl->GetCustomLabelHTML()?></td>
			<td><?=\CSite::SelectBox('FEED[LID]', $arFields['LID'])?></td>
		</tr>
	<?$tabControl->EndCustomField('FEED[LID]'); ?>
	<?
	$tabControl->AddEditField('FEED[LIMIT]', $arFieldTitle['LIMIT'], true, array('size' => 5), $arFields['LIMIT']);

	if(strlen($arFields['FILE_PATH'])>0)
		$tabControl->AddViewField('FEED[FILE_PATH]', $arFieldTitle['FILE_PATH'], '<a href="'. $arFields['FILE_PATH'] .'" target="_blank">'. $arFields['FILE_PATH'] .'</a>');
	else
		$tabControl->AddViewField('FEED[FILE_PATH]', $arFieldTitle['FILE_PATH'], $arFields['FILE_PATH']);

	$tabControl->AddViewField('FEED[LAST_START]', $arFieldTitle['LAST_START'], $arFields['LAST_START']);
	$tabControl->AddViewField('FEED[LAST_END]', $arFieldTitle['LAST_END'], $arFields['LAST_END']);
	$tabControl->AddViewField('FEED[TOTAL_ITEMS]', $arFieldTitle['TOTAL_ITEMS'], $arFields['TOTAL_ITEMS']);
	$tabControl->AddViewField('FEED[TOTAL_ELEMENTS]', $arFieldTitle['TOTAL_ELEMENTS'], $arFields['TOTAL_ELEMENTS']);
	$tabControl->AddViewField('FEED[TOTAL_OFFERS]', $arFieldTitle['TOTAL_OFFERS'], $arFields['TOTAL_OFFERS']);
	$tabControl->AddViewField('FEED[TOTAL_SECTIONS]', $arFieldTitle['TOTAL_SECTIONS'], $arFields['TOTAL_SECTIONS']);
	$tabControl->AddViewField('FEED[TOTAL_RUN_TIME]', $arFieldTitle['TOTAL_RUN_TIME'], $arFields['TOTAL_RUN_TIME']);
	$tabControl->AddViewField('FEED[TOTAL_MEMORY]', $arFieldTitle['TOTAL_MEMORY'], $arFields['TOTAL_MEMORY']);
	
	$tabControl->BeginNextFormTab(); //tab2
	$tabControl->AddEditField('FEED[SHOP_NAME]', $arFieldTitle['SHOP_NAME'], true, array('size' => 80), $arFields['SHOP_NAME']);
	$tabControl->AddEditField('FEED[SHOP_COMPANY]', $arFieldTitle['SHOP_COMPANY'], true, array('size' => 80), $arFields['SHOP_COMPANY']);
	$tabControl->AddEditField('FEED[SHOP_URL]', $arFieldTitle['SHOP_URL'], true, array('size' => 80), $arFields['SHOP_URL']);
	?>
	<?if($arPriceTypes):?>
		<?$tabControl->BeginCustomField('FEED[PRICE_CODE]', $arFieldTitle['PRICE_CODE'], true);?>
		<tr>
			<td><?=$tabControl->GetCustomLabelHTML()?></td>
			<td>
				<select name="FEED[PRICE_CODE]" size="<?=count($arPriceTypes)?>">
					<?foreach($arPriceTypes as $sValue => $sLabel):?>
						<option value="<?=$sValue?>" <?=($arFields['PRICE_CODE'] == $sValue ? 'selected' : '')?>><?=$sLabel?></option>
					<?endforeach;?>
				</select>		
			</td>
		</tr>
		<?$tabControl->EndCustomField('FEED[PRICE_CODE]');?>
	<?endif;?>
	<?$tabControl->BeginCustomField('FEED[DETAIL_URL]', $arFieldTitle['DETAIL_URL'], false);?>
	<tr>
		<td><?=$tabControl->GetCustomLabelHTML()?></td>
		<td>
			<input type="text" size="60" name="FEED[DETAIL_URL]" id="DETAIL_URL" size="55" maxlength="255" value="<?=$arFields['DETAIL_URL']?>">
			<input type="button" id="mnu_DETAIL_URL" value='...'>
		</td>
	</tr>
	<?$tabControl->EndCustomField('FEED[DETAIL_URL]');?>
	<?
	$tabControl->AddSection('FEED[HEADING_SHOP]', Loc::getMessage('YANDEX_TYRBO_API_HEADING_SHOP_DESCRIPTION'));
	$tabControl->AddCheckBoxField('FEED[USE_CATALOG]', $arFieldTitle['USE_CATALOG'], false, array('Y','N'), ($arFields['USE_CATALOG'] == 'Y'));
	$tabControl->AddCheckBoxField('FEED[USE_SUBSECTIONS]', $arFieldTitle['USE_SUBSECTIONS'], false, array('Y','N'), ($arFields['USE_SUBSECTIONS'] == 'Y'));
	?>
	<?$tabControl->BeginCustomField('IBLOCK_TYPE_ID', $arFieldTitle['IBLOCK_TYPE_ID'], true);?>
		<tr>
			<td colspan="2">
				<div id="api_iblock_type_id">
					<select name="FEED[IBLOCK_TYPE_ID]" size="5">
						<? if($arCatalogs): ?>
							<? foreach($arCatalogs as $arCatalog): ?>
								<option value="<?=$arCatalog['ID']?>"<?=((!isset($arFields['IBLOCK_TYPE_ID']) && $arCatalog['DEF'] == 'Y') || ($arCatalog['ID'] == $arFields['IBLOCK_TYPE_ID'])) ? " selected" : ""?>><?=$arCatalog['NAME']?></option>
							<? endforeach; ?>
						<? endif ?>
					</select>
				</div>

				<div id="api_iblock_id">
					<select name="FEED[IBLOCK_ID]" size="5">
						<? if($arFields['IBLOCK_ID'] && $arCatalogs): ?>
							<? foreach($arCatalogs as $arCatalog): ?>
								<? if($arCatalog['IBLOCK']): ?>
									<? foreach($arCatalog['IBLOCK'] as $id => $iblock): ?>
										<?
										if($arCatalog['ID'] != $arFields['IBLOCK_TYPE_ID'])
											continue;

										$selected = ($id == $arFields['IBLOCK_ID'] ? ' selected' : '')
										?>
										<option value="<?=$id?>"<?=$selected?>><?=$iblock?></option>
									<? endforeach ?>
								<? endif ?>
							<? endforeach ?>
						<? endif ?>
					</select>
				</div>
				<?
				$cnt = count($arCatalogSections);
				$attr_size = ($cnt > 20 ? 20 : $cnt);
				?>
				<div id="api_iblock_section_id">
					<select name="FEED[SECTION_ID][]" size="<?=$attr_size?>"
						 <? if(!$arFields['SECTION_ID'] && !$arCatalogSections): ?>  style="display:none"<? endif ?> multiple>
						<option value=""<? if(!$arFields['SECTION_ID'][0]): ?> selected<? endif ?>><?=Loc::getMessage('YANDEX_TYRBO_API_SELECT_OPTION_EMPTY')?></option>
						<? if($arFields['SECTION_ID'] || $arCatalogSections): ?>
							<? foreach($arCatalogSections as $id => $section): ?>
								<?
								$selected = '';
								if($arFields['SECTION_ID'] && in_array($id, $arFields['SECTION_ID']))
									$selected = ' selected';
								?>
								<option value="<?=$id?>"<?=$selected?>><?=$section?></option>
							<? endforeach; ?>
						<? endif ?>
					</select>
				</div>
			</td>
		</tr>
	<?$tabControl->EndCustomField('IBLOCK_TYPE_ID');?>
	<?$tabControl->BeginCustomField('FEED[CURRENCY]', $arFieldTitle['CURRENCY'], true);?>
		<tr class="heading" align="center">
			<td colspan="2"><?=$arFieldTitle['CURRENCY']?></td>
		</tr>
		<tr>
			<td colspan="2">
				<table class="internal" align="center" width="100%">
					<thead>
					<tr class="heading">
						<td align="center" colspan="3"><?=Loc::getMessage('YANDEX_TYRBO_API_TAB_HEADING_SHOP_CURRENCY_CODE')?></td>
						<td align="center"><?=Loc::getMessage('YANDEX_TYRBO_API_TAB_HEADING_SHOP_CURRENCY_RATE')?></td>
						<td align="center"><?=Loc::getMessage('YANDEX_TYRBO_API_TAB_HEADING_SHOP_CURRENCY_PLUS')?></td>
					</tr>
					</thead>
					<tbody>
					<?if($arCurrency):?>
						<?
						foreach($arCurrency as $id => $fullName):?>
							<?
							if(!isset($arFields['CURRENCY'][$id]))
								$arFields['CURRENCY'][$id] = array();
							?>
							<tr>
								<td>
									<?
									$checked = ($arFields['CURRENCY'][$id]['ACTIVE'] == 'Y' ? ' checked' : '');
									?>
									<input type="checkbox" name="FEED[CURRENCY][<?=$id?>][ACTIVE]" value="Y"<?=$checked?>>
								</td>
								<td align="center">
									<?
									$convertFrom = ($arFields['CURRENCY'][$id]['CONVERT_FROM'] ? $arFields['CURRENCY'][$id]['CONVERT_FROM'] : $id);
									?>
									<input type="hidden" name="FEED[CURRENCY][<?=$id?>][CONVERT_FROM]" value="<?=$convertFrom?>" readonly>
								</td>
								<td><?=$fullName?></td>
								<td align="center">
									<select name="FEED[CURRENCY][<?=$id?>][RATE]">
										<?foreach($arCurrencyRates as $rate => $name):?>
											<?
											$selected = ($rate == $arFields['CURRENCY'][$id]['RATE'] ? ' selected' : '');
											?>
											<option value="<?=$rate?>"<?=$selected?>><?=$name?></option>
										<? endforeach ?>
									</select>
								</td>
								<td align="center">+&nbsp;<input type="text" size="5" name="FEED[CURRENCY][<?=$id?>][PLUS]" value="<?=$arFields['CURRENCY'][$id]['PLUS']?>">&nbsp;%
								</td>
							</tr>
						<?endforeach;?>
					<?endif;?>
					</tbody>
				</table>
			</td>
		</tr>
	<?$tabControl->EndCustomField('FEED[CURRENCY]');?>
	<?$tabControl->BeginCustomField('FEED[DELIVERY]', $arFieldTitle['DELIVERY'], true);?>
	<tr class="heading" align="center">
		<td colspan="2">
			<?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_DELIVERY_OPTIONS')?>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<?=BeginNote()?>
			<?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_DELIVERY_NOTE')?>
			<?=EndNote()?>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<div class="copy_inner">
				<? foreach($arFields['DELIVERY']['cost'] as $key => $val): ?>
					<?
					$cost         = $arFields['DELIVERY']['cost'][ $key ];
					$days         = $arFields['DELIVERY']['days'][ $key ];
					$order_before = $arFields['DELIVERY']['order_before'][ $key ];
					?>
					<div class="copy_row">
						<div class="selectors">
							cost= <input type="text" name="FEED[DELIVERY][cost][]" value="<?=$cost?>" size="5">
							&nbsp;
							days= <input type="text" name="FEED[DELIVERY][days][]" value="<?=$days?>" size="5">
							&nbsp;
							order-before= <select name="FEED[DELIVERY][order_before][]">
								<? for($i = 0; $i <= 24; $i++): ?>
									<? $selected = ($i == $order_before ? 'selected' : ''); ?>
									<option value="<?=$i?>" <?=$selected?>><?=$i?></option>
								<? endfor ?>
							</select>
						</div>
						<div class="controls">
							<button type="button" class="adm-btn adm-btn-icon adm-btn-add"></button><button type="button" class="adm-btn adm-btn-icon adm-btn-delete"></button>
						</div>
					</div>
				<? endforeach; ?>
			</div>
		</td>
	</tr>
	<?$tabControl->EndCustomField('FEED[DELIVERY]');?>


	<?$tabControl->BeginCustomField('FEED[DIMENSIONS]', $arFieldTitle['DIMENSIONS'], true);?>
	<tr class="heading" align="center">
		<td colspan="2">
			<?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_DIMENSIONS')?>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<?=BeginNote()?>
			<?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_DIMENSIONS_NOTE')?>
			<?=EndNote()?>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<input type="text" name="FEED[DIMENSIONS]" value="<?=$arFields['DIMENSIONS']?>" size="40" placeholder="#LENGTH#/#WIDTH#/#HEIGHT#">
		</td>
	</tr>
	<? $tabControl->EndCustomField('FEED[DIMENSIONS]'); ?>


	<? $tabControl->BeginCustomField('FEED[UTM_TAGS]', $arFieldTitle['UTM_TAGS'], true); ?>
	<tr class="heading" align="center">
		<td colspan="2">
			<?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_UTM_TAGS')?>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<div style="display: inline-block; text-align: left">
				<?=BeginNote()?>
				<?=Loc::getMessage('YANDEX_TYRBO_API_EADING_UTM_TAGS_NOTE')?>
				<?=EndNote()?>
			</div>
		</td>
	</tr>
	<tr align="center">
		<td colspan="2">
			<div class="copy_inner">
				<?
				//Default value
				if(!$arFields['UTM_TAGS']){
					$arFields['UTM_TAGS'] = array(
						 'NAME' => array(0 => ''),
						 'VALUE' => array(0 => '')
			);
				}
				?>
				<? foreach($arFields['UTM_TAGS']['NAME'] as $pKey => $pName): ?>
					<?
					$pValue = $arFields['UTM_TAGS']['VALUE'][ $pKey ];
					?>
					<div class="copy_row">
						<div class="selectors">
							<select name="FEED[UTM_TAGS][NAME][]">
								<option value=""><?=Loc::getMessage('YANDEX_TYRBO_API_NOT_CHOSEN')?></option>
								<option value="utm_source" <?=($pName == 'utm_source' ? 'selected' : '')?>>utm_source</option>
								<option value="utm_medium" <?=($pName == 'utm_medium' ? 'selected' : '')?>>utm_medium</option>
								<option value="utm_campaign" <?=($pName == 'utm_campaign' ? 'selected' : '')?>>utm_campaign</option>
								<option value="utm_content" <?=($pName == 'utm_content' ? 'selected' : '')?>>utm_content</option>
								<option value="utm_term" <?=($pName == 'utm_term' ? 'selected' : '')?>>utm_term</option>
							</select>&nbsp;<input type="text" name="FEED[UTM_TAGS][VALUE][]" value="<?=$pValue?>" size="40">
						</div>
						<div class="controls">
							<button type="button" class="adm-btn adm-btn-icon adm-btn-add"></button><button type="button" class="adm-btn adm-btn-icon adm-btn-delete"></button>
						</div>
					</div>
				<? endforeach; ?>
			</div>
		</td>
	</tr>
	<?$tabControl->EndCustomField('FEED[UTM_TAGS]');?>
	
	<?$tabControl->BeginNextFormTab();//tab3?>
	<?
	$tabControl->AddSection('FEED[HEADING_ELEMENTS_FILTER]', Loc::getMessage('YANDEX_TYRBO_API_ELEMENTS_FILTER'));
	$tabControl->AddCheckBoxField('FILTER[ELEMENTS][ACTIVE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_ACTIVE'), false, array('Y','N'), ($arFields['ELEMENTS_FILTER']['ACTIVE'] == 'Y'));
	$tabControl->AddCheckBoxField('FILTER[ELEMENTS][ACTIVE_DATE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_ACTIVE_DATE'), false, array('Y','N'), ($arFields['ELEMENTS_FILTER']['ACTIVE_DATE'] == 'Y'));
	$tabControl->AddCheckBoxField('FILTER[ELEMENTS][SECTION_ACTIVE]', Loc::getMessage('YANDEX_TYRBO_API_ELEMENTS_FILTER_SECTION_ACTIVE'), false, array('Y','N'), ($arFields['ELEMENTS_FILTER']['SECTION_ACTIVE'] == 'Y'));
	$tabControl->AddCheckBoxField('FILTER[ELEMENTS][SECTION_GLOBAL_ACTIVE]', Loc::getMessage('YANDEX_TYRBO_API_ELEMENTS_FILTER_SECTION_GLOBAL_ACTIVE'), false, array('Y','N'), ($arFields['ELEMENTS_FILTER']['SECTION_GLOBAL_ACTIVE'] == 'Y'));
	if($catalogIncluded /* && $arCatalog['CATALOG'] == 'Y' */)
	{
		$tabControl->AddCheckBoxField('FILTER[ELEMENTS][AVAILABLE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_CATALOG_AVAILABLE'), false, array('Y','N'), ($arFields['ELEMENTS_FILTER']['AVAILABLE'] == 'Y'));
	}
	if($catalogIncluded /* && $arCatalog['CATALOG'] == 'Y' */)
	{
		$tabControl->BeginCustomField('FILTER_ELEMENTS_CONDITION', '');
		?>
		<tr id="tr_ELEMENTS_CONDITION">
			<td colspan="2">
				<div id="ELEMENTS_CONDITION" style="position: relative; z-index: 1;"></div>
				<?
				$obCond   = new CCatalogCondTree();
				$boolCond = $obCond->Init(
					 BT_COND_MODE_DEFAULT,
					 BT_COND_BUILD_CATALOG,
					 array(
							'FORM_NAME' => 'feed_edit_form',
							'CONT_ID'   => 'ELEMENTS_CONDITION',
							'JS_NAME'   => 'JSCatCond',
							'PREFIX'    => 'FILTER[ELEMENTS_CONDITION]',
					 )
				);
				if(!$boolCond) {
					if($ex = $APPLICATION->GetException())
						echo $ex->GetString() . "<br>";
				}
				else {
					$obCond->Show($arFields['ELEMENTS_CONDITION']);
				}
				?>
			</td>
		</tr>
		<?
		$tabControl->EndCustomField('FILTER_ELEMENTS_CONDITION');
		if($catalogIncluded /* $arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL || $arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_PRODUCT */)
		{
			$tabControl->AddSection('FEED[HEADING_OFFERS_FILTER]', Loc::getMessage('YANDEX_TYRBO_API_OFFERS_FILTER'));
			$tabControl->AddCheckBoxField('FILTER[OFFERS][ACTIVE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_ACTIVE'), false, array('Y','N'), ($arFields['OFFERS_FILTER']['ACTIVE'] == 'Y'));
			$tabControl->AddCheckBoxField('FILTER[OFFERS][ACTIVE_DATE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_ACTIVE_DATE'), false, array('Y','N'), ($arFields['OFFERS_FILTER']['ACTIVE_DATE'] == 'Y'));
			$tabControl->AddCheckBoxField('FILTER[OFFERS][AVAILABLE]', Loc::getMessage('YANDEX_TYRBO_API_FILTER_OFFERS_CATALOG_AVAILABLE'), false, array('Y','N'), ($arFields['OFFERS_FILTER']['AVAILABLE'] == 'Y'));
			$tabControl->BeginCustomField('FILTER_OFFERS_CONDITION', '');
			?>
			<tr id="tr_OFFERS_CONDITIONS">
				<td colspan="2">
					<div id="OFFERS_CONDITION" style="position: relative; z-index: 1;"></div>
					<?
					$obCond   = new CCatalogCondTree();
					$boolCond = $obCond->Init(
						 BT_COND_MODE_DEFAULT,
						 BT_COND_BUILD_CATALOG,
						 array(
								'FORM_NAME' => 'feed_edit_form',
								'CONT_ID'   => 'OFFERS_CONDITION',
								'JS_NAME'   => 'JSCatCond',
								'PREFIX'    => 'FILTER[OFFERS_CONDITION]',
						 )
					);
					if(!$boolCond) {
						if($ex = $APPLICATION->GetException())
							echo $ex->GetString() . "<br>";
					}
					else {
						$obCond->Show($arFields['OFFERS_CONDITION']);
					}
					?>
				</td>
			</tr>
			<?
			$tabControl->EndCustomField('FILTER_OFFERS_CONDITION');
		}
	}
	?>
	<?$tabControl->BeginNextFormTab(); //tab4?>
	
	<?$tabControl->BeginCustomField('FEED[TYPE]', '');?>
	<tr class="heading" align="center">
		<td colspan="2"><?=Loc::getMessage('YANDEX_TYRBO_API_HEADING_TYPE_SWITCH')?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<p>
				<select name="FEED[TYPE]">
					<?foreach($arTypes as $groupName => $types):?>
						<optgroup label="<?=$groupName?>">
							<?foreach($types as $arType):?>
								<?$selected = ($arFields['TYPE'] == $arType['CODE'] ? 'selected="selected"' : '');?>
								<option value="<?=$arType['CODE']?>"<?=$selected?>>&nbsp;&nbsp;<?=$arType['NAME']?></option>
							<?endforeach;?>
						</optgroup>
					<?endforeach;?>
				</select>
			</p>
			<p id="offer_type_desc" style="font-weight:bold"></p>
		</td>
	</tr>
	<tr class="heading" align="center">
		<td><?=Loc::getMessage('YANDEX_TYRBO_API_TAB_HEADING_TYPE_OFFER')?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<table id="feed_fields_table" cellpadding="0" cellspacing="0" width="100%"></table>
		</td>
	</tr>
	<tr class="<?=(in_array($arFields['TYPE'], array('ym_simple', 'ym_vendor_model')) ? '' : 'hide')?>" data-custom-field-add="">
		<td colspan="2" align="center" id="fieldset-item-add-button">
			<br>
			<button class="adm-btn" onclick="customXmlFieldAdd(this); return false;"><?=Loc::getMessage('YANDEX_TYRBO_API_TAB_HEADING_FIELD_ADD')?></button>
		</td>
	</tr>
	<?$tabControl->EndCustomField('FEED[TYPE]');?>
	
	<?$tabControl->BeginNextFormTab(); //tab5?>
	
	<?$tabControl->BeginCustomField('', '');?>
	<tr>
		<td colspan="2">
			<input class="adm-btn adm-btn-save" value="<?=Loc::getMessage("YANDEX_TYRBO_API_XML_EXPORT_RUN")?>" onclick="generateTurboFeed('<?=$ID?>', 'xml_export_run')" name="xml_export_run" id="xml_export_run_button_<?=$ID?>" type="button">
			<br>
			<div id="xml_export_run" style="display: none;">
				<div id="xml_export_run_progress"><?=Turbo::showProgress(Loc::getMessage('YANDEX_RUN_INIT'), Loc::getMessage('YANDEX_RUN_TITLE'), 0)?></div>
			</div>
		</td>
	</tr>
	<?$tabControl->EndCustomField('');?>
	<?
	$tabControl->Buttons(
	  array(
		"disabled"=>false,
		"back_url"=>"yandex_profile_list.php?lang=".LANG,
		
	  )
	);
	?>
<?
$tabControl->Show();
$tabControl->ShowWarnings("yandex_feed_edit", $message);
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>