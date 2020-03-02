<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\TurboAPI;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Currency,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Sale,
	Bitrix\Main\Type,
	Bitrix\Main\Config\Option,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc,
	Yandex\TurboAPI\FeedTable,
	Yandex\TurboAPI\Condition;

Loc::loadMessages(__FILE__);

class TurboFeedSections extends TurboFeed
{
    public $arResult = array();
    public $addSectionsChain = false;
	protected $sectionLastId = 0;
	protected $limitItem = 80;
	protected $limitSection = 30;
	public $arrSectionsFilter = array();

    public function execute($parameters = array())
    {
        global $APPLICATION;
		
		$this->loadFeed();

		if($parameters['LAST_ID'])
			$this->sectionLastId = $parameters['LAST_ID'];
		
		if($this->feed['FIELDS']['AMOUNT_ITEM'])
			$this->amountItem = min(intval($this->feed['FIELDS']['AMOUNT_ITEM']), 10000);
		
		// is subdomain
		if($this->feed['FIELDS']['IS_SUBDOMAIN'] == 'Y' && $this->feed['FIELDS']['HOST_ID_SUBDOMAIN'])
		{
			$this->isSubdomainHostId = true;
			$this->feed['SERVER_ADDRESS'] = \Yandex\TurboAPI\Model\Request::getHostNamebyYandexHostId($this->feed['FIELDS']['HOST_ID_SUBDOMAIN']);
		}
		
		$this->arResult['CHANNEL'] = $this->getChannelDescription();
		$this->arResult['ITEMS'] = $this->getSections();
		$this->arResult['LAST_ID'] = $this->sectionLastId;
		
		return $this->arResult;
    }
	
	public function SelectedRowsCount()
	{
		$this->loadFeed();
		$arFilter = $this->getSectionsFilter();
		return \CIBlockSection::GetCount($arFilter);
	}
	
    protected function getSections()
    {
        $arResult = array();
        $arSections = $arSections['SECTION'] = $arUserProperties = $arSectionsId = array();
		$needUserProperties = false;
		
		if(!empty($this->feed['FIELDS']['SECTION_USER_FIELDS']))
		{
			$needUserProperties = true;
			foreach($this->feed['FIELDS']['SECTION_USER_FIELDS'] as $id)
				$yandexNeedUserPropertyIds[$id] = true;
			unset($id);
			$userPropertyList = $this->getUserProperty($this->feed['IBLOCK_ID']);
		}
		
		$sort = $this->getSectionsSort();
		$filter = $this->getSectionsFilter();
		$navParams = $this->getSectionsNavParams();
        $arSelect = $this->getSectionsSelect();
		

		$boolPicture = empty($arSelect) || in_array('PICTURE', $arSelect);
		
        $rsSections = \CIBlockSection::GetList($sort, $filter, false, $arSelect, $navParams);
		$rsSections->SetUrlTemplates('', $this->feed['SECTION_URL']);
        while($arSection = $rsSections->GetNext()) 
		{
			$id = (int)$arSection['ID'];
			$arSectionsId[$id] = $id;
			$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($arSection['IBLOCK_ID'], $arSection['ID']);
			$arSection['IPROPERTY_VALUES'] = $ipropValues->getValues();
			if ($boolPicture)
			{
				\Bitrix\Iblock\Component\Tools::getFieldImageData(
					$arSection,
					array('PICTURE'),
					\Bitrix\Iblock\Component\Tools::IPROPERTY_ENTITY_SECTION,
					'IPROPERTY_VALUES'
				);
			}
			$arSection['ORIGINAL_PICTURE'] = $arSection['PICTURE'];
			unset($arSection['PICTURE']);
			if($needUserProperties)
			{
				foreach($userPropertyList as $arUserProp)
				{
					if($arSection[$arUserProp['FIELD_NAME']])
					{
						$arSection['USER_PROPERTY'][$arUserProp['FIELD_NAME']] = $arUserProp;
						$arSection['USER_PROPERTY'][$arUserProp['FIELD_NAME']]['VALUE'] = $arSection[$arUserProp['FIELD_NAME']];
						$arSection['USER_PROPERTY'][$arUserProp['FIELD_NAME']]['~VALUE'] = $arSection['~'.$arUserProp['FIELD_NAME']];
						unset($arSection[$arUserProp['FIELD_NAME']], $arSection['~'.$arUserProp['FIELD_NAME']]);
					}
				}
			}
			$arSections['SECTION'][$id] = $arSection;
			$this->sectionLastId = $id; 
        }
		unset($arSection);

		if(!empty($arSections['SECTION']))
		{
			if($needUserProperties)
			{
				foreach($arSections['SECTION'] as $k => $arSection)
				{
					foreach($arSection['USER_PROPERTY'] as $code => $arUserField)
					{
						if($arType = \Yandex\TurboAPI\Turbo::getUserType($arUserField['USER_TYPE_ID']))
						{
							if($arType['BASE_TYPE'] == 'enum')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['VALUE_ENUM'] = array();
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = 'L';
								$rsEnum = \CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $arUserField['ID'], 'ID' => $arUserField['VALUE']));
								while($arEnum = $rsEnum->GetNext())
								{
									if($arUserField['MULTIPLE'] == 'N')
									{
										$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['VALUE_ENUM'] = $arEnum['VALUE'];
										$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['VALUE_ENUM_XML_ID'] = $arEnum['XML_ID'];
									}
									else
									{
										$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['VALUE_ENUM'][$arEnum['ID']] = $arEnum['VALUE'];
										$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['VALUE_ENUM_XML_ID'][$arEnum['ID']] = $arEnum['XML_ID'];
									}
								}
							}
							elseif($arType['BASE_TYPE'] == 'int' && $arUserField['USER_TYPE_ID'] == 'iblock_section')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = $arUserField['PROPERTY_TYPE'] = 'G';
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['DISPLAY_VALUE'] = \CIBlockFormatProperties::GetDisplayValue(array(), $arUserField, $event1 = '');
							}
							elseif($arType['BASE_TYPE'] == 'int' && $arUserField['USER_TYPE_ID'] == 'iblock_element')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = $arUserField['PROPERTY_TYPE'] = 'E';
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['DISPLAY_VALUE'] = \CIBlockFormatProperties::GetDisplayValue(array(), $arUserField, $event1 = '');
							}
							elseif($arType['BASE_TYPE'] == 'string')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = 'S';
							}
							elseif($arType['BASE_TYPE'] == 'int')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = 'N';
							}
							elseif($arType['BASE_TYPE'] == 'file')
							{
								$arSections['SECTION'][$k]['USER_PROPERTY'][$code]['PROPERTY_TYPE'] = 'F';
							}
						}
						
					}	
				}
				unset($arSection, $arUserField);
			}
			
			foreach($arSectionsId as $sectionsId)
			{
				$this->arrFilter = array('SECTION_ID' => $sectionsId, 'INCLUDE_SUBSECTIONS' => 'Y');
				$arSections['SECTION'][$sectionsId]['ITEMS'] = $this->getItems();
			}
				
			foreach($arSections['SECTION'] as $arSection)
			{
				$arResult[] = $this->prepareSection($arSection);
			}
			 
			if($this->feed['RELATED_SOURCE'] === 'QUEUE')
			{
				$count = count($arResult);
				if($count >= 10)
				{
					foreach($arResult as $key => $arItem)
					{	
						if($arRelated = $this->getRelatedItems($count, $key, $arRelated, $this->feed['RELATED_LIMIT']))
						{
							foreach($arRelated as $k => $v)
							{
								$arResult[$key]['RELATED'][$k] = array(
									'LINK' => $arResult[$v]['LINK'],
									'PICTURE' => $arResult[$v]['PICTURE'],
									'TITLE' => $arResult[$v]['PAGE_TITLE'],
								);
							}
						}
					}
					unset($count, $arRelated);
				}
			}
		}	
		unset($arSections);
		
        return array_values($arResult);
	}
	
	public function getSectionsFilter()
    {
        $arFilter = array();
		$sectionFilter = (array)$this->feed['SECTIONS_FILTER'];
		$sectionIdFilter = array('ID' => (array)$this->feed['SECTIONS_ID']);
		$arFilter = array_merge($arFilter, $sectionFilter, $sectionIdFilter);
		if(!is_array($this->arrSectionsFilter))
			$this->arrSectionsFilter = array();
		/*bind events*/
		foreach(GetModuleEvents("yandex.turboapi", "OnFeedOneStepSectionFilterBefore", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->feed, &$this->arrSectionsFilter));
		}
		if(!is_array($this->arrSectionsFilter))
			$this->arrSectionsFilter = array();
		$arFilter = array_merge($arFilter, $this->arrSectionsFilter);
        $arFilter['IBLOCK_ID'] = $this->feed['IBLOCK_ID'];
		if(intval($this->sectionLastId) > 0) 
		{
            $arFilter['>ID'] = $this->sectionLastId;
        }
		
        return $arFilter;
    }

    protected function getSectionsSelect()
    {
        $arSelect = array(
            'ID',
            'NAME',
            'ACTIVE',
            'LEFT_MARGIN',
            'RIGHT_MARGIN',
            'DEPTH_LEVEL',
            'IBLOCK_SECTION_ID',
            'LIST_PAGE_URL',
            'SECTION_PAGE_URL',
			'PICTURE',
			'DETAIL_PICTURE',
			'DESCRIPTION',
        );
		
        $arSelect[] = $this->feed['CONTENT'];
        if($this->feed['FIELDS']['SECTION_USER_FIELDS'] && is_array($this->feed['FIELDS']['SECTION_USER_FIELDS']))
		{
			foreach($this->feed['FIELDS']['SECTION_USER_FIELDS'] as &$field)
			{
				if(is_string($field) && preg_match("/^UF_/", $field))
					$arSelect[] = $field;
			}
			if (isset($field))
				unset($field);
		}
		
        return array_unique($arSelect);
    }
	
	protected function getSectionsSort()
    {
        return array(
			'ID' => 'asc',
        );
    }
	
	protected function getSectionsNavParams()
    {
		if($this->modeDebug)
		{
			$this->feed['LIMIT'] = $this->limitDebugItem;
		}
        elseif (intval($this->feed['LIMIT']) > 0) 
		{
            $this->feed['LIMIT'] = min($this->feed['LIMIT'], $this->limitSection);
        } 
		else 
		{
            $this->feed['LIMIT'] = $this->limitSection;
        }
		
        return array(
            'nTopCount' => intval($this->feed['LIMIT']),
        );
    }
	
	protected function getItemsSort()
    {
		if (empty($this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD']))
			$this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD'] = 'sort';
		if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER']))
			$this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER'] = "asc";
		
		if (empty($this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD2']))
			$this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD2'] = 'id';
		if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER2']))
			$this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER2'] = "desc";
		
		return array(
            $this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD'] => $this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER'],
            $this->feed['FIELDS']['SORT']['ELEMENT_SORT_FIELD2'] => $this->feed['FIELDS']['SORT_ORDER']['ELEMENT_SORT_ORDER2'],
        );
    }
	
	protected function getItemsNavParams()
    {
		if($this->modeDebug)
		{
			$pageElementCount = $this->limitDebugItem;
		}
        elseif (intval($this->feed['FIELDS']['PAGE_ELEMENT_COUNT']) > 0) 
		{
            $pageElementCount = min($this->feed['FIELDS']['PAGE_ELEMENT_COUNT'], $this->limitItem);
        } 
		else 
		{
            $pageElementCount = $this->limitItem;
        }
		
       return array(
            'nTopCount' => intval($pageElementCount),
        );
    }
	
	protected function getUserProperty($iblockId = 0)
    {
		$arUserProperties = array();
		$res = \CUserTypeEntity::GetList( array($by => $order), array('ENTITY_ID' => 'IBLOCK_'.$iblockId.'_SECTION', 'LANG' => LANGUAGE_ID));
		while($arProp = $res->Fetch())
		{
			$arUserProperties[$arProp['FIELD_NAME']] = $arProp;
		}
		return $arUserProperties;
	}
	
    protected function prepareSection($arSection = array())
    {
		$arSections = array(
            'ID' => $arSection['ID'],
            'ACTIVE' => $arSection['ACTIVE'],
			'SERVER_ADDRESS' => $this->feed['SERVER_ADDRESS'],
            'LINK' => $this->feed['SERVER_ADDRESS'].$arSection['SECTION_PAGE_URL'],
			'DESCRIPTION' => '',
			'MENU' => '',
			'ELEMENT' => array(),
			'OFFERS' => array(),
			'CATEGORY' => false,
			'MIN_PRICE' => array(),
			'TURBO_CONTENT' => '',
			'PROPERTIES' => array(),
			'DISPLAY_PROPERTIES' => $arSection['USER_PROPERTY'],
			'ELEMENTS' => $arSection['ITEMS'],
        );
		unset($arSection['ITEMS']);
		
		if($arSection['ORIGINAL_PICTURE']) 
		{
			$arSections['PICTURE'] = $this->feed['SERVER_ADDRESS'] . $arSection['ORIGINAL_PICTURE']['SRC'];
		}
		else
		{
			$arSections['PICTURE'] = false;
		}
		
		if(strlen($arSection['IPROPERTY_VALUES']['G_SECTION_META_TITLE_'.$this->feed['ID']]) > 0)
		{
			$arSections['TITLE'] = $this->fullTextFormatting($arSection['IPROPERTY_VALUES']['G_SECTION_META_TITLE_'.$this->feed['ID']]);
		}
		elseif(strlen($arSection['IPROPERTY_VALUES']['SECTION_META_TITLE']) > 0)
		{
			$arSections['TITLE'] = $this->fullTextFormatting($arSection['IPROPERTY_VALUES']['SECTION_META_TITLE']);
		}
		else
		{
			$arSections['TITLE'] = $this->fullTextFormatting($arSection['NAME']);
		}
		
		if(strlen($arSection['IPROPERTY_VALUES']['G_SECTION_PAGE_TITLE_'.$this->feed['ID']]) > 0)
		{
			$arSections['PAGE_TITLE'] = $this->fullTextFormatting($arSection['IPROPERTY_VALUES']['G_SECTION_PAGE_TITLE_'.$this->feed['ID']]);
		}
		elseif(strlen($arSection['IPROPERTY_VALUES']['SECTION_PAGE_TITLE']) > 0)
		{
			$arSections['PAGE_TITLE'] = $this->fullTextFormatting($arSection['IPROPERTY_VALUES']['SECTION_PAGE_TITLE']);
		}
		else
		{
			$arSections['PAGE_TITLE'] = $this->fullTextFormatting($arSection['NAME']);
		}
		unset($arSection['IPROPERTY_VALUES']);
		
		
		$contentField = $this->feed['CONTENT'];
        if(substr($contentField, 0, 3) == 'UF_') 
		{
            if(isset($arSection['USER_PROPERTY'][$contentField]['VALUE']) && $arSection['USER_PROPERTY'][$contentField]['MULTIPLE'] == 'N')
			{
				$arSections['TURBO_CONTENT'] = $this->prepareTurboContent($arSection['USER_PROPERTY'][$contentField]['VALUE']);
				$arSections['~TURBO_CONTENT'] = $this->prepareTurboContent($arSection['USER_PROPERTY'][$contentField]['~VALUE']);
			}
        } 
		else 
		{
           $arSections['TURBO_CONTENT'] = $this->prepareTurboContent($arSection[$contentField]);
           $arSections['~TURBO_CONTENT'] = $this->prepareTurboContent($arSection['~'.$contentField]);
        }
		unset($contentField);
		if($this->feed['MENU'])
		{
			foreach($this->feed['MENU'] as $arMenu)
			{
				if(strlen($arMenu[1])>0)
					$arSections['MENU'] .= '<a href="'.$this->feed['SERVER_ADDRESS'].$arMenu[1].'">'.$arMenu[0].'</a>';
			}
		}
		if($this->feed['FEEDBACK'] && isset($this->feed['FEEDBACK']['SHOW']))
		{
			if(strlen($this->feed['FEEDBACK']['TITLE']) > 0)
				$arSections['FEEDBACK']['TITLE'] = $this->feed['FEEDBACK']['TITLE'];
			
			if(strlen($this->feed['FORM']['AGREEMENT']['COMPANY']) > 0 && strlen($this->feed['FORM']['AGREEMENT']['LINK']) > 0)
			{
				$arSections['FORM'] = array(
					'AGREEMENT_COMPANY' => $this->feed['FORM']['AGREEMENT']['COMPANY'],
					'AGREEMENT_LINK' => $this->feed['FORM']['AGREEMENT']['LINK'],
				);
			}
			
			if($this->feed['FEEDBACK']['TYPE'])
			{
				foreach($this->feed['FEEDBACK']['TYPE'] as $key => $arFeedback)
				{
					$arSections['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key] = array(
						'TYPE' => $arFeedback['PROVIDER_KEY'],
					);
					switch($arFeedback['PROVIDER_KEY']) 
					{
						case 'mail':
							$arSections['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = 'mailto:'.$arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
							break;
						case 'call':
							$arSections['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = 'tel:'.$arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
							break;
						case 'chat':
							break;
						default;
							$arSections['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
						break;
					}
				}
			}
		}
		else
		{
			$arSections['FEEDBACK'] = array();
			$arSections['FORM'] = array();
		}
		
        if($galleryField = $this->feed['FIELDS']['SECTION_GALLERY'])
		{
			$connection = \Bitrix\Main\Application::getInstance()->getConnection();
			$tableName = 'b_utm_iblock_'.$this->feed['IBLOCK_ID'].'_section';
			if($connection->isTableExists($tableName))
			{
				$res = $connection->query("select VALUE_INT from ".$tableName." where VALUE_ID = '".intval($arSection['ID'])."' and FIELD_ID = '".intval($this->feed['FIELDS']['SECTION_GALLERY'])."'");
				while($row = $res->fetch())
				{
					if($row['VALUE_INT']) 
					{
						$filePath = \CFile::GetPath($row['VALUE_INT']);
						if(\CFile::IsImage(basename($filePath))) 
						{
							$galleryFiles[] = $this->feed['SERVER_ADDRESS'].$filePath;
						}
					}
				}
				unset($tableName);
			}
			
            if(!empty($galleryFiles)) 
			{
                $arSections['GALLERY'] = array(
                    'TITLE' => $this->feed['FIELDS']['SECTION_GALLERY_TITLE'],
                    'ITEMS' => $galleryFiles,
                );
            }
			unset($galleryFiles);
        }
        $arSections['SHARE'] = $this->feed['SHARE_NETWORKS'] ? $this->feed['SHARE_NETWORKS'] : array();
		$arSections['RELATED_INFINITY'] = $this->feed['RELATED_SOURCE'];
        $arSections['SECTION'] = $arSection;
		
		return $arSections;
    }
	
	public function getItemsFilter()
    {
		$arFilter = array();
		$elementsFilter = (array)$this->feed['ELEMENTS_FILTER'];
		$arFilter = array_merge($arFilter, $elementsFilter);
		if(is_array($this->feed['ELEMENTS_CONDITION']) && $this->feed['ELEMENTS_CONDITION'])
		{
			$condition = new Condition();
			$conditionFilter = $condition->getConditionFilter($this->feed['ELEMENTS_CONDITION'], $arFilter);
			if($conditionFilter) 
			{
				$arFilter[] = $conditionFilter;
			}
			unset($conditionFilter, $condition);
		}
		if(!is_array($this->arrFilter))
			$this->arrFilter = array();
		/*bind events*/
		foreach(GetModuleEvents("yandex.turboapi", "OnFeedOneStepSectionFilterElementBefore", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->feed, &$this->arrFilter));
		}
		if(!is_array($this->arrFilter))
			$this->arrFilter = array();
		$arFilter = array_merge($arFilter, $this->arrFilter);
        $arFilter['IBLOCK_ID'] = $this->feed['IBLOCK_ID'];
		if($this->feed['ACTIVE_DATE'] == 'Y') 
		{
            $arFilter['ACTIVE_DATE'] = 'Y';
        }

        return $arFilter;
    }
}
?>