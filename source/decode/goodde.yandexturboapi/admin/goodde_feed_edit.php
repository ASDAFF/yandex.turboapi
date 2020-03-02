<?
use Bitrix\Main\Loader,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Currency,
	Bitrix\Main\Web\Json,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Goodde\YandexTurbo\Turbo,
	Goodde\YandexTurbo\FeedTable,
	Goodde\YandexTurbo\Model\Request;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/goodde.yandexturboapi/admin/tools.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

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

Loader::includeModule('iblock');
$catalogIncluded = Loader::includeModule('catalog');

CJSCore::Init(array('goodde_yandexturboapi'));
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/iblock/iblock_edit.js');

$POST_RIGHT = $APPLICATION->GetGroupRight("goodde.yandexturboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_MAIN"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_MAIN")), 
	array("DIV" => "edit2", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_FILTER"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_FILTER_TITLE")), 
	array("DIV" => "edit3", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_DEBUG"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_DEBUG_TITLE")), 
	array("DIV" => "edit4", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_PRODUCTION"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_PRODUCTION_TITLE")), 
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$ID = intval($ID);
$IBLOCK_ID = intval($_REQUEST['IBLOCK_ID']);
$message = null;
$bVarsFromForm = false;
$arProviderKeyByIndex = array(
	'call' => 0,
	'chat' => 1,
	'mail' => 2,
	'callback' => 3,
	'facebook' => 4,
	'google' => 5,
	'odnoklassniki' => 6,
	'telegram' => 7,
	'twitter' => 8,
	'viber' => 9,
	'vkontakte' => 10,
	'whatsapp' => 11,
);

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && check_bitrix_sessid())
{
	$FILTER = $_REQUEST['FILTER'];
	$arMap = FeedTable::getMap();
	$arFields = array();
	foreach($arMap as $key => $field)
	{
		if(isset($_REQUEST[$key]) && $field['editable'])
		{
			if(!is_array($_REQUEST[$key]))
			{
				$_REQUEST[$key] = trim($_REQUEST[$key]);
			}
			$arFields[$key] = $_REQUEST[$key];
		}
		elseif($field['data_type'] == 'boolean' && $field['editable'])
		{
			$arFields[$key] = 'N';
		}
	}
	
	if($FILTER['ELEMENTS'])
		$arFields['ELEMENTS_FILTER'] = $FILTER['ELEMENTS'];
	else
		$arFields['ELEMENTS_FILTER'] = array();
	
	if($FILTER['OFFERS'])
		$arFields['OFFERS_FILTER'] = $FILTER['OFFERS'];
	else
		$arFields['OFFERS_FILTER'] = array();
	
	if($catalogIncluded && $FILTER['ELEMENTS_CONDITION']) 
	{
		$obCond = new CCatalogCondTree();
		$boolCond = $obCond->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, array());
		$arFields['ELEMENTS_CONDITION'] = $obCond->Parse($FILTER['ELEMENTS_CONDITION']);
	}
	else 
	{
		$arFields['ELEMENTS_CONDITION'] = array();
	}

	if($catalogIncluded && $FILTER['OFFERS_CONDITION']) 
	{
		$obCond = new CCatalogCondTree();
		$boolCond = $obCond->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, array());
		$arFields['OFFERS_CONDITION'] = $obCond->Parse($FILTER['OFFERS_CONDITION']);
	}
	else 
	{
		$arFields['OFFERS_CONDITION'] = array();
	}
	
	if(is_array($arFields['IPROPERTY_TEMPLATES']) && $ID > 0)
	{
 		$arSetTemlates = array();
		$ipropTemlates = new \Bitrix\Iblock\InheritedProperty\IblockTemplates($IBLOCK_ID);
		foreach($arFields['IPROPERTY_TEMPLATES'] as $k => $arIproperty)
		{
			$arSetTemlates[$k] = $arIproperty['TEMPLATE'];
			if(strlen($arIproperty['TEMPLATE']) <= 0)
				unset($arFields['IPROPERTY_TEMPLATES'][$k]);
		}
		$ipropTemlates->set($arSetTemlates);
		unset($arSetTemlates);
	}

	foreach($arFields['PROPERTY'] as $k => $arProperty)
	{
		if(strlen($arProperty) <= 0)
			unset($arFields['PROPERTY'][$k]);
	}
	$arFields['PROPERTY'] = array_values($arFields['PROPERTY']);
	if(!is_array($arFields['PROPERTY']))
		$arFields['PROPERTY'] = array();
	
	foreach($arFields['OFFERS_PROPERTY'] as $k => $arProperty)
	{
		if(strlen($arProperty) <= 0)
			unset($arFields['OFFERS_PROPERTY'][$k]);
	}
	$arFields['OFFERS_PROPERTY'] = array_values($arFields['OFFERS_PROPERTY']);
	if(!is_array($arFields['OFFERS_PROPERTY']))
		$arFields['OFFERS_PROPERTY'] = array();
	
    $arFields['LIMIT'] = min(intval($arFields['LIMIT']), 1000);
    $arFields['FIELDS']['AMOUNT_ITEM'] = min(intval($arFields['FIELDS']['AMOUNT_ITEM']), 10000);
    $arFields['SERVER_ADDRESS'] = trim($arFields['SERVER_ADDRESS'], '/');
	$arFields['FIELDS']['IS_SUBDOMAIN'] = (isset($arFields['FIELDS']['IS_SUBDOMAIN'])  ? 'Y' : '');
	if($arFields['FIELDS']['IS_SUBDOMAIN'] != 'Y')
	{		
		$arFields['FIELDS']['HOST_ID_SUBDOMAIN'] = $arFields['FIELDS']['IS_SUBDOMAIN'] = '';
	}
    $arFields['RELATED_LIMIT'] = min(intval($arFields['RELATED_LIMIT']), 30);
    $arFields['RELATED_SOURCE'] = trim($arFields['RELATED_SOURCE']);
    $arFields['SHARE_NETWORKS'] = is_array($arFields['SHARE_NETWORKS']) ? $arFields['SHARE_NETWORKS'] : array();

	$arFields['MENU'] = array();
	if(is_array($ids))
	{
		$aMenuLinksTmp = $arFields['MENU'];
		$aMenuSort = Array();
		for($i = 0, $l = count($ids); $i < $l; $i++)
		{
			$num = $ids[$i];
			if (!isset($aMenuLinksTmp[$num-1]) && $only_edit)
				continue;

			if((${"del_".$num}=="Y" || (strlen(${"text_".$num})<=0 && strlen(${"link_".$num})<=0)) && !$only_edit)
			{
				unset($aMenuLinksTmp[$num-1]);
				continue;
			}

			$aMenuLinksTmp[$num-1][0] = ${"text_".$num};
			$aMenuLinksTmp[$num-1][1] = ${"link_".$num};
			
			$aMenuSort[] = IntVal(${"sort_".$num});
		}

		 for($i = 0, $l = count($aMenuSort)-1; $i < $l; $i++)
			for($j = $i + 1, $len = count($aMenuSort); $j < $len; $j++)
				if($aMenuSort[$i]>$aMenuSort[$j])
				{
					$tmpSort = $aMenuLinksTmp[$i];
					$aMenuLinksTmp[$i] = $aMenuLinksTmp[$j];
					$aMenuLinksTmp[$j] = $tmpSort;

					$tmpSort = $aMenuSort[$i];
					$aMenuSort[$i] = $aMenuSort[$j];
					$aMenuSort[$j] = $tmpSort;
				}
		$arFields['MENU'] = $aMenuLinksTmp;
	}
	
	$arFields['FORM'] = $_REQUEST['FORM'];
	$arFields['FEEDBACK'] = $_REQUEST['FEEDBACK'];
	$i = 1;
	$providerValue = '';
	$arRequestFeedback = array();
	foreach($_REQUEST['FEEDBACK']['TYPE'] as $key => $arFeedback)
	{
		if(!isset($arFeedback['DELETE']))
		{
			if($providerValue = $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']])
				$arFeedback['PROVIDER_VALUE'] = array($arFeedback['PROVIDER_KEY'] => $providerValue);
			elseif($providerValue = $arFeedback['PROVIDER_VALUE'][$arProviderKeyByIndex[$arFeedback['PROVIDER_KEY']]])
				$arFeedback['PROVIDER_VALUE'] = array($arFeedback['PROVIDER_KEY'] => $providerValue);
			else
				unset($arFeedback['PROVIDER_VALUE']);					
			
			$arRequestFeedback[$i] = $arFeedback;
			$i++;
		}
	}
	$arFields['FEEDBACK']['TYPE'] = $arRequestFeedback;
	unset($arFeedback, $arRequestFeedback);

	if($ID > 0)
	{
		unset($arFields['IBLOCK_ID']);
		if($arFields['LID']) 
		{
			$arFields['SERVER_ADDRESS'] = Request::getServerAddress($arFields['LID']);
		}
		$arFields['MODIFIED_BY'] = $GLOBALS['USER']->GetID();
		$arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();
		$result = FeedTable::update($ID, $arFields);
	}
	else
	{
		if($_REQUEST['LID']) 
		{
			$arFields['SERVER_ADDRESS'] = Request::getServerAddress($_REQUEST['LID']);
		}
		$arFields['IBLOCK_ID'] = $IBLOCK_ID;
		$arFields['CREATED_BY'] = $GLOBALS['USER']->GetID();
		$arFields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$result = FeedTable::add($arFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
		}
	}

	if($result->isSuccess())
	{
		if ($apply != "")
		  LocalRedirect("/bitrix/admin/goodde_feed_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
		else
		  LocalRedirect("/bitrix/admin/goodde_feed_list.php?lang=".LANG);
	}
	else
	{
		if($e = $result->getErrorMessages())
		  $message = new CAdminMessage(Loc::getMessage("GOODDE_TYRBO_API_ERROR").implode("; ",$e));
		$bVarsFromForm = true;
	}
}
		
$arPubDate = array(
    'DATE_CREATE' => Loc::getMessage("GOODDE_TYRBO_API_DATE_CREATE"),
    'TIMESTAMP_X' => Loc::getMessage("GOODDE_TYRBO_API_TIMESTAMP_X"),
    'ACTIVE_FROM' => Loc::getMessage("GOODDE_TYRBO_API_ACTIVE_FROM"),
);
$arShareNetworks = array(
    'facebook' => 'Facebook',
    'google' => 'Google+',
    'odnoklassniki' => Loc::getMessage("GOODDE_TYRBO_API_OK"),
    'telegram' => 'Telegram',
    'twitter' => 'Twitter',
    'vkontakte' => Loc::getMessage("GOODDE_TYRBO_API_VK"),
);
$arProps = array();
$arLinkProps = array();
$arPrice = array();
$arPropertyCode = array();
$arPropertyId = array();
$arOffersPropertyId = array();
$arGalleryProps = array();
$arIblockTree = array();
$arIblocks = array();
$str_IPROPERTY_TEMPLATES = array();
$res = CIBlockType::GetList(array('name' => 'asc'));
while($arIblockType = $res->Fetch())
{
	if($arIBType = CIBlockType::GetByIDLang($arIblockType["ID"], LANG))
	{
		$arIblockTree[$arIblockType['ID']] = array(
			'NAME' => htmlspecialcharsEx($arIBType["NAME"]),
			'IBLOCK' => array(),
		);
	}   
}
$res = CIBlock::GetList(array('name' => 'asc'), array('ACTIVE' => 'Y'));
while($arIblock = $res->Fetch())
{
	$arIblockTree[$arIblock['IBLOCK_TYPE_ID']]['IBLOCK'][$arIblock['ID']] = $arIblock['NAME'];
	$arIblocks[$arIblock['ID']] = $arIblock['NAME'];
}

///res
if($ID > 0) 
{
    if (!$arFields = FeedTable::getById($ID)->fetch()) 
		$ID = 0;
	if($arFields['IS_SECTION'] == 'Y')
		LocalRedirect("/bitrix/admin/goodde_feed_list.php?lang=".LANG);
	
	$IBLOCK_ID = $arFields['IBLOCK_ID'];
} 

if ($IBLOCK_ID > 0) 
{
    if (empty($arFields['NAME'])) 
	{
        $arFields['NAME'] = $arIblocks[$IBLOCK_ID];
    }
}
if(empty($arFields['SORT'])) 
{
    $arFields['SORT'] = 500;
}
if(empty($arFields['FIGCAPTION_VIDEO'])) 
{
    $arFields['FIGCAPTION_VIDEO'] = Loc::getMessage('GOODDE_TYRBO_API_FIGCAPTION_VIDEO_DEFAULT');
}
if(empty($arFields['TEMPLATE'])) 
{
    $arFields['TEMPLATE'] = '.default';
}
if(empty($arFields['LIMIT'])) 
{
    $arFields['LIMIT'] = 100;
}
if(empty($arFields['FIELDS']['AMOUNT_ITEM'])) 
{
    $arFields['FIELDS']['AMOUNT_ITEM'] = 10000;
}
if(empty($arFields['FIELDS']['IS_SUBDOMAIN'])) 
{
    $arFields['FIELDS']['IS_SUBDOMAIN'] = '';
}
if(empty($arFields['FIELDS']['HOST_ID_SUBDOMAIN'])) 
{
    $arFields['FIELDS']['HOST_ID_SUBDOMAIN'] = '';
}
if(empty($arFields['RELATED_LIMIT'])) 
{
    $arFields['RELATED_LIMIT'] = 4;
}
	
if($ID <= 0)
{
	if(!$arFields['ELEMENTS_FILTER'])
	{
		$arFields['ELEMENTS_FILTER'] = unserialize('a:1:{s:6:"ACTIVE";s:1:"Y";}');
	}
		
	if(!$arFields['OFFERS_FILTER'])
	{
		$arFields['OFFERS_FILTER'] = unserialize('a:1:{s:6:"ACTIVE";s:1:"Y";}');
	}

	if(empty($arFields['MENU'])) 
	{
		$arFields['MENU'][] = array(
			0 => Loc::getMessage("GOODDE_TYRBO_API_MENU_DEFAULT"),
			1 => '/',
		);
	}
}
else
{
	if(empty($arFields['MENU'])) 
	{
		$arFields['MENU'][] = array();
	}	
}

if($IBLOCK_ID > 0) 
{
    $arTemplateList = \Goodde\YandexTurbo\Turbo::getTemplateList();
	$ipropTemlates = new \Bitrix\Iblock\InheritedProperty\IblockTemplates($IBLOCK_ID);
	$str_IPROPERTY_TEMPLATES = $ipropTemlates->findTemplates();
	
	$rsProps = CIBlockProperty::GetList(array('name' => 'asc'), array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y'));
    while ($arProp = $rsProps->Fetch()) 
	{
        $arProps['PROPERTY_' . $arProp['CODE']] = $arProp['NAME'];
		if ($arProp['PROPERTY_TYPE'] != 'F') 
		{
           $arPropertyCode[$arProp['CODE']] = $arProp['NAME'];
           $arPropertyId[$arProp['ID']] = $arProp['NAME'];
        } 
        if ($arProp['PROPERTY_TYPE'] == 'E' && $arProp['LINK_IBLOCK_ID'] == $IBLOCK_ID) 
		{
            $arLinkProps[$arProp['CODE']] = $arProp['NAME'];
        }
        if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['MULTIPLE'] == 'Y') 
		{
            $arGalleryProps[$arProp['CODE']] = $arProp['NAME'];
        }
    }
	
	if($catalogIncluded)
	{
		$arCatalog = \CCatalogSKU::GetInfoByIBlock($IBLOCK_ID);
		if($arCatalog['CATALOG'] == 'Y')
		{
			$arPrice = CCatalogIBlockParameters::getPriceTypesList();
			if($arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL || $arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_PRODUCT)
			{
				$rsProps = CIBlockProperty::GetList(array('name' => 'asc'), array('IBLOCK_ID' => $arCatalog['IBLOCK_ID'], 'ACTIVE' => 'Y', 'MULTIPLE' => 'N'));
				while ($arProp = $rsProps->Fetch()) 
				{
					if($arProp['PROPERTY_TYPE'] != 'F')
					{
					   $arOffersPropertyId[$arProp['ID']] = $arProp['NAME'];
					}
				}
			}
		}		
	}
}

if (!is_array($arFields['SHARE_NETWORKS'])) 
{
    $arFields['SHARE_NETWORKS'] = array();
}

if($bVarsFromForm)
  $DB->InitTableVarsForEdit("goodde_yandex_turbo_feed", "", "str_");

$APPLICATION->SetTitle(($ID>0 ? Loc::getMessage("GOODDE_TYRBO_API_EDIT_TITLE").$ID : Loc::getMessage("GOODDE_TYRBO_API_ADD_TITLE")));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>Loc::getMessage("GOODDE_TYRBO_API_LIST"),
		"TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_LIST_TITLE"),
		"LINK"=>"goodde_feed_list.php?lang=".LANG,
		"ICON"=>"btn_list",
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("GOODDE_TYRBO_API_ADD"),
		"TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_ADD"),
		"LINK"=>"goodde_feed_edit.php?lang=".LANG,
		"ICON"=>"btn_new",
	);
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("GOODDE_TYRBO_API_DELETE"),
		"TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_DELETE"),
		"LINK"=>"javascript:if(confirm('".Loc::getMessage("GOODDE_TYRBO_API_DELETE_CONF")."'))window.location='goodde_feed_list.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
		"ICON"=>"btn_delete",
	);
}

function getEditHtml($key = '', $arFeedback = array())
{
	?>
	<div class="form-block <?=($key == 'template' ? 'template' : '')?>" data-form-block="" <?=($key == 'template' ? 'data-form-block-template=""' : '')?>>
		<span>
			<select name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][STICK]' : '')?>">
				<option <?=($arFeedback['STICK'] == 'left' ? 'selected=""' : '')?> value="left"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_LEFT")?></option>
				<option <?=($arFeedback['STICK'] == 'right' ? 'selected=""' : '')?> value="right"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_RIGHT")?></option>
				<option <?=($arFeedback['STICK'] == 'false' ? 'selected=""' : '')?> value="false"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_FALSE")?></option>
			</select>
		</span>
		<span>
			<select name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_KEY]' : '')?>" data-similar-init="">
				<option <?=($arFeedback['PROVIDER_KEY'] == 'call' ? 'selected=""' : '')?> value="call"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_CALL")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'chat' ? 'selected=""' : '')?> value="chat"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_CHAT")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'mail' ? 'selected=""' : '')?> value="mail"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_MAIL")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'callback' ? 'selected=""' : '')?> value="callback"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_CALLBACK")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'facebook' ? 'selected=""' : '')?> value="facebook"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_FACEBOOK")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'google' ? 'selected=""' : '')?> value="google"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_GOOGLE")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'odnoklassniki' ? 'selected=""' : '')?> value="odnoklassniki"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_ODNOKLASSNIKI")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'telegram' ? 'selected=""' : '')?> value="telegram"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_TELEGRAM")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'twitter' ? 'selected=""' : '')?> value="twitter"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_TWITTER")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'viber' ? 'selected=""' : '')?> value="viber"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_VIBER")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'vkontakte' ? 'selected=""' : '')?> value="vkontakte"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_VKONTAKTE")?></option>
				<option <?=($arFeedback['PROVIDER_KEY'] == 'whatsapp' ? 'selected=""' : '')?> value="whatsapp"><?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_WHATSAPP")?></option>
			</select> 
		</span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][call]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'call' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']] : '')?>"></span>
		<span data-similar-input=""></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][mail]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'mail' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][callback]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'callback' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][facebook]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'facebook' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][google]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'google' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][odnoklassniki]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'odnoklassniki' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][telegram]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'telegram' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][twitter]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'twitter' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][viber]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'viber' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][vkontakte]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'vkontakte' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span data-similar-input=""><input type="text" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][PROVIDER_VALUE][whatsapp]' : '')?>" value="<?=($arFeedback['PROVIDER_KEY'] == 'whatsapp' ? $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']]  : '')?>"></span>
		<span class="form-block-remove"><label title="<?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_DELETE")?>"><img src="/bitrix/themes/.default/images/actions/delete_button.gif" alt="<?=GetMessage("GOODDE_TYRBO_API_FEEDBACK_STICK_OP_DELETE")?>"><input data-remove="<?=($key != 'template' ? 'static' : '')?>" type="checkbox" name="<?=($key != 'template' ? 'FEEDBACK[TYPE]['.$key.'][DELETE]' : '')?>"></label></span>	
	</div>
	<?
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

if($_REQUEST["mess"] == "ok" && $ID>0)
  CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("GOODDE_TYRBO_API_SAVED"), "TYPE"=>"OK"));

if($message)
  echo $message->Show();
elseif($redirectElement->LAST_ERROR!="")
  CAdminMessage::ShowMessage($redirectElement->LAST_ERROR);
?>
<script>
BX.ready(function(){
	BX.bind(BX('select-iblock'), 'change', function () {
		var url = $('option:selected', this).data('url');
		if (url) {
			document.location = url;
		}
	});
	if(BX('feed_fields_table')){	
		execAjax('changeOfferType');
	}
});
var InheritedPropertiesTemplates = new JCInheritedPropertiesTemplates(
	'frm',
	'iblock_templates.ajax.php?ENTITY_TYPE=B&ENTITY_ID=<?echo intval($IBLOCK_ID)?>&bxpublic=y'
);
BX.ready(function(){
	setTimeout(function(){
		InheritedPropertiesTemplates.updateInheritedPropertiesTemplates(true);
	}, 1000);
});

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

$(document).ready(function() {
	if($('[data-subdomain]').length){
		$('input[data-subdomain]').change(function(){
			if($(this).attr('checked') != 'checked')
				$(this).closest('.adm-detail-content-table').find('tr[data-optioncode="HOST_ID_SUBDOMAIN"]').each(function(){
					$(this).css('display','none');
				});
			else
				$(this).closest('.adm-detail-content-table').find('tr[data-optioncode="HOST_ID_SUBDOMAIN"]').each(function(){
					$(this).css('display','');
				});
		});
		$('input[data-subdomain]').change();
	}
});
function getDefaultData() {
	var obData = {
		'FEED[ID]': $('input[type="hidden"][name="ID"]').val(),
		'FEED[IBLOCK_ID]': $('input[type="hidden"][name="IBLOCK_ID"]').val(),
		'FIELDS[SECTION_ID][]': $('select[name="FILTER[SECTIONS_ID][]"]').val(),
		'FEED[USE_CATALOG]': typeof $('input[type="hidden"][name="USE_CATALOG"]').val() === "undefined" ? '' : 'Y',
		'FIELDS[USE_SUBSECTIONS]': typeof $('input[name="FILTER[ELEMENTS][INCLUDE_SUBSECTIONS]"]:checked').val() === "undefined" ? '' : 'Y',
		'sessid': BX.bitrix_sessid()
	};

	return obData;
}
$(function () {
	$(document).on('click', '#feed_fields_table .controls .adm-btn-add', function () {
		var curRow = $(this).closest('.field-row');
		var cloneRow = curRow.clone();
		cloneRow.find('.adm-btn[disabled]').removeAttr('disabled');
		cloneRow.insertAfter(curRow);
	});
});
</script>
<div id="wait1" style="position: fixed; float: right; width: 100%; right: 0;"></div>
<form id="feed_edit_form" method="POST" action="<?echo $APPLICATION->GetCurPage()?>" enctype="multipart/form-data" name="feed_edit_form">
	<?=bitrix_sessid_post();?>
	<?
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	if($ID <= 0)
	{
		?>
		<tr>
			<th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_IBLOCK") ?></th>
			<td width="60%">
				<select name="IBLOCK_ID" required id="select-iblock">
					<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_VYBRAT")?></option>
					<?foreach ($arIblockTree as $arType):?>
						<optgroup label="<?=$arType['NAME']?>">
							<?foreach($arType['IBLOCK'] as $iIblockID => $sIblockName):?>
								<option value="<?=$iIblockID?>"
									data-url="<?=$APPLICATION->GetCurPageParam('IBLOCK_ID=' . $iIblockID, ['IBLOCK_ID'])?>"
									<?=($IBLOCK_ID == $iIblockID ? 'selected' : '')?>>
									<?=$sIblockName?>
								</option>
							<?endforeach;?>
						</optgroup>
					<?endforeach;?>
				</select>
			</td>
		</tr>
		<?
	}
	
	if($IBLOCK_ID > 0)
	{
		if($ID > 0)
		{
			?>
			<tr>
				<th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_IBLOCK")?></th>
				<td width="60%">
					<?foreach($arIblocks as $iIblockID => $sIblockName):?>
						<?if($IBLOCK_ID == $iIblockID):?>
							<?='['.$iIblockID.'] '.$sIblockName?>
						<?endif;?>
					<?endforeach;?>
				</td>
			</tr>
			<?
		}
		?>
		<tr>
            <th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_LID")?></th>
            <td width="60%">
				<?\Goodde\YandexTurbo\CGooddeYandexTurboTools::ShowLidField('LID', $arFields['LID']);?>
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_ACTIVE")?></td>
            <td>
                <input type="checkbox" name="ACTIVE" value="Y" <?=($arFields['ACTIVE'] ? ($arFields['ACTIVE'] == 'Y' ? 'checked' : '') : 'checked')?>>
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_SORT")?></td>
            <td>
                <input type="text" size="10" name="SORT" value="<?=$arFields['SORT']?>">
            </td>
        </tr>
		<tr>
            <th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_LIMIT")?></th>
            <td width="60%">
                <input type="text" size="10" name="LIMIT" id="LIMIT" value="<?=$arFields['LIMIT']?>">
            </td>
        </tr>
		<tr>
            <th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_AMOUNT_ITEM")?></th>
            <td width="60%">
                <input type="text" size="10" name="FIELDS[AMOUNT_ITEM]" id="AMOUNT_ITEM" value="<?=$arFields['FIELDS']['AMOUNT_ITEM']?>">
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_TEMPLATE")?></td>
            <td>
				<select name="TEMPLATE" id="TEMPLATE" style="width:45%">
					<?foreach($arTemplateList as $sValue => $name):?>
						<option value="<?=$sValue?>" <?=($arFields['TEMPLATE'] == $sValue ? 'selected' : '')?>><?=$name?></option>
					<?endforeach;?>
				</select>
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_DETAIL_URL")?></td>
            <td>
                <input type="text" size="60" name="DETAIL_URL" id="DETAIL_URL" size="55" maxlength="255" value="<?=$arFields['DETAIL_URL']?>">
				<input type="button" id="mnu_DETAIL_URL" value='...'>
            </td>
        </tr>
		<?
		if($ID > 0)
		{
			?>
			<tr>
				<td><?=Loc::getMessage("GOODDE_TYRBO_API_IPROPERTY_VALUES_TITLE")?></td>
				<td><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "G_ELEMENT_META_TITLE_".$ID, $str_IPROPERTY_TEMPLATES, "E")?></td>
			</tr>
			<tr>
				<td><?=Loc::getMessage("GOODDE_TYRBO_API_IPROPERTY_VALUES_PAGE_TITLE")?></td>
				<td><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "G_ELEMENT_PAGE_TITLE_".$ID, $str_IPROPERTY_TEMPLATES, "E")?></td>
			</tr>
			<?
		}
		if(!empty($arPrice))
		{
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_PRICE")?></td>
				<td width="60%">
					<select name="PRICE_CODE" id="PRICE_CODE" style="width:45%">
						<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
						<?foreach($arPrice as $sValue => $sLabel):?>
							<option value="<?=$sValue?>" <?=($arFields['PRICE_CODE'] == $sValue ? 'selected' : '')?>><?=$sLabel?></option>
						<?endforeach;?>
					</select>
				</td>
			</tr>
			<?
		}
		?>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_ITEM_STATUS")?></td>
            <td>
				<select name="ITEM_STATUS" style="width:45%">
					<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
					<option value="false" <?=($arFields['ITEM_STATUS'] == 'false' ? 'selected' : '')?>><?=Loc::getMessage("GOODDE_TYRBO_API_ITEM_STATUS_FALSE")?></option>
					<option value="true" <?=($arFields['ITEM_STATUS'] == 'true' ? 'selected' : '')?>><?=Loc::getMessage("GOODDE_TYRBO_API_ITEM_STATUS_TRUE")?></option>
				</select>
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_ALL_FEED")?></td>
            <td>
				<input type="checkbox" name="ALL_FEED" id="ALL_FEED" value="Y" <?=($arFields['ALL_FEED'] == 'Y' ? 'checked="checked"' : '')?>>
            </td>
        </tr>
		
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED") ?></td>
        </tr>
        <tr>
            <th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_NAME") ?></th>
            <td width="60%">
                <input type="text" size="60" name="NAME" id="NAME" value="<?= $arFields['NAME']; ?>">
            </td>
        </tr>
		<?
		if($ID > 0)
		{
			?>
			<tr>
				<th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_SERVER_ADDRESS") ?></th>
				<td width="60%">
					<?=$arFields['SERVER_ADDRESS']?>
				</td>
			</tr>
			<tr>
				<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_IS_DOMAIN") ?></td>
				<td width="60%">
					<input data-subdomain="" id="IS_SUBDOMAIN" type="checkbox" id="IS_SUBDOMAIN" value="Y" name="FIELDS[IS_SUBDOMAIN]" class="adm-designed-checkbox" <?=($arFields['FIELDS']['IS_SUBDOMAIN'] ? 'checked="checked"' : '')?>>
					<label class="adm-designed-checkbox-label" for="IS_SUBDOMAIN"></label>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<?
					echo BeginNote();
						echo Loc::getMessage("GOODDE_TYRBO_API_SUBDOMAIN_NOTE");
					echo EndNote();
					?>
				</td>
			</tr>
			<tr data-optioncode="HOST_ID_SUBDOMAIN">
				<th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_HOST_ID_SUBDOMAIN") ?></th>
				<td width="60%">
					<?if(strlen($arFields['FIELDS']['HOST_ID_SUBDOMAIN']) > 0):?>
						<input type="hidden" id="HOST_ID_SUBDOMAIN" name="FIELDS[HOST_ID_SUBDOMAIN]" value="<?=$arFields['FIELDS']['HOST_ID_SUBDOMAIN']?>">
						<?=$arFields['FIELDS']['HOST_ID_SUBDOMAIN']?>
						<input value="<?=$arFields['FIELDS']['HOST_ID_SUBDOMAIN']?>" name="FIELDS[HOST_ID_SUBDOMAIN]" type="hidden">
					<?else:?>	
						<select name="FIELDS[HOST_ID_SUBDOMAIN]" id="HOST_ID_SUBDOMAIN">
							<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_VYBRAT")?></option>
							<?foreach(\Goodde\YandexTurbo\Model\Request::curHost($arFields['LID']) as $arHost):?>
								<option value="<?=$arHost['host_id']?>"
									<?=($arFields['FIELDS']['HOST_ID_SUBDOMAIN'] == $arHost['host_id'] ? 'selected' : '')?>>
									<?=$arHost['host_id']?>
								</option>
							<?endforeach;?>
						</select>
					<?endif;?>
				</td>			
			</tr>
			<?
		}
		?>
        <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_DESCRIPTION") ?></td>
            <td width="60%">
                <textarea name="DESCRIPTION" cols="60" rows="10"><?=$arFields['DESCRIPTION']?></textarea>
            </td>
        </tr>
		<tr>
            <td><?=Loc::getMessage("GOODDE_TYRBO_API_FIGCAPTION_VIDEO")?></td>
            <td>
                <input type="text" size="60" name="FIGCAPTION_VIDEO" id="FIGCAPTION_VIDEO" value="<?=$arFields['FIGCAPTION_VIDEO']?>">
            </td>
        </tr>
		
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_1") ?></td>
        </tr>
        <tr>
            <th width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_CONTENT") ?></th>
            <td width="60%">
                <select name="CONTENT" id="CONTENT" style="width:45%">
                    <optgroup label="<?=Loc::getMessage("GOODDE_TYRBO_API_FILED_IBLOCK") ?>">
                        <option value="DETAIL_TEXT" <?= ($arFields['CONTENT'] == 'DETAIL_TEXT' ? 'selected' : ''); ?>>
                            <?=Loc::getMessage("GOODDE_TYRBO_API_DETAIL_TEXT") ?></option>
                        <option value="PREVIEW_TEXT" <?= ($arFields['CONTENT'] == 'PREVIEW_TEXT' ? 'selected' : ''); ?>>
                            <?=Loc::getMessage("GOODDE_TYRBO_API_PREVIEW_TEXT") ?></option>
                    </optgroup>
                    <?if(!empty($arProps)):?>
                        <optgroup label="<?=Loc::getMessage("GOODDE_TYRBO_API_PROPERTY_IBLOCK") ?>">
                            <?foreach($arProps as $sValue => $sLabel):?>
                                <option value="<?=strtoupper($sValue) ?>" <?=($arFields['CONTENT'] == $sValue ? 'selected' : '')?>><?=$sLabel?></option>
                            <?endforeach;?>
                        </optgroup>
                    <?endif;?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_PUB_DATE")?></td>
            <td width="60%">
                <select name="PUB_DATE" id="PUB_DATE">
					<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
                    <?foreach($arPubDate as $sValue => $sLabel):?>
                        <option value="<?=$sValue?>" <?=($arFields['PUB_DATE'] == $sValue ? 'selected' : '')?>>
                            <?=$sLabel?>
                        </option>
                    <?endforeach;?>
                </select>
            </td>
        </tr>
		<tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_PROPERTY_TITLE")?></td>
            <td width="60%">
                <select name="PROPERTY[]" id="PROPERTY" multiple size="8" style="width:45%">
				<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
                    <?if($arPropertyId):?>
                            <?foreach($arPropertyId as $sValue => $sLabel):?>
                                <option value="<?=$sValue?>" <?=(in_array($sValue, $arFields['PROPERTY']) ? 'selected' : '')?>><?=$sLabel?></option>
                            <?endforeach; ?>
                    <?endif;?>
                </select>
            </td>
        </tr>
		<?if($arOffersPropertyId):?>
		<tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_OFFERS_PROPERTY_TITLE")?></td>
            <td width="60%">
                <select name="OFFERS_PROPERTY[]" id="OFFERS_PROPERTY" multiple size="8" style="width:45%">
					<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
					<?foreach($arOffersPropertyId as $sValue => $sLabel):?>
						<option value="<?=$sValue?>" <?=(in_array($sValue, $arFields['OFFERS_PROPERTY']) ? 'selected' : '')?>><?=$sLabel?></option>
					<?endforeach; ?>
                </select>
            </td>
        </tr>
		<?endif;?>
		<tr>
			<td colspan="2" align="center">
				<table id="feed_fields_table" cellpadding="0" cellspacing="0" width="100%"></table>
			</td>
        </tr>
		<tr>
			<td colspan="2" align="center" id="fieldset-item-add-button">
				<br>
				<button class="adm-btn" onclick="customFieldAdd(this); return false;"><?=Loc::getMessage('GOODDE_TYRBO_API_HEADING_FIELD_ADD')?></button>
			</td>
		</tr>
		<tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_2") ?></td>
        </tr>
		<tr>
			<td colspan="2">
				<table cellspacing="4"  cellpadding="0" width="100%">
					<tr>
						<td align="center">
							<table cellPadding="2" cellSpacing="2" border="0" width="60%" id="t" class="internal">
								<tr class="heading">
									<td align="center" width="35%"><?=Loc::getMessage("GOODDE_TYRBO_API_MENU_NAME")?></td>
									<td align="center" width="35%"><?=Loc::getMessage("GOODDE_TYRBO_API_MENU_LINK")?></td>
									<td align="center" width="20%"><?=Loc::getMessage("GOODDE_TYRBO_API_MENU_SORT")?></td>
									<td align="center" width="10%"><?=Loc::getMessage("GOODDE_TYRBO_API_MENU_DEL")?></td>
								</tr>
								<?								
								$itemcnt = 0;
								for($i = 1, $l = count($arFields['MENU']); $i <= $l; $i++)
								{
									$itemcnt++;
									$aMenuLinksItem = $arFields['MENU'][$i-1];
									?>
									<tr>
										<input name="ids[]" value="<?=$i?>" type="hidden">
										<td style="padding: 2px;"><input type="text" name="text_<?=$i?>" value="<?=htmlspecialcharsbx($aMenuLinksItem[0])?>" size="40"></td>
										<td style="padding: 2px;"><input type="text" name="link_<?=$i?>" value="<?=htmlspecialcharsbx($aMenuLinksItem[1])?>" size="40"></td>
										<td style="padding: 2px;"><input type="text" name="sort_<?=$i?>" value="<?= $i*10?>" size="5"></td>
										<td align="center" style="padding: 2px;"><input name="del_<?=$i?>" value="Y" id="del_<?=$i?>" class="adm-designed-checkbox" type="checkbox"><label class="adm-designed-checkbox-label" for="del_<?=$i?>" title=""></label></td>
									</tr>
									<tr id="<?= $i?>">
										<td align="right" colspan="4"><input type="button" onClick="AddMenuItem(this)" value="<?=GetMessage("GOODDE_TYRBO_API_MENU_ADD_ITEM")?>"></td>
									</tr>
									<?
								}
								?>
							</table>
						</td>
					</tr>
				</table>
				<input type="hidden" name="itemcnt" value="<?=$itemcnt?>">
			</td>
		</tr>
		<tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_3") ?></td>
        </tr>
		 <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_GALLERY_TITLE")?></td>
            <td width="60%">
                <input type="text" size="60" name="GALLERY_TITLE" id="GALLERY_TITLE" value="<?=$arFields['GALLERY_TITLE']?>">
            </td>
        </tr>
		<tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_RELATED_SOURCE") ?></td>
            <td width="60%">
                <select name="GALLERY" id="GALLERY" style="width:45%">
                    <option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_NOT_CHOSEN")?></option>
                    <?if(!empty($arGalleryProps)):?>
                        <optgroup label="<?=Loc::getMessage("GOODDE_TYRBO_API_PROPERTY_TYPE_FILE") ?>">
                            <?foreach ($arGalleryProps as $sValue => $sLabel): ?>
                                <option value="<?=$sValue?>" <?=($arFields['GALLERY'] == $sValue ? 'selected' : '')?>><?=$sLabel?></option>
                            <?endforeach; ?>
                        </optgroup>
                    <?endif;?>
                </select>
            </td>
        </tr>		
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_4")?></td>
        </tr>
        <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_RELATED_SOURCE")?></td>
            <td width="60%">
                <select name="RELATED_SOURCE" id="RELATED_SOURCE" style="width:45%">
                    <option value="QUEUE" <?=($arFields['RELATED_SOURCE'] == 'QUEUE' ? 'selected' : '')?>><?=Loc::getMessage("GOODDE_TYRBO_API_QUEUE")?></option>
                    <option value="AUTOPARSING" <?=($arFields['RELATED_SOURCE'] == 'AUTOPARSING' ? 'selected' : '')?>><?=Loc::getMessage("GOODDE_TYRBO_API_AUTO_SPARSING")?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_RELATED_LIMIT")?></td>
            <td width="60%">
                <input type="text" size="10" name="RELATED_LIMIT" id="RELATED_LIMIT"  value="<?=$arFields['RELATED_LIMIT']?>">
            </td>
        </tr>
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_SHARE_BUTTON")?></td>
        </tr>
        <tr>
            <td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_SERVICES")?></td>
            <td width="60%">
                <?foreach($arShareNetworks as $sValue => $sLabel):?>
                    <label>
                        <input type="checkbox" name="SHARE_NETWORKS[]" value="<?=$sValue?>" <?=(in_array($sValue, $arFields['SHARE_NETWORKS']) ? 'checked' : '')?>> <?=$sLabel?>
                    </label>
                <?endforeach;?>
            </td>
        </tr>
		
		<tr class="heading">
		  <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_5")?></td>
		</tr>
		<tr>
			<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_RELATED_FORM_AGREEMENT_COMPANY")?>:</td>
			<td width="60%">
				<input type="text" name="FORM[AGREEMENT][COMPANY]" value="<?=$arFields['FORM']['AGREEMENT']['COMPANY']?>" style="width:50%">
			</td>
		</tr>
		<tr>
			<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_RELATED_FORM_AGREEMENT_LINK")?>:</td>
			<td width="60%">
				<input type="text" name="FORM[AGREEMENT][LINK]" value="<?=$arFields['FORM']['AGREEMENT']['LINK']?>" style="width:50%">
			</td>
		</tr>
		
		<tr class="heading">
		  <td colspan="2"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_6")?></td>
		</tr>
		<tr>
			<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_FEEDBACK_SHOW")?>:</td>
			<td width="60%">
				<label><input name="FEEDBACK[SHOW]" id="SHOW" value="Y" <?=($arFields['FEEDBACK']['SHOW'] == 'Y' ? ' checked="checked"' : '')?> class="adm-designed-checkbox" type="checkbox"><label class="adm-designed-checkbox-label" for="SHOW" title=""></label></label>
			</td>
		</tr>
		<tr>
			<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_FEEDBACK_TITLE")?>:</td>
			<td width="60%">
				<input type="text" name="FEEDBACK[TITLE]" value="<?=$arFields['FEEDBACK']['TITLE']?>" style="width:50%">
			</td>
		</tr>				
		<tr>
			<td colspan="2" style="padding: 15px 15px 3px;text-align: center; color: #4B6267;font-weight: bold; border-bottom: 5px solid #E0E8EA;"><?=Loc::getMessage("GOODDE_TYRBO_API_FEED_7")?></td>
		</tr>
		<tr>
			<td width="40%"></td>
			<td width="60%" data-clone-container="">
				<?
				getEditHtml('template');
				if($arFields['FEEDBACK']['TYPE'])
				{
					foreach($arFields['FEEDBACK']['TYPE'] as $key => $arFeedback)
					{
						getEditHtml($key, $arFeedback);
					}
				}			
				?>
				<div data-block-target=""></div>
				<div class="add-more" ><span class="adm-btn" data-add-more=""><span><?=Loc::getMessage("GOODDE_TYRBO_API_FEEDBACK_TYPE_ADD")?></span></span></div>
			</td>
		</tr>
		<?
	}
	$tabControl->BeginNextTab();
	?>
	<tr class="heading" align="center">
		<td colspan="2"><?=Loc::getMessage('GOODDE_TYRBO_API_ELEMENTS_FILTER')?></td>
	</tr>
	<tr>
		<td width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_FILTER_ACTIVE')?></td>
		<td width="60%">
			<input name="FILTER[ELEMENTS][ACTIVE]" value="Y" type="checkbox"<?=($arFields['ELEMENTS_FILTER']['ACTIVE'] == 'Y' ? ' checked' : '')?>>
		</td>
	</tr>
	<tr>
		<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_ACTIVE_DATE")?></td>
		<td width="60%">
			<input type="checkbox" name="FILTER[ELEMENTS][ACTIVE_DATE]" value="Y" <?=($arFields['ELEMENTS_FILTER']['ACTIVE_DATE'] == 'Y' ? 'checked' : '')?>>
		</td>
	</tr>
	<tr>
		<td width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_ELEMENTS_FILTER_SECTION_ACTIVE')?></td>
		<td width="60%">
			<input name="FILTER[ELEMENTS][SECTION_ACTIVE]" value="Y" type="checkbox"<?=($arFields['ELEMENTS_FILTER']['SECTION_ACTIVE'] == 'Y' ? ' checked' : '')?>>
		</td>
	</tr>
	<tr>
		<td width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_ELEMENTS_FILTER_SECTION_GLOBAL_ACTIVE')?></td>
		<td width="60%">
			<input name="FILTER[ELEMENTS][SECTION_GLOBAL_ACTIVE]" value="Y" type="checkbox"<?=($arFields['ELEMENTS_FILTER']['SECTION_GLOBAL_ACTIVE'] == 'Y' ? ' checked' : '')?>>
		</td>
	</tr>
	<?
	if($catalogIncluded && $arCatalog['CATALOG'] == 'Y')
	{
		?>
		<tr>
			<td width="40%"><span id="hint_elements_catalog_available"></span>&nbsp;<?=Loc::getMessage('GOODDE_TYRBO_API_FILTER_CATALOG_AVAILABLE')?></td>
			<td width="60%">
				<input name="FILTER[ELEMENTS][CATALOG_AVAILABLE]" value="Y" type="checkbox"<?=($arFields['ELEMENTS_FILTER']['CATALOG_AVAILABLE'] == 'Y' ? ' checked' : '')?>>
			</td>
		</tr>
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
		if($arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL || $arCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_PRODUCT)
		{
			?>
			<tr class="heading" align="center">
				<td colspan="2"><?=Loc::getMessage('GOODDE_TYRBO_API_OFFERS_FILTER')?></td>
			</tr>
			<tr>
				<td width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_FILTER_ACTIVE')?></td>
				<td width="60%">
					<input name="FILTER[OFFERS][ACTIVE]" value="Y" type="checkbox"<?=($arFields['OFFERS_FILTER']['ACTIVE'] == 'Y' ? ' checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_FILTER_ACTIVE_DATE")?></td>
				<td width="60%">
					<input type="checkbox" name="FILTER[OFFERS][ACTIVE_DATE]" value="Y" <?=($arFields['OFFERS_FILTER']['ACTIVE_DATE'] == 'Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td width="40%"><span id="hint_offers_catalog_available"></span>&nbsp;<?=Loc::getMessage('GOODDE_TYRBO_API_FILTER_CATALOG_AVAILABLE')?></td>
				<td width="60%">
					<input name="FILTER[OFFERS][CATALOG_AVAILABLE]" value="Y" type="checkbox"<?=($arFields['OFFERS_FILTER']['CATALOG_AVAILABLE'] == 'Y' ? ' checked' : '')?>>
				</td>
			</tr>
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
		}
	}
	?>
	<?
	$tabControl->BeginNextTab();
	?>
	<input class="adm-btn-save" value="<?=Loc::getMessage("GOODDE_TYRBO_API_RUN")?>" onclick="generateTurboFeed('<?=$ID?>', 'debug_run')" name="debug_run" id="feed_debug_run_button_<?=$ID?>" type="button">
	<?
	$tabControl->BeginNextTab();
	?>
	<input class="adm-btn-save" value="<?=Loc::getMessage("GOODDE_TYRBO_API_RUN")?>" onclick="generateTurboFeed('<?=$ID?>', 'production_run')" name="production_run" id="feed_production_run_button_<?=$ID?>" type="button">
	<?
	$tabControl->Buttons(
	  array(
		"disabled"=>false,
		"back_url"=>"goodde_feed_list.php?lang=".LANG,
		
	  )
	);
	?>
	<input type="hidden" name="USE_CATALOG" value="<?=($catalogIncluded ? 'Y' : '')?>">
	<input type="hidden" name="IBLOCK_ID" value="<?=$IBLOCK_ID?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<?if($ID>0 && !$bCopy):?>
	  <input type="hidden" name="ID" value="<?=$ID?>">
	<?endif;?>
	<?
	$tabControl->End();
	?>
</form>
<?
$tabControl->ShowWarnings("goodde_feed_edit", $message);
?>
<script>
function generateTurboFeed(ID, action)
{
	var node = BX('turbo_feed_run');

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
	BX.adminPanel.showWait(BX('feed_'+action+'_button_' + ID));
	BX.ajax.post('/bitrix/admin/turbo_'+action+'.php', {
		lang:'<?=LANGUAGE_ID?>',
		action: action,
		ID: ID,
		value: value,
		pid: pid,
		NS: NS,
		sessid: BX.bitrix_sessid()
	}, function(data)
	{
		BX.adminPanel.closeWait(BX('feed_'+action+'_button_' + ID));
		BX('turbo_feed_progress').innerHTML = data;
	});
};

BX.finishTurboFeed = function(ID)
{
	if(ID > 0){
		tabControl.SelectTab("edit3");
		document.location = "/bitrix/admin/goodde_task_edit.php?ID="+ID+"&lang=<?=LANGUAGE_ID?>";
	}
	else{
		tabControl.SelectTab("edit4");
		document.location = "/bitrix/admin/goodde_task_list.php?lang=<?=LANGUAGE_ID?>";
	}
};

function AddMenuItem(ob)
{
	var
		f = document.feed_edit_form,
		tbl = document.getElementById("t"),
		row = ob.parentNode.parentNode,
		curnum = parseInt(row.id),
		srt = 10;

	if(document.feed_edit_form["sort_"+curnum])
		srt = parseInt(document.feed_edit_form["sort_"+curnum].value) + 10;

	for(var i=1; i<=f.itemcnt.value; i++)
	{
		var s = document.feed_edit_form["sort_"+i];
		if(s)
		{
			s = parseInt(s.value);
			if(s>=srt)
				document.feed_edit_form["sort_"+i].value = s + 10;
		}
	}

	var num = row.rowIndex / 2;
	var nums = parseInt(f.itemcnt.value) + 1;
	var oRow = tbl.insertRow(num * 2 + 1);
	var oCell = oRow.insertCell(-1);
	oRow.id = nums;

	oCell.className = '';
	oCell.align='right';
	oCell.colSpan="4";
	oCell.innerHTML = '<input type="button" onClick="AddMenuItem(this)" value="<?=GetMessage("GOODDE_TYRBO_API_MENU_ADD_ITEM")?>">';

	oRow = tbl.insertRow(num * 2 + 1);

	var cond_str = '<?= $cond_str?>';
	cond_str = cond_str.replace(/tmp_menu_item_id/ig, nums);

	var code = [], start, end, i, cnt;
	while((start = cond_str.indexOf('<' + 'script>')) != -1)
	{
		var end = cond_str.indexOf('</' + 'script>', start);
		if(end == -1)
			break;
		code[code.length] = cond_str.substr(start + 8, end - start - 8);
		cond_str = cond_str.substr(0, start) + cond_str.substr(end + 9);
	}

	for(var i = 0, cnt = code.length; i < cnt; i++)
		if(code[i] != '')
			jsUtils.EvalGlobal(code[i]);

	oCell = oRow.insertCell(-1);
	oCell.style = 'padding: 2px;';
	oCell.innerHTML =
		'<input type="hidden" name="ids[]" value="'+nums+'">'+
		'<input type="text" size="40" name="text_'+nums+'" value="">';
	
	oCell = oRow.insertCell(-1);
	oCell.style = 'padding: 2px;';
	oCell.innerHTML =
		'<input type="text" size="40" name="link_'+nums+'" value="">';
	
	oCell = oRow.insertCell(-1);
	oCell.style = 'padding: 2px;';
	oCell.innerHTML =
		'<input type="text" size="5" name="sort_'+nums+'" value="'+srt+'">';
	
	oCell = oRow.insertCell(-1);
	oCell.align='center';
	oCell.style = 'padding: 2px;';
	oCell.innerHTML =
		'<input type="checkbox" name="del_'+nums+'" value="Y" class="adm-designed-checkbox"><label class="adm-designed-checkbox-label" for="del_'+nums+'" title=""></label>';

	f.itemcnt.value = nums;
}
BX.hint_replace(BX('hint_elements_catalog_available'), '<?=CUtil::JSEscape(Loc::getMessage('GOODDE_TYRBO_API_FILTER_CATALOG_AVAILABLE_HINT')); ?>');
BX.hint_replace(BX('hint_offers_catalog_available'), '<?=CUtil::JSEscape(Loc::getMessage('GOODDE_TYRBO_API_FILTER_CATALOG_AVAILABLE_HINT')); ?>');
</script>

<div id="turbo_feed_run" style="display: none;">
	<div id="turbo_feed_progress"><?=Turbo::showProgress(Loc::getMessage('GOODDE_RUN_INIT'), Loc::getMessage('GOODDE_RUN_TITLE'), 0)?></div>
</div>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>