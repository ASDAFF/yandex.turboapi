<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\Export;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Currency,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Sale,
	Bitrix\Main\Type,
	Bitrix\Main\Application,
	Bitrix\Main\Config\Option,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc,
	Yandex\Export\TurboProfileTable,
	Yandex\TurboAPI\Condition;

Loc::loadMessages(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/yandex.turboapi/admin/tools.php");

class ProfileExport extends \Yandex\TurboAPI\TurboFeed
{
    public $arResult = array();
	public $arErrors = array();
	
	protected $elementFields = array();
	protected $elementPropCodes = array();
	protected $offerFields = array();
	protected $offerPropCodes = array();
	protected $exportResult = array();
	
	
	protected $content;
	
	public function __construct($element = 0, $parameters = array())
	{
		parent::__construct($element, $parameters);
		
		if(!$this->loadFeed())
		{
			$this->arErrors[] = $GLOBALS['APPLICATION']->GetException();
			return false;
		}
		
		$this->setDefaultParams();
		
		$this->isXmlProfile = true;
		$this->content = '';
		$this->arTypes = \Yandex\TurboAPI\CYandexTurboAPITools::getOfferType($this->feed['TYPE']);
    }
	
	public function loadFeed()
    {
		global $APPLICATION;
		if ($this->feedId <= 0) {
            ShowError(Loc::getMessage("YANDEX_TYRBO_API_ERROR_FEED_ID"));
			$APPLICATION->ThrowException(Loc::getMessage("YANDEX_TYRBO_API_ERROR_FEED_ID"));
            return false;
        }

		if (!Loader::includeModule('iblock')) {
			ShowError(Loc::getMessage("YANDEX_TYRBO_API_ERROR_IBLOCK"));
			$APPLICATION->ThrowException(Loc::getMessage("YANDEX_TYRBO_API_ERROR_IBLOCK"));
			return false;
		}

		if ($this->feed = TurboProfileTable::getById($this->feedId)->fetch()){
			TurboProfileTable::decodeFields($this->feed);
		}
		else{
			ShowError(Loc::getMessage("YANDEX_TYRBO_API_ERROR_NOT_FEED"));
			$APPLICATION->ThrowException(Loc::getMessage("YANDEX_TYRBO_API_ERROR_NOT_FEED"));
			return false;
		}

		if ($this->feed['ACTIVE'] != 'Y') {
			ShowError(Loc::getMessage("YANDEX_TYRBO_API_ERROR_NOT_FEED"));
			$APPLICATION->ThrowException(Loc::getMessage("YANDEX_TYRBO_API_ERROR_NOT_FEED"));
			return false;
		}
		return true;
    }
	
	protected function setDefaultParams()
	{
		/*bind events*/
		foreach(GetModuleEvents("yandex.turboapi", "OnBeforeXmlProfileExport", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->feed['ID'], &$this->feed));
		}
		
		if($this->isCatalog)
		{
			$this->feed['IS_CATALOG'] = \CCatalogSku::GetInfoByIBlock($this->feed['IBLOCK_ID']);
		}
		if($this->isCurrency)
		{
			$this->feed['CURRENCY'] = $this->setCurrency();
		}
		
		$this->setBaseSelect();
		
		$this->feed['SHOP_NAME']  = trim($this->feed['SHOP_NAME']);
		$this->feed['SHOP_COMPANY'] = trim($this->feed['SHOP_COMPANY']);
		$this->feed['SHOP_URL'] = trim($this->feed['SHOP_URL']);
		$this->feed['SHOP_URL'] = rtrim($this->feed['SHOP_URL'], '/');
		$this->feed['SERVER_ADDRESS'] = $this->feed['SHOP_URL'];
		$this->feed['DIMENSIONS']   = trim($this->feed['DIMENSIONS']);

		//Дата в рамках прайс-листа для хедера, не для конструктора полей
		$curDate    = new Type\DateTime();
		$dateFormat = $this->arTypes['DATE_FORMAT'] ? trim($this->arTypes['DATE_FORMAT']) : 'd-m-Y H:i:s';
		$typeDate   = $curDate->format($dateFormat);

		$charset = trim($this->feed['CHARSET']);

		$this->feed['DATE'] = $typeDate;
		$this->feed['ENCODING'] = $charset ? $charset : SITE_CHARSET;
		$this->feed['FILE_PATH'] = $_SERVER['DOCUMENT_ROOT'] . $this->feed['FILE_PATH'];
		if($this->parameters['TMP_FILE_PATH'])
		{
			$this->feed['TMP_FILE_PATH'] = $this->parameters['TMP_FILE_PATH'];
		}
		else
		{
			$this->feed['TMP_FILE_PATH'] = $this->setTempBuffer();
		}
		$this->feed['LANGUAGE_ID'] = LANGUAGE_ID;
		$this->feed['WEIGHT_KOEF'] = Option::get('sale', 'weight_koef', 1000, $this->feed['LID']);
		
		$arFields = $this->feed['FIELDS'];
		unset($this->feed['FIELDS']);
		$this->feed['FIELDS']['CONTENT'] = $arFields;
		$this->feed['PROPERTY'] = $this->getElementProps();
		$this->feed['OFFERS_PROPERTY'] = $this->getOfferProps();

		unset($curDate, $dateFormat, $typeDate, $charset, $arFields);
	}
	
	public function SelectedRowsCount()
	{
		if($this->arErrors)
			return 0;
		$arFilter = $this->getItemsFilter();
		return \CIBlockElement::GetList(array(), $arFilter, array(), false, array('ID'));
	}
	
	protected function setBaseSelect()
	{
		$this->elementFields = $this->getBaseSelect();
		$this->offerFields = $this->getBaseSelect();
		if($this->feed['FIELDS']) 
		{
			foreach($this->feed['FIELDS'] as $key => $arField)
			{
				if($arField['TYPE'])
				{
					foreach($arField['TYPE'] as $typeKey => $typeID)
					{
						$typeValue = $arField['VALUE'][$typeKey];

						//NONE|FIELD|PROPERTY|OFFER_FIELD|OFFER_PROPERTY|CATALOG|PRICE|CURRENCY|BOOLEAN
						if(in_array($typeID, array('NONE', 'PRICE', 'CURRENCY', 'BOOLEAN')))
							continue;

						if($typeID == 'FIELD') {
							$this->elementFields[] = $typeValue;
							continue;
						}
						if($typeID == 'PROPERTY') {
							$this->elementPropCodes[] = $typeValue;
							continue;
						}
						if($typeID == 'OFFER_FIELD') {
							$this->offerFields[] = $typeValue;
							continue;
						}
						if($typeID == 'OFFER_PROPERTY') {
							$this->offerPropCodes[] = $typeValue;
							continue;
						}

						if($typeID == 'PRODUCT') {
							$typeValue = $typeValue;
						}

						$this->elementFields[] = $typeValue;
						$this->offerFields[]   = $typeValue;
					}
				}
			}
		}
		if($this->isCatalog && $this->feed['IS_CATALOG'])
		{
			foreach($this->getBaseCatalogSelect() as $fild)
			{
				$this->elementFields[] = $fild;
				$this->offerFields[] = $fild;
			}
		}
		
		if(!\Yandex\TurboAPI\ModuleVersion::isIblockNewCatalog18())
		{
			foreach($this->elementFields as $k => $val)
			{
				if(in_array($val, $this->getBaseCatalogSelect()))
				{
					$this->elementFields[$k] = 'CATALOG_'.$val;
				}
			}
			foreach($this->offerFields as $k => $val)
			{
				if(in_array($val, $this->getBaseCatalogSelect()))
				{
					$this->offerFields[$k] = 'CATALOG_'.$val;
				}
			}
		}

        return array_unique($this->elementFields);
	}
	
	protected function getItemsSelect()
    {
        return $this->getElementFields();
    }
	
	protected function getOffersSelect()
    {
        return $this->getOfferFields();
    }
	
	protected function getElementFields()
	{
		return array_unique($this->elementFields);
	}

	protected function getElementProps()
	{
		return array_unique($this->elementPropCodes);
	}
	
	protected function getOfferFields()
	{
		return array_unique($this->offerFields);
	}

	protected function getOfferProps()
	{
		return array_unique($this->offerPropCodes);
	}
	
	public function getItemsFilter()
    {
        $arFilter = array();
		$elementsFilter = (array)$this->feed['ELEMENTS_FILTER'];
		if(!$this->feed['IS_CATALOG'])
		{
			unset($elementsFilter['AVAILABLE']);
		}
		if(array_key_exists('AVAILABLE', $elementsFilter) && !\Yandex\TurboAPI\ModuleVersion::isIblockNewCatalog18())
		{
			$elementsFilter['CATALOG_AVAILABLE'] = $elementsFilter['AVAILABLE'];
			unset($elementsFilter['AVAILABLE']);
		}
		foreach($elementsFilter as $k => $val)
		{
			if($val === 'N')
				unset($elementsFilter[$k]);
		}
	
		$arFilter = array_merge($arFilter, $elementsFilter);
		
		if($this->feed['SECTION_ID'])
		{
			$arFilter['SECTION_ID'] = $this->feed['SECTION_ID'];
			$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
		}
		
		if($this->isCatalog && is_array($this->feed['ELEMENTS_CONDITION']) && $this->feed['ELEMENTS_CONDITION'])
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
		foreach(GetModuleEvents("yandex.turboapi", "OnXmlProfileOneStepElementFilterBefore", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->feed, &$this->arrFilter));
		}
		if(!is_array($this->arrFilter))
			$this->arrFilter = array();
		$arFilter = array_merge($arFilter, $this->arrFilter);
        $arFilter['IBLOCK_ID'] = $this->feed['IBLOCK_ID'];

		if(intval($this->lastId) > 0) 
		{
            $arFilter['>ID'] = $this->lastId;
        }
        return $arFilter;
    }
	
	protected function getoffersFilter()
	{
		$arFilter = array();
		$offersFilter = (array)$this->feed['OFFERS_FILTER'];
		if(!$this->feed['IS_CATALOG'])
		{
			unset($offersFilter['AVAILABLE']);
		}
		if(array_key_exists('AVAILABLE', $offersFilter) && !\Yandex\TurboAPI\ModuleVersion::isIblockNewCatalog18())
		{
			$offersFilter['CATALOG_AVAILABLE'] = $offersFilter['AVAILABLE'];
			unset($offersFilter['AVAILABLE']);
		}
		foreach($offersFilter as $k => $val)
		{
			if($val === 'N')
				unset($offersFilter[$k]);
		}
		
		$arFilter = array_merge($arFilter, $offersFilter);
		
		if(is_array($this->feed['OFFERS_CONDITION']) && $this->feed['OFFERS_CONDITION'])
		{
			$condition = new Condition();
			$conditionFilter = $condition->getConditionFilter($this->feed['OFFERS_CONDITION'], $arFilter);
			if($conditionFilter) 
			{
				$arFilter[] = $conditionFilter;
			}
			unset($conditionFilter, $condition);
		}
		$arFilter['IBLOCK_ID'] = $this->feed['IBLOCK_ID'];

		return $arFilter;
	}
	
	protected function setCurrency()
	{
		$RUR = 'RUB';
		$currencyIterator = \Bitrix\Currency\CurrencyTable::getList(array(
			'select' => array('CURRENCY'),
			'filter' => array('=CURRENCY' => 'RUR')
		));
		if ($currency = $currencyIterator->fetch())
			$RUR = 'RUR';
		unset($currency, $currencyIterator);

		$arCurrencyAllowed = array($RUR, 'USD', 'EUR', 'UAH', 'BYR', 'BYN', 'KZT');

		if (is_array($this->feed['CURRENCY']))
		{
			foreach ($this->feed['CURRENCY'] as $CURRENCY => $arCurData)
			{
				if (in_array($CURRENCY, $arCurrencyAllowed))
				{
					$this->feed['CURRENCY'][$CURRENCY] = $arCurData;
				}
			}
			unset($CURRENCY, $arCurData);
		}
		else
		{
			$currencyIterator = \Bitrix\Currency\CurrencyTable::getList(array(
				'select' => array('CURRENCY', 'SORT'),
				'filter' => array('@CURRENCY' => $arCurrencyAllowed),
				'order' => array('SORT' => 'ASC', 'CURRENCY' => 'ASC')
			));
			while ($currency = $currencyIterator->fetch())
			{
				$currency['RATE'] = 1;
				$this->feed['CURRENCY'][$currency['CURRENCY']] = $currency;
			}
			unset($currency, $currencyIterator);
		}
		
		return $this->feed['CURRENCY'];
	}
		
	public function execute($parameters = array())
    {
        global $APPLICATION;
		
		if($this->feed['LAST_ELEMENT_ID'])
			$this->lastId = $this->feed['LAST_ELEMENT_ID'];
		
		$arResult = $this->writeOffers();
		return $arResult;
    }
	
	protected function getItems()
    {
		$arResult = parent::getItems();
		
		$arOffers = array();
		$itemsCnt  = 0;
		$offersCnt = 0;
		
		if($arResult)
		{
			foreach($arResult as $arItem)
			{
				$itemsCnt++;
				if($arItem['OFFERS'])
				{
					foreach($arItem['OFFERS'] as $arOffer)
					{
						$offersCnt++;
						if($this->feed['IS_NOT_PRICE'])
						{
							$arOffers[$arOffer['ID']] = $arOffer;
						}
						else
						{
							//Выгружать только с ценами
							if($arOffer['MIN_PRICE'])
							{
								$arOffers[$arOffer['ID']] = $arOffer;
							}
						}
					}
				}
				else
				{
					if($this->feed['IS_NOT_PRICE'])
					{
						$arOffers[$arOffer['ID']] = $arOffer;
					}
					else
					{
						//На редакциях без каталога наоборот нужно выгружать товары без цен
						if($this->feed['IS_CATALOG'])
						{
							if($arItem['MIN_PRICE'])
							{
								$arOffers[$arItem['ID']] = $arItem;
							}
						}
						else
						{
							$arOffers[$arItem['ID']] = $arItem;
						}
					}
				}
			}
		}

		$this->exportResult = array(
			'LAST_ITEMS_COUNT' => intval($itemsCnt + $offersCnt),
			'LAST_ELEMENTS_COUNT' => intval($itemsCnt),
			'LAST_OFFERS_COUNT' => intval($offersCnt),
			'LAST_ID' => $this->lastId,
			'ALL_ELEMENTS_COUNT' => $this->arResult['ALL_ELEMENTS_COUNT'],
		);
		
		$this->feed['LAST_ELEMENT_ID'] = $this->lastId;
		$this->arResult['ITEMS'] = array_values($arOffers);
		
		return $this->arResult['ITEMS'];
	}
	
	protected function prepareItem($arItem = array())
    {
		if(!is_array($arItem['OFFERS']))
			$arItem['OFFERS'] = array();
		
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($arItem['IBLOCK_ID'], $arItem['ID']);
		$arItem['IPROPERTY_VALUES'] = $ipropValues->getValues();

		\Bitrix\Iblock\Component\Tools::getFieldImageData(
			$arItem,
			array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
			\Bitrix\Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
			'IPROPERTY_VALUES'
		);
		
		if(!$arItem['PREVIEW_PICTURE'] && $arItem['DETAIL_PICTURE'])
		{
			$arItem['PREVIEW_PICTURE'] = $arItem['DETAIL_PICTURE'];
			$arItem['~PREVIEW_PICTURE'] = $arItem['~DETAIL_PICTURE'];
		}
		elseif(!$arItem['DETAIL_PICTURE'] && $arItem['PREVIEW_PICTURE'])
		{
			$arItem['DETAIL_PICTURE'] = $arItem['PREVIEW_PICTURE'];
			$arItem['~DETAIL_PICTURE'] = $arItem['~PREVIEW_PICTURE'];
		}
		
		$arItems['NAME'] = $this->fullTextFormatting($arItem['NAME']);
		if($arItem['PREVIEW_TEXT'])
			$arItem['PREVIEW_TEXT'] = $this->validCharacters($arItem['PREVIEW_TEXT']);
		if($arItem['DETAIL_TEXT'])
			$arItem['DETAIL_TEXT'] = $this->validCharacters($arItem['DETAIL_TEXT']);
		if($arItem['IPROPERTY_VALUES'])
		{
			foreach($arItem['IPROPERTY_VALUES'] as $k => $v)
			{
				$arItem['IPROPERTY_VALUES'][$k] = $this->fullTextFormatting($v);
			}
		}
		
		$type = 'CATALOG_';
		if(\Yandex\TurboAPI\ModuleVersion::isIblockNewCatalog18())
		{
			$type = '';
		}	
		if($arItem['OFFERS'])
		{
			foreach($arItem['OFFERS'] as $k => $arOffer)
			{
				$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($arOffer['IBLOCK_ID'], $arOffer['ID']);
				$arOffer['IPROPERTY_VALUES'] = $ipropValues->getValues();
				
				\Bitrix\Iblock\Component\Tools::getFieldImageData(
					$arOffer,
					array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
					\Bitrix\Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
					'IPROPERTY_VALUES'
				);
				
				$this->modifyOffer($arOffer, $arItem);

				$arOffer['PRODUCT'] = array(
					'TYPE' => (int)$arOffer[$type.'TYPE'],
					'AVAILABLE' => $arOffer[$type.'AVAILABLE'],
					'BUNDLE' => $arOffer[$type.'BUNDLE'],
					'QUANTITY' => $arOffer[$type.'QUANTITY'],
					'QUANTITY_TRACE' => $arOffer[$type.'QUANTITY_TRACE'],
					'CAN_BUY_ZERO' => $arOffer[$type.'CAN_BUY_ZERO'],
					'MEASURE' => (int)$arOffer[$type.'MEASURE'],
					'SUBSCRIBE' => $arOffer[$type.'SUBSCRIBE'],
					'VAT_ID' => (int)$arOffer[$type.'VAT_ID'],
					'VAT_RATE' => 0,
					'VAT_INCLUDED' => $arOffer[$type.'VAT_INCLUDED'],
					'WEIGHT' => (float)$arOffer[$type.'WEIGHT'],
					'WIDTH' => (float)$arOffer[$type.'WIDTH'],
					'LENGTH' => (float)$arOffer[$type.'LENGTH'],
					'HEIGHT' => (float)$arOffer[$type.'HEIGHT'],
					'PAYMENT_TYPE' => $arOffer[$type.'PAYMENT_TYPE'],
					'RECUR_SCHEME_TYPE' => $arOffer[$type.'RECUR_SCHEME_TYPE'],
					'RECUR_SCHEME_LENGTH' => (int)$arOffer[$type.'RECUR_SCHEME_LENGTH'],
					'TRIAL_PRICE_ID' => (int)$arOffer[$type.'TRIAL_PRICE_ID']
				);
				$this->setDimensions($arOffer['PRODUCT']);
				
				$arOffer['FIELDS'] = $this->prepareItemContentFields($arOffer);
				$arItem['OFFERS'][$k] = $arOffer;
			}
		}
		else
		{
			if($this->isCatalog)
			{
				$arItem['PRODUCT'] = array(
					'TYPE' => (int)$arItem[$type.'TYPE'],
					'AVAILABLE' => $arItem[$type.'AVAILABLE'],
					'BUNDLE' => $arItem[$type.'BUNDLE'],
					'QUANTITY' => $arItem[$type.'QUANTITY'],
					'QUANTITY_TRACE' => $arItem[$type.'QUANTITY_TRACE'],
					'CAN_BUY_ZERO' => $arItem[$type.'CAN_BUY_ZERO'],
					'MEASURE' => (int)$arItem[$type.'MEASURE'],
					'SUBSCRIBE' => $arItem[$type.'SUBSCRIBE'],
					'VAT_ID' => (int)$arItem[$type.'VAT_ID'],
					'VAT_RATE' => 0,
					'VAT_INCLUDED' => $arItem[$type.'VAT_INCLUDED'],
					'WEIGHT' => (float)$arItem[$type.'WEIGHT'],
					'WIDTH' => (float)$arItem[$type.'WIDTH'],
					'LENGTH' => (float)$arItem[$type.'LENGTH'],
					'HEIGHT' => (float)$arItem[$type.'HEIGHT'],
					'PAYMENT_TYPE' => $arItem[$type.'PAYMENT_TYPE'],
					'RECUR_SCHEME_TYPE' => $arItem[$type.'RECUR_SCHEME_TYPE'],
					'RECUR_SCHEME_LENGTH' => (int)$arItem[$type.'RECUR_SCHEME_LENGTH'],
					'TRIAL_PRICE_ID' => (int)$arItem[$type.'TRIAL_PRICE_ID']
				);
				
				$this->setDimensions($row['PRODUCT']);
			}

			$arItem['FIELDS'] = $this->prepareItemContentFields($arItem);
		}
		
		return $arItem;
    }
	
	protected function modifyOffer(&$arOffer, $arItem)
	{
		if($arItem['IBLOCK_SECTION_ID'])
		{
			$arOffer['IBLOCK_SECTION_ID'] = $arItem['IBLOCK_SECTION_ID'];
		}
		
		$arOffer['DETAIL_PAGE_URL'] = str_replace(
			array('#SERVER_NAME#', '#SITE_DIR#', '#PRODUCT_URL#', '#ID#'),
			array($this->feed['SHOP_URL'], '', $arItem['DETAIL_PAGE_URL'], $arOffer['ID']),
			$arOffer['DETAIL_PAGE_URL']
		);
					
		//заполним поля элемента в поля Offer, если в Offer их нет
		if(!$arOffer['PREVIEW_PICTURE'] && $arItem['PREVIEW_PICTURE'])
		{
			$arOffer['PREVIEW_PICTURE'] = $arItem['PREVIEW_PICTURE'];
			$arOffer['~PREVIEW_PICTURE'] = $arItem['~PREVIEW_PICTURE'];
		}
		if(!$arOffer['DETAIL_PICTURE'] && $arItem['DETAIL_PICTURE'])
		{
			$arOffer['DETAIL_PICTURE'] = $arItem['DETAIL_PICTURE'];
			$arOffer['~DETAIL_PICTURE'] = $arItem['~DETAIL_PICTURE'];
		}
		
		if(!$arOffer['PREVIEW_TEXT'] && $arItem['PREVIEW_TEXT'])
		{
			$arOffer['PREVIEW_TEXT'] = $arItem['PREVIEW_TEXT'];
		}
		if(!$arOffer['DETAIL_TEXT'] && $arItem['DETAIL_TEXT'])
		{
			$arOffer['DETAIL_TEXT'] = $arItem['DETAIL_TEXT'];
		}
		
		if(!$arOffer['IPROPERTY_VALUES']&& $arItem['IPROPERTY_VALUES'])
		{
			$arOffer['IPROPERTY_VALUES'] = $arItem['IPROPERTY_VALUES'];
		}
		
		//заполним свойства элемента в Offer, если в Offer их нет
		if($arItem['PROPERTIES'])
		{
			foreach($arItem['PROPERTIES'] as $code => $arProp)
			{
				$propValue = ($arProp['USER_TYPE'] && $arProp['DISPLAY_VALUE'] ? $arProp['DISPLAY_VALUE'] : $arProp['~VALUE']);
				if($arOfferProp = $arOffer['PROPERTIES'][$code])
				{
					$offerPropValue = ($arOfferProp['USER_TYPE'] && $arOfferProp['DISPLAY_VALUE'] ? $arOfferProp['DISPLAY_VALUE'] : $arOfferProp['~VALUE']);
					if(!$offerPropValue && $propValue)
					{
						if($arOfferProp['USER_TYPE'] && $arOfferProp['DISPLAY_VALUE'])
							$arOffer['PROPERTIES'][$code]['DISPLAY_VALUE'] = $propValue;
						else
						{
							$arOffer['PROPERTIES'][$code]['VALUE'] = $propValue;
							$arOffer['PROPERTIES'][$code]['~VALUE'] = $propValue;
						}
					}
				}
				else
				{
					$arOffer['PROPERTIES'][$code] = $arProp;
				}
			}
		}
		
		$arOffer['NAME'] = $this->fullTextFormatting($arOffer['NAME']);
		if($arOffer['PREVIEW_TEXT'])
			$arOffer['PREVIEW_TEXT'] = $this->validCharacters($arOffer['PREVIEW_TEXT']);
		if($arOffer['DETAIL_TEXT'])
			$arOffer['DETAIL_TEXT'] = $this->validCharacters($arOffer['DETAIL_TEXT']);
		if($arOffer['IPROPERTY_VALUES'])
		{
			foreach($arOffer['IPROPERTY_VALUES'] as $k => $v)
			{
				$arOffer['IPROPERTY_VALUES'][$k] = $this->fullTextFormatting($v);
			}
		}
		unset($propValue, $arOfferProp, $offerPropValue);
	}
	
	protected function setDimensions(&$arFields)
	{
		$format = $this->feed['DIMENSIONS'] ? trim($this->feed['DIMENSIONS']) : '#LENGTH#/#WIDTH#/#HEIGHT#';

		$arFields['DIMENSIONS'] = str_replace(
			 array('#LENGTH#', '#WIDTH#', '#HEIGHT#'),
			 array($arFields['LENGTH'], $arFields['WIDTH'], $arFields['HEIGHT']),
			 $format
		);
		unset($format);
	}
	
	protected function getCategories()
	{
		$arFilter = array(
			 'IBLOCK_ID' => $this->feed['IBLOCK_ID'],
			 'ACTIVE' => 'Y',
			 'GLOBAL_ACTIVE' => 'Y',
		);

		if($this->feed['SECTION_ID'])
			$arFilter['=ID'] = $this->feed['SECTION_ID'];

		$res1 = \CIBlockSection::GetList(
			 array(),
			 $arFilter,
			 false,
			 array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL')
		);

		$arSections = array();
		while($arSection = $res1->Fetch())
		{
			$arSections[ $arSection['ID'] ] = $arSection;

			$arFilter = array(
				 'ACTIVE'        => 'Y',
				 'IBLOCK_ID'     => $this->feed['IBLOCK_ID'],
				 '>LEFT_MARGIN'  => $arSection['LEFT_MARGIN'],
				 '<RIGHT_MARGIN' => $arSection['RIGHT_MARGIN'],
				 '>DEPTH_LEVEL'  => $arSection['DEPTH_LEVEL'],
			);
			$res2 = \CIBlockSection::GetList(
				 array('left_margin' => 'asc'),
				 $arFilter,
				 false,
				 array('ID', 'NAME', 'IBLOCK_SECTION_ID')
			);
			while($subSection = $res2->Fetch()) {
				$arSections[ $subSection['ID'] ] = $subSection;
			}
		}

		\Yandex\Export\TurboProfileTable::update(
			 $this->feed['ID'],
			 array('TOTAL_SECTIONS' => count($arSections))
		);

		unset($arFilter, $res1, $res2, $arSection, $subSection);

		return $arSections;
	}
	
	public function getHandlerDeliveryPath($fName)
	{
		$files = array(
			$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/yandex_turbo/' . $fName,
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/yandex_turbo/' . $fName,
		);

		foreach($files as $file)
		{
			if(file_exists($file))
			{
				return $file;
			}
		}

		return false;
	}
	
	public function getHandlerCategoryPath($fName)
	{
		$files = array(
			$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/yandex_turbo/' . $fName,
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/yandex_turbo/' . $fName,
		);

		foreach($files as $file)
		{
			if(file_exists($file))
			{
				return $file;
			}
		}

		return false;
	}

	public function setTempBuffer()
	{
		$tempBuffer = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp/' . substr(basename($this->feed['FILE_PATH']), 0, -4);

		if(file_exists($tempBuffer))
			@unlink($tempBuffer);

		return $tempBuffer;
	}

	public function getTempBuffer()
	{
		return $this->feed['TMP_FILE_PATH'];
	}

	public function getTargetBuffer()
	{
		return $this->feed['FILE_PATH'];
	}

	public function pushTempBuffer()
	{
		$out = fopen($this->getTempBuffer(), 'a+');

		$charset = trim($this->feed['CHARSET']);
		if($charset)
			fwrite($out, iconv(SITE_CHARSET, $charset . "//TRANSLIT", $this->content));
		else
			fwrite($out, $this->content);

		fclose($out);

		unset($out, $this->content);
	}

	public function saveXML()
	{
		@rename($this->getTempBuffer(), $this->getTargetBuffer());
	}

	public function writeHeader()
	{
		$content = $this->arTypes['XML_HEADER'];
		
		//---------- Profile string fields ----------//
		if($this->feed && $this->arTypes)
		{
			foreach($this->feed as $key => $val)
			{
				if(is_string($val))
				{
					$content = str_replace('#' . $key . '#', $val, $content);
				}
			}

			//---------- #CURRENCIES# ----------//
			if(strpos($content, '#CURRENCIES#') !== false)
			{
				$CURRENCIES = '';
				if($this->feed['CURRENCY'])
				{
					foreach($this->feed['CURRENCY'] as $ID => $arCurrency)
					{
						$search  = array('#ID#', '#RATE#', '#PLUS#');
						$replace = array($ID, $arCurrency['RATE'], $arCurrency['PLUS']);

						$CURRENCIES .= "\n\t" . str_replace($search, $replace, $this->arTypes['XML_CURRENCY']);
					}
				}

				$content = str_replace('#CURRENCIES#', $CURRENCIES . "\n", $content);
			}

			//---------- #CATEGORIES# ----------//
			if(strpos($content, '#CATEGORIES#') !== false)
			{
				$categories = '';
				if($file = $this->getHandlerCategoryPath('category_'.$this->feed['ID'].'.php'))
				{
					require_once $file;
				}
				if(strlen($categories) == 0)
				{
					if($arCategories = $this->getCategories())
					{
						foreach($arCategories as $arCategory)
						{
							$search  = array('#ID#', '#PARENT_ID#', '#NAME#');
							$replace = array(
								 intval($arCategory['ID']),
								 intval($arCategory['IBLOCK_SECTION_ID']),
								 htmlspecialcharsbx($arCategory['NAME']),
							);

							if($arCategory['IBLOCK_SECTION_ID'])
								$categories .= "\n\t" . str_replace($search, $replace, $this->arTypes['XML_CATEGORY_PARENT']);
							else
								$categories .= "\n\t" . str_replace($search, $replace, $this->arTypes['XML_CATEGORY']);
						}
					}
				}

				$content = str_replace('#CATEGORIES#', $categories . "\n", $content);
			}


			//---------- #DELIVERY_OPTIONS# ----------//
			if(strpos($content, '#DELIVERY_OPTIONS#') !== false)
			{
				$delivery = '';
				if($file = $this->getHandlerDeliveryPath('delivery_'.$this->feed['ID'].'.php'))
				{
					require_once $file;
				}

				//Если в включаемом файле пусто, то запишутся доставки модуля
				if(strlen($delivery) == 0)
				{
					if($arDeliveries = $this->feed['DELIVERY'])
					{
						if($arDeliveries['cost'])
						{
							foreach($arDeliveries['cost'] as $key => $arDelivery)
							{
								$cost = $arDeliveries['cost'][ $key ];
								$days = $arDeliveries['days'][ $key ];

								$order_before = $arDeliveries['order_before'][ $key ];
								$order_before = ($order_before ? $order_before : '');

								$search  = array('#cost#', '#days#', '#order_before#');
								$replace = array($cost, $days, $order_before);

								$delivery .= "\n\t" . str_replace($search, $replace, $this->arTypes['XML_DELIVERY_OPTION']);
							}
						}
					}
				}
				if(strlen($delivery) == 0)
				{
					$content = str_replace('#DELIVERY_OPTIONS#', $delivery . "\n", $content);
				}
				else
				{
					$content = preg_replace('#<delivery-options[^>]*>(.*?)</delivery-options>#im' . BX_UTF_PCRE_MODIFIER, "\r", $content);
				}
			}
		}
		unset($key, $val, $CURRENCIES, $categories, $delivery, $arDeliveries, $arDelivery, $search, $replace, $arCategories, $arCategory, $cost, $days, $order_before);
		
		$content = preg_replace('/\s*\r+/' . BX_UTF_PCRE_MODIFIER, "", $content);
			
		$this->content = $content;
		unset($content);
		$this->pushTempBuffer();
	}

	public function writeOffers()
	{
		$arOffers = $this->getItems();

		foreach($arOffers as $arOffer)
		{
			$arFields = $arOffer['FIELDS'];
			$content = $this->arTypes['XML_OFFER'];

			foreach($arFields as $arField)
			{
				$key = trim($arField['KEY']);
				$val = $arField['VALUE'];

				//#custom# field prepare
				if($arField['IS_CUSTOM'])
				{
					$param_name = $arField['PARAM_NAME'];
					$param_unit = $arField['PARAM_UNIT'];
					$param_attr = $arField['PARAM_ATTR'];

					if($param_name == 'enclosure')
					{
						/* zen.yandex */
						if($param_attr)
						{
							if(is_array($param_attr))
							{
								foreach($param_attr as $attr)
								{
									$val .= "<$key url=\"{$attr['url']}\" type=\"{$attr['type']}\"/>\n\t\t\t";
								}
							}
						}
					}
					else 
					{
						/* market.yandex (default) */
						if(is_array($val))
						{
							$strValue = '';
							foreach($val as $v)
							{
								if(strlen($v) > 0)
								{
									if(strlen($param_unit) > 0)
										$strValue .= "<$key name=\"$param_name\" unit=\"$param_unit\">$v</$key>\n\t\t";
									else
										$strValue .= "<$key name=\"$param_name\">$v</$key>\n\t\t";
								}
							}

							$val = $strValue;
						}
						elseif(strlen($val) > 0)
						{
							if(strlen($param_unit) > 0)
								$val = "<$key name=\"$param_name\" unit=\"$param_unit\">$val</$key>\n\t\t";
							else
								$val = "<$key name=\"$param_name\">$val</$key>\n\t\t";
						}
					}

					$val .= '#custom#';
					$key = 'custom';//!required
				}
				
				//Заполнение шаблона XML_OFFER
				if(is_array($val) && !empty($val))
				{
					$strVal = '';
					foreach($val as $v)
					{
						$strVal .= "<$key>$v</$key>\n\t\t";
					}
					$strKey  = "<$key>#$key#</$key>";
					$content = str_replace($strKey, $strVal, $content);
				}
				elseif(strlen($val) > 0)
				{
					$content = str_replace('#' . $key . '#', $val, $content);
				}
				elseif($arField['IS_REQUIRED'])//Если поле обязательное
				{
					$content = str_replace('#' . $key . '#', '', $content);
				}
				else
				{
					//Заменяем в тегах макросы на пустоту
					$content = str_replace('#' . $key . '#', '', $content);

					//Удаляем пустые теги и добавляем \r, чтобы очистить пустые строки после удаления
					$content = preg_replace('#<' . $key . '[^>]*>(.*?)</' . $key . '>#im' . BX_UTF_PCRE_MODIFIER, "\r", $content);

					//Удаляем пустые атрибуты типа cbid=""
					$content = preg_replace('#\s\w+\W*\w*=\"\"#im' . BX_UTF_PCRE_MODIFIER, "", $content);
				}
			}

			//Заменяем кастомный макрос на пустоту
			$content = str_replace('#custom#', "\r", $content);

			//Удаляем пустые строки
			$content = preg_replace('/\s*\r+/' . BX_UTF_PCRE_MODIFIER, "", $content);

			//После каждого товара переносы
			$this->content .= "\n\t" . $content;
		}

		$this->content .= "\n";

		unset($arOffers, $arOffer, $content, $arFields, $arField, $key, $val, $strKey, $strVal);

		$this->pushTempBuffer();

		return $this->exportResult;
	}

	public function writeFooter()
	{
		$this->content = $this->arTypes['XML_FOOTER'];
		$this->pushTempBuffer();
	}
}
?>