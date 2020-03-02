<?
/** @var array $arField */
use \Bitrix\Main\Localization\Loc;
?>
<item turbo="<?=$arField['ITEM']?>">			
	<title><?=$arField['TITLE']?></title>
	<?
	if(strlen($arField['DESCRIPTION']) > 0)
	{
		?>
		<description><?=$arField['DESCRIPTION']?></description>
		<?
	}
	?>
	<link><?=$arField['LINK']?></link>
	<turbo:content>
	<![CDATA[
		<header>
			<h1><?=$arField['PAGE_TITLE']?></h1>
			<?
			if(strlen($arField['MENU']) > 0)
			{
				?>
				<menu><?=$arField['MENU']?></menu>
				<?
			}
			?>
		</header>
		<?
		if($arField['GALLERY'])
		{				
			?>
			<div data-block="slider" data-view="landscape">
				<?
				if($arField['PICTURE'])
				{
					?>
					<figure><img src="<?=$arField['PICTURE']?>"/></figure>
					<?
				}
				foreach ($arField['GALLERY']['ITEMS'] as $sPhoto)
				{
					?>
					<figure><img src="<?=$sPhoto?>"/></figure>
					<?
				}
				?>
			</div>
			<?
		}
		if($arField['ELEMENTS'])
		{
			?>
			<table>
				<tbody>
					<?foreach($arField['ELEMENTS'] as $k => $arItem):?>
						<?if(($k + 1) % 2):?>
						<tr>
						<?endif;?>
							<td width="50%">
								<?
								if($arItem['PICTURE'])
								{
									?>
									<figure><img src="<?=$arItem['PICTURE']?>"/></figure>
									<p>&nbsp;</p>
									<?
								}
								?>
								<a href="<?=$arItem['LINK']?>"><?=$arItem['PAGE_TITLE']?></a>
								<p>&nbsp;</p>
								<?
								if($arItem['MIN_PRICE'])
								{
									if($arItem['OFFERS'])
									{
										if($arItem['MIN_PRICE']['DISCOUNT_VALUE'] < $arItem['MIN_PRICE']['VALUE'])
										{
											?>
											<p><big><b><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_FROM")?></b></big> <small><del><?=$arItem['MIN_PRICE']['PRINT_VALUE']?></del></small> <big><b><?=$arItem['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></br><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_DISCOUNT")?> <b><?=$arItem['MIN_PRICE']['PRINT_DISCOUNT']?></b></p>
											<?
										}
										else
										{
											?>
											<p><big><b><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_FROM")?> <?=$arItem['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></p>
											<?
										}
									}
									else
									{										
										if($arItem['MIN_PRICE']['DISCOUNT_VALUE'] < $arItem['MIN_PRICE']['VALUE'])
										{
											?>
											<p><small><del><?=$arItem['MIN_PRICE']['PRINT_VALUE']?></del></small> <big><b><?=$arItem['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></br><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_DISCOUNT")?> <b><?=$arItem['MIN_PRICE']['PRINT_DISCOUNT']?></b></p>
											<?
										}
										else
										{
											?>
											<p><big><b><?=$arItem['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></p>
											<?
										}
									}
								}
								if($arItem['ELEMENT']['CATALOG_AVAILABLE'] == 'Y' && $arItem['MIN_PRICE'])
								{
									if($arItem['OFFERS'])
									{
										?>
										<button
											formaction="<?=$arItem['LINK']?>"
											data-background-color="#1976d2"
											data-color="#FFFFFF"
											data-primary="true"><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_MORE")?>
										</button>
										<?
									}
									else
									{
										?>
										<button
											formaction="<?=$arItem['LINK']?>?action=BUY&id=<?=$arItem['ID']?>"
											data-background-color="#1976d2"
											data-color="#ffffff"
											data-turbo="false"
											data-primary="true"><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_ADD")?>
										</button>
										<?
									}
								}
								else
								{
									?><p><b><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_AVAILABLE")?></b></p><?
								}
								?>
							</td>
						<?if(!(($k + 1) % 2)):?>
						</tr>
						<?endif;?>
					<?endforeach;?>
					<?if(($k + 1) % 2):?>
						</tr>
					<?endif;?>
				</tbody>
			</table>
			<?
		}
		?>
		<p>&nbsp;</p>
		<?=$arField['TURBO_CONTENT']?>
		<p>&nbsp;</p>
		<?=\Goodde\YandexTurbo\Turbo::getStrUserProrertyValue($arField['DISPLAY_PROPERTIES'])?>
		<button
			formaction="<?=$arField['LINK']?>"
			data-background-color="#1976d2"
			data-color="#FFFFFF"
			data-turbo="false"
			data-primary="false"><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_ALL")?>
		</button>
		<p>&nbsp;</p>
		<?
		if($arField['FEEDBACK']['ITEMS'])
		{
			foreach($arField['FEEDBACK']['ITEMS'] as $key => $arFeedback)
			{
				switch($key) 
				{
					case 'left':
						?>
						<div data-block="widget-feedback" data-stick="left">
							<?
							foreach($arFeedback['TYPE'] as $arVal)
							{
								if($arVal['TYPE'] == 'callback')
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" data-send-to="<?=$arVal['VALUE']?>" <?=(isset($arField['FORM']['AGREEMENT_COMPANY']) ? 'data-agreement-company="'.$arField['FORM']['AGREEMENT_COMPANY'].'"' : '')?> <?=(isset($arField['FORM']['AGREEMENT_LINK']) ? 'data-agreement-link="'.$arField['FORM']['AGREEMENT_LINK'].'"' : '')?> ></div>
									<?
								}
								else
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" <?=(isset($arVal['VALUE']) ? 'data-url="'.$arVal['VALUE'].'"' : '')?> ></div>
									<?
								}
							}
							?>
						</div>
						<?
						break;
					case 'right':
						?>
						<div data-block="widget-feedback" data-stick="right">
							<?
							foreach($arFeedback['TYPE'] as $arVal)
							{
								if($arVal['TYPE'] == 'callback')
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" data-send-to="<?=$arVal['VALUE']?>" <?=(isset($arField['FORM']['AGREEMENT_COMPANY']) ? 'data-agreement-company="'.$arField['FORM']['AGREEMENT_COMPANY'].'"' : '')?> <?=(isset($arField['FORM']['AGREEMENT_LINK']) ? 'data-agreement-link="'.$arField['FORM']['AGREEMENT_LINK'].'"' : '')?> ></div>
									<?
								}
								else
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" <?=(isset($arVal['VALUE']) ? 'data-url="'.$arVal['VALUE'].'"' : '')?> ></div>
									<?
								}
							}
						?>
						</div>
						<?
						break;
					case 'false':
						?>
						<div data-block="widget-feedback" data-stick="false" <?=(isset($arField['FEEDBACK']['TITLE']) ? 'data-title="'.$arField['FEEDBACK']['TITLE'].'"' : '')?>>
							<?
							foreach($arFeedback['TYPE'] as $arVal)
							{
								if($arVal['TYPE'] == 'callback')
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" data-send-to="<?=$arVal['VALUE']?>" <?=(isset($arField['FORM']['AGREEMENT_COMPANY']) ? 'data-agreement-company="'.$arField['FORM']['AGREEMENT_COMPANY'].'"' : '')?> <?=(isset($arField['FORM']['AGREEMENT_LINK']) ? 'data-agreement-link="'.$arField['FORM']['AGREEMENT_LINK'].'"' : '')?>></div>
									<?
								}
								else
								{
									?>
									<div data-type="<?=$arVal['TYPE']?>" <?=(isset($arVal['VALUE']) ? 'data-url="'.$arVal['VALUE'].'"' : '')?>></div>
									<?
								}
							}
						?>
						</div>
						<?
						break;
				}
			}
		}
				
		if($arField['SHARE'])
		{
			?>
			<h5><?=Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_SHARE")?></h5>
			<div data-block="share" data-network="<?=implode(', ', $arField['SHARE'])?>"></div>
			<?
		}
		?>
	]]>		
	</turbo:content>
	<?
	if($arField['RELATED'])
	{
		?>
		<yandex:related>
			<?
			foreach($arField['RELATED'] as $arRelated)
			{
				?><link url="<?=$arRelated['LINK']?>" <?=(is_array($arRelated['PICTURE']) ? ' img="'.$arRelated['PICTURE'].'"' : '')?>><?=$arRelated['TITLE']?></link><?
			}
			?>
		</yandex:related>
		<?
	}
	?>
</item>