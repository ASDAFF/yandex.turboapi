<?
namespace Goodde\YandexTurbo;

use Bitrix\Main\Type;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ArchiveFeedTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_CREATE datetime optional
 * <li> FEED_ID int optional
 * <li> ELEMENT_ID int optional
 * <li> IBLOCK_ID int optional
 * <li> LINK string optional
 * <li> DELETE_MARK bool optional default 'N'
 * <li> ITEM text optional
 * </ul>
 *
 * @package Goodde\YandexTurbo
 **/

class ArchiveFeedTable extends Entity\DataManager
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
		return 'goodde_yandex_turbo_archive';
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
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'FEED_ID' => array(
				'data_type' => 'integer',
			),
			'ELEMENT_ID' => array(
				'data_type' => 'integer',
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
			),
			'LINK' => array(
				'data_type' => 'string',
				'type_field' => 'lid',
			),
			'DELETE_MARK' => array(
				'data_type' => 'boolean',
				'default' => self::RIGHT_TO_LEFT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'editable' => true,
			),	
            'ITEM' => array(
                'data_type' => 'string',
                'serialized' => true,
            ),
		);
	}
}