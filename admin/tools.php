<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\TurboAPI;
use	Bitrix\Main,
	Bitrix\Currency,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;
	
if(!Loader::includeModule ('iblock'))
{
	ShowError(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CIBlockPropertyUserID
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "UserID",
			"DESCRIPTION" => Loc::getMessage("IBLOCK_PROP_USERID_DESC"),
			"GetAdminListViewHTML" => array("CIBlockPropertyUserID","GetAdminListViewHTML"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyUserID","GetPropertyFieldHtml"),
			"ConvertToDB" => array("CIBlockPropertyUserID","ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyUserID","ConvertFromDB"),
			"GetSettingsHTML" => array("CIBlockPropertyUserID","GetSettingsHTML"),
		);
	}
	public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		static $cache = array();
		$value = intVal($value["VALUE"]);
		if(!array_key_exists($value, $cache))
		{
			$rsUsers = CUser::GetList($by, $order, array("ID" => $value));
			$cache[$value] = $rsUsers->Fetch();
		}
		$arUser = $cache[$value];
		if($arUser)
		{
			return "[<a title='".Loc::getMessage("MAIN_EDIT_USER_PROFILE")."' href='user_edit.php?ID=".$arUser["ID"]."&lang=".LANG."'>".$arUser["ID"]."</a>] (".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
		}
		else
			return "&nbsp;";
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		global $USER;
		$default_value = intVal($value["VALUE"]);
		$res = "";
		if ($default_value == $USER->GetID())
		{
			$select = "CU";
			$res = "[<a title='".Loc::getMessage("MAIN_EDIT_USER_PROFILE")."'  href='/bitrix/admin/user_edit.php?ID=".$USER->GetID()."&lang=".LANG."'>".$USER->GetID()."</a>] (".htmlspecialcharsbx($USER->GetLogin()).") ".htmlspecialcharsbx($USER->GetFirstName())." ".htmlspecialcharsbx($USER->GetLastName());
		}
		elseif ($default_value > 0)
		{
			$select = "SU";
			$rsUsers = \CUser::GetList($by, $order, array("ID" => $default_value));
			if ($arUser = $rsUsers->Fetch())
				$res = "[<a title='".Loc::getMessage("MAIN_EDIT_USER_PROFILE")."'  href='/bitrix/admin/user_edit.php?ID=".$arUser["ID"]."&lang=".LANG."'>".$arUser["ID"]."</a>] (".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
			else
				$res = "&nbsp;".Loc::getMessage("MAIN_NOT_FOUND");
		}
		else
		{
			$select = "none";
			$default_value = "";
		}
		
		$name_x = preg_replace("/([^a-z0-9])/is", "x", $strHTMLControlName["VALUE"]);
		if (strLen(trim($strHTMLControlName["FORM_NAME"])) <= 0)
			$strHTMLControlName["FORM_NAME"] = "form_element";
		ob_start();
		?><select id="SELECT<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" name="SELECT<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" onchange="if(this.value == 'none')
						{
							var v=document.getElementById('<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>');
							v.value = '';
							v.readOnly = true;
							document.getElementById('FindUser<?=$name_x?>').disabled = true;
						}
						else
						{
							var v=document.getElementById('<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>');
							v.value = this.value == 'CU'?'<?=$USER->GetID()?>':'';
							v.readOnly = false;
							document.getElementById('FindUser<?=$name_x?>').disabled = false;
						}">
					<option value="none"<?if($select=="none")echo " selected"?>><?=Loc::getMessage("IBLOCK_PROP_USERID_NONE")?></option>
					<option value="CU"<?if($select=="CU")echo " selected"?>><?=Loc::getMessage("IBLOCK_PROP_USERID_CURR")?></option>
					<option value="SU"<?if($select=="SU")echo " selected"?>><?=Loc::getMessage("IBLOCK_PROP_USERID_OTHR")?></option>
				</select>&nbsp;
				<?echo self::FindUserIDNew(htmlspecialcharsbx($strHTMLControlName["VALUE"]), $value["VALUE"], $res, htmlspecialcharsEx($strHTMLControlName["FORM_NAME"]), $select);
			$return = ob_get_contents();
			ob_end_clean();
		return  $return;
	}
	public static function ConvertToDB($arProperty, $value)
	{
		$value["VALUE"] = intval($value["VALUE"]);
		if($value["VALUE"] <= 0)
			$value["VALUE"] = "";
		return $value;
	}
	public static function ConvertFromDB($arProperty, $value)
	{
		$value["VALUE"] = intval($value["VALUE"]);
		if($value["VALUE"] <= 0)
			$value["VALUE"] = "";
		return $value;
	}
	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("WITH_DESCRIPTION"),
		);
		return '';
	}
	
	public static function FindUserIDNew($tag_name, $tag_value, $user_name="", $form_name = "form1", $select="none", $tag_size = "3", $tag_maxlength="", $button_value = "...", $tag_class="typeinput", $button_class="tablebodybutton", $search_page="/bitrix/admin/user_search.php")
	{
		global $APPLICATION, $USER;
		$tag_name_x = preg_replace("/([^a-z0-9])/is", "x", $tag_name);
		$tag_name_escaped = \CUtil::JSEscape($tag_name);
		
		if($APPLICATION->GetGroupRight("main") >= "R")
		{
			$strReturn = "
	<input type=\"text\" name=\"".$tag_name."\" id=\"".$tag_name."\" value=\"".($select=="none"?"":$tag_value)."\" size=\"".$tag_size."\" maxlength=\"".$tag_maxlength."\" class=\"".$tag_class."\">
	<IFRAME style=\"width:0px; height:0px; border: 0px\" src=\"javascript:void(0)\" name=\"hiddenframe".$tag_name."\" id=\"hiddenframe".$tag_name."\"></IFRAME>
	<input class=\"".$button_class."\" type=\"button\" name=\"FindUser".$tag_name_x."\" id=\"FindUser".$tag_name_x."\" OnClick=\"window.open('".$search_page."?lang=".LANGUAGE_ID."&FN=".$form_name."&FC=".$tag_name_escaped."', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"".$button_value."\" ".($select=="none"?"disabled":"").">
	<span id=\"div_".$tag_name."\">".$user_name."</span>
	<script>
	";
			if($user_name=="")
				$strReturn.= "var tv".$tag_name_x."='';\n";
			else
				$strReturn.= "var tv".$tag_name_x."='".\CUtil::JSEscape($tag_value)."';\n";
			$strReturn.= "
	function Ch".$tag_name_x."()
	{
		var DV_".$tag_name_x.";
		DV_".$tag_name_x." = document.getElementById(\"div_".$tag_name_escaped."\");
		if (!!DV_".$tag_name_x.")
		{
			if (
				document.".$form_name."
				&& document.".$form_name."['".$tag_name_escaped."']
				&& typeof tv".$tag_name_x." != 'undefined'
				&& tv".$tag_name_x." != document.".$form_name."['".$tag_name_escaped."'].value
			)
			{
				tv".$tag_name_x."=document.".$form_name."['".$tag_name_escaped."'].value;
				if (tv".$tag_name_x."!='')
				{
					DV_".$tag_name_x.".innerHTML = '<i>".Loc::getMessage("MAIN_WAIT")."</i>';
					if (tv".$tag_name_x."!=".intVal($USER->GetID()).")
					{
						document.getElementById(\"hiddenframe".$tag_name_escaped."\").src='/bitrix/admin/get_user.php?ID=' + tv".$tag_name_x."+'&strName=".$tag_name_escaped."&lang=".LANG.(defined("ADMIN_SECTION") && ADMIN_SECTION===true?"&admin_section=Y":"")."';
						document.getElementById('SELECT".$tag_name_escaped."').value = 'SU';
					}
					else
					{
						DV_".$tag_name_x.".innerHTML = '".\CUtil::JSEscape("[<a title=\"".Loc::getMessage("MAIN_EDIT_USER_PROFILE")."\" class=\"tablebodylink\" href=\"/bitrix/admin/user_edit.php?ID=".$USER->GetID()."&lang=".LANG."\">".$USER->GetID()."</a>] (".htmlspecialcharsbx($USER->GetLogin()).") ".htmlspecialcharsbx($USER->GetFirstName())." ".htmlspecialcharsbx($USER->GetLastName()))."';
						document.getElementById('SELECT".$tag_name_escaped."').value = 'CU';
					}
				}
				else
				{
					DV_".$tag_name_x.".innerHTML = '';
					document.getElementById('SELECT".$tag_name_escaped."').value = 'SU';
				}
			}
			else if (
				DV_".$tag_name_x."
				&& DV_".$tag_name_x.".innerHTML.length > 0
				&& document.".$form_name."
				&& document.".$form_name."['".$tag_name_escaped."']
				&& document.".$form_name."['".$tag_name_escaped."'].value == ''
			)
			{
				document.getElementById('div_".$tag_name."').innerHTML = '';
			}
		}
		setTimeout(function(){Ch".$tag_name_x."()},1000);
	}
	Ch".$tag_name_x."();
	//-->
	</script>
	";
		}
		else
		{
			$strReturn = "
				<input type=\"text\" name=\"$tag_name\" id=\"$tag_name\" value=\"$tag_value\" size=\"$tag_size\" maxlength=\"strMaxLenght\">
				<input type=\"button\" name=\"FindUser".$tag_name_x."\" id=\"FindUser".$tag_name_x."\" OnClick=\"window.open('".$search_page."?lang=".LANGUAGE_ID."&FN=$form_name&FC=$tag_name_escaped', '', 'scrollbars=yes,resizable=yes,width=760,height=560,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"$button_value\">
				$user_name
				";
		}
		return $strReturn;
	}
}


class CYandexTurboAPITools
{
	public static $useCatalog;
	public static $iblockInfo;
	public static $useOffers;
	
	public static function isCatalog()
	{
		if(!isset($useCatalog))
			self::$useCatalog = Loader::includeModule('catalog');

		return self::$useCatalog;
	}

	public static function getIblockInfo($iblockId)
	{
		if(!isset(self::$iblockInfo) && self::$useCatalog) {
			$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);
			if(!empty($catalog) && is_array($catalog)) {
				self::$iblockInfo = $catalog;

				self::$useOffers = ($catalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL || $catalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_PRODUCT);
			}
		}
	}

	public static function construct()
	{
		self::isCatalog();
	}
	
	public static function ShowElementField($name, $property_fields, $values, $bVarsFromForm = false)
	{
		global $bCopy;
		$index = 0;
		$show = true;
	
		$MULTIPLE_CNT = intval($property_fields["MULTIPLE_CNT"]);
		if ($MULTIPLE_CNT <= 0 || $MULTIPLE_CNT > 30)
			$MULTIPLE_CNT = 5;
	
		$bInitDef = $bInitDef && (strlen($property_fields["DEFAULT_VALUE"]) > 0);
	
		$cnt = ($property_fields["MULTIPLE"] == "Y"? $MULTIPLE_CNT + ($bInitDef? 1: 0) : 1);
	
		if(!is_array($values))
			$values = array();
	
		$fixIBlock = $property_fields["LINK_IBLOCK_ID"] > 0;
	
		echo '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb'.md5($name).'">';
		foreach ($values as $key=>$val)
		{
			$show = false;
			if ($bCopy)
			{
				$key = "n".$index;
				$index++;
			}
	
			if (is_array($val) && array_key_exists("VALUE", $val))
				$val = $val["VALUE"];
	
			$db_res = \CIBlockElement::GetByID($val);
			$ar_res = $db_res->GetNext();
			echo '<tr><td>'.
			'<input name="'.$name.'" id="'.$name.'['.$key.']" value="'.htmlspecialcharsex($val).'" size="5" type="text">'.
			'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$ar_res["IBLOCK_ID"].'&amp;n='.$name.'&amp;k='.$key.($fixIBlock ? '&amp;iblockfix=y' : '').'\', 900, 700);">'.
			'&nbsp;<span id="sp_'.md5($name).'_'.$key.'" >'.$ar_res['NAME'].'</span>'.
			'</td></tr>';
	
			if ($property_fields["MULTIPLE"] != "Y")
			{
				$bVarsFromForm = true;
				break;
			}
		}
	
		if (!$bVarsFromForm || $show)
		{
			for ($i = 0; $i < $cnt; $i++)
			{
				$val = "";
				$key = "n".$index;
				$index++;
	
				echo '<tr><td>'.
				'<input name="'.$name.'['.$key.']" id="'.$name.'['.$key.']" value="'.htmlspecialcharsex($val).'" size="5" type="text">'.
				'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$property_fields["LINK_IBLOCK_ID"].'&amp;n='.$name.'&amp;k='.$key.($fixIBlock ? '&amp;iblockfix=y' : '').'\', 900, 700);">'.
				'&nbsp;<span id="sp_'.md5($name).'_'.$key.'"></span>'.
				'</td></tr>';
			}
		}
	
		if($property_fields["MULTIPLE"]=="Y")
		{
			echo '<tr><td>'.
				'<input type="button" value="'.Loc::getMessage("IBLOCK_AT_PROP_ADD").'..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$property_fields["LINK_IBLOCK_ID"].'&amp;n='.$name.'&amp;m=y&amp;k='.$key.($fixIBlock ? '&amp;iblockfix=y' : '').'\', 900, 700);">'.
				'<span id="sp_'.md5($name).'_'.$key.'" ></span>'.
				'</td></tr>';
		}
	
		echo '</table>';
		echo '<script type="text/javascript">'."\r\n";
		echo "var MV_".md5($name)." = ".$index.";\r\n";
		echo "function InS".md5($name)."(id, name){ \r\n";
		echo "	oTbl=document.getElementById('tb".md5($name)."');\r\n";
		echo "	oRow=oTbl.insertRow(oTbl.rows.length-1); \r\n";
		echo "	oCell=oRow.insertCell(-1); \r\n";
		echo "	oCell.innerHTML=".
			"'<input name=\"".$name."[n'+MV_".md5($name)."+']\" value=\"'+id+'\" id=\"".$name."[n'+MV_".md5($name)."+']\" size=\"5\" type=\"text\">'+\r\n".
			"'<input type=\"button\" value=\"...\" '+\r\n".
			"'onClick=\"jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=".LANGUAGE_ID."&amp;IBLOCK_ID=".$property_fields["LINK_IBLOCK_ID"]."&amp;n=".$name."&amp;k=n'+MV_".md5($name)."+'".($fixIBlock ? '&amp;iblockfix=y' : '')."\', '+\r\n".
			"' 900, 700);\">'+".
			"'&nbsp;<span id=\"sp_".md5($name)."_'+MV_".md5($name)."+'\" >'+name+'</span>".
			"';";
		echo 'MV_'.md5($name).'++;';
		echo '}';
		echo "\r\n</script>";
	}
	
	public static function ShowUserField($name, $property_fields, $values, $form_name = "preorder_edit_form", $bCopy = false)
	{
		return CIBlockPropertyUserID::GetPropertyFieldHtml(
			array(
				'ID' => 10101,
				'CODE' => $name,
				'PROPERTY_TYPE' => 'S',
				'MULTIPLE' => 'N',
				'USER_TYPE' => 'UserID',
			),
			$values,
			array(
				
					'VALUE' => $name,
					'DESCRIPTION' => $name."[DESCRIPTION]",
					'FORM_NAME' => $form_name,
					'MODE' => 'FORM_FILL',
					'COPY' => "" 
				
			)
		);
	}
	public static function ShowLidField($name, $values = false, $optionAll = false)
	{
		$str = '<select name="'.$name.'">';
		if($optionAll)
			$str .= '<option value="" selected >'.Loc::getMessage("IBLOCK_PROP_USERID_ALL").'</option>';
		$rsSite = \CSite::GetList($by='id', $order='asc', $arFilter=array("ACTIVE" => "Y"));
		while ($arSite = $rsSite->GetNext())
		{
			$str .= '<option value="'.$arSite['LID'].'" '.($values == $arSite['LID'] ? 'selected' : '').'>('.$arSite['LID'].') '.$arSite["NAME"].'</option>';
		}
		$str .= '</select>';
		echo $str;
	}
	
	public static function GetAdminElementEditLink($ELEMENT_ID, $url = '', $arParams = array(), $strAdd = "")
    {
		if($ELEMENT_ID !== null)
			$url.= intval($ELEMENT_ID);
		else
			return false;
		$url.= "&lang=".urlencode(LANGUAGE_ID);
		foreach ($arParams as $name => $value)
			if (isset($value))
				$url.= "&".urlencode($name)."=".urlencode($value);
		
		return $url.$strAdd;
    }
	
	public static function sanitizeUrl($url, $regex = false) 
	{
		// Make sure that the old URL is relative
		$url = preg_replace('@^https?://(.*?)/@', '/', $url);
		$url = preg_replace('@^https?://(.*?)$@', '/', $url);

		// No new lines
		$url = preg_replace("/[\r\n\t].*?$/s", '', $url);

		// Clean control codes
		$url = preg_replace('/[^\PC\s]/u', '', $url);

		// Ensure a slash at start
		if(substr($url, 0, 1) !== '/' && $regex === false) 
		{
			$url = '/'.$url;
		}

		return $url;
	}
	
	// List or section tree
	public static function getCatalogSections($iblockId, $getAll = false)
	{
		if(!$iblockId)
			return false;

		$arSections = array();

		$arSort   = array('left_margin' => 'asc');
		$arSelect = array('ID', 'DEPTH_LEVEL', 'NAME');
		$arFilter = array(
			 'IBLOCK_ID' => $iblockId,
			 'ACTIVE'    => 'Y',
		);

		if(!$getAll)
			$arFilter['DEPTH_LEVEL'] = 1;

		$rsSection = \CIBlockSection::GetList($arSort, $arFilter, false, $arSelect);

		while($arSection = $rsSection->Fetch()) {
			if($arFilter['DEPTH_LEVEL'] == 1)
				$arSections[ $arSection['ID'] ] = $arSection['NAME'];
			else
				$arSections[ $arSection['ID'] ] = str_repeat(' . ', $arSection['DEPTH_LEVEL']) . $arSection['NAME'];
		}
		return $arSections;
	}
	
	// Currency
	public static function getCurrency()
	{
		if(Loader::includeModule('currency')) {
			$currencies = Currency\CurrencyManager::getCurrencyList();
		}
		else {
			$currencies = Loc::getMessage('YANDEX_TYRBO_API_CURRENCIES');
		}

		return (array)$currencies;
	}

	// Currency rates
	public static function getCurrencyRates()
	{
		return Loc::getMessage('YANDEX_TYRBO_API_CURRENCY_RATES');
	}

	// Price types
	public static function getPriceTypes()
	{
		$types = (array)Loc::getMessage('YANDEX_TYRBO_API_OPTIMAL_RICE');

		if(Loader::includeModule('catalog')) {
			$res = \CCatalogGroup::GetList(array('SORT' => 'ASC'), array(), false, false, array('ID', 'NAME', 'NAME_LANG'));
			while($type = $res->Fetch())
				$types[ $type['ID'] ] = $type['NAME_LANG'];
		}

		return $types;
	}

	// Iblock or catalog
	public static function getCatalogs($bUseCatalog = false)
	{
		$arAll = $arIblock = array();

		$bCatalog = Loader::includeModule('catalog');
		if(!$bCatalog)
			$bUseCatalog = false;


		$res = \CIBlock::GetList(array("NAME" => "ASC"));
		while($ar_res = $res->Fetch()) {
			if($bUseCatalog)
				if(!\CCatalog::GetByID($ar_res["ID"]))
					continue;

			$arIblock[] = $ar_res;
		}

		foreach($arIblock as $iBlock) {
			$arIbType = \CIBlockType::GetByIDLang($iBlock['IBLOCK_TYPE_ID'], LANG);

			$arAll[ $arIbType['ID'] ]['ID']                      = $arIbType['ID'];
			$arAll[ $arIbType['ID'] ]['NAME']                    = $arIbType['NAME'] . ' [' . $arIbType['ID'] . ']';
			$arAll[ $arIbType['ID'] ]['IBLOCK'][ $iBlock['ID'] ] = $iBlock['NAME'] . ' [' . $iBlock['ID'] . ']';;
		}

		return $arAll;
	}

	// Sites
	public static function getSites()
	{
		$arSites = array();

		$res = \CSite::GetList($by = "sort", $order = "desc");
		while($ar_res = $res->Fetch()) {
			$arSites[ $ar_res['ID'] ] = $ar_res;
		}

		return $arSites;
	}
	
	public static function getHttpFilePath($path)
	{
		if($path)
			$path = (\CMain::IsHTTPS() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $path;
		else
			$path = Loc::getMessage('YANDEX_TYRBO_API_DEFAULT_FILE_PATH');

		return $path;
	}
	
	// Element field selector
	public static function getOfferFieldsSelect($iblockId)
	{
		static::construct();
		static::getIblockInfo($iblockId);

		static $iblockProps;
		static $offerProps;

		// Element Fields
		$arFields['FIELDS'] = Loc::getMessage('YANDEX_TYRBO_API_OFFER_FIELDS_LANG');

		// Fields offers
		$arFields['OFFER_FIELD'] = Loc::getMessage('YANDEX_TYRBO_API_OFFER_FIELDS_LANG');


		// Element Property
		if(!isset($iblockProps)){
			$res = \CIBlockProperty::GetList(array('NAME' => 'ASC'), array('IBLOCK_ID' => $iblockId, 'ACTIVE' => "Y"));
			while($arProp = $res->Fetch()) {
				$iblockProps[ $arProp['ID'] ] = $arProp;
			}
		}
		if($iblockProps){
			$arFields['PROPERTY'] = $iblockProps;
		}


		// Property offers
		if(!isset($offerProps) && self::$useOffers){
			if($offerIblockId = self::$iblockInfo['IBLOCK_ID']){
				$res = \CIBlockProperty::GetList(array('NAME' => 'ASC'), array('IBLOCK_ID' => $offerIblockId, 'ACTIVE' => "Y"));
				while($arProp = $res->Fetch()) {
					$offerProps[ $arProp['ID'] ] = $arProp;
				}
			}
		}
		if($offerProps){
			$arFields['OFFER_PROPERTY'] = $offerProps;
		}

		// Element Fields
		$arFields["PRODUCT"] = Loc::getMessage('YANDEX_TYRBO_API_CATALOG_FIELDS_LANG');

		// Price
		$arFields['PRICE'] = Loc::getMessage('YANDEX_TYRBO_API_PRICE_FIELDS_LANG');

		//Currency
		$arFields['CURRENCY'] = static::getCurrency();

		// Meta-tag
		$arFields['IPROPERTY'] = Loc::getMessage('YANDEX_TYRBO_API_IPROPERTY_FIELDS_LANG');

		return $arFields;
	}

	public static function showOfferFieldsSelect($arIBlockId = array(), $type = 'FIELD', $value = '')
	{
		$arOptions   = array();
		$arOptions[] = '<option value="">' . Loc::getMessage('YANDEX_TYRBO_API_SELECT_OPTION_EMPTY') . '</option>';

		$arIBlock = self::getOfferFieldsSelect($arIBlockId);

		// Element Fields
		if($type == 'FIELD' && $arIBlock['FIELDS']) {
			foreach($arIBlock["FIELDS"] as $id => $name) {
				$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
				$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
			}

			unset($id,$name,$selected);
		}

		// Fields offers
		if($type == 'OFFER_FIELD' && $arIBlock['OFFER_FIELD']) {
			foreach($arIBlock["OFFER_FIELD"] as $id => $name) {
				$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
				$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
			}
			unset($id,$name,$selected);
		}

		//Element Property
		if($type == 'PROPERTY' && $arIBlock['PROPERTY']) {
			foreach($arIBlock["PROPERTY"] as $id => $fields) {
				$selected    = ((is_array($value) && in_array($fields['CODE'], $value)) || $fields['CODE'] == $value ? ' selected' : '');
				$arOptions[] = "<option value=\"{$fields["CODE"]}\"$selected>[{$id}] {$fields["NAME"]}</option>";
			}

			unset($id,$name,$selected);
		}

		// Property offers
		if($type == 'OFFER_PROPERTY' && $arIBlock['OFFER_PROPERTY']) {
			foreach($arIBlock["OFFER_PROPERTY"] as $id => $fields) {
				$selected    = ((is_array($value) && in_array($fields['CODE'], $value)) || $fields['CODE'] == $value ? ' selected' : '');
				$arOptions[] = "<option value=\"{$fields["CODE"]}\"$selected>[{$id}] {$fields["NAME"]}</option>";
			}

			unset($id,$name,$selected);
		}

		// Element Fields
		if($type == 'PRODUCT' && $arIBlock['PRODUCT']) {
			foreach($arIBlock["PRODUCT"] as $id => $name) {
				$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
				$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
			}
			unset($id,$name,$selected);
		}

		// Price
		if($type == 'PRICE' && $arIBlock['PRICE']) {
			foreach($arIBlock["PRICE"] as $id => $name) {
				$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
				$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
			}
			unset($id,$name,$selected);
		}

		// Currency
		if($type == 'CURRENCY' && $arIBlock['CURRENCY']) {
			foreach($arIBlock["CURRENCY"] as $id => $name) {
				$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
				$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
			}
			unset($id,$name,$selected);
		}

		// Meta-tag
		if($type == 'IPROPERTY' && $arIBlock['IPROPERTY']) {
			foreach($arIBlock["IPROPERTY"] as $group) {
				$arOptions[] = '<optgroup label="'. $group['NAME'] .'">';
				foreach($group["VALUES"] as $id => $name) {
					$selected    = (is_array($value) && in_array($id, $value) || $id == $value) ? ' selected' : '';
					$arOptions[] = "<option value=\"$id\"$selected>[{$id}] {$name}</option>";
				}
				$arOptions[] = '</optgroup>';
			}
			unset($id,$name,$selected);
		}


		$strOptions = implode("\n", $arOptions);

		return $strOptions;
	}

	/** List of "field type" */
	public static function showFieldTypeSelect($iblockId, $typeId, $typeVal, $isXmlProfile = false)
	{
		static::construct();
		static::getIblockInfo($iblockId);

		$options = array();
		if($isXmlProfile)
		{
			$arType = (array)Loc::getMessage('YANDEX_TYRBO_API_FIELD_TYPE_SELECT_XML_PROFILE');
		}
		else
		{
			$arType = (array)Loc::getMessage('YANDEX_TYRBO_API_FIELD_TYPE_SELECT');
		}
			

		if(!self::$useOffers) {
			unset($arType['OFFER_FIELD'], $arType['OFFER_PROPERTY']);
		}

		if(!self::$iblockInfo) {
			unset($arType['PRODUCT'], $arType['PRICE'], $arType['CURRENCY']);
		}

		foreach($arType as $key => $value) {
			$selected = ($key == $typeId ? 'selected' : '');

			$options[] = "<option value=\"{$key}\" {$selected}>{$value}</option>";
		}

		return implode("\n", $options);
	}
	
	//all type descriptions by group
	public static function getOfferTypes()
	{
		$arOfferTypes = array();

		$arExportTypes = array();
		$dir = (__DIR__ . '/type');
		$arFiles = scandir($dir);
		foreach($arFiles as $file) {
			if($file != '.' && $file != '..')
				require($dir . '/' . $file);
		}
		\Bitrix\Main\Type\Collection::sortByColumn($arExportTypes, 'SORT');
		foreach($arExportTypes as $arType) {
			$arOfferTypes[$arType['GROUP']][] = array(
				'CODE' => $arType['CODE'],
				'NAME' => $arType['NAME'],
			);
		}

		return $arOfferTypes;
	}

	//one type descriptions
	public static function getOfferType($type)
	{
		$arExportTypes = array();
		$dir  = (__DIR__ . '/type');

		$arFiles = scandir($dir);
		foreach($arFiles as $file) {
			if($file != '.' && $file != '..')
				require($dir . '/' . $file);
		}

		return $arExportTypes[$type];
	}
	
	//setings profile defaults
	public static function getProfileDefaults()
	{
		$defSite = array();
		$defType = 'ym_simple';

		$arSites = self::getSites();
		foreach($arSites as $arSite)
		{
			if($arSite['DEF'] == 'Y')
				$defSite = $arSite;
		}

		$defSite['SITE_NAME'] = ($defSite['SITE_NAME'] ? $defSite['SITE_NAME'] : $defSite['NAME']);
		$arType = self::getOfferType($defType);

		return array(
			'ACTIVE'          => 'N',
			'NAME'            => $defSite['SITE_NAME'],
			'SITE_ID'         => $defSite['ID'],
			'SORT'            => 500,
			'LIMIT'      	  => 300,
			'SHOP_NAME'       => $defSite['SITE_NAME'],
			'SHOP_COMPANY'    => $defSite['SITE_NAME'],
			'SHOP_URL'        => (\CMain::IsHTTPS() ? 'https://' : 'http://') . $defSite['SERVER_NAME'],
			'DELIVERY'        => unserialize('a:3:{s:4:"cost";a:1:{i:0;s:3:"300";}s:4:"days";a:1:{i:0;s:1:"1";}s:12:"order_before";a:1:{i:0;s:2:"17";}}'),
			'USE_CATALOG'     => 'Y',
			'USE_SUBSECTIONS' => 'N',
			'ELEMENTS_FILTER' => unserialize('a:1:{s:6:"ACTIVE";s:1:"Y";}'),
			'OFFERS_FILTER'   => unserialize('a:1:{s:6:"ACTIVE";s:1:"Y";}'),
			'TYPE'            => $defType,
			'FIELDS'          => $arType['FIELDS'],
		);
	}
}