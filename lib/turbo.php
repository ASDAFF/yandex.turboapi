<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\TurboAPI;

use Bitrix\Main\Type,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Text\Converter;

Loc::loadMessages(__FILE__);

class Turbo
{
	const PROGRESS_WIDTH = 500;
	
	public static function getStrProrertyValue($arProperties = array())
	{
		$str = '';
		if($arProperties)
		{
			$turboFeed = new \Yandex\TurboAPI\TurboFeed();
			$str .= '<table>';
			foreach($arProperties as $arProperty)
			{
				if($arProperty['USER_TYPE'])
				{
					if(is_array($arProperty['DISPLAY_VALUE']) && $arProperty['DISPLAY_VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['DISPLAY_VALUE'])).'</p></td>';
						$str .= '</tr>';
					}
					elseif($arProperty['DISPLAY_VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['DISPLAY_VALUE']).'</p></td>';
						$str .= '</tr>';
					}
				}
				elseif($arProperty["PROPERTY_TYPE"] == "E")
				{
					$arDisplayValue = array();
					foreach($arProperty['LINK_ELEMENT_VALUE'] as $val)
					{
						$arDisplayValue[] = $val['NAME'];
					}
					if($arDisplayValue)
					{
						$displayCount = count($arDisplayValue);
						if($displayCount == 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
						elseif($displayCount > 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
					}
					unset($arDisplayValue);
				}
				elseif($arProperty["PROPERTY_TYPE"] == "G")
				{
					$arDisplayValue = array();
					foreach($arProperty['LINK_SECTION_VALUE'] as $val)
					{
						$arDisplayValue[] = $val['NAME'];
					}
					if($arDisplayValue)
					{
						$displayCount = count($arDisplayValue);
						if($displayCount == 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
						elseif($displayCount > 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
					}
					unset($arDisplayValue);
				}
				elseif($arProperty["PROPERTY_TYPE"]=="L")
				{
					if(is_array($arProperty['VALUE_ENUM']) && $arProperty['VALUE_ENUM'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['VALUE_ENUM'])).'</p></td>';
						$str .= '</tr>';
					}
					elseif($arProperty['VALUE_ENUM'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['VALUE_ENUM']).'</p></td>';
						$str .= '</tr>';
					}
				}
				else
				{
					if(is_array($arProperty['VALUE']) && $arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['VALUE'])).'</p></td>';
						$str .= '</tr>';
						
						
					}
					elseif($arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["NAME"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['VALUE']).'</p></td>';
						$str .= '</tr>';
					}
				}			
			}
			$str .= '</table>';
		}
		return $str;
	}
	
	public static function getStrUserProrertyValue($arProperties = array())
	{
		$str = '';
		if($arProperties)
		{
			$turboFeed = new \Yandex\TurboAPI\TurboFeed();
			$str .= '<table>';
			foreach($arProperties as $arProperty)
			{
				if($arProperty['USER_TYPE'])
				{
					if(is_array($arProperty['VALUE']) && $arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['VALUE'])).'</p></td>';
						$str .= '</tr>';
					}
					elseif($arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['VALUE']).'</p></td>';
						$str .= '</tr>';
					}
				}
				elseif($arProperty["PROPERTY_TYPE"] == "E")
				{
					$arDisplayValue = array();
					foreach($arProperty['DISPLAY_VALUE']['LINK_ELEMENT_VALUE'] as $val)
					{
						$arDisplayValue[] = $val['NAME'];
					}
					if($arDisplayValue)
					{
						$displayCount = count($arDisplayValue);
						if($displayCount == 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
						elseif($displayCount > 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
					}
					unset($arDisplayValue);
				}
				elseif($arProperty["PROPERTY_TYPE"] == "G")
				{
					$arDisplayValue = array();
					foreach($arProperty['DISPLAY_VALUE']['LINK_SECTION_VALUE'] as $val)
					{
						$arDisplayValue[] = $val['NAME'];
					}
					if($arDisplayValue)
					{
						$displayCount = count($arDisplayValue);
						if($displayCount == 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
						elseif($displayCount > 1)
						{
							$str .= '<tr>';
								$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
								$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arDisplayValue)).'</p></td>';
							$str .= '</tr>';
						}
					}
					unset($arDisplayValue);
				}
				elseif($arProperty["PROPERTY_TYPE"]=="L")
				{
					if(is_array($arProperty['VALUE_ENUM']) && $arProperty['VALUE_ENUM'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['VALUE_ENUM'])).'</p></td>';
						$str .= '</tr>';
					}
					elseif($arProperty['VALUE_ENUM'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['VALUE_ENUM']).'</p></td>';
						$str .= '</tr>';
					}
				}
				else
				{
					if(is_array($arProperty['VALUE']) && $arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting(implode(', ', $arProperty['VALUE'])).'</p></td>';
						$str .= '</tr>';
						
						
					}
					elseif($arProperty['VALUE'])
					{
						$str .= '<tr>';
							$str .= '<td>'.$turboFeed->fullTextFormatting($arProperty["EDIT_FORM_LABEL"]).'</td>';
							$str .= '<td><p class="table__cell__p">'.$turboFeed->fullTextFormatting($arProperty['VALUE']).'</p></td>';
						$str .= '</tr>';
					}
				}			
			}
			$str .= '</table>';
		}
		return $str;
	}
	
	public static function getStrAccordionFields($arContents = array())
	{
		$str = '';
		if($arContents)
		{
			foreach($arContents as $arContent)
			{
				if($arContent['KEY'] == 'accordion')
				{
					if($arContent['VALUE'])
					{
						$str .= '<div data-block="item" data-title="'.$arContent['PARAM_UNIT'].'">';
							$str .= is_array($arContent['VALUE']) ? implode("<br>", $arContent['VALUE']) : $arContent['VALUE'];
						$str .= '</div>';
					}
				}
			}
		}
		return $str;
	}
	
	public static function getStrContentFields($arContents = array())
	{
		$turboFeed = new \Yandex\TurboAPI\TurboFeed();
		$str = '';
		if($arContents)
		{
			foreach($arContents as $arContent)
			{
				if($arContent['KEY'] == 'content')
				{
					if($arContent['VALUE'])
					{
						$str .= ' ' . (is_array($arContent['VALUE']) ? implode("<br>", $arContent['VALUE']) : $arContent['VALUE']);
					}
				}
			}
		}

		return $turboFeed->prepareTurboContent($str);
	}
	
	public static function showProgress($text, $title, $v)
	{
		$v = $v >= 0 ? $v : 0;
		
		if ($v < 100)
		{
			$msg = new \CAdminMessage(array(
				"TYPE" => "PROGRESS",
				"HTML" => true,
				"MESSAGE" => $title,
				"DETAILS" => "#PROGRESS_BAR#<div style=\"width: " . self::PROGRESS_WIDTH . "px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-top: 20px;\">" . Converter::getHtmlConverter()->encode($text) . "</div>",
				"PROGRESS_TOTAL" => 100,
				"PROGRESS_VALUE" => $v,
				"PROGRESS_TEMPLATE" => '#PROGRESS_PERCENT#',
				"PROGRESS_WIDTH" => self::PROGRESS_WIDTH,
			));
		}
		else
		{
			$msg = new \CAdminMessage(array(
				"TYPE" => "OK",
				"MESSAGE" => $title,
				"DETAILS" => $text,
			));
		}
		
		return $msg->show();
	}
	
	public static function getPath()
    {
		return $_SERVER['DOCUMENT_ROOT'].'/'.\COption::GetOptionString('main', 'upload', 'upload').'/yandex_turbo';
    }

	public static function CleanUpReportsAgent()
	{
		if(!\Bitrix\Main\Loader::includeModule('yandex.turboapi'))
			return false;
		
		global $DB;
		$cleanup_days = \COption::GetOptionInt("yandex.turboapi", "file_log_cleanup_days", 7);
		if($cleanup_days > 0)
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$results = $DB->Query("SELECT `NAME` FROM yandex_yandex_turbo_task WHERE DATE_CREATE <= ".$DB->CharToDateFunction(ConvertTimeStamp($date, "FULL")));
			while($row = $results->Fetch())
			{
				if(strlen($row['NAME']) > 0)
				{
					$file = new \Bitrix\Main\IO\File(self::getPath().'/reports/'.$row['NAME']);
					if($file->isExists())
					{
						$file->delete();
					}
				}
			}
		}
		return "\Yandex\TurboAPI\Turbo::CleanUpReportsAgent();";
	}
	

	public static function addFeedArchive($ID)
	{
		$IBLOCK_ID = \CIBlockElement::GetIBlockByID($ID);
		if(intval($IBLOCK_ID) > 0)
		{
			global $DB;
			$arFields = array();
			$results = $DB->Query("SELECT `ID`, `SERVER_ADDRESS`, `DETAIL_URL` FROM `yandex_yandex_turbo_feed` WHERE `IBLOCK_ID` = " .intval($IBLOCK_ID). ";");
			while($row = $results->Fetch())
			{
				$res = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'ID' => $ID), false, false, array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL'));
				$res->SetUrlTemplates($row['DETAIL_URL'], '', '');
				if($arItem = $res->GetNext()) 
				{
					$arFields[] = array(
						'FEED_ID' => $row['ID'],
						'ELEMENT_ID' => $ID,
						'IBLOCK_ID' => $IBLOCK_ID,
						'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
						'LINK' => $row['SERVER_ADDRESS'].$arItem['DETAIL_PAGE_URL'],
						'ITEM' => array(
							'NAME' => $arItem['NAME'],
						),
					);
				}
			}
			if($arFields)
			{
				foreach($arFields as $data)
				{
					\Yandex\TurboAPI\ArchiveFeedTable::add($data);
				}
			}
		}
	}
	
	public function getTemplateList($type = 'element')
	{
		$arResult = array();
		$directory = new \Bitrix\Main\IO\Directory($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/yandex.turboapi/load/templates/'.$type.'/');
		if($directory->isExists())
		{
			$arChildren = $directory->getChildren();
			foreach($arChildren as $k => $child)
			{
				if($child->isExists() && $child->isDirectory())
				{
					$name = $child->getName();
					if(!\Bitrix\Main\Loader::includeModule('catalog'))
					{
						unset($arResult['catalog']);
					}
					$arResult[$name] = $name;
				}				
			}
		}
		$directory = new \Bitrix\Main\IO\Directory($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/include/yandex_turbo/templates/'.$type.'/');
		if($directory->isExists())
		{
			$arChildren = $directory->getChildren();
			foreach($arChildren as $k => $child)
			{
				if($child->isExists() && $child->isDirectory())
				{
					$name = $child->getName();
					$arResult[$name] = $name;
				}				
			}
		}
		ksort($arResult);
		return $arResult;
	}
	
	public static function getMinPriceFromOffersExt(&$offers, $currency = '', $replaceMinPrice = true)
	{
		$replaceMinPrice = ($replaceMinPrice === true);
		$result = false;
		$minPrice = 0;
		if (!empty($offers) && is_array($offers))
		{
			$doubles = array();
			foreach ($offers as $oneOffer)
			{
				if(!$oneOffer["MIN_PRICE"])
					continue;
				$oneOffer['ID'] = (int)$oneOffer['ID'];
				if (isset($doubles[$oneOffer['ID']]))
					continue;

				\CIBlockPriceTools::setRatioMinPrice($oneOffer, $replaceMinPrice);

				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_RATIO'] = $oneOffer['CATALOG_MEASURE_RATIO'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE'] = $oneOffer['CATALOG_MEASURE'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_NAME'] = $oneOffer['CATALOG_MEASURE_NAME'];
				$oneOffer['MIN_PRICE']['~CATALOG_MEASURE_NAME'] = $oneOffer['~CATALOG_MEASURE_NAME'];

				if(empty($result))
				{
					$minPrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: \CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					$result = $oneOffer['MIN_PRICE'];
				}
				else
				{
					$comparePrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: \CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					if ($minPrice > $comparePrice)
					{
						$minPrice = $comparePrice;
						$result = $oneOffer['MIN_PRICE'];
					}
				}
				$doubles[$oneOffer['ID']] = true;
			}
		}
		return $result;
	}
	
	public static function preGenerateExport($feedId)
    {
        $feedId = (int)$feedId;
        if ($feedId <= 0)
            return false;
		
		if(!\Bitrix\Main\Loader::includeModule('yandex.turboapi'))
			return false;

		if(!\Bitrix\Main\Loader::includeModule('iblock'))
			return false;
		
        
		$arFeed = \Yandex\TurboAPI\FeedTable::getById($feedId)->fetch();
		if(!$arFeed)
            return false;
		
		$strFile = '/bitrix/modules/yandex.turboapi/load/turbo_run.php';
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
			return false;
		
		if($arFeed['IS_SECTION'] == 'Y')
		{
			$turboFeed = new \Yandex\TurboAPI\TurboFeedSections($arFeed['ID']);
		}
		else
		{
			$turboFeed = new \Yandex\TurboAPI\TurboFeed($arFeed['ID']);
		}
		$totalItems = intVal($turboFeed->SelectedRowsCount());
		if($totalItems > 0 || $arFeed['IS_NOT_UPLOAD_FEED'] == 'Y')
		{
			 include($_SERVER["DOCUMENT_ROOT"].$strFile);
		}

        return "\Yandex\TurboAPI\Turbo::preGenerateExport(".$feedId.");";
    }
	
	public static function checkTypeCount($totalCount)
	{
		if(is_float($totalCount))
			return floatval($totalCount);
		else
			return intval($totalCount);
	}
	
	public static function getUserType($user_type_id = false)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		
		if(!is_array($USER_FIELD_MANAGER->arUserTypes))
		{
			$USER_FIELD_MANAGER->arUserTypes = array();
		}
		if($user_type_id !== false)
		{
			if(array_key_exists($user_type_id, $USER_FIELD_MANAGER->arUserTypes))
				return $USER_FIELD_MANAGER->arUserTypes[$user_type_id];
			else
				return false;
		}
		else
			return $USER_FIELD_MANAGER->arUserTypes;
	}
}