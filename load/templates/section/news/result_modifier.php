<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

/** @var array $arFields */
if($arFields['ITEMS'])
{
	foreach($arFields["ITEMS"] as $k => $arItem)
	{
		foreach($arItem['ELEMENTS'] as $key => $arElement)
		{
			$arVideo = array();
			if(isset($arElement['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
			{
				if(is_array($arElement['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
				{
					$arVideo = $arVideo + $arElement['PROPERTIES']['VIDEO_YOUTUBE']['~VALUE'];
				}
				elseif(strlen($arElement['PROPERTIES']['VIDEO_YOUTUBE']['VALUE']))
				{
					$arVideo[] = $arElement['PROPERTIES']['VIDEO_YOUTUBE']['~VALUE'];
				}
			}
			$arFields["ITEMS"][$k]['ELEMENTS'][$key]['VIDEO'] = $arVideo;
			unset($arVideo);
		}
	}	
}