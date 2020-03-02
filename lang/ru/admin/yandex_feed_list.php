<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$MESS['YANDEX_TYRBO_API_ERROR_MODULE_DEMO_EXPIRED'] = "Срок работы демо-режима модуля истек!";
$MESS['YANDEX_TYRBO_API_ERROR_MODULE'] = "Модуль не установлен!";
$MESS['YANDEX_TYRBO_API_DEL_CONF'] = 'Точно?';
$MESS['YANDEX_TYRBO_API_ADMIN_TITLE'] = 'Список RSS-каналов';
$MESS['YANDEX_TYRBO_API_ADMIN_FILTER_CREATED'] = 'Дата создания';
$MESS['YANDEX_TYRBO_API_ADMIN_FILTER_USER_ID'] = 'Пользователь';
$MESS['YANDEX_TYRBO_API_ADMIN_NAV'] = 'RSS-Каналы';
$MESS['YANDEX_TYRBO_API_DELETE'] = 'Удалить';
$MESS['YANDEX_TYRBO_API_EDIT'] = 'Изменить';
$MESS['YANDEX_TYRBO_API_FILTER_NAME'] = 'Название';
$MESS['YANDEX_TYRBO_API_FILTER_ACTIVE'] = 'Активность';
$MESS['YANDEX_TYRBO_API_ALL'] = 'Все';
$MESS['YANDEX_TYRBO_API_YES'] = 'Да';
$MESS['YANDEX_TYRBO_API_NO'] = 'Нет';
$MESS['YANDEX_TYRBO_API_SAVE_ERROR'] = 'Ошибка сохранения!';
$MESS['YANDEX_TYRBO_API_NO_ELEMENT'] = 'Не найден элемент';
$MESS['YANDEX_TYRBO_API_DELETE_ERROR'] = 'Ошибка удаления!';
$MESS['YANDEX_TYRBO_API_ADD'] = 'Добавить RSS-канал';
$MESS["YANDEX_TYRBO_API_ERRORS"] = "Ошибки при выполнении операции:";
$MESS["YANDEX_TYRBO_API_SUCCESS"] = "Операция успешно завершена.";
$MESS["YANDEX_TYRBO_API_SET"] = "Установить";
$MESS["YANDEX_TYRBO_API_DELETE"] = "Удалить";
$MESS["YANDEX_TYRBO_API_CLOSE"] = "Закрыть";
$MESS["YANDEX_TYRBO_API_RUN_INTERVAL"] = "Интервал между запусками (часов):";
$MESS["YANDEX_TYRBO_API_AGENT_DESCR"] = "Создать агента автоматического выполнения";
$MESS["YANDEX_TYRBO_API_AGENT"] = "Создать агента";
$MESS["YANDEX_TYRBO_API_AGENT_DESCR_DEL"] = "Удалить агента автоматического выполнения";
$MESS["YANDEX_TYRBO_API_AGENT_DEL"] = "Удалить агента";
$MESS["YANDEX_TYRBO_API_NOTES1"] = "Агенты - это PHP-функции, которые запускаются с определенной периодичностью. В самом начале загрузки каждой страницы система автоматически проверяет, есть ли агент, который нуждается в запуске, и в случае необходимости исполняет его. Не рекомендуется создавать агентов для длительных по времени выгрузок. Для этих случаев лучше использовать cron.";
$MESS["YANDEX_TYRBO_API_NOTES2"] = "Утилита cron доступна только на хостингах, работающих под операционными системами семейства UNIX.";
$MESS["YANDEX_TYRBO_API_NOTES3"] = "Утилита cron работает в фоновом режиме и выполняет указанные задачи в указанное время. Для включения экспорта в список задач необходимо установить конфигурационный файл";
$MESS["YANDEX_TYRBO_API_NOTES4"] = "в cron. Этот файл содержит инструкции на выполнение указанных вами экспортов. После изменения набора экспортов, установленных на cron, необходимо заново установить конфигурационный файл.";
$MESS["YANDEX_TYRBO_API_NOTES5"] = "Для установки конфигурационного файла необходимо соединиться с вашим сайтом по SSH (SSH2) или какому-либо другому аналогичному протоколу, поддерживаемому вашим провайдером для удаленного доступа. В строке ввода нужно выполнить команду";
$MESS["YANDEX_TYRBO_API_NOTES6"] = "Для просмотра списка установленных задач нужно выполнить команду";
$MESS["YANDEX_TYRBO_API_NOTES7"] = "Для удаления списка установленных задач нужно выполнить команду";
$MESS["YANDEX_TYRBO_API_NOTES8"] = "Текущий список установленных на cron задач:";
$MESS["YANDEX_TYRBO_API_NOTES10"] = "Внимание! Если у вас установлены на cron задачи, которых нет в конфигурационном файле, то при применении этого файла такие задачи будут удалены.";
$MESS["YANDEX_TYRBO_API_NOTES11_EXT"] = "Оболочкой для выполнения задач на cron является файл<br><b>#FILE#</b> (путь указан от корня сайта).";
$MESS["YANDEX_TYRBO_API_NOTES12_EXT"] = "Убедитесь, что в нем прописаны правильные пути к php и корню сайта (<b>\$_SERVER['DOCUMENT_ROOT']<b>).";
$MESS["YANDEX_TYRBO_API_NOTES13_EXT"] = "Если по указанному пути <b>cron_frame.php</b> отсутствует, необходимо скопировать его из папки <b>#FOLDER#</b> (путь указан от корня сайта).";
$MESS["YANDEX_TYRBO_API_EXPORT_SETUP_CAT"] = "Скрипты экспорта находятся в каталоге:";
?>