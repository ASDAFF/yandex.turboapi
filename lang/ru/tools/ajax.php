<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


$MESS['YANDEX_TYRBO_API_ERROR_MODULE'] 				 = 'Модуль не установлен!';
$MESS['YANDEX_TYRBO_API_FUNCTION_LABEL']             = 'Обработать значение поля классом/функцией';
$MESS['YANDEX_TYRBO_API_USE_CONCAT_LABEL']           = 'Склеить значения полей';
$MESS['YANDEX_TYRBO_API_REQUIRED_LABEL']             = 'Не вырезать пустой xml-тег';
$MESS['YANDEX_TYRBO_API_USE_CONDITIONS_LABEL']       = 'Доп. условия для логического значения поля';
$MESS['YANDEX_TYRBO_API_USE_OFFER_CONDITIONS_LABEL'] = 'Доп. условия для логического значения поля ТП';
$MESS['YANDEX_TYRBO_API_TEXT_LENGTH_LABEL']          = 'Максимальная длина текста (символов)';
$MESS['YANDEX_TYRBO_API_TEXT_VALUE_LABEL']           = 'Использовать в значении поля готовый текст/HTML';
$MESS['YANDEX_TYRBO_API_BOOLEAN_LABEL']              = 'Заменить значение поля (1|Y|>0|true|Да) на логическое';
$MESS['YANDEX_TYRBO_API_DATE_FORMAT_LABEL']          = 'Формат даты для полей типа Дата';
$MESS['YANDEX_TYRBO_API_FIELDS_UNIT_PLACEHOLDER'] = 'Название раздела';
$MESS['YANDEX_TYRBO_API_SELECT_OPTION_EMPTY'] = '-- Все --';
$MESS['YANDEX_TYRBO_API_USE_IPROPERTY_LABEL'] = '(другое)';

$MESS['YANDEX_TYRBO_API_FIELDS'] = array(
	'content' => array(
		"CODE" => "content",
		"NAME" => "<b>Дополнительные поля содержимого страницы:</b>",
		//"REQUIRED" => 'N',
		//"USE_FUNCTION" => "Y",
		//"FUNCTION" => "htmlspecialchars",
		//"TYPE" => array('FIELD'),
		// "VALUE" => array("NAME"),
	),
	'accordion' => array(
		 'CODE' => 'accordion',
		 'NAME'  => "<b>Информационный раздел</b>
						<p>Разделы видны на всех страницах конкретного фида.<br>Примеры: «Оплата», «Доставка», «Таблица размеров»</p>",
		 'IS_CUSTOM' => 1,
	)
);

$MESS['YANDEX_TYRBO_API_BOOLEAN_VALUES'] = array(
	'' 				=> '(не выбрано)',
	'true/false'	=> 'true/false',
	'Y/N' 			=> 'Y/N',
	'Да/Нет'		=> 'Да/Нет',
);

$MESS['YANDEX_TYRBO_API_USE_CONCAT_VALUES'] = array(
	'SINGLE'   => 'В один тег',
	'MULTIPLE' => 'В несколько тегов',
);
$MESS['YANDEX_TYRBO_API_OFFER_FIELDS_LANG'] = array(
	'ID'                  => 'Уникальный идентификатор',
	'XML_ID'              => 'Внешний код из 1С',
	'CODE'                => 'Символьный код',
	'NAME'                => 'Название элемента',
	'IBLOCK_ID'           => 'ID инфоблока',
	'IBLOCK_CODE'         => 'Символический код инфоблока',
	'ACTIVE'              => 'Флаг активности (Y|N).',
	'DATE_CREATE'         => 'Дата создания элемента',
	'ACTIVE_FROM'         => 'Дата начала активности',
	'ACTIVE_TO'           => 'Дата окончания активности',
	'SORT'                => 'Сортировка',
	'SEARCHABLE_CONTENT'  => 'Содержимое для поиска',
	'TIMESTAMP_X'         => 'Время последнего изменения',
	'MODIFIED_BY'         => 'Код пользователя, изменившего элемент',
	'PREVIEW_TEXT'        => 'Описание анонса',
	'PREVIEW_PICTURE'     => 'Изображение анонса',
	'DETAIL_TEXT'         => 'Детальное описание',
	'DETAIL_PICTURE'      => 'Детальное изображение',
	'IBLOCK_SECTION_ID'   => 'ID раздела',
	'IBLOCK_SECTION_NAME' => 'Название раздела',
	'LIST_PAGE_URL'       => 'Ссылка на страницу списка',
	'DETAIL_PAGE_URL'     => 'Ссылка на детальную страницу',
	'SHOW_COUNTER'        => 'Количество показов',
	'TAGS'                => 'Теги',
);
$MESS['YANDEX_TYRBO_API_IPROPERTY_FIELDS_LANG'] = array(
	array(
		'NAME'   => 'Настройки для элементов',
		'VALUES' => array(
			'ELEMENT_META_TITLE'       => 'Шаблон META TITLE',
			'ELEMENT_META_KEYWORDS'    => 'Шаблон META KEYWORDS',
			'ELEMENT_META_DESCRIPTION' => 'Шаблон META DESCRIPTION',
			'ELEMENT_PAGE_TITLE'       => 'Заголовок товара',
		),
	),

	array(
		'NAME'   => 'Настройки для картинок анонса элементов',
		'VALUES' => array(
			'ELEMENT_PREVIEW_PICTURE_FILE_ALT'   => 'Шаблон ALT',
			'ELEMENT_PREVIEW_PICTURE_FILE_TITLE' => 'Шаблон TITLE',
			'ELEMENT_PREVIEW_PICTURE_FILE_NAME'  => 'Шаблон имени файла',
		),
	),

	array(
		'NAME'   => 'Настройки для детальных картинок элементов',
		'VALUES' => array(
			'ELEMENT_DETAIL_PICTURE_FILE_ALT'   => 'Шаблон ALT',
			'ELEMENT_DETAIL_PICTURE_FILE_TITLE' => 'Шаблон TITLE',
			'ELEMENT_DETAIL_PICTURE_FILE_NAME'  => 'Шаблон имени файла',
		),
	),
);
$MESS['YANDEX_TYRBO_API_CATALOG_FIELDS_LANG'] = array(
	'AVAILABLE'    => 'Доступен для покупки (Y/N)',
	'VAT_INCLUDED' => 'НДС включен в цену (Y/N)',
	'QUANTITY'     => 'Доступное количество (100)',
	'WEIGHT'       => 'Вес (кг)',
	'LENGTH'       => 'Длина (мм)',
	'WIDTH'        => 'Ширина (мм)',
	'HEIGHT'       => 'Высота (мм)',
);

$MESS['YANDEX_TYRBO_API_PRICE_FIELDS_LANG'] = Array(
	'PERCENT' => 'Процент скидки (50)',
	'VALUE' => 'Базовая цена (1000)',
	'PRINT_VALUE' => 'Базовая цена (1 000 руб)',
	'DISCOUNT_VALUE' => 'Цена со скидкой (800)',
	'PRINT_DISCOUNT_VALUE' => 'Цена со скидкой (800 руб)',
	'DISCOUNT' => 'Сумма скидки (200)',
	'PRINT_DISCOUNT' => 'Сумма скидки (200 руб)',
	'OLD_PRICE' => 'Старая цена (1000)',
	'PRINT_OLD_PRICE' => 'Старая цена (1 000 руб)',
);