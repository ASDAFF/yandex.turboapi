<?
namespace Goodde\YandexTurbo;

use Bitrix\Main\Type;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class TaskTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_CREATE datetime optional
 * <li> NAME string optional
 * <li> FEED_ID int optional
 * <li> LID string optional
 * <li> TASK_ID string optional
 * <li> MODE string optional
 * <li> RSS_FEED_DELETE string optional default 'N'
 * </ul>
 *
 * @package Goodde\YandexTurbo
 **/

class TaskTable extends Entity\DataManager
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
		return 'goodde_yandex_turbo_task';
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
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_ID_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_DATE_CREATE_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_NAME_FIELD'),
			),
			'FEED_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_FEED_ID_FIELD'),
			),
			'LID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_LID_FIELD'),
			),
			'TASK_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_TASK_ID_FIELD'),
			),
			'MODE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('GOODDE_TYRBO_API_ENTITY_MODE_FIELD'),
				'hidden' => true,
			),
			'RSS_FEED_DELETE' => array(
				'data_type' => 'string',
				'default' => self::RIGHT_TO_LEFT, 
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
				'hidden' => true,
			)
		);
	}
	
	public static function OnDelete(\Bitrix\Main\Entity\Event $event)
    {
	    $task = $event->getParameter('primary');
        if(intval($task['ID']) > 0)
		{
			$arTask = \Goodde\YandexTurbo\TaskTable::getById($task['ID'])->fetch();
			\Bitrix\Main\IO\File::deleteFile(\Goodde\YandexTurbo\Turbo::getPath().'/reports/'.$arTask['NAME']);
		}
    }
}