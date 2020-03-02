<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

/** @var array $arFields */
if($arFields['ITEMS'])
{
	if(\Bitrix\Main\Loader::includeModule('currency'))
	{
		$strBaseCurrency = CCurrency::GetBaseCurrency();
	}
	foreach($arFields["ITEMS"] as $k => $arItem)
	{
		$arVideo = array();
		if(isset($arItem['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
		{
			if(is_array($arItem['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
			{
				$arVideo = $arVideo + $arItem['PROPERTIES']['VIDEO_YOUTUBE']['~VALUE'];
			}
			elseif(strlen($arItem['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
			{
				$arVideo[] = $arItem['PROPERTIES']['VIDEO_YOUTUBE']['~VALUE'];
			}
		}
		$arFields["ITEMS"][$k]['VIDEO'] = $arVideo;
		unset($arVideo);
		
		if($arItem['OFFERS'] && $strBaseCurrency)
		{
			$arFields["ITEMS"][$k]['MIN_PRICE'] = \Yandex\TurboAPI\Turbo::getMinPriceFromOffersExt($arItem['OFFERS'], $strBaseCurrency);
		}
		
		$arFields["ITEMS"][$k]['DISPLAY_TURBO_CONTENT_FIELDS'] = \Yandex\TurboAPI\Turbo::getStrContentFields($arItem['TURBO_CONTENT_FIELDS']);
	}	
}

