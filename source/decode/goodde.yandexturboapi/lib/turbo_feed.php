<?
namespace Goodde\YandexTurbo;

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
	Goodde\YandexTurbo\FeedTable,
	Goodde\YandexTurbo\Condition;

Loc::loadMessages(__FILE__);

class TurboFeed
{
    public $arResult = array();
    protected $feedId = 0;
	protected $parameters;
    public $feed;
    protected $lastId = 0;
    protected $limitItem = 300;
    protected $limitDebugItem = 10;
    protected $amountItem = 10000;
    public $modeDebug = false;
    public $arrFilter = array();
    public $isGzip = true;
    public $gzipLevel = 9;
    protected $isCatalog = false;
    protected $isCurrency = false;
    protected $baseCurrency = 'RUB';
    protected $obCond;
    protected $isSubdomainHostId = false;

    private $_sections_cache = array();
	
	protected $isXmlProfile = false;

	/**
	 * DateField constructor.
	 *
	 * @param       $element
	 * @param array $parameters
	 *
	 * @throws SystemException
	 */
	public function __construct($element = 0, $parameters = array())
	{
		$this->feedId = intval($element);
		$this->parameters = $parameters;
		$this->isCatalog  = Loader::includeModule('catalog');
		$this->isCurrency = Loader::includeModule('currency');
		
		if($this->isCurrency) 
		{
			$this->baseCurrency = Currency\CurrencyManager::getBaseCurrency();
		}

		if($this->isCatalog) 
		{
			$this->obCond = new \CCatalogCondTree();
		}
    }

    public function execute($parameters = array())
    {
        global $APPLICATION;
		
		$this->loadFeed();
		
		if($parameters['LAST_ID'])
			$this->lastId = $parameters['LAST_ID'];

		if($this->feed['FIELDS']['AMOUNT_ITEM'])
			$this->amountItem = min(intval($this->feed['FIELDS']['AMOUNT_ITEM']), 10000);
		
		// is subdomain
		if($this->feed['FIELDS']['IS_SUBDOMAIN'] == 'Y' && $this->feed['FIELDS']['HOST_ID_SUBDOMAIN'])
		{
			$this->isSubdomainHostId = true;
			$this->feed['SERVER_ADDRESS'] = \Goodde\YandexTurbo\Model\Request::getHostNamebyYandexHostId($this->feed['FIELDS']['HOST_ID_SUBDOMAIN']);
		}

		$this->arResult['CHANNEL'] = $this->getChannelDescription();
		$this->arResult['ITEMS'] = $this->getItems();
		$this->arResult['LAST_ID'] = $this->lastId;
		
		return $this->arResult;
    }

    public function loadFeed()
    {
		if ($this->feedId <= 0) {
            ShowError(Loc::getMessage("GOODDE_TYRBO_API_ERROR_FEED_ID"));
            return;
        }

		if (!Loader::includeModule('iblock')) {
			ShowError(Loc::getMessage("GOODDE_TYRBO_API_ERROR_IBLOCK"));
			return;
		}

		if (!$this->feed = FeedTable::getById($this->feedId)->fetch()) {
			ShowError(Loc::getMessage("GOODDE_TYRBO_API_ERROR_NOT_FEED"));
			return;
		}

		if ($this->feed['ACTIVE'] != 'Y') {
			ShowError(Loc::getMessage("GOODDE_TYRBO_API_ERROR_NOT_FEED"));
			return;
		}
    }
	
	public function SelectedRowsCount()
	{
		$this->loadFeed();
		$arFilter = $this->getItemsFilter();
		return \CIBlockElement::GetList(array(), $arFilter, array(), false, array('ID'));
	}
	
    public function getChannelDescription()
    {
        $description = array(
            'FEED_ID' => $this->feedId,
            'TITLE' => $this->feed['NAME'],
            'DESCRIPTION' => $this->feed['DESCRIPTION'],
            'LINK' => $this->feed['SERVER_ADDRESS'],
        );
		
        return $description;
    }
	
    protected function getItems()
    {
        $arResult = array();
        $arItems = array();
		$arCatalog = array();
		$selectedPriceType = 0;
		$saleIncluded = false;
		$needProperties = false;
		$arProperties = array();
		$arOffersProperties = array();
		
		if($this->feed['PROPERTY'] || $this->feed['FIELDS']['CONTENT'])
		{
			$needProperties = true;
			if(!$this->isXmlProfile)
			{
				foreach($this->feed['PROPERTY'] as $id)
					$yandexNeedPropertyIds[$id] = true;
				unset($id);
			}
			$arProperties = $this->getProperty($this->feed['IBLOCK_ID']);
			$propertyIdList = array_keys($arProperties);
		}
		

		if($saleIncluded = Loader::includeModule('catalog'))
		{
			$arCatalog = \CCatalogSKU::GetInfoByIBlock($this->feed['IBLOCK_ID']);
			if($priceCode = $this->feed['PRICE_CODE'])
			{
				//This function returns array with prices description and access rights
				//in case catalog module n/a prices get values from element properties
				$arItems['PRICES'] = \CIBlockPriceTools::GetCatalogPrices($this->feed['IBLOCK_ID'], array($priceCode));
				$selectedPriceType = (int)$arItems['PRICES'][$priceCode]['ID'];
			}
		}
		
		if($selectedPriceType > 0)
		{
			$saleDiscountOnly = false;
			$calculationConfig = array(
				'CURRENCY' => $this->baseCurrency,
				'USE_DISCOUNTS' => true,
				'RESULT_WITH_VAT' => true,
				'RESULT_MODE' => Catalog\Product\Price\Calculation::RESULT_MODE_COMPONENT
			);
			if ($saleIncluded)
			{
				$saleDiscountOnly = (string)Main\Config\Option::get('sale', 'use_sale_discount_only') == 'Y';
				if ($saleDiscountOnly)
					$calculationConfig['PRECISION'] = (int)Main\Config\Option::get('sale', 'value_precision');
			}
			Catalog\Product\Price\Calculation::setConfig($calculationConfig);
			unset($calculationConfig);
		}
		
		$arItems['CATALOG'] = $arCatalog;
		
		$sort = $this->getItemsSort();
		$filter = $this->getItemsFilter();
		$navParams = $this->getItemsNavParams();
        $itemFields = $this->getItemsSelect();
		$arItems['ITEMS'] =  array();
		$itemIdsList = array();

		$skuIdsList = array();
		$simpleIdsList = array();
		
        $iterator = \CIBlockElement::GetList($sort, $filter, false, $navParams, $itemFields);
		$iterator->SetUrlTemplates($this->feed['DETAIL_URL'], '', '');
        while($row = $iterator->GetNext()) 
		{
			$id = (int)$row['ID'];
			if($needProperties)
				$row['PROPERTIES'] = array();
			if($arCatalog)
			{
				$elementType = (int)($row['CATALOG_TYPE'] ? $row['CATALOG_TYPE'] : $row['TYPE']);
				$row['PRICES'] = array();
				
				if ($elementType == Catalog\ProductTable::TYPE_SKU)
					$skuIdsList[$id] = $id;
				else
					$simpleIdsList[$id] = $id;
			}

			if($row['ACTIVE_FROM'])
			{
				$data = new Type\DateTime($row['ACTIVE_FROM']);
				$year = $data->format('Y');
				$row['DETAIL_PAGE_URL'] = str_replace('#YEAR#', $year, $row['DETAIL_PAGE_URL']);
				unset($data, $year);
			}
		
			$arItems['ITEMS'][$id] = $row;
			$itemIdsList[$id] = $id;
			$this->lastId = $id; 
        }
		unset($row, $iterator);
		
		if(!empty($arItems['ITEMS']))
		{
			if($needProperties)
			{
				$propertyFields = array('ID', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE', 'NAME', 'USER_TYPE_SETTINGS');
				
				if (!empty($propertyIdList))
				{
					\CIBlockElement::GetPropertyValuesArray(
						$arItems['ITEMS'],
						$this->feed['IBLOCK_ID'],
						array(
							'ID' => $itemIdsList,
							'IBLOCK_ID' => $this->feed['IBLOCK_ID']
						),
						array('ID' => $propertyIdList),
						array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields)
					);
				}
				
				if (!$needProperties)
				{
					foreach ($itemIdsList as $id)
						$arItems['ITEMS'][$id]['PROPERTIES'] = array();
					unset($id);
				}
				else
				{
					foreach($itemIdsList as $id)
					{
						if (empty($arItems['ITEMS'][$id]['PROPERTIES']))
							continue;
						foreach (array_keys($arItems['ITEMS'][$id]['PROPERTIES']) as $index)
						{
							$propertyId = $arItems['ITEMS'][$id]['PROPERTIES'][$index]['ID'];
							$propertyCode = $arItems['ITEMS'][$id]['PROPERTIES'][$index]['CODE'];
							if(isset($yandexNeedPropertyIds[$propertyId]))
							{
								if(in_array($arItems['ITEMS'][$id]['PROPERTIES'][$index]['PROPERTY_TYPE'],  array('E', 'G')) || strlen($arItems['ITEMS'][$id]['PROPERTIES'][$index]['USER_TYPE']) > 0)
								{
									$arItems['ITEMS'][$id]['PROPERTIES'][$index] = \CIBlockFormatProperties::GetDisplayValue(array(), $arItems['ITEMS'][$id]['PROPERTIES'][$index], $event1 = '');
								}
								$arItems['ITEMS'][$id]['DISPLAY_PROPERTIES'][$propertyCode] = $arItems['ITEMS'][$id]['PROPERTIES'][$index];
							}
							$arItems['ITEMS'][$id]['PROPERTIES'][$propertyCode] = $arItems['ITEMS'][$id]['PROPERTIES'][$index];
							unset($arItems['ITEMS'][$id]['PROPERTIES'][$index]);
						}
						unset($propertyId, $propertyCode, $index);
					}
					unset($id);
				}
			}
			
			if(!empty($skuIdsList))
			{
				if(!empty($this->feed['OFFERS_PROPERTY']))
				{
					$offersPropertyFields = array('ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'USER_TYPE', 'USER_TYPE_SETTINGS');
					$offersNeedProperties = true;
					if(!$this->isXmlProfile)
					{
						foreach($this->feed['OFFERS_PROPERTY'] as $id)
							$yandexNeedoffersPropertyIds[$id] = true;
						unset($id);
					}
					$arOffersProperties = $this->getProperty($arCatalog['IBLOCK_ID']);
					$offersPropertyIdList = array_keys($arOffersProperties);
				}
				
				$offerPropertyFilter = array();
				if ($offersNeedProperties)
				{
					if (!empty($offersPropertyIdList))
						$offerPropertyFilter = array('ID' => $offersPropertyIdList);
				}

				$offers = \CCatalogSku::getOffersList(
					$skuIdsList,
					$this->feed['IBLOCK_ID'],
					$this->getoffersFilter(),
					$this->getOffersSelect(),
					$offerPropertyFilter,
					array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $offersPropertyFields)
				);
				unset($offerPropertyFilter);
				
				if(!empty($offers))
				{
					$offerLinks = array();
					$offerIdsList = array();
					$parentsUrl = array();
					foreach (array_keys($offers) as $productId)
					{
						$arItems['ITEMS'][$productId]['OFFERS'] = array();
						foreach(array_keys($offers[$productId]) as $offerId)
						{
							$productOffer = $offers[$productId][$offerId];

							$productOffer['PRICES'] = array();
							$productOffer['MIN_PRICE'] = array();
							if(!$offersNeedProperties)
							{
								$productOffer['PROPERTIES'] = array();
							}
							else
							{
								if (!empty($productOffer['PROPERTIES']))
								{
									foreach (array_keys($productOffer['PROPERTIES']) as $index)
									{
										$propertyId = $productOffer['PROPERTIES'][$index]['ID'];
										$propertyCode = $productOffer['PROPERTIES'][$index]['CODE'];
										if(isset($yandexNeedoffersPropertyIds[$propertyId]))
										{
											if(in_array($productOffer['PROPERTIES'][$index]['PROPERTY_TYPE'],  array('E', 'G')) || strlen($productOffer['PROPERTIES'][$index]['USER_TYPE']) > 0)
											{
												$productOffer['DISPLAY_PROPERTIES'][$propertyCode] = \CIBlockFormatProperties::GetDisplayValue(array(), $productOffer['PROPERTIES'][$index], $event1 = '');
											}
											else
											{
												$productOffer['DISPLAY_PROPERTIES'][$propertyCode] =  $productOffer['PROPERTIES'][$index];
											}
										}
										$productOffer['PROPERTIES'][$propertyCode] = $productOffer['PROPERTIES'][$index];
										unset($productOffer['PROPERTIES'][$index]);
									}
									unset($propertyId, $propertyCode, $index);
								}
							}
							$arItems['ITEMS'][$productId]['OFFERS'][$offerId] = $productOffer;
							unset($productOffer);

							$offerLinks[$offerId] = &$arItems['ITEMS'][$productId]['OFFERS'][$offerId];
							$offerIdsList[$offerId] = $offerId;
						}
						unset($offerId);
					}
					
					if(!empty($offerIdsList))
					{
						if($selectedPriceType > 0)
						{
							foreach (array_chunk($offerIdsList, 500) as $pageIds)
							{
								// load vat cache
								$vatList = \CCatalogProduct::GetVATDataByIDList($pageIds);
								unset($vatList);
								
								$priceFilter = array(
									'@PRODUCT_ID' => $pageIds,
									'=CATALOG_GROUP_ID' => $selectedPriceType,
									array(
										'LOGIC' => 'OR',
										'<=QUANTITY_FROM' => 1,
										'=QUANTITY_FROM' => null
									),
									array(
										'LOGIC' => 'OR',
										'>=QUANTITY_TO' => 1,
										'=QUANTITY_TO' => null
									)
									
								);
								
								$iterator = Catalog\PriceTable::getList([
									'select' => ['ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
									'filter' => $priceFilter
								]);

								while ($price = $iterator->fetch())
								{
									$id = (int)$price['PRODUCT_ID'];
									$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
									$offerLinks[$id]['PRICES'][$priceTypeId] = $price;
									unset($priceTypeId, $id);
								}
								unset($price, $iterator);

								if ($saleDiscountOnly)
								{
									Catalog\Discount\DiscountManager::preloadPriceData(
										$pageIds,
										($selectedPriceType > 0 ? [$selectedPriceType] : $priceTypeList)
									);
								}
							}
							unset($pageIds);
						
							foreach($offerIdsList as $id)
							{
								$row = $offerLinks[$id];
								if(!empty($row['PRICES']))
								{
									$calculatePrice = \CCatalogProduct::GetOptimalPrice(
										$row['ID'],
										1,
										array(2), // anonymous
										'N',
										$row['PRICES'],
										$this->feed['LID'],
										array()
									);
									if(!empty($calculatePrice))
									{
										$offerLinks[$id]['MIN_PRICE'] = array(
											'DISCOUNT_VALUE' => $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'],
											'PRINT_DISCOUNT_VALUE' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
											'DISCOUNT' => $calculatePrice['RESULT_PRICE']['DISCOUNT'],
											'PRINT_DISCOUNT' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['DISCOUNT'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
											'PERCENT' => ceil($calculatePrice['RESULT_PRICE']['PERCENT']),
											'VALUE' => $calculatePrice['RESULT_PRICE']['BASE_PRICE'],
											'PRINT_VALUE' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['BASE_PRICE'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
											'CURRENCY' => $calculatePrice['RESULT_PRICE']['CURRENCY'],					
											'PRICE_TYPE_ID' => $calculatePrice['RESULT_PRICE']['PRICE_TYPE_ID'],
										);										
									}
									unset($calculatePrice);	
								}
							}
						}
					}
					unset($offerIdsList, $offerLinks);
				}
				unset($arOffers);
			}
			
			if(!empty($simpleIdsList))
			{
				if($selectedPriceType > 0)
				{
					foreach (array_chunk($simpleIdsList, 500) as $pageIds)
					{
						// load vat cache
						$vatList = \CCatalogProduct::GetVATDataByIDList($pageIds);
						unset($vatList);

						$priceFilter = array(
							'@PRODUCT_ID' => $pageIds,
							'=CATALOG_GROUP_ID' => $selectedPriceType,
							array(
								'LOGIC' => 'OR',
								'<=QUANTITY_FROM' => 1,
								'=QUANTITY_FROM' => null
							),
							array(
								'LOGIC' => 'OR',
								'>=QUANTITY_TO' => 1,
								'=QUANTITY_TO' => null
							)
							
						);

						$iterator = Catalog\PriceTable::getList([
							'select' => ['ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
							'filter' => $priceFilter
						]);

						while ($price = $iterator->fetch())
						{
							$id = (int)$price['PRODUCT_ID'];
							$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
							$arItems['ITEMS'][$id]['PRICES'][$priceTypeId] = $price;
							unset($priceTypeId, $id);
						}
						unset($price, $iterator);

						if ($saleDiscountOnly)
						{
							Catalog\Discount\DiscountManager::preloadPriceData(
								$pageIds,
								($selectedPriceType > 0 ? [$selectedPriceType] : $priceTypeList)
							);
						}
					}
					unset($pageIds);
					
					foreach($itemIdsList as $id)
					{
						$row = $arItems['ITEMS'][$id];
						
						if (isset($simpleIdsList[$id]) && !empty($row['PRICES']))
						{
							$calculatePrice = \CCatalogProduct::GetOptimalPrice(
								$row['ID'],
								1,
								array(2), // anonymous
								'N',
								$row['PRICES'],
								$this->feed['LID'],
								array()
							);

							if(!empty($calculatePrice))
							{
								$arItems['ITEMS'][$id]['MIN_PRICE'] = array(
									'DISCOUNT_VALUE' => $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'],
									'PRINT_DISCOUNT_VALUE' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
									'DISCOUNT' => $calculatePrice['RESULT_PRICE']['DISCOUNT'],
									'PRINT_DISCOUNT' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['DISCOUNT'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
									'PERCENT' => ceil($calculatePrice['RESULT_PRICE']['PERCENT']),
									'VALUE' => $calculatePrice['RESULT_PRICE']['BASE_PRICE'],
									'PRINT_VALUE' => \CCurrencyLang::CurrencyFormat($calculatePrice['RESULT_PRICE']['BASE_PRICE'], $calculatePrice['RESULT_PRICE']['CURRENCY'], true),
									'CURRENCY' => $calculatePrice['RESULT_PRICE']['CURRENCY'],					
									'PRICE_TYPE_ID' => $calculatePrice['RESULT_PRICE']['PRICE_TYPE_ID'],
								);
							}
							unset($calculatePrice);	
						}
					}
				}
			}	
		
			foreach($arItems['ITEMS'] as $arItem)
			{
				$arResult[] = $this->prepareItem($arItem);
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
		unset($arItems);
		
        return array_values($arResult);
	}
	
	public function getItemsFilter()
    {
        $arFilter = array();
		$elementsFilter = (array)$this->feed['ELEMENTS_FILTER'];
		foreach($elementsFilter as $k => $val)
		{
			if($val === 'N')
				unset($elementsFilter[$k]);
		}
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
		foreach(GetModuleEvents("goodde.yandexturboapi", "OnFeedOneStepElementFilterBefore", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($this->feed, &$this->arrFilter));
		}
		if(!is_array($this->arrFilter))
			$this->arrFilter = array();
		$arFilter = array_merge($arFilter, $this->arrFilter);
        $arFilter['IBLOCK_ID'] = $this->feed['IBLOCK_ID'];
		if($this->feed['DATE_ADD_FEED'] && !($this->feed['ALL_FEED'] == 'Y' || $this->modeDebug)) 
		{
            $arFilter['>TIMESTAMP_X'] = $this->feed['DATE_ADD_FEED'];
        }
		if($this->feed['ACTIVE_DATE'] == 'Y') 
		{
            $arFilter['ACTIVE_DATE'] = 'Y';
        }
		if(intval($this->lastId) > 0) 
		{
            $arFilter['>ID'] = $this->lastId;
        }
		
        return $arFilter;
    }
	
	protected function getBaseSelect()
	{
		return array(
            'ID',
			'XML_ID',
			'CODE',
            'NAME',
            'ACTIVE',
			'IBLOCK_ID',
			'IBLOCK_CODE',
			'DATE_CREATE',
			'ACTIVE_FROM',
			'ACTIVE_TO',
			'SORT',
            'IBLOCK_SECTION_ID',
            'DETAIL_PAGE_URL',
			'PREVIEW_PICTURE',
			'DETAIL_PICTURE',
			'PREVIEW_TEXT',
			'DETAIL_TEXT',
			'SEARCHABLE_CONTENT',
			'TIMESTAMP_X',
			'MODIFIED_BY',
			'IBLOCK_SECTION_NAME',
			'LIST_PAGE_URL',
			'SHOW_COUNTER',
			'TAGS',
        );
	}
	
	protected function getBaseCatalogSelect()
	{
		return array(
            'TYPE',
			'MEASURE',
			'AVAILABLE',
			'VAT_ID',
			'VAT_INCLUDED',
			'QUANTITY',
			'QUANTITY_TRACE',
			'CAN_BUY_ZERO',
			'SUBSCRIBE',
			'WEIGHT',
			'LENGTH',
			'HEIGHT',
			'GROUP_ID',
        );
	}
	
    protected function getItemsSelect()
    {
        $select = $this->getBaseSelect();

        $select[] = $this->feed['CONTENT'];
		if($this->feed['PRICE_CODE'])
		{
			$select[] = 'CATALOG_TYPE';
		}
		
        return array_unique($select);
    }

    protected function getItemsSort()
    {
        $sort = array(
            'ID' => 'asc',
        );

        return $sort;
    }
	
    protected function getItemsNavParams($parameters = array())
    {
		if($this->modeDebug)
		{
			$this->feed['LIMIT'] = $this->limitDebugItem;
		}
        elseif (intval($this->feed['LIMIT']) > 0) 
		{
            $this->feed['LIMIT'] = min($this->feed['LIMIT'], $this->limitItem);
        } 
		else 
		{
            $this->feed['LIMIT'] = $this->limitItem;
        }
		
        $nav = array(
            'nTopCount' => intval($this->feed['LIMIT']),
        );
        return $nav;
    }
	
	protected function getoffersFilter()
	{
		$arFilter = array();
		$offersFilter = (array)$this->feed['OFFERS_FILTER'];
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
	
	protected function getOffersSort()
    {
        $sort = array(
            'ID' => 'asc',
            'NAME' => 'asc',
        );

        return $sort;
    }	
	
	protected function getOffersSelect()
    {
        $select = array(
			'ID',
			'XML_ID',
			'CODE',
            'NAME',
            'ACTIVE',
			'IBLOCK_ID',
			'IBLOCK_CODE',
			'SORT',
            'DETAIL_PAGE_URL',
			'PREVIEW_PICTURE',
			'DETAIL_PICTURE',
			'PREVIEW_TEXT',
			'DETAIL_TEXT',
			'CATALOG_QUANTITY',
		);
		
        return $select;
    }
	
	protected function getOffersPropertyCode()
    {
        $propertyCode = array();
		
		if($this->feed['OFFERS_PROPERTY'])
		{
			$propertyCode = $this->feed['OFFERS_PROPERTY'];
		}
		
        return array_unique($propertyCode);
    }
	
	protected function getProperty($iblockId = 0)
    {
		$arProperties = array();
		$rsProps = \CIBlockProperty::GetList(
			array('SORT' => 'ASC', 'NAME' => 'ASC'),
			array('IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
		);
		while ($arProp = $rsProps->Fetch())
		{
			$arProp['ID'] = (int)$arProp['ID'];
			$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
			$arProp['CODE'] = (string)$arProp['CODE'];
			if ($arProp['CODE'] == '')
				$arProp['CODE'] = $arProp['ID'];
			$arProp['LINK_IBLOCK_ID'] = (int)$arProp['LINK_IBLOCK_ID'];
			$arProperties[$arProp['ID']] = $arProp;
		}
		
		return $arProperties;
	}
	
    protected function prepareItem($arItem = array())
    {
		$arItems = array(
            'ID' => $arItem['ID'],
            'ACTIVE' => $arItem['ACTIVE'],
			'SERVER_ADDRESS' => $this->feed['SERVER_ADDRESS'],
            'LINK' => $this->feed['SERVER_ADDRESS'].$arItem['DETAIL_PAGE_URL'],
			'DESCRIPTION' => $this->fullTextFormatting($arItem['PREVIEW_TEXT']),
			'MENU' => '',
			'CATEGORY' => false,
			'MIN_PRICE' => $arItem['MIN_PRICE'],
			'TURBO_CONTENT' => '',
			'PROPERTIES' => $arItem['PROPERTIES'],
			'DISPLAY_PROPERTIES' => $arItem['DISPLAY_PROPERTIES'],
			'PUB_DATE' => '',
        );
		
		if(!is_array($arItem['OFFERS']))
			$arItem['OFFERS'] = array();
		
        if($arItem['IBLOCK_SECTION_ID'] > 0) 
		{
            if(!array_key_exists($arItem['IBLOCK_SECTION_ID'], $this->_sections_cache)) 
			{
				$this->_sections_cache[$arItem['IBLOCK_SECTION_ID']] = '';
				$rsNavChain = \CIBlockSection::GetNavChain($arItem['IBLOCK_ID'], $arItem['IBLOCK_SECTION_ID']);
				while($arNavChain = $rsNavChain->Fetch())
				{
					$this->_sections_cache[$arItem['IBLOCK_SECTION_ID']] .= $this->fullTextFormatting($arNavChain["NAME"]).'/';
				}
            }

            if($navChain = $this->_sections_cache[$arItem['IBLOCK_SECTION_ID']]) 
			{
                $arItems['CATEGORY'] = trim($navChain, '/');
            }
        }
		
		if($arItem['DETAIL_PICTURE']) 
		{
			$arItems['PICTURE'] = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arItem['DETAIL_PICTURE']);
		}
		elseif($arItem['PREVIEW_PICTURE']) 
		{
			$arItems['PICTURE'] = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arItem['PREVIEW_PICTURE']);
		}
		else
		{
			$arItems['PICTURE'] = false;
		}
		
		if($arItem['OFFERS'] && strlen($arItems['PICTURE']) == 0)
		{
			foreach($arItem['OFFERS'] as $arOffer)
			{
				if($arOffer['DETAIL_PICTURE'])
				{
					$arItems['PICTURE'] = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arOffer['DETAIL_PICTURE']);
					break;
				}
				elseif($arOffer['PREVIEW_PICTURE'])
				{
					$arItems['PICTURE'] = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arOffer['PREVIEW_PICTURE']);
					break;
				}	
			}
		}
		
		$arItem['IPROPERTY_VALUES'] = $ipropertyValues = array();
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($this->feed['IBLOCK_ID'], $arItem['ID']);
		$arItem['IPROPERTY_VALUES'] = $ipropertyValues = $ipropValues->getValues();

		if(strlen($ipropertyValues['G_ELEMENT_META_TITLE_'.$this->feed['ID']]) > 0)
		{
			$arItems['TITLE'] = $this->fullTextFormatting($ipropertyValues['G_ELEMENT_META_TITLE_'.$this->feed['ID']]);
		}
		elseif(strlen($ipropertyValues['ELEMENT_META_TITLE']) > 0)
		{
			$arItems['TITLE'] = $this->fullTextFormatting($ipropertyValues['ELEMENT_META_TITLE']);
		}
		else
		{
			$arItems['TITLE'] = $this->fullTextFormatting($arItem['NAME']);
		}
		
		if(strlen($ipropertyValues['G_ELEMENT_PAGE_TITLE_'.$this->feed['ID']]) > 0)
		{
			$arItems['PAGE_TITLE'] = $this->fullTextFormatting($ipropertyValues['G_ELEMENT_PAGE_TITLE_'.$this->feed['ID']]);
		}
		elseif(strlen($ipropertyValues['ELEMENT_PAGE_TITLE']) > 0)
		{
			$arItems['PAGE_TITLE'] = $this->fullTextFormatting($ipropertyValues['ELEMENT_PAGE_TITLE']);
		}
		else
		{
			$arItems['PAGE_TITLE'] = $this->fullTextFormatting($arItem['NAME']);
		}
		unset($ipropertyValues);
		
		
        if($pubDate = $arItem[$this->feed['PUB_DATE']]) 
		{
			$arItems['PUB_DATE'] = date("r", MkDateTime($GLOBALS['DB']->FormatDate($pubDate, \Clang::GetDateFormat("FULL"), "DD.MM.YYYY H:I:S"), "d.m.Y H:i:s"));
        }
		
		$contentField = $this->feed['CONTENT'];
        if(substr($contentField, 0, 9) == 'PROPERTY_') 
		{
            if(isset($arItem[$contentField . '_VALUE']['TEXT']))
			{
				$arItems['TURBO_CONTENT'] = $this->prepareTurboContent($arItem['~'.$contentField . '_VALUE']['TEXT']);
			}
			else
			{
				$arItems['TURBO_CONTENT'] = $this->prepareTurboContent($arItem[$contentField . '_VALUE']);
			}
        } 
		else 
		{
           $arItems['TURBO_CONTENT'] = $this->prepareTurboContent($arItem[$contentField]);
		   $arItems['~TURBO_CONTENT'] = $this->prepareTurboContent($arItem['~'.$contentField]);
        }
		unset($contentField);
		
		$arItems['TURBO_CONTENT_FIELDS'] = $this->prepareItemContentFields($arItem);

		unset($arItem['PROPERTIES'], $arItem['DISPLAY_PROPERTIES'], $arItem['MIN_PRICE']);
		
		if($this->feed['MENU'])
		{
			foreach($this->feed['MENU'] as $arMenu)
			{
				if(strlen($arMenu[1])>0)
					$arItems['MENU'] .= '<a href="'.$this->feed['SERVER_ADDRESS'].$arMenu[1].'">'.$arMenu[0].'</a>';
			}
		}
		if($this->feed['FEEDBACK'] && isset($this->feed['FEEDBACK']['SHOW']))
		{
			if(strlen($this->feed['FEEDBACK']['TITLE']) > 0)
				$arItems['FEEDBACK']['TITLE'] = $this->feed['FEEDBACK']['TITLE'];
			
			if(strlen($this->feed['FORM']['AGREEMENT']['COMPANY']) > 0 && strlen($this->feed['FORM']['AGREEMENT']['LINK']) > 0)
			{
				$arItems['FORM'] = array(
					'AGREEMENT_COMPANY' => $this->feed['FORM']['AGREEMENT']['COMPANY'],
					'AGREEMENT_LINK' => $this->feed['FORM']['AGREEMENT']['LINK'],
				);
			}
			
			if($this->feed['FEEDBACK']['TYPE'])
			{
				foreach($this->feed['FEEDBACK']['TYPE'] as $key => $arFeedback)
				{
					$arItems['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key] = array(
						'TYPE' => $arFeedback['PROVIDER_KEY'],
					);
					switch($arFeedback['PROVIDER_KEY']) 
					{
						case 'mail':
							$arItems['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = 'mailto:'.$arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
							break;
						case 'call':
							$arItems['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = 'tel:'.$arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
							break;
						case 'chat':
							break;
						default;
							$arItems['FEEDBACK']['ITEMS'][$arFeedback['STICK']]['TYPE'][$key]['VALUE'] = $arFeedback['PROVIDER_VALUE'][$arFeedback['PROVIDER_KEY']];
						break;
					}
				}
			}
		}
		else
		{
			$arItems['FEEDBACK'] = array();
			$arItems['FORM'] = array();
		}
		
        if($galleryField = $this->feed['GALLERY'])
		{
            $galleryFiles = array();
            $res = \CIBlockElement::GetProperty($this->feed['IBLOCK_ID'], $arItem['ID'], array(), array('CODE' => $galleryField, 'ACTIVE' => 'Y'));
            while($value = $res->Fetch())
			{
                if ($value['VALUE']) 
				{
                    $filePath = \CFile::GetPath($value['VALUE']);
                    if(\CFile::IsImage(basename($filePath))) 
					{
                        $galleryFiles[] = $this->feed['SERVER_ADDRESS'].$filePath;
                    }
                }
            }

            if(!empty($galleryFiles)) 
			{
                $arItems['GALLERY'] = array(
                    'TITLE' => $this->feed['GALLERY_TITLE'],
                    'ITEMS' => $galleryFiles,
                );
            }
			unset($galleryFiles);
        }

        $arItems['SHARE'] = $this->feed['SHARE_NETWORKS'] ? $this->feed['SHARE_NETWORKS'] : array();
		$arItems['RELATED_INFINITY'] = $this->feed['RELATED_SOURCE'];
		$arItems['OFFERS'] = $arItem['OFFERS'];
		unset($arItem['OFFERS']);
        $arItems['ELEMENT'] = $arItem;
		
		return $arItems;
    }
	
	protected function prepareItemContentFields($arItem = array())
	{
		$arResult = $previewPic = $detailPic = array();

		if($arItem['PREVIEW_PICTURE']) 
		{
			$previewPic = array(
				'ID' => $arItem['~PREVIEW_PICTURE'],
				'SRC' => $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arItem['~PREVIEW_PICTURE']),
			);
			if($this->isXmlProfile)
			{
				$arItem['PREVIEW_PICTURE'] = $previewPic['SRC'];
			}
			else
			{
				$arItem['PREVIEW_PICTURE'] = '<figure><img src="'.$previewPic['SRC'].'"/></figure>';
			}
			
		}

		if($arItem['DETAIL_PICTURE']) 
		{
			$detailPic = array(
				'ID' => $arItem['~DETAIL_PICTURE'],
				'SRC' => $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($arItem['~DETAIL_PICTURE']),
			);
			if($this->isXmlProfile)
			{
				$arItem['DETAIL_PICTURE'] = $detailPic['SRC'];
			}
			else
			{
				$arItem['DETAIL_PICTURE'] = '<figure><img src="'.$detailPic['SRC'].'"/></figure>';
			}
		}

		if($this->isXmlProfile)
		{
			if(strlen($arItem['DETAIL_PAGE_URL']) > 0) 
			{
				$arItem['DETAIL_PAGE_URL'] = $this->formatUri(
					$this->feed['SERVER_ADDRESS'] . $arItem['DETAIL_PAGE_URL'],
					$this->feed['UTM_TAGS'],
					$arItem['ID']
				);
			}
			
			if($arItem['WEIGHT'] && $this->feed['WEIGHT_KOEF'])
			{
				$arItem['WEIGHT'] = roundEx(doubleval($arItem['WEIGHT'] / $this->feed['WEIGHT_KOEF']), SALE_WEIGHT_PRECISION);
			}
		}

		/*
		@CONSTRUCTOR FIELD
		*/
		
		if($this->feed['FIELDS']['CONTENT']) 
		{
			foreach($this->feed['FIELDS']['CONTENT'] as $i => $arField) 
			{
				// function handler
				$userFunc = '';
				if($arField['USE_FUNCTION'] == 'Y' && $arField['FUNCTION']) 
				{
					$userFunc = trim($arField['FUNCTION']);

					// Format: TurboYa::getDetailText || Turbo\Export\Ya\Feed::getDetailText
					preg_match('/([a-z_\\\]+)::([a-z_]+)/is', $userFunc, $matches);

					if($matches[1] && $matches[2] && class_exists($matches[1], true) && method_exists($matches[1], $matches[2])) 
					{
						$userFunc = $userFunc;
					}
					elseif(method_exists(__CLASS__, $userFunc)) 
					{
						$userFunc = __CLASS__ . '::' . $userFunc;
					}
					elseif(!function_exists($arField['FUNCTION'])) 
					{
						$userFunc = '';
					}
				}

				$isCustom   = $arField['IS_CUSTOM'];
				$isRequired = $arField['REQUIRED'] == 'Y';

				$isSingle   = false;
				$isMultiple = false;
				$isMultipleType = (count($arField['TYPE']) > 1 ? true : false);
				if($arField['USE_CONCAT'] == 'Y') 
				{
					if($arField['CONCAT_VALUE'] == 'MULTIPLE')
						$isMultiple = true;
					if($arField['CONCAT_VALUE'] == 'SINGLE')
						$isSingle = true;
				}
				if($isMultiple)
					$isMultipleType = false;
				$textLength = ($arField['USE_TEXT_LENGTH'] == 'Y' ? intval($arField['TEXT_LENGTH']) : 0);

				$propName = '';
				$propUnit = trim($arField['UNIT_VALUE']);
				$propAttr = array();

				$key      = $arField['CODE'];
				$tmpValue = null;
				$value    = null;

				
				/*
				@TYPE FIELD
				*/
				foreach($arField['TYPE'] as $typeKey => $typeId) 
				{
					$typeValue = $arField['VALUE'][$typeKey];

					/** Field */
					if($typeId == 'FIELD' || $typeId == 'OFFER_FIELD')
					{
						$tmpValue = $arItem[$typeValue];

						/** Date format */
						if($arField['USE_DATE_FORMAT'] == 'Y' && is_string($tmpValue) && strlen($tmpValue) > 0) 
						{
							if($dateFormat = trim($arField['DATE_FORMAT_VALUE'])) 
							{
								$date     = new Type\DateTime($tmpValue);
								$newDate  = $date->format($dateFormat);
								$tmpValue = $newDate;
								unset($date, $newDate);
							}
						}

						if($isMultiple) 
						{
							if($typeValue == 'PREVIEW_PICTURE') 
							{
								$value[$previewPic['ID']] = $tmpValue;
							}
							elseif($typeValue == 'DETAIL_PICTURE')
							{
								$value[$detailPic['ID']] = $tmpValue;
							}
							else
							{
								$value[] = $tmpValue;
							}
						}
						elseif($isSingle)
						{
							$value .= ' ' . $tmpValue;
						}
						elseif($isMultipleType)
						{
							if($typeValue == 'PREVIEW_PICTURE') 
							{
								$value[$previewPic['ID']] = $tmpValue;
							}
							elseif($typeValue == 'DETAIL_PICTURE')
							{
								$value[$detailPic['ID']] = $tmpValue;
							}
							else
							{
								$value[] = $tmpValue;
							}
						}
						else
						{
							$value = $tmpValue;
						}
					}

					/** Property */
					if($typeId == 'PROPERTY' || $typeId == 'OFFER_PROPERTY')
					{
						if($arProp = $arItem['PROPERTIES'][$typeValue]) 
						{
							$propName  = $arProp['NAME'];
							$propValue = ($arProp['USER_TYPE'] && $arProp['DISPLAY_VALUE'] ? $arProp['DISPLAY_VALUE'] : $arProp['~VALUE']); //DISPLAY_VALUE

							if($arProp['PROPERTY_TYPE'] == 'F') 
							{
								if($propValue) 
								{
									if(is_array($propValue)) 
									{
										foreach($propValue as $kFile => $vFile) 
										{
											$propValue[$vFile] = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($vFile);
											unset($propValue[ $kFile ]);
										}
										unset($kFile, $vFile);
									}
									else 
									{
										$propValue = $this->feed['SERVER_ADDRESS'] . \CFile::GetPath($propValue);
									}
								}
							}
							elseif($arProp['PROPERTY_TYPE'] == 'L') 
							{
								/** Баг модуля инфоблоки, в свойстве типа "Список" прилетает в "PROPERTY_641_VALUE" строка с одним значением, а должен быть массив с идентификатором значения
								Иначе не сработает условие CCatalogCondTree: (((isset($arItem['PROPERTY_641_VALUE']) && in_array(181, $arItem['PROPERTY_641_VALUE'])))) */
								if(!is_array($propValue))
									$arItem['PROPERTY_'. $arProp['ID'] .'_VALUE'] = array($arProp['VALUE_ENUM_ID']);
							}
							
							if($isCustom && strlen($propUnit) == 0 && $this->isXmlProfile)
							{
								$propUnit = trim(is_array($arProp['DESCRIPTION']) ? $arProp['DESCRIPTION'][0] : $arProp['DESCRIPTION']);
							}

							if($isMultiple) 
							{
								if(is_array($propValue)) 
								{
									foreach($propValue as $pKey => $pVal) 
									{
										$value[$pKey] = $pVal;
									}
									unset($pKey, $pVal);
								}
								else
									$value[] = $propValue;
							}
							elseif($isSingle)
							{
								$value .= ' ' . (is_array($propValue) ? implode(' / ', $propValue) : $propValue);
							}
							elseif($isMultipleType)
							{
								if(is_array($propValue)) 
								{
									foreach($propValue as $pKey => $pVal) 
									{
										$value[$pKey] = $pVal;
									}
									unset($pKey, $pVal);
								}
								else
								{
									$value[] = $propValue;
								}
							}
							else
							{
								$value = (is_array($propValue) && $arProp['PROPERTY_TYPE'] != 'F' ? implode(' / ', $propValue) : $propValue);
							}
						}
					}

					/** Catalog fields */
					if($typeId == 'PRODUCT')
					{
						if($this->isXmlProfile)
						{
							$tmpValue = $arItem['PRODUCT'][$typeValue];
						}
						else
						{
							$tmpValue = $arItem['CATALOG_'.$typeValue];
						}
						
						if(in_array($typeValue, array('CATALOG_AVAILABLE', 'CATALOG_VAT_INCLUDED', 'CATALOG_QUANTITY_TRACE', 'CATALOG_CAN_BUY_ZERO', 
							'AVAILABLE', 'VAT_INCLUDED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'))) 
						{
							$tmpValue = ($tmpValue == 'Y' ? 'true' : 'false');
						}

						if($isMultiple)
							$value[] = $tmpValue;
						elseif($isSingle)
							$value .= ' ' . $tmpValue;
						elseif($isMultipleType)
							$value[] = $tmpValue;
						else
							$value = $tmpValue;
					}

					/** Currency */
					if($typeId == 'CURRENCY') 
					{
						$tmpValue = ($typeValue ? $typeValue : $this->baseCurrency);

						if($isMultiple)
							$value[] = $tmpValue;
						elseif($isSingle)
							$value .= ' ' . $tmpValue;
						elseif($isMultipleType)
							$value[] = $tmpValue;
						else
							$value = $tmpValue;
					}

					/** Price */
					if($typeId == 'PRICE') 
					{
						$price = $arItem['MIN_PRICE'];

						if($typeValue == 'OLD_PRICE') 
						{
							if($price['DISCOUNT_VALUE'] < $price['VALUE'])
								$typeValue = 'VALUE';
							else
								$typeValue = '';
						}

						$tmpValue = strlen($typeValue) > 0 ? $price[$typeValue] : '';

						if($isMultiple)
							$value[] = $tmpValue;
						elseif($isSingle)
							$value .= ' ' . $tmpValue;
						elseif($isMultipleType)
							$value[] = $tmpValue;
						else
							$value = $tmpValue;
					}
					
					/** Meta-tag*/
					if($typeId == 'IPROPERTY') 
					{
						if($this->isXmlProfile)
						{
							$arField['USE_IPROPERTY_VALUE'][$typeKey] = trim($arField['USE_IPROPERTY_VALUE'][$typeKey]);
							if(strlen($arField['USE_IPROPERTY_VALUE'][$typeKey]) > 0) 
							{
								$typeValue = $arField['USE_IPROPERTY_VALUE'][$typeKey];
							}
						}
						$tmpValue = $arItem['IPROPERTY_VALUES'] ? $arItem['IPROPERTY_VALUES'][$typeValue] : '';

						if($isMultiple)
							$value[] = $tmpValue;
						elseif($isSingle)
							$value .= ' ' . $tmpValue;
						elseif($isMultipleType)
							$value[] = $tmpValue;
						else
							$value = $tmpValue;
					}
				}

				if($this->isXmlProfile)
				{
					if($this->feed['TYPE'] === 'ya_zen')
					{
						if($isCustom)
						{
							$propName = 'enclosure';
							$propAttr = \Goodde\Export\ProfileTools::getDetailMedia($arItem['DETAIL_TEXT'], $arField, $arItem, $this->feed);
						}
					}
					elseif(in_array($this->feed['TYPE'], array('ym_simple', 'ym_vendor_model')) && $key === 'picture')
					{
						/*
						@market.yandex
						*/
						/** You can specify up to 10 links to product images for each offer. */
						if(is_array($value))
						{
							$i = 1;
							foreach($value as $k => $val) 
							{
								if($i > 10)
									unset($value[$k]);
								$i++;
							}
						}
						else
						{
							$expValues = explode(' ', trim($value));
							$i = 1;
							foreach($expValues as $k => $val) 
							{
								if($i > 10)
									unset($expValues[$k]);
								$i++;
							}
							$value = implode(' ', $expValues);
						}
						unset($i, $k, $val, $expValues);
					}
				}
				else
				{
					if($isCustom && strlen($propUnit) == 0)
					{
						$propUnit = Loc::getMessage('GOODDE_TYRBO_API_FIELDS_NOT_UNIT_VALUE');
					}
				}
							
				/*
				@FIELD OPTIONS
				*/
				/** Использовать в значении поля готовый текст */
				if($arField['USE_TEXT'] == 'Y') 
				{
					$tmpValue = trim($arField['TEXT_VALUE']);

					if($isMultiple)
						$value[] = $tmpValue;
					elseif($isSingle)
						$value .= ' ' . $tmpValue;
					else
						$value = $tmpValue;
				}


				/** Заменить строковое значение поля (1/Y/>0/true/да) на логическое (true || false) */
				if($arField['USE_BOOLEAN'] == 'Y') 
				{
					if(!is_array($value)) 
					{
						if(strlen($arField['BOOLEAN_VALUE']) > 0) 
						{
							$expValues = explode('/', trim($arField['BOOLEAN_VALUE']));
						}
						else 
						{
							$expValues = array('true', 'false');
						}

						$valueTrue  = $expValues[0];
						$valueFalse = $expValues[1];

						$value = ($value == 'Y' || $value == 1 || $value > 0 || $value == 'true' || ToLower($value) == Loc::getMessage('GOODDE_TYRBO_API_TEMP_YES_VALUE') ? $valueTrue : $valueFalse);

						unset($expValues, $valueTrue, $valueFalse);
					}
				}

				/** У свойств ищет значения по такому ключу: PROPERTY_124_VALUE */
				if($arField['USE_CONDITIONS'] == 'Y' && $arField['CONDITIONS'])
				{
					$obCond = $this->obCond;
					if($obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_CATALOG)) 
					{
						$strEval = $obCond->Generate(
							 $arField['CONDITIONS'],
							 array('FIELD' => '$arItem')
						);

						$value = ($strEval && $strEval != '((1 == 1))' && eval('return ' . $strEval . ';') == 1 ? 'true' : 'false');
					}
				}

				if(is_array($value)) 
				{
					$arResult[$i] = array(
						 'KEY'         => $key,
						 'VALUE'       => ($isMultipleType ? '' : array()),
						 'IS_CUSTOM'   => $isCustom,
						 'IS_REQUIRED' => $isRequired,
						 'PARAM_NAME'  => $this->fullTextFormatting($propName),
						 'PARAM_UNIT'  => $propUnit,
						 'PARAM_ATTR'  => $propAttr,
					);

					foreach($value as $k => $val) 
					{
						if($userFunc)
							$value[$k] = call_user_func_array($userFunc, array($val, $arField, $arItem, $profile));

						if($textLength)
							$value[$k] = substr($val, 0, $textLength);
					}
					foreach($value as $val) 
					{
						if($isMultipleType)
						{
							if($val)
							{
								$arResult[$i]['VALUE'] = $val;
								break;
							}
						}
						else
						{					
							$arResult[$i]['VALUE'][] = $val;
						}
					}
				}
				else 
				{
					if($userFunc)
						$value = call_user_func_array($userFunc, array($value, $arField, $arItem, $profile));

					if($textLength)
						$value = substr($value, 0, $textLength);

					$arResult[$i] = array(
						 'KEY'         => $key,
						 'VALUE'       => $value,
						 'IS_CUSTOM'   => $isCustom,
						 'IS_REQUIRED' => $isRequired,
						 'PARAM_NAME'  => $this->fullTextFormatting($propName),
						 'PARAM_UNIT'  => $propUnit,
						 'PARAM_ATTR'  => $propAttr,
					);
				}
			}
			unset($userFunc, $isCustom, $isRequired, $textLength, $arProp, $propName, $propUnit, $propAttr, $propValue, $key, $value, $i, $arField, $profile, $arItem, $previewPic, $detailPic);
		}

		return $arResult;
	}
	
	protected function formatUri($url, $tags = array(), $id = null)
	{
		if($tags && $tags['NAME'])
		{
			$params = array();
			foreach($tags['NAME'] as $pKey => $pName)
			{
				$pValue = $tags['VALUE'][ $pKey ];
				$pValue = str_replace('#ID#', $id, $pValue);

				$params[] = $pName . '=' . $pValue;
			}

			$url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $params);
		}

		return $this->validCharacters($url);
	}
	
	/** formatting */
	public function fullTextFormatting($text)
	{
		$text = htmlspecialchars_decode($text);
		$text = $this->stripAllTags($text);
		$text = preg_replace('/\s\s+/', ' ', $text);
		$text = preg_replace('/(\r|\n|\r\n){3,}/', '', $text);
		return $this->validCharacters($text);
	}
	
	public function prepareTurboContent($turboContent) 
	{
		$turboContent = htmlspecialchars_decode($turboContent);
		$turboContent = $this->stripAllTags($turboContent, $this->yandexTurboAllOwedTags(), false);
		
		$turboContent = preg_replace('/<p[^>]*?>/', '<p>', $turboContent);
		$turboContent = preg_replace('/<ul[^>]*?>/', '<ul>', $turboContent);
		$turboContent = preg_replace('/<ol[^>]*?>/', '<ol>', $turboContent);
		$turboContent = preg_replace('/<li[^>]*?>/', '<li>', $turboContent);
		$turboContent = preg_replace('/style\s*=\s*".*?"/', '', $turboContent);
		$turboContent = preg_replace('/style\s*=\s*\'.*?\'/', '', $turboContent);
		$turboContent = preg_replace('/\s+>/', '>', $turboContent);
		$turboContent = preg_replace('/class\s*=\s*".*?"/', '', $turboContent);
		$turboContent = preg_replace('/class\s*=\s*\'.*?\'/', '', $turboContent);
		$turboContent = preg_replace('/\s+>/', '>', $turboContent);
		
		$turboContent = $this->wrapTurboImages($turboContent);
		$turboContent = $this->wrapTurboVideo($turboContent);

		return $turboContent;
	}
	
	protected function validCharacters($text) 
	{
		$text = htmlspecialchars_decode($text);
		$text = htmlspecialchars($text, ENT_HTML5 | ENT_QUOTES, SITE_CHARSET, false);
		$text = strtr($text, $this->getCharsTable());
		return $text;
	}
	
	public function wrapTurboImages($turboContent) 
	{
	    preg_match_all('!(<img.*>)!Ui', $turboContent, $matches);
	    
	    if(isset($matches[1]) && !empty($matches))
		{
	        foreach($matches[1] as $k => $v) 
			{
	            if(!preg_match('!<figure>.*?'. preg_quote($v).'.*?</figure>!is', $turboContent)) 
				{
	                $turboContent = str_replace($v, "<figure>{$v}</figure>", $turboContent);
	            }
	        }
	    }
	    return $turboContent;
	}
	
	public function wrapTurboVideo($turboContent) 
	{
		$turboContent = preg_replace('/<video[^>]*?>/', '<video>', $turboContent);
		preg_match_all('!<video>(.*)<\/video>!Ui', $turboContent, $matches);
		if(isset($matches[1]) && !empty($matches))
		{
			foreach($matches[1] as $k => $v) 
			{
				if(!preg_match('!<figure><video>.*?'.preg_quote($v).'.*?</video><img src="(.*?)" /><figcaption>(.*?)</figcaption></figure>!is', $turboContent)) 
				{
					$turboContent = preg_replace('@(<figure><video>|<video>)'.$v.'(</video>|</video></figure>)@', '<figure><video>'.$v.'</video><img src="'.$this->feed['SERVER_ADDRESS'].'/yandex-turbo/img-by-video.png" /><figcaption>'.$this->figcaptionByVideo().'</figcaption></figure>', $turboContent);
				}
			}
		}
		return $turboContent;
	}
	
	protected function stripAllTags($string, $allowedTags = false, $removeBreaks = false) 
	{
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
		
		if($allowedTags)
			$string = strip_tags($string, $allowedTags);
		else
			$string = strip_tags($string);
		
		if ($removeBreaks)
			$string = preg_replace('/[\r\n\t ]+/', ' ', $string);

		return trim($string);
	}
	
	protected function yandexTurboAllOwedTags()
	{
		return '<p><a><br><h1><h2><h3><h4><h5><h6><figure><img><figcaption><header><ul><ol><li><table><th><tr><td><video><source><b><strong><i><em><sup><sub><ins><del><small><big><pre><iframe><blockquote><hr><abr><u><div><code>';
	}
	
	protected function figcaptionByVideo()
	{
		return $this->feed['FIGCAPTION_VIDEO'];
	}
	
	protected function getCharsTable()
	{
		return array(
			'&nbsp;'     => '&#160;',  # no-break space = non-breaking space, U+00A0 ISOnum
			'&iexcl;'    => '&#161;',  # inverted exclamation mark, U+00A1 ISOnum
			'&cent;'     => '&#162;',  # cent sign, U+00A2 ISOnum
			'&pound;'    => '&#163;',  # pound sign, U+00A3 ISOnum
			'&curren;'   => '&#164;',  # currency sign, U+00A4 ISOnum
			'&yen;'      => '&#165;',  # yen sign = yuan sign, U+00A5 ISOnum
			'&brvbar;'   => '&#166;',  # broken bar = broken vertical bar, U+00A6 ISOnum
			'&sect;'     => '&#167;',  # section sign, U+00A7 ISOnum
			'&uml;'      => '&#168;',  # diaeresis = spacing diaeresis, U+00A8 ISOdia
			'&copy;'     => '&#169;',  # copyright sign, U+00A9 ISOnum
			'&ordf;'     => '&#170;',  # feminine ordinal indicator, U+00AA ISOnum
			'&laquo;'    => '&#171;',  # left-pointing double angle quotation mark = left pointing guillemet, U+00AB ISOnum
			'&not;'      => '&#172;',  # not sign, U+00AC ISOnum
			'&shy;'      => '&#173;',  # soft hyphen = discretionary hyphen, U+00AD ISOnum
			'&reg;'      => '&#174;',  # registered sign = registered trade mark sign, U+00AE ISOnum
			'&macr;'     => '&#175;',  # macron = spacing macron = overline = APL overbar, U+00AF ISOdia
			'&deg;'      => '&#176;',  # degree sign, U+00B0 ISOnum
			'&plusmn;'   => '&#177;',  # plus-minus sign = plus-or-minus sign, U+00B1 ISOnum
			'&sup2;'     => '&#178;',  # superscript two = superscript digit two = squared, U+00B2 ISOnum
			'&sup3;'     => '&#179;',  # superscript three = superscript digit three = cubed, U+00B3 ISOnum
			'&acute;'    => '&#180;',  # acute accent = spacing acute, U+00B4 ISOdia
			'&micro;'    => '&#181;',  # micro sign, U+00B5 ISOnum
			'&para;'     => '&#182;',  # pilcrow sign = paragraph sign, U+00B6 ISOnum
			'&middot;'   => '&#183;',  # middle dot = Georgian comma = Greek middle dot, U+00B7 ISOnum
			'&cedil;'    => '&#184;',  # cedilla = spacing cedilla, U+00B8 ISOdia
			'&sup1;'     => '&#185;',  # superscript one = superscript digit one, U+00B9 ISOnum
			'&ordm;'     => '&#186;',  # masculine ordinal indicator, U+00BA ISOnum
			'&raquo;'    => '&#187;',  # right-pointing double angle quotation mark = right pointing guillemet, U+00BB ISOnum
			'&frac14;'   => '&#188;',  # vulgar fraction one quarter = fraction one quarter, U+00BC ISOnum
			'&frac12;'   => '&#189;',  # vulgar fraction one half = fraction one half, U+00BD ISOnum
			'&frac34;'   => '&#190;',  # vulgar fraction three quarters = fraction three quarters, U+00BE ISOnum
			'&iquest;'   => '&#191;',  # inverted question mark = turned question mark, U+00BF ISOnum
			'&Agrave;'   => '&#192;',  # latin capital letter A with grave = latin capital letter A grave, U+00C0 ISOlat1
			'&Aacute;'   => '&#193;',  # latin capital letter A with acute, U+00C1 ISOlat1
			'&Acirc;'    => '&#194;',  # latin capital letter A with circumflex, U+00C2 ISOlat1
			'&Atilde;'   => '&#195;',  # latin capital letter A with tilde, U+00C3 ISOlat1
			'&Auml;'     => '&#196;',  # latin capital letter A with diaeresis, U+00C4 ISOlat1
			'&Aring;'    => '&#197;',  # latin capital letter A with ring above = latin capital letter A ring, U+00C5 ISOlat1
			'&AElig;'    => '&#198;',  # latin capital letter AE = latin capital ligature AE, U+00C6 ISOlat1
			'&Ccedil;'   => '&#199;',  # latin capital letter C with cedilla, U+00C7 ISOlat1
			'&Egrave;'   => '&#200;',  # latin capital letter E with grave, U+00C8 ISOlat1
			'&Eacute;'   => '&#201;',  # latin capital letter E with acute, U+00C9 ISOlat1
			'&Ecirc;'    => '&#202;',  # latin capital letter E with circumflex, U+00CA ISOlat1
			'&Euml;'     => '&#203;',  # latin capital letter E with diaeresis, U+00CB ISOlat1
			'&Igrave;'   => '&#204;',  # latin capital letter I with grave, U+00CC ISOlat1
			'&Iacute;'   => '&#205;',  # latin capital letter I with acute, U+00CD ISOlat1
			'&Icirc;'    => '&#206;',  # latin capital letter I with circumflex, U+00CE ISOlat1
			'&Iuml;'     => '&#207;',  # latin capital letter I with diaeresis, U+00CF ISOlat1
			'&ETH;'      => '&#208;',  # latin capital letter ETH, U+00D0 ISOlat1
			'&Ntilde;'   => '&#209;',  # latin capital letter N with tilde, U+00D1 ISOlat1
			'&Ograve;'   => '&#210;',  # latin capital letter O with grave, U+00D2 ISOlat1
			'&Oacute;'   => '&#211;',  # latin capital letter O with acute, U+00D3 ISOlat1
			'&Ocirc;'    => '&#212;',  # latin capital letter O with circumflex, U+00D4 ISOlat1
			'&Otilde;'   => '&#213;',  # latin capital letter O with tilde, U+00D5 ISOlat1
			'&Ouml;'     => '&#214;',  # latin capital letter O with diaeresis, U+00D6 ISOlat1
			'&times;'    => '&#215;',  # multiplication sign, U+00D7 ISOnum
			'&Oslash;'   => '&#216;',  # latin capital letter O with stroke = latin capital letter O slash, U+00D8 ISOlat1
			'&Ugrave;'   => '&#217;',  # latin capital letter U with grave, U+00D9 ISOlat1
			'&Uacute;'   => '&#218;',  # latin capital letter U with acute, U+00DA ISOlat1
			'&Ucirc;'    => '&#219;',  # latin capital letter U with circumflex, U+00DB ISOlat1
			'&Uuml;'     => '&#220;',  # latin capital letter U with diaeresis, U+00DC ISOlat1
			'&Yacute;'   => '&#221;',  # latin capital letter Y with acute, U+00DD ISOlat1
			'&THORN;'    => '&#222;',  # latin capital letter THORN, U+00DE ISOlat1
			'&szlig;'    => '&#223;',  # latin small letter sharp s = ess-zed, U+00DF ISOlat1
			'&agrave;'   => '&#224;',  # latin small letter a with grave = latin small letter a grave, U+00E0 ISOlat1
			'&aacute;'   => '&#225;',  # latin small letter a with acute, U+00E1 ISOlat1
			'&acirc;'    => '&#226;',  # latin small letter a with circumflex, U+00E2 ISOlat1
			'&atilde;'   => '&#227;',  # latin small letter a with tilde, U+00E3 ISOlat1
			'&auml;'     => '&#228;',  # latin small letter a with diaeresis, U+00E4 ISOlat1
			'&aring;'    => '&#229;',  # latin small letter a with ring above = latin small letter a ring, U+00E5 ISOlat1
			'&aelig;'    => '&#230;',  # latin small letter ae = latin small ligature ae, U+00E6 ISOlat1
			'&ccedil;'   => '&#231;',  # latin small letter c with cedilla, U+00E7 ISOlat1
			'&egrave;'   => '&#232;',  # latin small letter e with grave, U+00E8 ISOlat1
			'&eacute;'   => '&#233;',  # latin small letter e with acute, U+00E9 ISOlat1
			'&ecirc;'    => '&#234;',  # latin small letter e with circumflex, U+00EA ISOlat1
			'&euml;'     => '&#235;',  # latin small letter e with diaeresis, U+00EB ISOlat1
			'&igrave;'   => '&#236;',  # latin small letter i with grave, U+00EC ISOlat1
			'&iacute;'   => '&#237;',  # latin small letter i with acute, U+00ED ISOlat1
			'&icirc;'    => '&#238;',  # latin small letter i with circumflex, U+00EE ISOlat1
			'&iuml;'     => '&#239;',  # latin small letter i with diaeresis, U+00EF ISOlat1
			'&eth;'      => '&#240;',  # latin small letter eth, U+00F0 ISOlat1
			'&ntilde;'   => '&#241;',  # latin small letter n with tilde, U+00F1 ISOlat1
			'&ograve;'   => '&#242;',  # latin small letter o with grave, U+00F2 ISOlat1
			'&oacute;'   => '&#243;',  # latin small letter o with acute, U+00F3 ISOlat1
			'&ocirc;'    => '&#244;',  # latin small letter o with circumflex, U+00F4 ISOlat1
			'&otilde;'   => '&#245;',  # latin small letter o with tilde, U+00F5 ISOlat1
			'&ouml;'     => '&#246;',  # latin small letter o with diaeresis, U+00F6 ISOlat1
			'&divide;'   => '&#247;',  # division sign, U+00F7 ISOnum
			'&oslash;'   => '&#248;',  # latin small letter o with stroke, = latin small letter o slash, U+00F8 ISOlat1
			'&ugrave;'   => '&#249;',  # latin small letter u with grave, U+00F9 ISOlat1
			'&uacute;'   => '&#250;',  # latin small letter u with acute, U+00FA ISOlat1
			'&ucirc;'    => '&#251;',  # latin small letter u with circumflex, U+00FB ISOlat1
			'&uuml;'     => '&#252;',  # latin small letter u with diaeresis, U+00FC ISOlat1
			'&yacute;'   => '&#253;',  # latin small letter y with acute, U+00FD ISOlat1
			'&thorn;'    => '&#254;',  # latin small letter thorn, U+00FE ISOlat1
			'&yuml;'     => '&#255;',  # latin small letter y with diaeresis, U+00FF ISOlat1
			'&fnof;'     => '&#402;',  # latin small f with hook = function = florin, U+0192 ISOtech
			'&Alpha;'    => '&#913;',  # greek capital letter alpha, U+0391
			'&Beta;'     => '&#914;',  # greek capital letter beta, U+0392
			'&Gamma;'    => '&#915;',  # greek capital letter gamma, U+0393 ISOgrk3
			'&Delta;'    => '&#916;',  # greek capital letter delta, U+0394 ISOgrk3
			'&Epsilon;'  => '&#917;',  # greek capital letter epsilon, U+0395
			'&Zeta;'     => '&#918;',  # greek capital letter zeta, U+0396
			'&Eta;'      => '&#919;',  # greek capital letter eta, U+0397
			'&Theta;'    => '&#920;',  # greek capital letter theta, U+0398 ISOgrk3
			'&Iota;'     => '&#921;',  # greek capital letter iota, U+0399
			'&Kappa;'    => '&#922;',  # greek capital letter kappa, U+039A
			'&Lambda;'   => '&#923;',  # greek capital letter lambda, U+039B ISOgrk3
			'&Mu;'       => '&#924;',  # greek capital letter mu, U+039C
			'&Nu;'       => '&#925;',  # greek capital letter nu, U+039D
			'&Xi;'       => '&#926;',  # greek capital letter xi, U+039E ISOgrk3
			'&Omicron;'  => '&#927;',  # greek capital letter omicron, U+039F
			'&Pi;'       => '&#928;',  # greek capital letter pi, U+03A0 ISOgrk3
			'&Rho;'      => '&#929;',  # greek capital letter rho, U+03A1
			'&Sigma;'    => '&#931;',  # greek capital letter sigma, U+03A3 ISOgrk3
			'&Tau;'      => '&#932;',  # greek capital letter tau, U+03A4
			'&Upsilon;'  => '&#933;',  # greek capital letter upsilon, U+03A5 ISOgrk3
			'&Phi;'      => '&#934;',  # greek capital letter phi, U+03A6 ISOgrk3
			'&Chi;'      => '&#935;',  # greek capital letter chi, U+03A7
			'&Psi;'      => '&#936;',  # greek capital letter psi, U+03A8 ISOgrk3
			'&Omega;'    => '&#937;',  # greek capital letter omega, U+03A9 ISOgrk3
			'&alpha;'    => '&#945;',  # greek small letter alpha, U+03B1 ISOgrk3
			'&beta;'     => '&#946;',  # greek small letter beta, U+03B2 ISOgrk3
			'&gamma;'    => '&#947;',  # greek small letter gamma, U+03B3 ISOgrk3
			'&delta;'    => '&#948;',  # greek small letter delta, U+03B4 ISOgrk3
			'&epsilon;'  => '&#949;',  # greek small letter epsilon, U+03B5 ISOgrk3
			'&zeta;'     => '&#950;',  # greek small letter zeta, U+03B6 ISOgrk3
			'&eta;'      => '&#951;',  # greek small letter eta, U+03B7 ISOgrk3
			'&theta;'    => '&#952;',  # greek small letter theta, U+03B8 ISOgrk3
			'&iota;'     => '&#953;',  # greek small letter iota, U+03B9 ISOgrk3
			'&kappa;'    => '&#954;',  # greek small letter kappa, U+03BA ISOgrk3
			'&lambda;'   => '&#955;',  # greek small letter lambda, U+03BB ISOgrk3
			'&mu;'       => '&#956;',  # greek small letter mu, U+03BC ISOgrk3
			'&nu;'       => '&#957;',  # greek small letter nu, U+03BD ISOgrk3
			'&xi;'       => '&#958;',  # greek small letter xi, U+03BE ISOgrk3
			'&omicron;'  => '&#959;',  # greek small letter omicron, U+03BF NEW
			'&pi;'       => '&#960;',  # greek small letter pi, U+03C0 ISOgrk3
			'&rho;'      => '&#961;',  # greek small letter rho, U+03C1 ISOgrk3
			'&sigmaf;'   => '&#962;',  # greek small letter final sigma, U+03C2 ISOgrk3
			'&sigma;'    => '&#963;',  # greek small letter sigma, U+03C3 ISOgrk3
			'&tau;'      => '&#964;',  # greek small letter tau, U+03C4 ISOgrk3
			'&upsilon;'  => '&#965;',  # greek small letter upsilon, U+03C5 ISOgrk3
			'&phi;'      => '&#966;',  # greek small letter phi, U+03C6 ISOgrk3
			'&chi;'      => '&#967;',  # greek small letter chi, U+03C7 ISOgrk3
			'&psi;'      => '&#968;',  # greek small letter psi, U+03C8 ISOgrk3
			'&omega;'    => '&#969;',  # greek small letter omega, U+03C9 ISOgrk3
			'&thetasym;' => '&#977;',  # greek small letter theta symbol, U+03D1 NEW
			'&upsih;'    => '&#978;',  # greek upsilon with hook symbol, U+03D2 NEW
			'&piv;'      => '&#982;',  # greek pi symbol, U+03D6 ISOgrk3
			'&bull;'     => '&#8226;', # bullet = black small circle, U+2022 ISOpub
			'&hellip;'   => '&#8230;', # horizontal ellipsis = three dot leader, U+2026 ISOpub
			'&prime;'    => '&#8242;', # prime = minutes = feet, U+2032 ISOtech
			'&Prime;'    => '&#8243;', # double prime = seconds = inches, U+2033 ISOtech
			'&oline;'    => '&#8254;', # overline = spacing overscore, U+203E NEW
			'&frasl;'    => '&#8260;', # fraction slash, U+2044 NEW
			'&weierp;'   => '&#8472;', # script capital P = power set = Weierstrass p, U+2118 ISOamso
			'&image;'    => '&#8465;', # blackletter capital I = imaginary part, U+2111 ISOamso
			'&real;'     => '&#8476;', # blackletter capital R = real part symbol, U+211C ISOamso
			'&trade;'    => '&#8482;', # trade mark sign, U+2122 ISOnum
			'&alefsym;'  => '&#8501;', # alef symbol = first transfinite cardinal, U+2135 NEW
			'&larr;'     => '&#8592;', # leftwards arrow, U+2190 ISOnum
			'&uarr;'     => '&#8593;', # upwards arrow, U+2191 ISOnum
			'&rarr;'     => '&#8594;', # rightwards arrow, U+2192 ISOnum
			'&darr;'     => '&#8595;', # downwards arrow, U+2193 ISOnum
			'&harr;'     => '&#8596;', # left right arrow, U+2194 ISOamsa
			'&crarr;'    => '&#8629;', # downwards arrow with corner leftwards = carriage return, U+21B5 NEW
			'&lArr;'     => '&#8656;', # leftwards double arrow, U+21D0 ISOtech
			'&uArr;'     => '&#8657;', # upwards double arrow, U+21D1 ISOamsa
			'&rArr;'     => '&#8658;', # rightwards double arrow, U+21D2 ISOtech
			'&dArr;'     => '&#8659;', # downwards double arrow, U+21D3 ISOamsa
			'&hArr;'     => '&#8660;', # left right double arrow, U+21D4 ISOamsa
			'&forall;'   => '&#8704;', # for all, U+2200 ISOtech
			'&part;'     => '&#8706;', # partial differential, U+2202 ISOtech
			'&exist;'    => '&#8707;', # there exists, U+2203 ISOtech
			'&empty;'    => '&#8709;', # empty set = null set = diameter, U+2205 ISOamso
			'&nabla;'    => '&#8711;', # nabla = backward difference, U+2207 ISOtech
			'&isin;'     => '&#8712;', # element of, U+2208 ISOtech
			'&notin;'    => '&#8713;', # not an element of, U+2209 ISOtech
			'&ni;'       => '&#8715;', # contains as member, U+220B ISOtech
			'&prod;'     => '&#8719;', # n-ary product = product sign, U+220F ISOamsb
			'&sum;'      => '&#8721;', # n-ary sumation, U+2211 ISOamsb
			'&minus;'    => '&#8722;', # minus sign, U+2212 ISOtech
			'&lowast;'   => '&#8727;', # asterisk operator, U+2217 ISOtech
			'&radic;'    => '&#8730;', # square root = radical sign, U+221A ISOtech
			'&prop;'     => '&#8733;', # proportional to, U+221D ISOtech
			'&infin;'    => '&#8734;', # infinity, U+221E ISOtech
			'&ang;'      => '&#8736;', # angle, U+2220 ISOamso
			'&and;'      => '&#8743;', # logical and = wedge, U+2227 ISOtech
			'&or;'       => '&#8744;', # logical or = vee, U+2228 ISOtech
			'&cap;'      => '&#8745;', # intersection = cap, U+2229 ISOtech
			'&cup;'      => '&#8746;', # union = cup, U+222A ISOtech
			'&int;'      => '&#8747;', # integral, U+222B ISOtech
			'&there4;'   => '&#8756;', # therefore, U+2234 ISOtech
			'&sim;'      => '&#8764;', # tilde operator = varies with = similar to, U+223C ISOtech
			'&cong;'     => '&#8773;', # approximately equal to, U+2245 ISOtech
			'&asymp;'    => '&#8776;', # almost equal to = asymptotic to, U+2248 ISOamsr
			'&ne;'       => '&#8800;', # not equal to, U+2260 ISOtech
			'&equiv;'    => '&#8801;', # identical to, U+2261 ISOtech
			'&le;'       => '&#8804;', # less-than or equal to, U+2264 ISOtech
			'&ge;'       => '&#8805;', # greater-than or equal to, U+2265 ISOtech
			'&sub;'      => '&#8834;', # subset of, U+2282 ISOtech
			'&sup;'      => '&#8835;', # superset of, U+2283 ISOtech
			'&nsub;'     => '&#8836;', # not a subset of, U+2284 ISOamsn
			'&sube;'     => '&#8838;', # subset of or equal to, U+2286 ISOtech
			'&supe;'     => '&#8839;', # superset of or equal to, U+2287 ISOtech
			'&oplus;'    => '&#8853;', # circled plus = direct sum, U+2295 ISOamsb
			'&otimes;'   => '&#8855;', # circled times = vector product, U+2297 ISOamsb
			'&perp;'     => '&#8869;', # up tack = orthogonal to = perpendicular, U+22A5 ISOtech
			'&sdot;'     => '&#8901;', # dot operator, U+22C5 ISOamsb
			'&lceil;'    => '&#8968;', # left ceiling = apl upstile, U+2308 ISOamsc
			'&rceil;'    => '&#8969;', # right ceiling, U+2309 ISOamsc
			'&lfloor;'   => '&#8970;', # left floor = apl downstile, U+230A ISOamsc
			'&rfloor;'   => '&#8971;', # right floor, U+230B ISOamsc
			'&lang;'     => '&#9001;', # left-pointing angle bracket = bra, U+2329 ISOtech
			'&rang;'     => '&#9002;', # right-pointing angle bracket = ket, U+232A ISOtech
			'&loz;'      => '&#9674;', # lozenge, U+25CA ISOpub
			'&spades;'   => '&#9824;', # black spade suit, U+2660 ISOpub
			'&clubs;'    => '&#9827;', # black club suit = shamrock, U+2663 ISOpub
			'&hearts;'   => '&#9829;', # black heart suit = valentine, U+2665 ISOpub
			'&diams;'    => '&#9830;', # black diamond suit, U+2666 ISOpub
			//'&quot;'     => '&#34;',   # quotation mark = APL quote, U+0022 ISOnum
			//'&amp;'      => '&#38;',   # ampersand, U+0026 ISOnum
			//'&lt;'       => '&#60;',   # less-than sign, U+003C ISOnum
			//'&gt;'       => '&#62;',   # greater-than sign, U+003E ISOnum
			'&OElig;'    => '&#338;',  # latin capital ligature OE, U+0152 ISOlat2
			'&oelig;'    => '&#339;',  # latin small ligature oe, U+0153 ISOlat2
			'&Scaron;'   => '&#352;',  # latin capital letter S with caron, U+0160 ISOlat2
			'&scaron;'   => '&#353;',  # latin small letter s with caron, U+0161 ISOlat2
			'&Yuml;'     => '&#376;',  # latin capital letter Y with diaeresis, U+0178 ISOlat2
			'&circ;'     => '&#710;',  # modifier letter circumflex accent, U+02C6 ISOpub
			'&tilde;'    => '&#732;',  # small tilde, U+02DC ISOdia
			'&ensp;'     => '&#8194;', # en space, U+2002 ISOpub
			'&emsp;'     => '&#8195;', # em space, U+2003 ISOpub
			'&thinsp;'   => '&#8201;', # thin space, U+2009 ISOpub
			'&zwnj;'     => '&#8204;', # zero width non-joiner, U+200C NEW RFC 2070
			'&zwj;'      => '&#8205;', # zero width joiner, U+200D NEW RFC 2070
			'&lrm;'      => '&#8206;', # left-to-right mark, U+200E NEW RFC 2070
			'&rlm;'      => '&#8207;', # right-to-left mark, U+200F NEW RFC 2070
			'&ndash;'    => '&#8211;', # en dash, U+2013 ISOpub
			'&mdash;'    => '&#8212;', # em dash, U+2014 ISOpub
			'&lsquo;'    => '&#8216;', # left single quotation mark, U+2018 ISOnum
			'&rsquo;'    => '&#8217;', # right single quotation mark, U+2019 ISOnum
			'&sbquo;'    => '&#8218;', # single low-9 quotation mark, U+201A NEW
			'&ldquo;'    => '&#8220;', # left double quotation mark, U+201C ISOnum
			'&rdquo;'    => '&#8221;', # right double quotation mark, U+201D ISOnum
			'&bdquo;'    => '&#8222;', # double low-9 quotation mark, U+201E NEW
			'&dagger;'   => '&#8224;', # dagger, U+2020 ISOpub
			'&Dagger;'   => '&#8225;', # double dagger, U+2021 ISOpub
			'&permil;'   => '&#8240;', # per mille sign, U+2030 ISOtech
			'&lsaquo;'   => '&#8249;', # single left-pointing angle quotation mark, U+2039 ISO proposed
			'&rsaquo;'   => '&#8250;', # single right-pointing angle quotation mark, U+203A ISO proposed
			'&euro;'     => '&#8364;', # euro sign, U+20AC NEW
			//'&apos;'     => '&#39;',   # apostrophe = APL quote, U+0027 ISOnum
		);
	}
	
	//previous and next related
	protected function getRelatedItems($count, $key = null, $arRelated = array(), $cntRelated = 2)
	{	
		if($count > 0 && isset($key))
		{		
			$keyL = $count - 1; 
			$keyR = 1;
			$arResult = array();
			for($i = 1; $i <= $cntRelated; $i++)
			{
				if($key == 0)
				{
					if(($i % 2) == 0)
					{
						$curKey = $count - $keyL;
						$keyL--;
					}
					else
					{
						$curKey = $count - $keyR;
						$keyR++;
					}
				}
				else
				{
					if($arRelated[$i] >= $count - 1)
					{
						$curKey = 0;
					}
					else
					{
						$curKey = $arRelated[$i] + 1;
					}
				}
				$arResult[$i] = $curKey;	
			}
			return $arResult;
		}
	}
	
	public function rssBody($fp = '', $path = '', $arFields = array(), $arParams = array(), &$bytesWritten = 0, &$numberRss = 0, &$numberItem = 0)
    {		
		if(strlen($path) > 0)
		{
			$fp = fopen($path, 'ab');
		}
		
		$arParams['ID'] = $arParams['ID'] ? intval($arParams['ID']) : 0;
		$arParams['ITEM_STATUS'] = $arParams['ITEM_STATUS'] ? $arParams['ITEM_STATUS'] : '';
		$arParams['TEMPLATE'] = $arParams['TEMPLATE'] ? $arParams['TEMPLATE'] : '.default';
		$pathToAction = $this->getDirTemplates($arParams['TEMPLATE']);

		if(file_exists($pathToAction))
		{
			$this->includeResultModifier($arFields, $pathToAction);
		}

		foreach($arFields['ITEMS'] as $arField)
		{
			if(strlen($arParams['ITEM_STATUS']) <= 0)
			{
				if($arField['ACTIVE'] == 'Y')
				{
					$arField['ITEM'] = 'true';
				}
				elseif($arField['ACTIVE'] == 'N')
				{
					$arField['ITEM'] = 'false';
				}
			}
			else
			{
				$arField['ITEM'] = $arParams['ITEM_STATUS'];
			}
			
			$str = "\n";
			if(file_exists($pathToAction))
			{
				ob_start();
				$this->includeTemplates($arField, $pathToAction);
				$str .= ob_get_contents();
				ob_end_clean();
			}

			$rv = fwrite($fp, $str);
			
			$numberItem ++;
			$bytesWritten += $rv;


			if($numberItem == $this->amountItem )
			{
				$this->rssFooter($fp);
				$bytesWritten = 0;
				$numberItem = 1;
				$numberRss ++;
				$fp = $this->rssHeader($this->getPath().'/'.$arParams['ID'].'/turbo_'.$numberRss.'.xml', $bytesWritten, $arFields['CHANNEL']);
			}	
		}
		return $fp;
	}
	
	public function rssHeader($fileName = '', &$bytesWritten = 0, $arFields = array())
    {	
		global $runError;
		$str = '<?xml version="1.0" encoding="'.SITE_CHARSET.'"?>';
        $str .= '<rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" xmlns:turbo="http://turbo.yandex.ru" version="2.0">';
        $str .= '<channel>';
        $str .= '<title>'.$arFields['TITLE'].'</title>';
        $str .= '<link>'.$arFields['LINK'].'/</link>';
        $str .= '<turbo:cms_plugin>62668D13197952BEA072E87B176AA7BE</turbo:cms_plugin>';
        $str .= '<description>'.$arFields['DESCRIPTION'].'</description>';
		
		if(!$fp = @fopen($fileName, "wb"))
		{
			if(intval($arFields['ID']) > 0)
			{
				$directory = \Bitrix\Main\IO\Directory::createDirectory(\Goodde\YandexTurbo\Turbo::getPath().'/'.$arFields['ID'].'/');
				if($directory->isExists())
				{
					$fp = @fopen($fileName, "wb");
				}
				else
				{
					$runError = str_replace('#FILE#', $fileName, Loc::getMessage('GOODDE_TYRBO_API_FILE_OPEN_WRITING'));
				}
			}
		}
		
		if($fp)
		{
			$rv = fwrite($fp, $str);
			$bytesWritten += $rv;
		}
		
		return $fp;
	}
	
	public function rssFooter($fp)
    {
		$str = '</channel>';
        $str .= '</rss>';
		fwrite($fp, $str);
		fclose($fp);
    }
	
	public function getPath()
    {
		return Turbo::getPath();
    }
	
	protected function getDirTemplates($templates = '.default')
	{
		$type = $this->feed['IS_SECTION'] == 'Y' ? 'section' : 'element';
		$arTemplateList = \Goodde\YandexTurbo\Turbo::getTemplateList($type);
		if(file_exists($pathToAction = $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/yandex_turbo/templates/'.$type.'/'.$templates))
		{
	
			$pathToAction = str_replace('\\', '/', $pathToAction);
			while(substr($pathToAction, strlen($pathToAction) - 1, 1) == '/')
				$pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);
		}
		elseif($arTemplateList[$templates])
		{
			$pathToAction = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/goodde.yandexturboapi/load/templates/'.$type.'/'.$templates;
		}
		
		return $pathToAction;
	}
	
	protected function includeResultModifier(&$arFields, $pathToAction = '')
	{
		if(file_exists($pathToAction))
		{
			if(is_dir($pathToAction) && file_exists($pathToAction.'/result_modifier.php'))
				include($pathToAction .= '/result_modifier.php');
		}	
	}
	
	protected function includeTemplates($arField, $pathToAction = '')
	{
		if(file_exists($pathToAction))
		{
			if(is_dir($pathToAction) && file_exists($pathToAction.'/template.php'))
				include($pathToAction .= '/template.php');
		}
	}
	
	public function uploadFeed($path = '', $arFields = array())
	{
		global $arErrors;
		$arErrors = array();
		$directory = new \Bitrix\Main\IO\Directory($path, null);
		if($directory->isExists())
		{
			$i = 0;
			$j = 0;
			$n = 0;
			$arChildren = $directory->getChildren();
			$count = count($arChildren);
			$newName = '';
			foreach($arChildren as $k => $file)
			{
				if($file->isExists())
				{
					if(in_array($file->getName(), array('debug_turbo.xml', 'debug_turbo.gz')))
					{
						$file->delete();
						continue;
					}
					
					$data = $file->getContents();
					
					/*bind events*/
					foreach(GetModuleEvents("goodde.yandexturboapi", "OnBeforeContentAdd", true) as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array($arFields, &$data));
					}
					
					if($this->isGzip)
					{
						$data = gzencode($data, $this->gzipLevel);
					}
					
					$result = \Goodde\YandexTurbo\Model\Request::addFeed($arFields['LID'], 'production', $data, $this->isGzip, $this->feed['FIELDS']['HOST_ID_SUBDOMAIN']);
					if(is_array($result) && isset($result['task_id']))
					{
						$resultTask = \Goodde\YandexTurbo\TaskTable::add(array('FEED_ID' => $arFields['ID'], 'LID' => $arFields['LID'], 'TASK_ID' => $result['task_id'], 'MODE' => 'PRODUCTION'));
						if($resultTask->isSuccess())
						{
							$taskId = $resultTask->getId();
							$newName = str_replace('.xml', '_'.$taskId.'.xml', $file->getName());
							if(rename($file->getPhysicalPath(), $this->getPath().'/reports/'.$newName))
							{
								if($this->isGzip)
								{
									$fileName = $this->addArchive($this->getPath().'/reports/'.$newName, $data);
									if(strlen($fileName) > 0)
									{
										$newName = $fileName;
									}
									unset($fileName);
								}
								\Goodde\YandexTurbo\TaskTable::update($taskId, array('NAME' => $newName));
							}
							unset($newName);
						}
						$j++;
					}
					else
					{
						$newName = str_replace('.xml', '_'.$arFields['ID'].'_'.strftime('%Y-%m-%d_%H-%M-%S').'.xml', $file->getName());
						if(rename($file->getPhysicalPath(), $this->getPath().'/reports/'.$newName))
						{
							if($this->isGzip)
							{
								$this->addArchive($this->getPath().'/reports/'.$newName, $data);
								
							}
						}
						unset($newName);
						
						$arErrors[$file->getName()] = array(
							'ERROR_CODE' => $result['error_code'],
							'ERROR_MESSAGE' => $result['error_message'],
						);
						$n++;
					}
					
					if($i++ == 9)
					{
						break;
					}	
				}				
			}
			\Goodde\YandexTurbo\FeedTable::update($arFields['ID'], array('IS_NOT_UPLOAD_FEED' => ($count - $j > 0 ? 'Y' : 'N')));
		}
		return array(
			'TOTAL_FILE' => $count,
			'PROCESSED' => $i,
			'ADD' => $j,
			'ERROR' => $n,
		);
	}
	
	public function addArchive($path = '', $data = '')
	{
		$fileName = '';
		$file = new \Bitrix\Main\IO\File($path, null);
		if($file->isExists())
		{
			$directoryName = $file->getDirectoryName();
			$fileName = str_replace('.xml', '', $file->getName());
			$fileNameZip = $directoryName.'/'.$fileName.'.gz';
			
			if(strlen($data) > 0)
			{
				$put = file_put_contents($fileNameZip, $data);
			}
			else
			{
				$data = file_get_contents($file->getPath());
				/*bind events*/
				foreach(GetModuleEvents("goodde.yandexturboapi", "OnBeforeContentAdd", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array($arFields, &$data));
				}
				
				$put = file_put_contents($fileNameZip, gzencode($data, $this->gzipLevel));
			}
			if(intval($put) > 0)
			{
				$file->delete();
				$file = new \Bitrix\Main\IO\File($fileNameZip, null);
				$fileName = $file->getName();
			}
		}
		return $fileName;
	}
	
	public function addFalseByCsv($fileName = '',  $lid = '', $arFields = array())
    {	
		global $runError;
		$str = '<?xml version="1.0" encoding="'.SITE_CHARSET.'"?>';
        $str .= '<rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" xmlns:turbo="http://turbo.yandex.ru" version="2.0">';
        $str .= "<channel>\n";
		$path = \Goodde\YandexTurbo\Turbo::getPath().'/'.$fileName;
		if(!$fp = @fopen($path, "wb"))
		{
			$directory = \Bitrix\Main\IO\Directory::createDirectory(\Goodde\YandexTurbo\Turbo::getPath().'/');
			if($directory->isExists())
			{
				$fp = @fopen($path, "wb");
			}
			else
			{
				$runError = str_replace('#FILE#', $path, Loc::getMessage('GOODDE_TYRBO_API_FILE_OPEN_WRITING'));
			}
		}
		if($fp)
		{
			$numberItem = 0;
			if($arFields)
			{
				foreach($arFields as $arField)
				{
					if($arField['URL'])
					{
						$str .= '<item turbo="false"><link>'.$arField['URL'].'</link><turbo:content><![CDATA[<header><h1>false</h1></header><p>&nbsp;</p>]]></turbo:content>'."</item>\n";
					}
					$numberItem ++;
					if($numberItem == $this->amountItem)
					{
						break;
					}	
				}
				fwrite($fp, $str);
			}
			$this->rssFooter($fp);
			
			$file = new \Bitrix\Main\IO\File($path);
			if($file->isExists())
			{
				$data = $file->getContents();
				if($this->isGzip)
				{
					$data = gzencode($data, $this->gzipLevel);
				}
				$result = \Goodde\YandexTurbo\Model\Request::addFeed($lid, 'production', $data, $this->isGzip);
				if(is_array($result) && isset($result['task_id']))
				{
					$resultTask = \Goodde\YandexTurbo\TaskTable::add(array('FEED_ID' => 0, 'LID' => $lid, 'TASK_ID' => $result['task_id'], 'MODE' => 'PRODUCTION'));
					if($resultTask->isSuccess())
					{
						$taskId = $resultTask->getId();
						$newName = str_replace('.xml', '_'.$taskId.'.xml', $file->getName());
						if(rename($file->getPhysicalPath(), $this->getPath().'/reports/'.$newName))
						{
							if($this->isGzip)
							{
								$fileName = $this->addArchive($this->getPath().'/reports/'.$newName, $data);
								if(strlen($fileName) > 0)
								{
									$newName = $fileName;
								}
								unset($fileName);
							}
							\Goodde\YandexTurbo\TaskTable::update($taskId, array('NAME' => $newName));
							unset($newName);
						}
					}
				}
				else
				{
					$runError = $result['error_code']. ' - '.$result['error_message'];
				}
			}
			else
			{
				$runError = str_replace('#FILE#', $path, Loc::getMessage('GOODDE_TYRBO_API_FILE_OPEN_WRITING'));
			}
			return true;
		}
		return false;
	}
}
?>