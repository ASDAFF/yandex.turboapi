<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

namespace Yandex\TurboAPI;

use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Iblock\Component\ElementList;

Loc::loadMessages(__FILE__);

class Condition extends ElementList
{
	/**
	* Return parsed conditions array.
	*
	* @param $condition
	* @param $params
	* @return array
	*/
	 
	public function getConditionFilter(array $condition, array $params)
	{
		if(Loader::includeModule('catalog') && isset($condition) && is_array($condition))
		{
			try
			{
				$arFilter = $this->parseCondition($condition, $params);
			}
			catch (\Exception $e)
			{
				$arFilter = array();
			}
		}
		else
		{
			$arFilter = array();
		}	
		return $arFilter;
	}
	
	protected function parseConditionName(array $condition)
	{
		$name = '';
		$conditionNameMap = array(
			'CondIBElement'        => 'ID',
			'CondIBIBlock'         => 'IBLOCK_ID',
			'CondIBSection'        => 'SECTION_ID',
			'CondIBCode'           => 'CODE',
			'CondIBXmlID'          => 'XML_ID',
			'CondIBName'           => 'NAME',
			'CondIBDateActiveFrom' => 'DATE_ACTIVE_FROM',
			'CondIBDateActiveTo'   => 'DATE_ACTIVE_TO',
			'CondIBSort'           => 'SORT',
			'CondIBPreviewText'    => 'PREVIEW_TEXT',
			'CondIBDetailText'     => 'DETAIL_TEXT',
			'CondIBDateCreate'     => 'DATE_CREATE',
			'CondIBCreatedBy'      => 'CREATED_BY',
			'CondIBTimestampX'     => 'TIMESTAMP_X',
			'CondIBModifiedBy'     => 'MODIFIED_BY',
			'CondIBTags'           => 'TAGS',
			'CondCatQuantity'      => 'CATALOG_QUANTITY',
			'CondCatWeight'        => 'CATALOG_WEIGHT',
			'CondCatVatID'         => 'CATALOG_VAT_ID',
			'CondCatVatIncluded'   => 'CATALOG_VAT_INCLUDED',
			//Not Found
			'CondIBActive'         => 'ACTIVE',
		);

		if (isset($conditionNameMap[$condition['CLASS_ID']]))
		{
			$name = $conditionNameMap[$condition['CLASS_ID']];
		}
		elseif (strpos($condition['CLASS_ID'], 'CondIBProp') !== false)
		{
			$name = $condition['CLASS_ID'];
		}
		return $name;
	}
}