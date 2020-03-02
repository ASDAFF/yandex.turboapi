<?
use Bitrix\Main\Localization\Loc;
IncludeModuleLangFile(__FILE__);

if($GLOBALS['APPLICATION']->GetGroupRight("goodde.yandexturboapi") != 'D')
{
	$MODULE_ID = basename(dirname(__FILE__));
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"section" => '',
		"sort" => 50,
		"text" => Loc::getMessage('GOODDE_TYRBO_API_TITLE'),
		"title" => Loc::getMessage('GOODDE_TYRBO_API_TITLE'),
		"icon" => "default_page_icon",
		"page_icon" => "",
		"items_id" => $MODULE_ID."_items",
		"items" => array(
			array(
				"text" => Loc::getMessage("GOODDE_TYRBO_API_LIST"),
				"title" => Loc::getMessage("GOODDE_TYRBO_API_LIST"),
				"items_id" => $MODULE_ID."_items_feed_list",
				"icon" => "default_page_icon",
				"page_icon" => "",
				"items"  => array(
					array(
						"text" => Loc::getMessage("GOODDE_TYRBO_API_LIST_ELEMENTS"),
						"url" => "goodde_feed_list.php?lang=".LANGUAGE_ID,
						"more_url" => array("goodde_feed_edit.php"),
						"title" => Loc::getMessage("GOODDE_TYRBO_API_LIST_ELEMENTS")
					),
					array(
						"text" => Loc::getMessage("GOODDE_TYRBO_API_LIST_SECTIONS"),
						"url" => "goodde_feed_sections_list.php?lang=".LANGUAGE_ID,
						"more_url" => array("goodde_feed_sections_edit.php"),
						"title" => Loc::getMessage("GOODDE_TYRBO_API_LIST_SECTIONS")
					),
				),
			),
			array(
				"text" => Loc::getMessage("GOODDE_TYRBO_API_TASK_LIST"),
				"url" => "goodde_task_list.php?lang=".LANGUAGE_ID,
				"more_url" => array("goodde_task_edit.php"),
				"title" => Loc::getMessage("GOODDE_TYRBO_API_TASK_LIST")
			),
			array(
				"text" => Loc::getMessage("GOODDE_TYRBO_API_PROFILE"),
				"url" => "goodde_profile_list.php?lang=".LANGUAGE_ID,
				"more_url" => array("goodde_profile_edit.php"),
				"title" => Loc::getMessage("GOODDE_TYRBO_API_PROFILE")
			),
			array(
				"text" => Loc::getMessage("GOODDE_TYRBO_API_ADD_LINK_FALSE"),
				"url" => "goodde_add_link_false.php?lang=".LANGUAGE_ID,
				"more_url" => array(),
				"title" => Loc::getMessage("GOODDE_TYRBO_API_ADD_LINK_FALSE")
			),
			array(
				"text" => Loc::getMessage("GOODDE_TYRBO_API_SETTINGS"),
				"url" => "/bitrix/admin/settings.php?mid=goodde.yandexturboapi&lang=".LANGUAGE_ID."&tabControl_active_tab=edit1",
				"more_url" => array(),
				"title" => Loc::getMessage("GOODDE_TYRBO_API_SETTINGS")
			),
		)
	);
	
	return $aMenu;
}
return false;
?>