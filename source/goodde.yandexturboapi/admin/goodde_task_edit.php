<?
use Bitrix\Main\Loader,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Currency,
	Bitrix\Main\Web\Json,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Goodde\YandexTurbo\Turbo,
	Goodde\YandexTurbo\TaskTable,
	Goodde\YandexTurbo\Model\Request;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/goodde.yandexturboapi/admin/tools.php");

$moduleId = 'goodde.yandexturboapi';
Loc::loadMessages(__FILE__);

if(CModule::IncludeModuleEx($moduleId) == 3)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("GOODDE_TYRBO_API_ERROR_MODULE_DEMO_EXPIRED"),
		"TYPE" => "ERROR",
	));
	return;
}
elseif(!Loader::IncludeModule($moduleId))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("GOODDE_TYRBO_API_ERROR_MODULE"),
		"TYPE" => "ERROR",
	));
	return;
}

CJSCore::Init(array('goodde_yandexturboapi'));

$POST_RIGHT = $APPLICATION->GetGroupRight("goodde.yandexturboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_MAIN"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_MAIN")),
	array("DIV" => "edit2", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_PAGES"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_PAGES")),
	array("DIV" => "edit3", "TAB" => Loc::getMessage("GOODDE_TYRBO_API_TAB_ERROR"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_TAB_ERROR")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$ID = intval($ID);
//res
if($ID > 0) 
{
    if (!$arFields = TaskTable::getById($ID)->fetch()) 
		$ID = 0;
} 

$APPLICATION->SetTitle(Loc::getMessage("GOODDE_TYRBO_API_OPEN_TITLE").$ID);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$aMenu = array(
	array(
		"TEXT"=>Loc::getMessage("GOODDE_TYRBO_API_OPEN_LIST"),
		"TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_OPEN_LIST_TITLE"),
		"LINK"=>"goodde_task_list.php?lang=".LANG,
		"ICON"=>"btn_list",
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("GOODDE_TYRBO_API_DELETE"),
		"TITLE"=>Loc::getMessage("GOODDE_TYRBO_API_DELETE"),
		"LINK"=>"javascript:if(confirm('".Loc::getMessage("GOODDE_TYRBO_API_DELETE_CONF")."'))window.location='goodde_task_list.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
		"ICON"=>"btn_delete",
	);
}
$context = new CAdminContextMenu($aMenu);
$context->Show();

if($ID > 0)
{
	$arResult = Request::getFeed($arFields['LID'], $arFields['TASK_ID']);
}

// load directory structure
if(isset($_REQUEST['remove']) && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();

	if($_REQUEST['remove'] === 'Y')
	{
		$file = new \Bitrix\Main\IO\File(Turbo::getPath().'/reports/'.$arFields['NAME']);
		if($file->isExists())
		{
			if($file->delete())
			{
				TaskTable::update($ID, array('RSS_FEED_DELETE' => 'Y'));
				echo Loc::getMessage('GOODDE_TYRBO_API_RESULT_REMOVE');
			}
		}
	}
	
	die();
}
?>
<script>
BX.ready(function(){
	BX.bind(BX('load_status'), 'click', function (e) {
		e.preventDefault();
		location.reload();
	});
});
function removeFile()
{
	BX.ajax.get('<?=$APPLICATION->GetCurPageParam('', array('remove', 'Y'))?>', {remove: 'Y', sessid:BX.bitrix_sessid()}, function(res)
	{
		BX('remove').innerHTML = res;
	});
};
</script>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
if($ID > 0)
{
	if($arResult['error_code'])
	{
		?>
		<tr>
			<th width="40%"></th>
			<td width="60%">
			<?
			echo Loc::getMessage('GOODDE_TYRBO_API_ERROR_CODE', array(
				"#ERROR_CODE#" => $arResult['error_code'],
				"#ERROR_MESSAGE#" => $arResult['error_message'],
			));
			?>
			</td>
		</tr>
		<?
	}
	else
	{
		?>
		<tr>
			<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_MODE')?></th>
			<td width="60%"><?=$arResult['mode']?></td>
		</tr>
		<tr>
			<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_LOAD_STATUS')?></th>
			<td width="60%"><?=$arResult['load_status']?></td>
		</tr>
		<?
		if($arResult['load_status'] == 'PROCESSING')
		{
			?>
			<tr>
				<td width="3%"></td>
				<td width="70%"><a id="load_status" class="adm-btn adm-btn-save" href="<?=$APPLICATION->GetCurUri();?>"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_PROCESSING')?></a></td>
			</tr>
			<?
		}

		if($arResult['stats'])
		{
			?>
			<tr>
				<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_PAGES_COUNT')?></th>
				<td width="60%"><?=$arResult['stats']['pages_count']?></td>
			</tr>
			<tr>
				<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_ERRORS_COUNT')?></th>
				<td width="60%"><?=$arResult['stats']['errors_count']?></td>
			</tr>
			<tr>
				<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_WARNINGS_COUNT')?></th>
				<td width="60%"><?=$arResult['stats']['warnings_count']?></td>
			</tr>
			<?
		}
		
		if(!($arResult['load_status'] == 'PROCESSING' || $arFields['RSS_FEED_DELETE'] == 'Y'))
		{
			$file = new \Bitrix\Main\IO\File(Turbo::getPath().'/reports/'.$arFields['NAME']);
			if($file->isExists())
			{
				if($arResult['load_status'] == 'OK')
				{
					if($file->delete())
					{
						TaskTable::update($ID, array('RSS_FEED_DELETE' => 'Y'));
					}
				}
				else
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RSS_FEED')?></th>
						<td width="60%" id="remove">
							<div class="form-block">
								<span><a target="_blank" href="<?='/upload/yandex_turbo/reports/'.$file->getName()?>"><?=$file->getName()?></a></span> <span onclick="removeFile();" class="form-block-remove" data-url="<?=$file->getPhysicalPath()?>"><img src="/bitrix/themes/.default/images/actions/delete_button.gif" alt="<?=Loc::getMessage("GOODDE_TYRBO_API_DELETE")?>"></span>	
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: center;">
							<?
							echo BeginNote();
								echo Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES1");
							echo EndNote();
							?>
						</td>
					</tr>
					<?
				}
			}
		}
		
		$tabControl->BeginNextTab();
		
		if($arResult['turbo_pages'] && is_array($arResult['turbo_pages']))
		{
			$i = 1;
			foreach($arResult['turbo_pages'] as $arItem)
			{
				?>
				<tr>
					<td colspan="2" style="padding: 15px 15px 3px;text-align: center; color: #4B6267;font-weight: bold; border-bottom: 5px solid #E0E8EA;"><?=$i++?></td>
				</tr>
				<?
				if($arItem['title'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_TITLE')?></th>
						<td width="60%"><?=$APPLICATION->ConvertCharset($arItem['title'], 'utf-8', LANG_CHARSET);?></td>
					</tr>
					<?
				}
				if($arItem['link'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_LINK')?></th>
						<td width="60%"><a target="_blank" href="<?=$arItem['link']?>"><?=$arItem['link']?></a></td>
					</tr>
					<?
				}
				if($arItem['preview'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_PREVIEW')?></th>
						<td width="60%"><a target="_blank" href="<?=$arItem['preview']?>"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_PREVIEW_LINK')?></a></td>
					</tr>
					<?
				}
			}
		}
		
		$tabControl->BeginNextTab();
		
		if($arResult['errors'] && is_array($arResult['errors']))
		{
			$i = 1;
			foreach($arResult['errors'] as $arErrors)
			{
				?>
				<tr>
					<td colspan="2" style="padding: 15px 15px 3px;text-align: center; color: #4B6267;font-weight: bold; border-bottom: 5px solid #E0E8EA;"><?=$i++?></td>
				</tr>
				<?
				if($arErrors['error_code'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_ERROR_CODE')?></th>
						<td width="60%"><?=$arErrors['error_code']?></td>
					</tr>
					<?
				}
				if($arErrors['help_link'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_HELP_LINK')?></th>
						<td width="60%"><a target="_blank" href="<?=$arErrors['help_link']?>"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_HELP_LINK_URL')?></a></td>
					</tr>
					<?
				}
				if($arErrors['line'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_LINE')?></th>
						<td width="60%"><?=$arErrors['line']?></td>
					</tr>
					<?
				}
				if($arErrors['column'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_COLUMN')?></th>
						<td width="60%"><?=$arErrors['column']?></td>
					</tr>
					<?
				}
				if($arErrors['text'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_TEXT')?></th>
						<td width="60%"><textarea><?=$APPLICATION->ConvertCharset($arErrors['text'], 'utf-8', LANG_CHARSET);?></textarea></td>
					</tr>
					<?
				}
				if($arErrors['context']['text'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_CONTEXT_TEXT')?></th>
						<td width="60%"><textarea><?=$arErrors['context']['text']?></textarea></td>
					</tr>
					<?
				}
				if($arErrors['context']['position'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_POSITION')?></th>
						<td width="60%"><?=$arErrors['context']['position']?></td>
					</tr>
					<?
				}
				if($arErrors['tag'])
				{
					?>
					<tr>
						<th width="40%"><?=Loc::getMessage('GOODDE_TYRBO_API_RESULT_TAG')?></th>
						<td width="60%"><textarea><?=$arErrors['tag']?></textarea></td>
					</tr>
					<?
				}
			}
		}
	}
}
?>

<?if($ID>0):?>
  <input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>
<?
$tabControl->End();
?>
<?
$tabControl->ShowWarnings("goodde_task_edit", $message);

echo BeginNote();
	echo Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES2")?><br><br>
	<?=Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES3");?>
	<?=Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES4");?>
	<?=Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES5");?>
	<?=Loc::getMessage("GOODDE_TYRBO_API_RESULT_NOTES6");?>
	<?
echo EndNote();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>