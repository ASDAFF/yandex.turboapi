<?php
namespace Goodde\Export;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TurboProfileTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SORT int optional default 500
 * <li> LID string(2) optional
 * <li> CHARSET string(255) optional
 * <li> TIMESTAMP_X datetime mandatory default 'current_timestamp()'
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> NAME string(255) optional
 * <li> LIMIT int optional default 500
 * <li> FILE_PATH string(255) optional
 * <li> SHOP_NAME string(20) optional
 * <li> SHOP_COMPANY string(255) optional
 * <li> SHOP_URL string(255) optional
 * <li> CURRENCY string optional
 * <li> PRICE_CODE string(255) optional
 * <li> DETAIL_URL string(255) optional
 * <li> DELIVERY string optional
 * <li> DIMENSIONS string optional
 * <li> UTM_TAGS string optional
 * <li> USE_CATALOG bool optional default 'Y'
 * <li> USE_SUBSECTIONS bool optional default 'N'
 * <li> IBLOCK_TYPE_ID string optional
 * <li> IBLOCK_ID int mandatory
 * <li> SECTION_ID string optional
 * <li> ELEMENTS_FILTER string optional
 * <li> OFFERS_FILTER string optional
 * <li> ELEMENTS_CONDITION string optional
 * <li> OFFERS_CONDITION string optional
 * <li> TYPE string optional
 * <li> FIELDS string optional
 * <li> LAST_START datetime optional
 * <li> LAST_END datetime optional
 * <li> TOTAL_ITEMS int optional
 * <li> TOTAL_ELEMENTS int optional
 * <li> TOTAL_OFFERS int optional
 * <li> TOTAL_SECTIONS int optional
 * <li> TOTAL_RUN_TIME string(255) optional
 * <li> TOTAL_MEMORY string(255) optional
 * <li> IN_AGENT bool optional default 'N'
 * </ul>
 *
 * @package Goodde\Export
 **/

class TurboProfileTable extends Main\Entity\DataManager
{
	protected static $exportFilePath  = '/upload/yandex_turbo/xml_export_#id#.xml';
	protected static $commaFields     = array(
		 'SECTION_ID',
	);
	protected static $serializeFields = array(
		'ELEMENTS_CONDITION', 'OFFERS_CONDITION', 'FIELDS', 'DELIVERY', 'CURRENCY', 'ELEMENTS_FILTER', 'OFFERS_FILTER', 'UTM_TAGS'
	);
	
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'goodde_yandex_turbo_profile';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'editable' => false,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_ID_FIELD'),
			)),
			new Main\Entity\IntegerField('SORT', array(
				'format' => '/^[0-9]{1,11}$/',
				'default_value' => 500,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_SORT_FIELD'),
			)),
			new Main\Entity\StringField('LID',  array(
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_LID_FIELD'),
			)),
			new Main\Entity\StringField('CHARSET',  array(
				'validation' => array(__CLASS__, 'validateCharset'),
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_CHARSET_FIELD'),
				'hidden' => true,
			)),
			new Main\Entity\DatetimeField('TIMESTAMP_X',  array(
				'required' => true,
				'default_value' => new Main\Type\DateTime(),
				'editable' => false,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TIMESTAMP_X_FIELD'),
			)),
			new Main\Entity\IntegerField('MODIFIED_BY',  array(
				'editable' => false,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_MODIFIED_BY_FIELD'),
			)),
			new Main\Entity\DatetimeField('DATE_CREATE',  array(
				'default_value' => new Main\Type\DateTime(),
				'editable' => false,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_DATE_CREATE_FIELD'),
			)),
			new Main\Entity\IntegerField('CREATED_BY',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_CREATED_BY_FIELD'),
			)),
			new Main\Entity\BooleanField('ACTIVE',  array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_ACTIVE_FIELD'),
			)),
			new Main\Entity\StringField('NAME',  array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_NAME_FIELD'),
			)),
			new Main\Entity\IntegerField('LIMIT',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_LIMIT_FIELD'),
			)),
			new Main\Entity\StringField('FILE_PATH',  array(
				'validation' => array(__CLASS__, 'validateFilePath'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_FILE_PATH_FIELD'),
			)),
			new Main\Entity\StringField('SHOP_NAME',  array(
				'validation' => array(__CLASS__, 'validateShopName'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_SHOP_NAME_FIELD'),
			)),
			new Main\Entity\StringField('SHOP_COMPANY',  array(
				'validation' => array(__CLASS__, 'validateShopCompany'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_SHOP_COMPANY_FIELD'),
			)),
			new Main\Entity\StringField('SHOP_URL',  array(
				'validation' => array(__CLASS__, 'validateShopUrl'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_SHOP_URL_FIELD'),
			)),
			new Main\Entity\TextField('CURRENCY',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_CURRENCY_FIELD'),
			)),
			new Main\Entity\StringField('PRICE_CODE',  array(
				'validation' => array(__CLASS__, 'validatePriceCode'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_PRICE_CODE_FIELD'),
			)),
			new Main\Entity\StringField('DETAIL_URL',  array(
				'validation' => array(__CLASS__, 'validateDetailUrl'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_DETAIL_URL_FIELD'),
			)),
			new Main\Entity\TextField('DELIVERY',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_DELIVERY_FIELD'),
			)),
			new Main\Entity\TextField('DIMENSIONS',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_DIMENSIONS_FIELD'),
			)),
			new Main\Entity\TextField('UTM_TAGS',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_UTM_TAGS_FIELD'),
			)),
			new Main\Entity\BooleanField('USE_CATALOG',  array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_USE_CATALOG_FIELD'),
			)),
			new Main\Entity\BooleanField('USE_SUBSECTIONS',  array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_USE_SUBSECTIONS_FIELD'),
			)),
			new Main\Entity\IntegerField('IBLOCK_TYPE_ID',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_IBLOCK_TYPE_ID_FIELD'),
			)),
			new Main\Entity\IntegerField('IBLOCK_ID',  array(
				'required' => true,
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_IBLOCK_ID_FIELD'),
			)),
			new Main\Entity\TextField('SECTION_ID',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_SECTION_ID_FIELD'),
			)),
			new Main\Entity\TextField('ELEMENTS_FILTER',  array(
				'hidden' => true,
				'serialized' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_ELEMENTS_FILTER_FIELD'),
			)),
			new Main\Entity\TextField('OFFERS_FILTER',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_OFFERS_FILTER_FIELD'),
			)),
			new Main\Entity\TextField('ELEMENTS_CONDITION',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_ELEMENTS_CONDITION_FIELD'),
			)),
			new Main\Entity\TextField('OFFERS_CONDITION',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_OFFERS_CONDITION_FIELD'),
			)),
			new Main\Entity\TextField('TYPE',  array(
				'validation' => array(__CLASS__, 'validateType'),
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TYPE_FIELD'),
			)),
			new Main\Entity\TextField('FIELDS',  array(
				'hidden' => true,
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_FIELDS_FIELD'),
			)),
			new Main\Entity\DatetimeField('LAST_START',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_LAST_START_FIELD'),
			)),
			new Main\Entity\DatetimeField('LAST_END',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_LAST_END_FIELD'),
			)),
			new Main\Entity\IntegerField('TOTAL_ITEMS',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_ITEMS_FIELD'),
			)),
			new Main\Entity\IntegerField('TOTAL_ELEMENTS',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_ELEMENTS_FIELD'),
			)),
			new Main\Entity\IntegerField('TOTAL_OFFERS',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_OFFERS_FIELD'),
			)),
			new Main\Entity\IntegerField('TOTAL_SECTIONS',  array(
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_SECTIONS_FIELD'),
			)),
			new Main\Entity\StringField('TOTAL_RUN_TIME',  array(
				'validation' => array(__CLASS__, 'validateTotalRunTime'),
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_RUN_TIME_FIELD'),
			)),
			new Main\Entity\StringField('TOTAL_MEMORY',  array(
				'validation' => array(__CLASS__, 'validateTotalMemory'),
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_TOTAL_MEMORY_FIELD'),
			)),
			new Main\Entity\BooleanField('IN_AGENT',  array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('TURBO_PROFILE_ENTITY_IN_AGENT_FIELD'),
			)),
		);
	}
	
	public static function OnAfterAdd(Main\Entity\Event $event)
	{
		if($id = $event->getParameter('id'))
		{
			static::update($id, array(
				 'FILE_PATH' => str_replace('#id#', $id.'_'.randString(7), static::$exportFilePath),
			));
		}
	}
	
	public static function decodeFields(&$fields)
	{
		if($fields)
		{
			foreach($fields as $key => &$val)
			{
				if(is_string($val))
				{
					if(in_array($key, self::$commaFields))
						$val = (strlen($val) > 0 ? explode(',', $val) : '');
					elseif(in_array($key, self::$serializeFields))
						$val = (strlen($val) > 0 ? unserialize($val) : array());
					else
						$val = htmlspecialcharsbx($val);
				}
			}
		}
	}

	public static function encodeFields(&$fields)
	{
		if($fields)
		{
			foreach($fields as $key => &$val)
			{
				if(in_array($key, self::$commaFields))
					$val = !empty($val) ? implode(',', $val) : '';

				if(in_array($key, self::$serializeFields))
					$val = !empty($val) ? serialize($val) : '';
			}
		}
	}
	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for CHARSET field.
	 *
	 * @return array
	 */
	public static function validateCharset()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for FILE_PATH field.
	 *
	 * @return array
	 */
	public static function validateFilePath()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for SHOP_NAME field.
	 *
	 * @return array
	 */
	public static function validateShopName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 80),
		);
	}
	/**
	 * Returns validators for SHOP_COMPANY field.
	 *
	 * @return array
	 */
	public static function validateShopCompany()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for SHOP_URL field.
	 *
	 * @return array
	 */
	public static function validateShopUrl()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PRICE_CODE field.
	 *
	 * @return array
	 */
	public static function validatePriceCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for DETAIL_URL field.
	 *
	 * @return array
	 */
	public static function validateDetailUrl()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for TOTAL_RUN_TIME field.
	 *
	 * @return array
	 */
	public static function validateTotalRunTime()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for TOTAL_MEMORY field.
	 *
	 * @return array
	 */
	public static function validateTotalMemory()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}
?>