<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\TurboAPI\Model;

use Bitrix\Main\Type,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Request
{
	private static $moduleId = 'yandex.turboapi';
	private static $protocol = 'https://';
	private static $host = 'api.webmaster.yandex.net';
	private static $version = '/v4/';
	private static $curlTimeout = 10;
	
	
	public static function getPropSite($siteId = '')
	{
		$arTurboProp = array();	
		$turboProp = Option::get(self::$moduleId, 'turbo_prod', '', $siteId);
		if (strlen($turboProp) > 0)
			$arTurboProp = unserialize($turboProp);
		
		return $arTurboProp;
	}
	
	public static function getServerAddress($siteId = '')
	{			
		$arProp = self::getPropSite($siteId);
		return self::getHostNamebyYandexHostId($arProp['host_id']);
	}
	
	public static function getHostNamebyYandexHostId($hostId = '')
	{			
		$strHost = '';
		if($hostId)
		{
			$arHost = explode(':', $hostId);
			$strHost .= $arHost[0].'://'.$arHost[1];
		}
		return $strHost;
	}
	
	public static function getToken($siteId = '')
	{			
		$arToken = self::getPropSite($siteId);
		return $arToken['token'];
	}
	
	public static function getUserId($siteId = '')
	{			
		$arUserId = self::getPropSite($siteId);
		return $arUserId['user_id'];
	}
	
	public static function getHostId($siteId = '')
	{		
		$arHostId = self::getPropSite($siteId);
		return $arHostId['host_id'];
	}
	
	public static function getUrl($version = '/v4/')
	{
		$url = self::$protocol.self::$host.$version;
		return $url;
	}
	
	public static function getHeaderList($siteId = '')
	{
		return array(
			'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
			'Authorization: OAuth '. self::getToken($siteId)
		);
	}
	
	public static function strUser()
	{
		return self::getUrl(self::$version).'user/';
	}
	
	public static function strHost($siteId = '')
	{
		return self::getUrl(self::$version).'user/'.self::getUserId($siteId).'/hosts/';
	}
	
	public static function strUploadAddress($siteId = '', $mode = '', $subdomainHostId = '')
	{
		$hostId = (strlen($subdomainHostId) > 0 ? $subdomainHostId : self::getHostId($siteId));
		return self::getUrl(self::$version).'user/'.self::getUserId($siteId).'/hosts/'.$hostId.'/turbo/uploadAddress/?mode='.$mode;
	}
	
	public static function stsTaskId($siteId = '', $taskId = '')
	{
		return self::getUrl(self::$version).'user/'.self::getUserId($siteId).'/hosts/'.self::getHostId($siteId).'/turbo/tasks/'.$taskId;
	}
	
	public static function strAddAddress($siteId = '', $mode = '', $subdomainHostId = '')
	{
		$strAddAddress = '';
		$arProp = Request::curUploadAddress($siteId, $mode, $subdomainHostId);
		if(strlen($arProp['url_'.$mode]) > 0)
		{
			$strAddAddress = $arProp['url_'.$mode];
		}
		return $strAddAddress;
	}
	
	public static function curUser($siteId = '')
	{
		$arUserId = array();
		if(function_exists('curl_init'))
		{
			if($curl = curl_init(self::strUser()))
			{
				$arHeaderList = self::getHeaderList($siteId);
				$arHeaderList[] = 'Accept: application/json';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
				curl_setopt($curl, CURLOPT_TIMEOUT, self::$curlTimeout);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				$result = curl_exec($curl);
				curl_close($curl);
				$arResult = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
				if(isset($arResult['error_code']))
				{
					$GLOBALS['APPLICATION']->ThrowException($arResult['error_message'], $arResult['error_code']);
				}
				else
				{
					$arUserId['user_id'] = $arResult['user_id'];
				}
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('curl_init function doesnt exist');
		}
		return $arUserId;
	}
	
	public static function curHost($siteId = '')
	{
		$arHosts = array();
		if(function_exists('curl_init'))
		{
			if($curl = curl_init(self::strHost($siteId)))
			{
				$arHeaderList = self::getHeaderList($siteId);
				$arHeaderList[] = 'Accept: application/json';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
				curl_setopt($curl, CURLOPT_TIMEOUT, self::$curlTimeout);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				$result = curl_exec($curl);
				curl_close($curl);
				$arResult = json_decode($result, true);
				if(isset($arResult['error_code']))
				{
					$GLOBALS['APPLICATION']->ThrowException($arResult['error_message'], $arResult['error_code']);
				}
				else
				{	
					$arHosts = $arResult['hosts'];
				}
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('curl_init function doesnt exist');
		}
		return $arHosts;
	}
	
	public static function curUploadAddress($siteId = '', $mode = '', $subdomainHostId = '')
	{	
		$arUploadAddress = array();
		if(function_exists('curl_init'))
		{
			if($curl = curl_init(self::strUploadAddress($siteId, $mode, $subdomainHostId)))
			{
				$arHeaderList = self::getHeaderList($siteId);
				$arHeaderList[] = 'Accept: application/json';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
				curl_setopt($curl, CURLOPT_TIMEOUT, self::$curlTimeout);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				$result = curl_exec($curl);
				curl_close($curl);
				$arResult = json_decode($result, true);
				if(isset($arResult['error_code']))
				{
					$GLOBALS['APPLICATION']->ThrowException($arResult['error_message'], $arResult['error_code']);
				}
				else
				{	
					$arUploadAddress['url_'.strtolower($mode)] = $arResult['upload_address'];
				}
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('curl_init function doesnt exist');
		}
		return $arUploadAddress;
	}
	
	public static function addFeed($siteId = '', $mode = '', $data = '', $isGzip = false, $subdomainHostId = '')
	{	
		$arResult = array();
		if(function_exists('curl_init'))
		{
			if($curl = curl_init(self::strAddAddress($siteId, $mode, $subdomainHostId)))
			{
				$arHeaderList = self::getHeaderList($siteId);
				$arHeaderList[] = 'Content-type: application/rss+xml';
				if($isGzip)
				{
					$arHeaderList[] = 'Content-Encoding: gzip';
				}
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
				curl_setopt($curl, CURLOPT_TIMEOUT, self::$curlTimeout);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				if($isGzip)
				{
					curl_setopt($curl, CURLOPT_ENCODING , 'gzip'); 
				}
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				$result = curl_exec($curl);
				curl_close($curl);
				$arResult = json_decode($result, true);
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('curl_init function doesnt exist');
		}
		return $arResult;
	}
	
	public static function getFeed($siteId = '', $taskId = '')
	{
		$arResult = array();
		if(function_exists('curl_init'))
		{
			if($curl = curl_init(self::stsTaskId($siteId, $taskId)))
			{
				$arHeaderList = self::getHeaderList($siteId);
				$arHeaderList[] = 'Accept: application/json';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
				curl_setopt($curl, CURLOPT_TIMEOUT, self::$curlTimeout);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				$result = curl_exec($curl);
				curl_close($curl);
				$arResult = json_decode($result, true);
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('curl_init function doesnt exist');
		}
		return $arResult;
	}
}