<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use Bitrix\Main\Localization\Loc;
IncludeModuleLangFile(__FILE__);

if($GLOBALS['APPLICATION']->GetGroupRight("yandex.turboapi") != 'D')
{
	$MODULE_ID = basename(dirname(__FILE__));
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"section" => '',
		"sort" => 50,
		"text" => Loc::getMessage('YANDEX_TYRBO_API_TITLE'),
		"title" => Loc::getMessage('YANDEX_TYRBO_API_TITLE'),
		"icon" => "default_page_icon",
		"page_icon" => "",
		"items_id" => $MODULE_ID."_items",
		"items" => array(
			array(
				"text" => Loc::getMessage("YANDEX_TYRBO_API_LIST"),
				"title" => Loc::getMessage("YANDEX_TYRBO_API_LIST"),
				"items_id" => $MODULE_ID."_items_feed_list",
				"icon" => "default_page_icon",
				"page_icon" => "",
				"items"  => array(
					array(
						"text" => Loc::getMessage("YANDEX_TYRBO_API_LIST_ELEMENTS"),
						"url" => "yandex_feed_list.php?lang=".LANGUAGE_ID,
						"more_url" => array("yandex_feed_edit.php"),
						"title" => Loc::getMessage("YANDEX_TYRBO_API_LIST_ELEMENTS")
					),
					array(
						"text" => Loc::getMessage("YANDEX_TYRBO_API_LIST_SECTIONS"),
						"url" => "yandex_feed_sections_list.php?lang=".LANGUAGE_ID,
						"more_url" => array("yandex_feed_sections_edit.php"),
						"title" => Loc::getMessage("YANDEX_TYRBO_API_LIST_SECTIONS")
					),
				),
			),
			array(
				"text" => Loc::getMessage("YANDEX_TYRBO_API_TASK_LIST"),
				"url" => "yandex_task_list.php?lang=".LANGUAGE_ID,
				"more_url" => array("yandex_task_edit.php"),
				"title" => Loc::getMessage("YANDEX_TYRBO_API_TASK_LIST")
			),
			array(
				"text" => Loc::getMessage("YANDEX_TYRBO_API_PROFILE"),
				"url" => "yandex_profile_list.php?lang=".LANGUAGE_ID,
				"more_url" => array("yandex_profile_edit.php"),
				"title" => Loc::getMessage("YANDEX_TYRBO_API_PROFILE")
			),
			array(
				"text" => Loc::getMessage("YANDEX_TYRBO_API_ADD_LINK_FALSE"),
				"url" => "yandex_add_link_false.php?lang=".LANGUAGE_ID,
				"more_url" => array(),
				"title" => Loc::getMessage("YANDEX_TYRBO_API_ADD_LINK_FALSE")
			),
			array(
				"text" => Loc::getMessage("YANDEX_TYRBO_API_SETTINGS"),
				"url" => "/bitrix/admin/settings.php?mid=yandex.turboapi&lang=".LANGUAGE_ID."&tabControl_active_tab=edit1",
				"more_url" => array(),
				"title" => Loc::getMessage("YANDEX_TYRBO_API_SETTINGS")
			),
		)
	);
	
	return $aMenu;
}
return false;
?>