<?
/** @var array $arField */
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
	<?
	if(strlen($arField['CATEGORY']) > 0)
	{
		?>
		<category><?=$arField['CATEGORY']?></category>
		<?
	}
	?>
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
		elseif($arField['PICTURE'])
		{
			?>
			<figure><img src="<?=$arField['PICTURE']?>"/></figure>
			<?
		}
		?>
		<?
		if(strlen($arField['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE']) > 0)
		{
			?>
			<p><?=$arField['DISPLAY_PROPERTIES']['CML2_ARTICLE']['NAME'];?>: <?=$arField['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE']?></p>
			<?
		}
		?>
		<?
		if($arField['MIN_PRICE'])
		{
			if(!$arField['OFFERS'])
			{
				if($arField['MIN_PRICE']['DISCOUNT_VALUE'] < $arField['MIN_PRICE']['VALUE'])
				{
					?>
					<p><small><del><?=$arField['MIN_PRICE']['PRINT_VALUE']?></del></small> <big><b><?=$arField['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></br><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_DISCOUNT")?> <b><?=$arField['MIN_PRICE']['PRINT_DISCOUNT']?></b></p>
					<?
				}
				else
				{
					?>
					<p><big><b><?=$arField['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></p>
					<?
				}
			}
		}
		if($arField['ELEMENT']['CATALOG_AVAILABLE'] == 'Y')
		{
			if($arField['OFFERS'])
			{
				?>
				<div data-block="accordion">
					<?
					$i = 0;
					foreach($arField["OFFERS"] as $arOffer)
					{
						?>	
						<div data-block="item" data-title="<?=$arOffer['NAME']?>"<?=($i == 0 ? ' data-expanded="true"' : '')?>>
							<?
							if($arOffer['DISPLAY_PROPERTIES'])
							{
								echo \Goodde\YandexTurbo\Turbo::getStrProrertyValue($arOffer['DISPLAY_PROPERTIES']);
							}
							?>
							<?
							if($arOffer['MIN_PRICE']['DISCOUNT_VALUE'] < $arOffer['MIN_PRICE']['VALUE'])
							{
								?>
								<p><small><del><?=$arOffer['MIN_PRICE']['PRINT_VALUE']?></del></small> <big><b><?=$arOffer['MIN_PRICE']['PRINT_DISCOUNT_VALUE']?></b></big></br><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_DISCOUNT")?> <b><?=$arOffer['MIN_PRICE']['PRINT_DISCOUNT']?></b></p>
								<?
							}
							else
							{
								?>
								<p><big><b><?=$arOffer['MIN_PRICE']['PRINT_VALUE']?></b></big></p>
								<?
							}
							?>
							<button
								formaction="<?=$arField['LINK']?>?&action=BUY&id=<?=$arOffer['ID']?>"
								data-background-color="#1976d2"
								data-color="#FFFFFF"
								data-primary="true"><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_ADD")?>
							</button>
						</div>
						<?
						$i++;
					}
					?>
				</div>
				<?
			}
			else
			{
				?>
				<button
					formaction="<?=$arField['LINK']?>?&action=BUY&id=<?=$arField['ID']?>"
					data-background-color="#1976d2"
					data-color="#ffffff"
					data-primary="true"><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_ADD")?>
				</button>
				<?
			}
		}
		else
		{
			?><p><b><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_AVAILABLE")?></b></p><?
		}
		?>
		<div data-block="accordion">
			<?
			$expanded = true;
			if(strlen($arField['TURBO_CONTENT']) > 0)
			{
				$expanded = false;
				?>
				<div data-block="item" data-title="<?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_TAB_1")?>" data-expanded="true">
					<?=$arField['TURBO_CONTENT']?>
				</div>
				<?
			}
			if($arField['DISPLAY_PROPERTIES'])
			{
				?>
				<div data-block="item" data-title="<?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_TAB_2")?>" <?=($expanded ? 'data-expanded="true"' : '')?>>
					<?=\Goodde\YandexTurbo\Turbo::getStrProrertyValue($arField['DISPLAY_PROPERTIES'])?>
				</div>
				<?
			}
			if($arField['GALLERY'])
			{				
				?>
				<div data-block="item" data-title="<?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_TAB_3")?>">
					<div data-block="gallery">
						<?
						if($arField['GALLERY']['TITLE'])
						{
							?>
							<header><?=$arField['GALLERY']['TITLE']?></header>
							<?
						}
						foreach ($arField['GALLERY']['ITEMS'] as $sPhoto)
						{
							?><img src="<?=$sPhoto?>"/><?
						}
						?>
					</div>
				</div>
				<?
			}
			?>
			<?
			if($arField['VIDEO'])
			{				
				$cntVideo = count($arField['VIDEO']);
				?>
				<div data-block="item" data-title="<?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_TAB_4")?><?=($cntVideo > 1 ? '&nbsp;('.$cntVideo.')' : '')?>">
					<?
					if($cntVideo > 1)
					{
						?>
						<table>
							<tbody>
								<?foreach($arField['VIDEO'] as $v => $value):?>
									<?if(($v + 1) % 2):?>
										<tr>
									<?endif;?>
									<td width="50%"><?=str_replace('src=', 'width="458" height="257" src=', str_replace(array('width', 'height'), array('data-width', 'data-height'), $value));?></td>
									<?if(!(($v + 1) % 2)):?>
										</tr>
									<?endif;?>
								<?endforeach;?>
								<?if(($v + 1) % 2):?>
									</tr>
								<?endif;?>
							</tbody>
						</table>
						<?
					}
					else
					{
						echo str_replace('src=', 'width="458" height="257" src=', str_replace(array('width', 'height'), array('data-width', 'data-height'), $arField['VIDEO'][0]));
					}
					?>
				</div>
				<?
			}
			?>
		</div>
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
			<h5><?=\Bitrix\Main\Localization\Loc::getMessage("GOODDE_TYRBO_API_TEMP_CATALOG_SHARE")?></h5>
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