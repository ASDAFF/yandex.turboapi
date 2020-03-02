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
		echo $arField['TURBO_CONTENT'];
		if($arField['DISPLAY_PROPERTIES'])
		{
			echo \Goodde\YandexTurbo\Turbo::getStrProrertyValue($arField['DISPLAY_PROPERTIES']);
		}
		?>
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