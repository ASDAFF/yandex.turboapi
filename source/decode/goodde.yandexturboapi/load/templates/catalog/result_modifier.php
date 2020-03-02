<?
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
			$arFields["ITEMS"][$k]['MIN_PRICE'] = \Goodde\YandexTurbo\Turbo::getMinPriceFromOffersExt($arItem['OFFERS'], $strBaseCurrency);
		}	
	}	
}

