<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use \Bitrix\Main\Localization\Loc;
Loc::loadLanguageFile(__FILE__);
?>

<form action="<?= $GLOBALS['APPLICATION']->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="yandex.turboapi">
    <input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
    <?= CAdminMessage::ShowMessage(Loc::getMessage('YANDEX_TYRBO_API_CAUTION')) ?>
    <p><?= Loc::getMessage('YANDEX_TYRBO_API_UNINST_SAVE_TITLE') ?></p>
    <p><input type="checkbox" name="save_tables" id="save_tables" value="Y" checked><label
            for="save_tables"><?= Loc::getMessage('YANDEX_TYRBO_API_UNINST_SAVE_TABLE') ?></label></p>
    <input type="submit" name="inst" value="<?= Loc::getMessage('YANDEX_TYRBO_API_UNINST_UNINST_MODULE') ?>">
</form>