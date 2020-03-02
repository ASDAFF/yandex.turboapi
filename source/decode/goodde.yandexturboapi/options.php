<?
use Bitrix\Main\Loader,
	Bitrix\Main\SiteTable,
	Bitrix\Main\Application,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Goodde\YandexTurbo\Model\Request;
		
$module_id = 'goodde.yandexturboapi';
Loc::loadMessages(__FILE__);

$RIGHT = $APPLICATION->GetGroupRight($module_id);
$RIGHT_W = ($RIGHT>="W");
$RIGHT_R = ($RIGHT>="R");

if ($RIGHT_R)
{
	$aTabs = array(
		array("DIV" => "edit1", "TAB" => Loc::getMessage("MAIN_TAB_SET"), "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET")),
		array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
	);
	$tabControl = new CAdminTabControl("tabControl", $aTabs);
	
	$appInstance = Application::getInstance();
	$context = $appInstance->getContext();
	$request = $context->getRequest();

	Loc::loadMessages($context->getServer()->getDocumentRoot() . "/bitrix/modules/main/options.php");

	if(!Loader::includeModule('iblock') || !Loader::includeModule($module_id)){
		CAdminMessage::showMessage(array(
			"MESSAGE" => Loc::getMessage("GOODDE_TYRBO_API_ERROR_MODULE"),
			"TYPE" => "ERROR",
		));
		return;
	}
	
	$siteList = array();
	$siteIterator = SiteTable::getList(array(
		'select' => array('LID', 'NAME'),
		'order' => array('SORT' => 'ASC')
	));
	while ($oneSite = $siteIterator->fetch())
	{
		$siteList[] = array('ID' => $oneSite['LID'], 'NAME' => $oneSite['NAME']);
	}
	unset($oneSite, $siteIterator);
	$siteCount = count($siteList);
	$aTabs2 = Array();
	foreach($siteList as $val)
	{
		$aTabs2[] = Array("DIV"=>"reminder".$val["ID"], "TAB" => "[".$val["ID"]."] ".htmlspecialcharsbx($val["NAME"]), "TITLE" => "[".htmlspecialcharsbx($val["ID"])."] ".htmlspecialcharsbx($val["NAME"]), 'ICON' => '', 'ONSELECT' => "$('#tabControl2_active_tab').attr('value','reminder".$val["ID"]."')");
	}
	$tabControl2 = new CAdminViewTabControl("tabControl2", $aTabs2);
	if($_REQUEST[$tabControl2->name."_active_tab"])
	{
		$activeTabParam = $tabControl2->name."_active_tab=".urlencode($_REQUEST[$tabControl2->name."_active_tab"]);
	}
	
	if((!empty($Update) || !empty($restoreDefaults)) && $request->isPost() && $RIGHT_W && check_bitrix_sessid()) 
	{
		if(!empty($restoreDefaults)) 
		{
			Option::delete($module_id);
			
			$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
			while($zr = $z->Fetch())
				$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
		} 
		else
		{
			$arRequestData = array();
			$arRequestData = $request->getPost('turbo');
			foreach($siteList as $val)
			{
				$arTurboProp = array();
				$turboProp = COption::GetOptionString($module_id, "turbo_prod", "", $val["ID"]);
				if (strlen($turboProp) > 0)
					$arTurboProp = unserialize($turboProp);
				
				if($arRequestData[$val['ID']]['token'])
				{
					if($arRequestData[$val['ID']]['token'] != $arTurboProp['token'])
					{
						$arTurboProp = array();
						foreach($arRequestData[$val['ID']] as $k => $v)
						{
							if($k != 'token')
								unset($arRequestData[$val['ID']][$k]);
						}
					}
				}

				Option::set(
					$module_id,
					"turbo_prod",
					serialize(array_merge($arTurboProp, $arRequestData[$val['ID']])),
					$val['ID']
				);
				
				$arTurboProp = array();
				$turboProp = COption::GetOptionString($module_id, "turbo_prod", "", $val["ID"]);
				if (strlen($turboProp) > 0)
					$arTurboProp = unserialize($turboProp);
			}
			
			ob_start();
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
			ob_end_clean();
		}
		
		if (!$error) 
		{	
			LocalRedirect($APPLICATION->GetCurPageParam()."&".$tabControl->ActiveTabParam()."&".$activeTabParam);
		}
	}
	
	if(!empty($getUserId) && $request->isPost() && $RIGHT_W && check_bitrix_sessid())
	{
		foreach($siteList as $val)
		{
			$arTurboProp = array();
			$turboProp = COption::GetOptionString($module_id, "turbo_prod", "", $val["ID"]);
			if (strlen($turboProp) > 0)
				$arTurboProp = unserialize($turboProp);
			
			$arUserId = Request::curUser($val["ID"]);
			if(!$arUserId)
			{
				if ($ex = $APPLICATION->GetException())
				{
					$error = $ex->GetString();
				}	
			}
	
			Option::set(
				$module_id,
				"turbo_prod",
				serialize(array_merge($arTurboProp, $arUserId)),
				$val['ID']
			);
		}

		if(!$error) 
		{	
			LocalRedirect($APPLICATION->GetCurPageParam()."&".$tabControl->ActiveTabParam()."&".$activeTabParam);
		}
	}
	
	if($error) 
	{	
		CAdminMessage::ShowMessage($error);
	}
	CJSCore::Init(array("jquery"));
	?>
	<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
		<?$tabControl->begin();?>
		<?$tabControl->BeginNextTab();?>
		<tr>
			<td colspan="2">
				<?
				$tabControl2->Begin();
				foreach($siteList as $val)
				{
					$arTurboProp = array();
					$turboProp = COption::GetOptionString($module_id, "turbo_prod", "", $val["ID"]);
					if (strlen($turboProp) > 0)
						$arTurboProp = unserialize($turboProp);

					$tabControl2->BeginNextTab();
					?>
					<table cellspacing="5" cellpadding="0" border="0" width="100%" align="center">
						<tr>
							<th align="right" width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_TOKEN")?>:</th>
							<td width="60%"><input size="60" maxlength="255" value="<?=$arTurboProp['token']?>" name="turbo[<?=$val["ID"]?>][token]" type="text"></td>			
						</tr>
						<?if(strlen($arTurboProp['token']) > 0):?>
							<tr>
								<th align="right" width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_USER")?></th>
								<td width="60%">
									<?if(strlen($arTurboProp['user_id']) > 0):?>
										<?=$arTurboProp['user_id']?>
										<input value="<?=$arTurboProp['user_id']?>" name="turbo[<?=$val["ID"]?>][host_id]" type="hidden">
									<?else:?>
										<input type="submit" name="getUserId" class="adm-btn-save" value="<?=Loc::getMessage("GOODDE_TYRBO_API_SAVE")?>" title="<?=Loc::getMessage("GOODDE_TYRBO_API_SAVE")?>">
									<?endif;?>
								</td>			
							</tr>
							<?if(strlen($arTurboProp['user_id']) > 0):?>
							<tr>
								<th align="right" width="40%"><?=Loc::getMessage("GOODDE_TYRBO_API_HOST")?></th>
								<td width="60%">
									<?if(strlen($arTurboProp['host_id']) > 0):?>
										<?=$arTurboProp['host_id']?>
										<input value="<?=$arTurboProp['host_id']?>" name="turbo[<?=$val["ID"]?>][host_id]" type="hidden">
									<?else:?>	
										<select name="turbo[<?=$val["ID"]?>][host_id]">
											<option value=""><?=Loc::getMessage("GOODDE_TYRBO_API_SELECT")?></option>
											<?foreach(Request::curHost($val["ID"]) as $arHost):?>
												<option value="<?=$arHost['host_id']?>"
													<?=($arTurboProp['host_id'] == $arHost['host_id'] ? 'selected' : '')?>>
													<?=$arHost['host_id']?>
												</option>
											<?endforeach;?>
										</select>
									<?endif;?>
								</td>			
							</tr>
							<?endif;?>
						<?endif;?>
					</table>
					<?
				}
				$tabControl2->End();
				?>
				<input id="<?=$tabControl2->name."_active_tab"?>" name="<?=$tabControl2->name."_active_tab"?>" value="<?=urlencode($_REQUEST[$tabControl2->name."_active_tab"])?>" type="hidden">
			</td>
		</tr>	
		<?$tabControl->BeginNextTab();?>
		<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
		<?$tabControl->Buttons();?>		
		<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="Update" class="adm-btn-save" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
		<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="restoreDefaults" title="<?echo Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="confirm('<?echo AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
		<?=bitrix_sessid_post();?>
		<?$tabControl->End();?>
	</form>
	<?
}
?>