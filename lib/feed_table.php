<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


namespace Yandex\TurboAPI;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class FeedTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> IBLOCK_ID int optional
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> ALL_FEED bool optional default 'N'
 * <li> LID string optional
 * <li> NAME string optional
 * <li> DETAIL_URL string optional
 * <li> IPROPERTY_TEMPLATES string optional
 * <li> PRICE_CODE string optional
 * <li> SERVER_ADDRESS string optional
 * <li> DESCRIPTION text optional
 * <li> FIGCAPTION_VIDEO string optional
 * <li> SORT int optional default 500
 * <li> TEMPLATE string optional default '.default';
 * <li> ITEM_STATUS string optional
 * <li> MENU string optional
 * <li> FORM string optional
 * <li> FEEDBACK string optional
 * <li> CONTENT string optional
 * <li> PUB_DATE string optional
 * <li> LIMIT int optional default '1000'
 * <li> ACTIVE_DATE bool optional default 'N'
 * <li> ELEMENTS_FILTER string optional
 * <li> OFFERS_FILTER string optional
 * <li> ELEMENTS_CONDITION string optional
 * <li> OFFERS_CONDITION string optional
 * <li> PROPERTY string optional
 * <li> OFFERS_PROPERTY string optional
 * <li> GALLERY string optional
 * <li> SECTIONS_FILTER string optional
 * <li> IS_SECTION bool optional default 'N'
 * <li> SECTIONS_ID string optional
 * <li> FIELDS string optional
 * <li> SECTION_URL string optional
 * <li> RELATED_LIMIT int optional default '4'
 * <li> RELATED_SOURCE string optional default 'QUEUE'
 * <li> SHARE_NETWORKS string optional
 * <li> DATE_ADD_FEED datetime optional
 * <li> IS_NOT_UPLOAD_FEED bool optional default 'N'
 * <li> IN_AGENT bool optional default 'N'
 * </ul>
 *
 * @package Yandex\TurboAPI
 **/

class FeedTable extends Entity\DataManager
{
	const LEFT_TO_RIGHT = 'Y';
    const RIGHT_TO_LEFT = 'N';
	
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'yandex_yandex_turbo_feed';
	}
	
	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_ID_FIELD'),
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_IBLOCK_ID_FIELD'),
				'validation' => array(__CLASS__, 'validateIblockId'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_MODIFIED_BY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_DATE_CREATE_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_CREATED_BY_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'default' => self::LEFT_TO_RIGHT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_ACTIVE_FIELD'),
				'editable' => true,
			),
			'LID' => array(
				'data_type' => 'string',
				'type_field' => 'lid',
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_LID_FIELD'),
				'editable' => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_NAME_FIELD'),
				'validation' => array(__CLASS__, 'validateName'),
				'editable' => true
			),
			'DETAIL_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_DETAIL_URL_FIELD'),
				'validation' => array(__CLASS__, 'validateDetailUrl'),
				'editable' => true,
				'hidden' => true,
			),
			'PRICE_CODE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_PRICE_CODE_FIELD'),
				'validation' => array(__CLASS__, 'validatePriceCode'),
				'editable' => true,
				'hidden' => true,
			),
			'SERVER_ADDRESS' => array(
                'data_type' => 'string',
                'required' => true,
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_SERVER_ADDRESS_FIELD'),
                'validation' => array(__CLASS__, 'validateServerAddress'),
				'editable' => true,
            ),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_DESCRIPTION_FIELD'),
				'editable' => true,
				'hidden' => true,
			),
			'FIGCAPTION_VIDEO' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('YANDEX_TYRBO_API_FIGCAPTION_VIDEO_FIELD'),
				'validation' => array(__CLASS__, 'validateFigcaptionVideo'),
				'editable' => true,
				'hidden' => true,
			),
			'SORT' => array(
				'data_type' => 'integer',
				'default' => '500', 
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_SORT_FIELD'),
				'validation' => array(__CLASS__, 'validateSort'),
				'editable' => true
			),
			'TEMPLATE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateTemplate'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_TEMPLATE_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'ITEM_STATUS' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_ITEM_STATUS_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'IPROPERTY_TEMPLATES' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_IPROPERTY_TEMPLATES_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'ALL_FEED' => array(
				'data_type' => 'boolean',
				'default' => self::RIGHT_TO_LEFT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_ALL_FEED_FIELD'),
				'editable' => true,
				'hidden' => true
			),
			'LIMIT' => array(
                'data_type' => 'integer',
				'default' => '1000',
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_LIMIT_FIELD'), 
				'editable' => true,
				'hidden' => true
            ),
            'ACTIVE_DATE' => array(
                'data_type' => 'boolean',
                'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_ACTIVE_DATE_FIELD'),
                'default_value' => self::RIGHT_TO_LEFT,
				'editable' => true,
				'hidden' => true
            ),
			'SECTIONS_FILTER' => array(
				'data_type' => 'text',
				'serialized' => true,
				'editable' => true,
				'hidden' => true,
			),
			'ELEMENTS_FILTER' => array(
				'data_type' => 'text',
				'serialized' => true,
				'editable' => true,
				'hidden' => true,
			),
			'OFFERS_FILTER' => array(
				'data_type' => 'text',
				'serialized' => true,
				'editable' => true,
				'hidden' => true,
			),
			'ELEMENTS_CONDITION' => array(
				'data_type' => 'text',
				'serialized' => true,
				'editable' => true,
				'hidden' => true,
			),
			'OFFERS_CONDITION' => array(
				'data_type' => 'text',
				'serialized' => true,
				'editable' => true,
				'hidden' => true,
			),
            'MENU' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_MENU_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
            'FORM' => array(
                'data_type' => 'string',
				'serialized' => true,
                'validation' => array(__CLASS__, 'validateForm'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_FORM_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'FEEDBACK' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_FEEDBACK_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
            'CONTENT' => array(
                'data_type' => 'string',
				'required' => true,
                'validation' => array(__CLASS__, 'validateContent'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_CONTENT_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
            'PUB_DATE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validatePubDate'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_PUB_DATE_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
            'GALLERY' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateGallery'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_GALLERY_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'IS_SECTION' => array(
				'data_type' => 'boolean',
				'default' => self::RIGHT_TO_LEFT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'hidden' => true
			),
			'SECTIONS_ID' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_SECTIONS_ID_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'FIELDS' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_FIELDS_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'SECTION_URL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSectionUrl'),
				'editable' => true,
				'hidden' => true,
			),
			'PROPERTY' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_PROPERTY_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'OFFERS_PROPERTY' => array(
                'data_type' => 'string',
				'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_OFFERS_PROPERTY_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
            'RELATED_LIMIT' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_RELATED_LIMIT_FIELD'),
				'default' => '4', 
				'editable' => true,
				'hidden' => true
            ),
            'RELATED_SOURCE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateRelatedSource'),
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_RELATED_SOURCE_FIELD'),
				'editable' => true,
				'hidden' => true
            ),			
            'SHARE_NETWORKS' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateShareNetworks'),
                'serialized' => true,
                'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_SHARE_NETWORKS_FIELD'),
				'editable' => true,
				'hidden' => true
            ),
			'DATE_ADD_FEED' => array(
				'data_type' => 'datetime',
				'hidden' => true
			),
			'IS_NOT_UPLOAD_FEED' => array(
				'data_type' => 'boolean',
				'default' => self::RIGHT_TO_LEFT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'hidden' => true
			),
			'IN_AGENT' => array(
				'data_type' => 'boolean',
				'default' => self::RIGHT_TO_LEFT,
				 'title' => Loc::getMessage('YANDEX_TYRBO_API_ENTITY_IN_AGENT_FIELD'),
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT)
			),
		);
	}
	
	public static function onAfterAdd(\Bitrix\Main\Entity\Event $event)
    {
        $feed = $event->getParameter('primary');
        if(intval($feed['ID']) > 0)
			\Bitrix\Main\IO\Directory::createDirectory(\Yandex\TurboAPI\Turbo::getPath().'/'.$feed['ID'].'/');
    }
	
	public static function OnBeforeDelete(\Bitrix\Main\Entity\Event $event)
    {
        $feed = $event->getParameter('primary');
        if(intval($feed['ID']) > 0)
		{
			$arFeed = \Yandex\TurboAPI\FeedTable::getById($feed['ID'])->fetch();
			$ipropTemlates = new \Bitrix\Iblock\InheritedProperty\IblockTemplates($arFeed['IBLOCK_ID']);
			$ipropTemlates->set(array(
				'G_ELEMENT_META_TITLE_'.$feed['ID'] => '',
				'G_ELEMENT_PAGE_TITLE_'.$feed['ID'] => '',
				'G_SECTION_META_TITLE_'.$feed['ID'] => '',
				'G_SECTION_PAGE_TITLE_'.$feed['ID'] => ''
			));
		}
		
    }
	
	public static function onAfterDelete(\Bitrix\Main\Entity\Event $event)
    {
	    $feed = $event->getParameter('primary');
        if(intval($feed['ID']) > 0)
		{
			\Bitrix\Main\IO\Directory::deleteDirectory(\Yandex\TurboAPI\Turbo::getPath().'/'.$feed['ID'].'/');
		}
    }
	
	/**
     * Returns validateIblockId for IBLOCK_ID field.
     *
     * @return array
     */
    public static function validateIblockId()
    {
        return array(
            new Entity\Validator\Length(null, 11),
        );
    }
	
	/**
	 * Returns validateLid for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	
	/**
     * Returns validateName for NAME field.
     *
     * @return array
     */
    public static function validateName()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
     * Returns validateDetailUrl for DETAIL_URL field.
     *
     * @return array
     */
    public static function validateDetailUrl()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
     * Returns validateSectionUrl for SECTION_URL field.
     *
     * @return array
     */
    public static function validateSectionUrl()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
     * Returns validatePriceCode for PRICE_CODE field.
     *
     * @return array
     */
    public static function validatePriceCode()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
     * Returns validateFigcaptionVideo for FIGCAPTION_VIDEO field.
     *
     * @return array
     */
    public static function validateFigcaptionVideo()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
	 * Returns validateSort for SORT field.
	 *
	 * @return array
	 */
	public static function validateSort()
	{
		return array(
			new Entity\Validator\Length(null, 11),
		);
	}
	
    /**
     * Returns validateServerAddress for SERVER_ADDRESS field.
     *
     * @return array
     */
    public static function validateServerAddress()
    {
        return array(
            new Entity\Validator\Length(null, 255),
            new Entity\Validator\RegExp('/^http/'),
        );
    }

    /**
     * Returns validatePubDate for PUB_DATE field.
     *
     * @return array
     */
    public static function validatePubDate()
    {
        return array(
            new Entity\Validator\Length(null, 20),
        );
    }
	
	 /**
     * Returns validateTemplate for TEMPLATE field.
     *
     * @return array
     */
    public static function validateTemplate()
    {
        return array(
            new Entity\Validator\Length(null, 100),
        );
    }
	
    /**
     * Returns validateContent for CONTENT field.
     *
     * @return array
     */
    public static function validateContent()
    {
        return array(
            new Entity\Validator\Length(null, 100),
        );
    }

    /**
     * Returns validateGallery for GALLERY field.
     *
     * @return array
     */
    public static function validateGallery()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }

    /**
     * Returns validateShareNetworks for SHARE_NETWORKS field.
     *
     * @return array
     */
    public static function validateShareNetworks()
    {
        return array(
            new Entity\Validator\Length(null, 255),
        );
    }
	
	/**
	 * Returns validateForm for FORM field.
	 *
	 * @return array
	 */
	public static function validateForm()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

    /**
     * Returns validateRelatedSource for RELATED_SOURCE field.
     *
     * @return array
     */
    public static function validateRelatedSource()
    {
        return array(
            new Entity\Validator\Length(null, 100),
        );
    }
}