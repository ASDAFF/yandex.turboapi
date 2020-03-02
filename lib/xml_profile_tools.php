<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

namespace Yandex\Export;

use Bitrix\Main\Type,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Text\Converter;

Loc::loadMessages(__FILE__);

class ProfileTools
{
	public static function getGuid($value = '', $field = array(), $item = array(), $profile = array())
	{
		return $profile['SHOP_URL'] . '/' . $value;
	}

	public static function getPreviewText($value = '', $field = array(), $item = array(), $profile = array())
	{
		$text = trim($value);
		if(strlen($text) > 0)
		{
			$text = "<![CDATA[" . static::clearSpaces($text) . "]]>";
		}

		return $text;
	}

	public static function getDetailText($value = '', $field = array(), $item = array(), $profile = array())
	{
		$text = trim($value);

		if(strlen($text) > 0) 
		{
			preg_match_all("/<img[\s\S]*>/U", $text, $matches);
			if($matches[0]) {
				foreach($matches[0] as $value)
				{
					$text = str_replace($value, '<figure>' . $value . '</figure>', $text);
				}
			}
		}

		if(strlen($text) > 0)
		{
			$text = "<![CDATA[" . static::clearSpaces($text) . "]]>";
		}

		return $text;
	}

	public static function getDetailMedia($value = '', $field = array(), $item = array(), $profile = array())
	{
		$text = trim($value);

		$result = array();
		preg_match_all("/<img[\s\S]*>/U", $text, $matches);

		if($matches[0]) {
			foreach($matches[0] as $key => $value) 
			{
				preg_match("/src=[\"'](.+)[\"']/U", $value, $matches_src);
				
				$src = $matches_src[1];
				if(strlen($src) > 0)
				{
					if(strpos($src, "resizer2GD.php") !== false) 
					{
						$src = str_replace('/yenisite.resizer2/resizer2GD.php?url=', '', strtok($src, '&'));
					}

					$url  = (strpos($src, "http") === false) ? $profile['SHOP_URL'] . $src : $src;
					$info = getimagesize($url);

					if(empty($info['mime']))
					{
						$info['mime'] = 'image/' . substr($url, -3);
					}

					$result[ $key ] = array(
						 'url'    => $url,
						 'src'    => $src,
						 'width'  => $info[0],
						 'height' => $info[1],
						 'type'   => $info['mime'],
					);
				}
			}
		}

		return $result;
	}

	protected static function clearSpaces($text)
	{
		$text = preg_replace("/^\s+/im" . BX_UTF_PCRE_MODIFIER, "", $text);
		$text = preg_replace("/[\n\r]/im" . BX_UTF_PCRE_MODIFIER, "", $text);

		return $text;
	}
	
	public static function preGenerateExport($profileId)
    {
        $profileId = (int)$profileId;
        if ($profileId <= 0)
            return false;
		
		if(!\Bitrix\Main\Loader::includeModule('yandex.turboapi'))
			return false;

		if(!\Bitrix\Main\Loader::includeModule('iblock'))
			return false;
		
		$strFile = '/bitrix/modules/yandex.turboapi/load/xml_export_run.php';
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
			return false;
		
		include($_SERVER['DOCUMENT_ROOT'].$strFile);

        return "\Yandex\Export\ProfileTools::preGenerateExport(".$profileId.");";
    }
}