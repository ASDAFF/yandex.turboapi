<?php
/**
 * Copyright (c) 2/3/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */


use Bitrix\Main\Loader,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Currency,
	Bitrix\Main\Web\Json,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Yandex\TurboAPI\Turbo,
	Yandex\TurboAPI\FeedTable,
	Yandex\TurboAPI\Model\Request;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/yandex.turboapi/admin/tools.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

$moduleId = 'yandex.turboapi';
Loc::loadMessages(__FILE__);

if(CModule::IncludeModuleEx($moduleId) == 3)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("YANDEX_TYRBO_API_ERROR_MODULE_DEMO_EXPIRED"),
		"TYPE" => "ERROR",
	));
	return;
}
elseif(!Loader::IncludeModule($moduleId))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::showMessage(array(
		"MESSAGE" => Loc::getMessage("YANDEX_TYRBO_API_ERROR_MODULE"),
		"TYPE" => "ERROR",
	));
	return;
}

$catalogIncluded = Loader::includeModule('catalog');

CJSCore::Init(array('yandex_turboapi'));

$POST_RIGHT = $APPLICATION->GetGroupRight("yandex.turboapi");
if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

set_time_limit(0);

$STEP = intval($STEP);
if ($STEP <= 0)
	$STEP = 1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["backButton"]) && strlen($_POST["backButton"]) > 0)
	$STEP = $STEP - 2;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["backButton2"]) && strlen($_POST["backButton2"]) > 0)
	$STEP = 1;

$max_execution_time = intval($max_execution_time);
if ($max_execution_time <= 0)
	$max_execution_time = 0;

if (isset($_REQUEST["CUR_LOAD_SESS_ID"]) && strlen($_REQUEST["CUR_LOAD_SESS_ID"]) > 0)
	$CUR_LOAD_SESS_ID = $_REQUEST["CUR_LOAD_SESS_ID"];
else
	$CUR_LOAD_SESS_ID = "CL".time();

$bVarsFromForm = false; 
$bAllLinesLoaded = True;
$CUR_FILE_POS = isset($_REQUEST["CUR_FILE_POS"]) ? intval($_REQUEST["CUR_FILE_POS"]) : 0;
$strError = "";
$line_num = 0;
$correct_lines = 0;
$error_lines = 0;
$killed_lines = 0;
$io = CBXVirtualIo::GetInstance();

/////////////////////////////////////////////////////////////////////
$arRiderectAvailProdFields = array(
	'GYT_URL' => array(
		'field' => 'URL',
		'name' => 'Url',
	)
);
/////////////////////////////////////////////////////////////////////

class CAssocData extends CCSVData
{
	var $__rows = array();
	var $__pos = array();
	var $__last_pos = 0;
	var $NUM_FIELDS = 0;
	var $PK = array();

	function __construct($fields_type = "R", $first_header = false, $NUM_FIELDS = 0)
	{
		parent::__construct($fields_type, $first_header);
		$this->NUM_FIELDS = (int)$NUM_FIELDS;
	}

	function GetPos()
	{
		if(empty($this->__pos))
			return parent::GetPos();
		else
			return $this->__pos[count($this->__pos) - 1];
	}

	function Fetch()
	{
		if (empty($this->__rows))
		{
			$this->__last_pos = $this->GetPos();
			return parent::Fetch();
		}
		else
		{
			$this->__last_pos = array_pop($this->__pos);
			return array_pop($this->__rows);
		}
	}

	function PutBack($row)
	{
		$this->__rows[] = $row;
		$this->__pos[] = $this->__last_pos;
	}

	function AddPrimaryKey($field_name, $field_ind)
	{
		$this->PK[$field_name] = $field_ind;
	}

	function FetchAssoc()
	{
		global $line_num;
		$result = array();
		while ($ar = $this->Fetch())
		{
			$line_num++;
			//Search for "PRIMARY KEY"
			foreach ($this->PK as $pk_field => $pk_ind)
			{
				if (array_key_exists($pk_field, $result))
				{
					//Check for Next record
					if ($result[$pk_field] !== "".trim($ar[$pk_ind]))
					{
						$line_num--;
						$this->PutBack($ar);
						return $result;
					}
					else
					{
						//When XML_ID do match we skip NAME check
						break;
					}
				}
			}
			for ($i = 0; $i < $this->NUM_FIELDS; $i++)
			{
				$key = $GLOBALS["field_".$i];
				$value = "".trim($ar[$i]);
				$result[$key] = $value;
			}

			if (empty($this->PK))
				return $result;
		}
		//eof
		 if (empty($result))
			return $ar;
		else
			return $result; 
	}
}
/////////////////////////////////////////////////////////////////////
if (($_SERVER['REQUEST_METHOD'] == "POST" || $CUR_FILE_POS > 0) && $STEP > 1 && check_bitrix_sessid())
{
	//*****************************************************************//
	if ($STEP > 1)
	{
		//*****************************************************************//
		$DATA_FILE_NAME = '';
		if(strlen($URL_DATA_FILE) > 0)
		{
			$URL_DATA_FILE = trim(str_replace("\\", "/", trim($URL_DATA_FILE)) , "/");
			$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$URL_DATA_FILE);
			if (strtolower(GetFileExtension($FILE_NAME)) != "csv")
			{
				$strError .= Loc::getMessage("YANDEX_TYRBO_API_NOT_CSV").".<br>";
			}
			elseif(
				(strlen($FILE_NAME) > 1)
				&& ($FILE_NAME === "/".$URL_DATA_FILE)
				&& $io->FileExists($_SERVER["DOCUMENT_ROOT"].$FILE_NAME)
				&& ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W")
			)
			{
				$DATA_FILE_NAME = $FILE_NAME;
			}
		}
		else
		{
			$strError .= Loc::getMessage("YANDEX_TYRBO_API_NO_DATA_FILE_SIMPLE").".<br>";
		}

		if (strlen($strError) <= 0)
		{
			if ($CUR_FILE_POS > 0 && is_set($_SESSION, $CUR_LOAD_SESS_ID) && is_set($_SESSION[$CUR_LOAD_SESS_ID], "LOAD_SCHEME"))
			{
				parse_str($_SESSION[$CUR_LOAD_SESS_ID]["LOAD_SCHEME"]);
				$STEP = 4;
			}
		}

		if (strlen($strError) > 0)
			$STEP = 1;
		//*****************************************************************//

	}
	if ($STEP > 2)
	{
		//*****************************************************************//
		$csvFile = new CAssocData;
		$csvFile->LoadFile($io->GetPhysicalName($_SERVER["DOCUMENT_ROOT"].$DATA_FILE_NAME));

		$arDataFileFields = array();
		if (strlen($strError) <= 0)
		{
			$fields_type = 'R';
			$csvFile->SetFieldsType($fields_type);
			$first_names_r = (($first_names_r == "Y") ? "Y" : "N");
			$csvFile->SetFirstHeader(($first_names_r == "Y") ? true : false);
			$delimiter_r_char = "";
			switch ($delimiter_r)
			{
			case "TAB":
				$delimiter_r_char = "\t";
				break;

			case "ZPT":
				$delimiter_r_char = ",";
				break;

			case "SPS":
				$delimiter_r_char = " ";
				break;

			case "OTR":
				$delimiter_r_char = substr($delimiter_other_r, 0, 1);
				break;

			case "TZP":
				$delimiter_r_char = ";";
				break;
			}
			if (strlen($delimiter_r_char) != 1)
				$strError.= GetMessage("YANDEX_TYRBO_API_NO_DELIMITER")."<br>";

			if (strlen($strError) <= 0)
			{
				$csvFile->SetDelimiter($delimiter_r_char);
			}

			if (strlen($strError) <= 0)
			{
				$bFirstHeaderTmp = $csvFile->GetFirstHeader();
				$csvFile->SetFirstHeader(false);
				if ($arRes = $csvFile->Fetch())
				{
					foreach ($arRes as $i => $ar)
					{
						$arDataFileFields[$i] = $ar;
					}
				}
				else
				{
					$strError.= GetMessage("YANDEX_TYRBO_API_NO_DATA")."<br>";
				}
				$NUM_FIELDS = count($arDataFileFields);
			}
		}

		if (strlen($strError) > 0)
			$STEP = 2;
		//*****************************************************************//

	}
	if ($STEP > 3)
	{
		//*****************************************************************//
		$bFieldsPres = False;
		for ($i = 0; $i < $NUM_FIELDS; $i++)
		{
			if (strlen(${"field_".$i}) > 0)
			{
				$bFieldsPres = True;
				break;
			}
		}
		if (!$bFieldsPres)
			$strError.= GetMessage("YANDEX_TYRBO_API_NO_FIELDS")."<br>";

		if (strlen($strError) <= 0)
		{
			$csvFile->SetPos($CUR_FILE_POS);
			if ($CUR_FILE_POS <= 0 && $bFirstHeaderTmp)
			{
				$arRes = $csvFile->Fetch();
			}
			
			$io = CBXVirtualIo::GetInstance();
			
			if ($CUR_FILE_POS > 0 && is_set($_SESSION, $CUR_LOAD_SESS_ID))
			{

				if (is_set($_SESSION[$CUR_LOAD_SESS_ID], "line_num"))
					$line_num = intval($_SESSION[$CUR_LOAD_SESS_ID]["line_num"]);

				if (is_set($_SESSION[$CUR_LOAD_SESS_ID], "correct_lines"))
					$correct_lines = intval($_SESSION[$CUR_LOAD_SESS_ID]["correct_lines"]);

				if (is_set($_SESSION[$CUR_LOAD_SESS_ID], "error_lines"))
					$error_lines = intval($_SESSION[$CUR_LOAD_SESS_ID]["error_lines"]);

				if (is_set($_SESSION[$CUR_LOAD_SESS_ID], "killed_lines"))
					$killed_lines = intval($_SESSION[$CUR_LOAD_SESS_ID]["killed_lines"]);

			}

			$csvFile->NUM_FIELDS = $NUM_FIELDS;
			// Main loop
			$turboFeed = new \Yandex\TurboAPI\TurboFeed();
			$arFields = array();
			$countLine = 0;
			while ($arRes = $csvFile->FetchAssoc())
			{
				$strErrorR = "";
				if($countLine >= 10000)
				{
					break;
				}
				// Create xml
				foreach ($arRiderectAvailProdFields as $key => $arField)
				{
					if (array_key_exists($key, $arRes))
						$arFields[][$arField["field"]] = $arRes[$key];
				}
				if (strlen($strErrorR) <= 0)
				{
					$correct_lines++;
				}
				else
				{
					$error_lines++;
					$strError.= $strErrorR;
				}
				$countLine++;
				
				if (intval($max_execution_time) > 0 && (getmicrotime() - START_EXEC_TIME) > intval($max_execution_time))
				{
					$bAllLinesLoaded = False;
					break;
				}
			}
			if(strlen($strErrorR) <= 0 && $arFields)
			{
				global $runError;
				if($LID)
				{
					$res = $turboFeed->addFalseByCsv('yandex_turb_item_false.xml', $LID, $arFields);
				}
				
				if(strlen($runError) > 0)
				{
					$strError.= $runError;
				}
			}
			
			if(!$bAllLinesLoaded)
			{
				if (strlen($CUR_LOAD_SESS_ID) <= 0)
					$CUR_LOAD_SESS_ID = "CL".time();

				$_SESSION[$CUR_LOAD_SESS_ID]["line_num"] = $line_num;
				$_SESSION[$CUR_LOAD_SESS_ID]["correct_lines"] = $correct_lines;
				$_SESSION[$CUR_LOAD_SESS_ID]["error_lines"] = $error_lines;
				$_SESSION[$CUR_LOAD_SESS_ID]["killed_lines"] = $killed_lines;
				$paramsStr = "fields_type=".urlencode($fields_type);
				$paramsStr.= "&first_names_r=".urlencode($first_names_r);
				$paramsStr.= "&delimiter_r=".urlencode($delimiter_r);
				$paramsStr.= "&delimiter_other_r=".urlencode($delimiter_other_r);
				for ($i = 0; $i < $NUM_FIELDS; $i++)
				{
					$paramsStr.= "&field_".$i."=".urlencode(${"field_".$i});
				}
				$paramsStr.= "&max_execution_time=".urlencode($max_execution_time);
				$_SESSION[$CUR_LOAD_SESS_ID]["LOAD_SCHEME"] = $paramsStr;
				$curFilePos = $csvFile->GetPos();
			}
		}
		if (strlen($strError) > 0)
		{
			if(strlen($runError) <= 0)
			{
				$strError.= GetMessage("YANDEX_TYRBO_API_TOTAL_ERRS")." ".$error_lines.".<br>";
				$strError.= GetMessage("YANDEX_TYRBO_API_TOTAL_COR1")." ".$correct_lines." ".GetMessage("YANDEX_TYRBO_API_TOTAL_COR2")."<br>";
			}
			$STEP = 3;
		}
		//*****************************************************************//

	}
	//*****************************************************************//

}
/////////////////////////////////////////////////////////////////////
$APPLICATION->SetTitle(GetMessage("YANDEX_TYRBO_API_PAGE_TITLE").$STEP);
require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
/*********************************************************************/
/********************  BODY  *****************************************/
/*********************************************************************/
CAdminMessage::ShowMessage($strError);

if(!$bAllLinesLoaded)
{
	$strParams = bitrix_sessid_get()."&CUR_FILE_POS=".$curFilePos."&CUR_LOAD_SESS_ID=".urlencode($CUR_LOAD_SESS_ID)."&STEP=4&URL_DATA_FILE=".urlencode($DATA_FILE_NAME)."&fields_type=".urlencode($fields_type)."&max_execution_time=".IntVal($max_execution_time);
	$strParams.= "&delimiter_r=".urlencode($delimiter_r)."&delimiter_other_r=".urlencode($delimiter_other_r)."&first_names_r=".urlencode($first_names_r);
	?>

	<?echo GetMessage("YANDEX_TYRBO_API_AUTO_REFRESH"); ?>
	<a href="<?echo $APPLICATION->GetCurPage(); ?>?lang=<?echo LANGUAGE_ID; ?>&<?echo $strParams ?>"><?echo GetMessage("YANDEX_TYRBO_API_AUTO_REFRESH_STEP"); ?></a><br>

	<script type="text/javascript">
	function DoNext()
	{
		window.location="<?echo $APPLICATION->GetCurPage(); ?>?lang=<?echo LANG ?>&<?echo $strParams ?>";
	}
	setTimeout('DoNext()', 2000);
	</script>
	<?
}
?>
<?
if ($STEP == 1)
{
	CAdminMessage::ShowMessage(array('MESSAGE' => Loc::getMessage("YANDEX_TYRBO_API_HELP_INFO"), 'TYPE' => 'OK', 'HTML' => true));
	CAdminMessage::ShowMessage(array('MESSAGE' => Loc::getMessage("YANDEX_TYRBO_API_HELP_INFO_2"), 'TYPE' => 'ERROR', 'HTML' => true));
}
?>
<form method="POST" action="<?=$APPLICATION->GetCurPage();?>?lang=<?=LANGUAGE_ID; ?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload">

<?$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("YANDEX_TYRBO_API_TAB1") ,
		"ICON" => "",
		"TITLE" => GetMessage("YANDEX_TYRBO_API_TAB1_ALT"),
	) ,
	array(
		"DIV" => "edit2",
		"TAB" => GetMessage("YANDEX_TYRBO_API_TAB2") ,
		"ICON" => "",
		"TITLE" => GetMessage("YANDEX_TYRBO_API_TAB2_ALT"),
	) ,
	array(
		"DIV" => "edit3",
		"TAB" => GetMessage("YANDEX_TYRBO_API_TAB3") ,
		"ICON" => "",
		"TITLE" => GetMessage("YANDEX_TYRBO_API_TAB3_ALT"),
	) ,
	array(
		"DIV" => "edit4",
		"TAB" => GetMessage("YANDEX_TYRBO_API_TAB4") ,
		"ICON" => "",
		"TITLE" => GetMessage("YANDEX_TYRBO_API_TAB4_ALT"),
	) ,
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();
if ($STEP == 1)
{
	?>
	<tr>
		<td width="40%"><?=Loc::getMessage("YANDEX_TYRBO_API_FILE_NAME")?></td>
		<td width="60%">
			<input type="text" name="URL_DATA_FILE" value="<?echo htmlspecialcharsbx($URL_DATA_FILE); ?>" size="30">
			<input type="button" value="<?=Loc::getMessage("YANDEX_TYRBO_API_OPEN");?>" OnClick="BtnClick()">
			<?
			CAdminFileDialog::ShowScript(array(
				"event" => "BtnClick",
				"arResultDest" => array(
					"FORM_NAME" => "dataload",
					"FORM_ELEMENT_NAME" => "URL_DATA_FILE",
				) ,
				"arPath" => array(
					"SITE" => SITE_ID,
					"PATH" => "/".COption::GetOptionString("main", "upload_dir", "upload"),
				) ,
				"select" => 'F', // F - file only, D - folder only
				"operation" => 'O', // O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => 'csv',
				"allowAllFiles" => true,
				"SaveConfig" => true,
			));
			?>
		</td>
	</tr>
	<?
}
$tabControl->EndTab();

$tabControl->BeginNextTab();
if ($STEP == 2)
{
?>
	<tr>
		<td width="40%">&nbsp;</td>
		<td width="60%">&nbsp;</td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("YANDEX_TYRBO_API_RAZDEL_TYPE"); ?>:</td>
		<td>
			<input type="radio" name="delimiter_r" id="delimiter_r_TZP" value="TZP" <?
			if ($delimiter_r == "TZP" || strlen($delimiter_r) <= 0)
				echo "checked" ?>><label for="delimiter_r_TZP"><?echo GetMessage("YANDEX_TYRBO_API_TZP"); ?></label><br>
					<input type="radio" name="delimiter_r" id="delimiter_r_ZPT" value="ZPT" <?
			if ($delimiter_r == "ZPT")
				echo "checked" ?>><label for="delimiter_r_ZPT"><?echo GetMessage("YANDEX_TYRBO_API_ZPT"); ?></label><br>
					<input type="radio" name="delimiter_r" id="delimiter_r_TAB" value="TAB" <?
			if ($delimiter_r == "TAB")
				echo "checked" ?>><label for="delimiter_r_TAB"><?echo GetMessage("YANDEX_TYRBO_API_TAB"); ?></label><br>
					<input type="radio" name="delimiter_r" id="delimiter_r_SPS" value="SPS" <?
			if ($delimiter_r == "SPS")
				echo "checked" ?>><label for="delimiter_r_SPS"><?echo GetMessage("YANDEX_TYRBO_API_SPS"); ?></label><br>
					<input type="radio" name="delimiter_r" id="delimiter_r_OTR" value="OTR" <?
			if ($delimiter_r == "OTR")
				echo "checked" ?>><label for="delimiter_r_OTR"><?echo GetMessage("YANDEX_TYRBO_API_OTR"); ?></label>
			<input type="text" name="delimiter_other_r" size="3" value="<?echo htmlspecialcharsbx($delimiter_other_r); ?>">
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("YANDEX_TYRBO_API_FIRST_NAMES"); ?>:</td>
		<td>
			<input type="hidden" name="first_names_r" id="first_names_r_N" value="N">
			<input type="checkbox" name="first_names_r" id="first_names_r_Y" value="Y" <?
			if ($first_names_r != "N")
				echo "checked" ?>>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("YANDEX_TYRBO_API_DATA_SAMPLES"); ?></td>
	</tr>
	<tr>
		<td align="center" colspan="2">
			<?$sContent = "";
			if (strlen($DATA_FILE_NAME) > 0)
			{
				$DATA_FILE_NAME = trim(str_replace("\\", "/", trim($DATA_FILE_NAME)) , "/");
				$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$DATA_FILE_NAME);
				if (
					(strlen($FILE_NAME) > 1)
					&& ($FILE_NAME == "/".$DATA_FILE_NAME)
					&& $APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W"
				)
				{
					$f = $io->GetFile($_SERVER["DOCUMENT_ROOT"].$FILE_NAME);
					$file_id = $f->open("rb");
					$sContent = fread($file_id, 10000);
					fclose($file_id);
				}
			}
			?>
			<textarea name="data" rows="10" cols="80" style="width:100%"><?echo htmlspecialcharsbx($sContent); ?></textarea>
		</td>
	</tr>
	<?
}
$tabControl->EndTab();

$tabControl->BeginNextTab();
if ($STEP == 3)
{
?>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("YANDEX_TYRBO_API_FIELDS_SOOT"); ?></td>
	</tr>
	<?
	$arAvailFields = array();
	foreach ($arRiderectAvailProdFields as $field_name => $arField)
	{
		$arAvailFields[] = array(
			"value" => $field_name,
			"name" => $arField["name"],
		);
	}
	foreach ($arDataFileFields as $i => $field)
	{
		?>
		<tr>
			<td width="40%">
				<b><?echo GetMessage("YANDEX_TYRBO_API_FIELD"); ?> <?echo $i + 1 ?></b> (<?echo htmlspecialcharsbx($field); ?>):
			</td>
			<td width="60%">
				<select name="field_<?echo $i ?>">
					<option value=""> - </option>
					<?
					foreach ($arAvailFields as $ar)
					{
						$bSelected = ${"field_".$i} == $ar["value"];
						if (!$bSelected && !isset(${"field_".$i}))
							$bSelected = $ar["value"] == $field;
						?>
						<option value="<?echo htmlspecialcharsbx($ar["value"]); ?>" <?
						if ($bSelected)
							echo "selected"?>><?echo htmlspecialcharsbx($ar["name"]); ?></option>
						<?
					}
					?>
				</select>
			</td>
		</tr>
		<?
	}
	?>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("YANDEX_TYRBO_API_ADDIT_SETTINGS"); ?></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("YANDEX_TYRBO_API_LID"); ?>:</td>
		<td align="left">
			<?\Yandex\TurboAPI\CYandexTurboAPITools::ShowLidField('LID', htmlspecialcharsbx($LID));?>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?echo GetMessage("YANDEX_TYRBO_API_DATA_SAMPLES"); ?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<?$sContent = "";
			if (strlen($DATA_FILE_NAME) > 0)
			{
				$DATA_FILE_NAME = trim(str_replace("\\", "/", trim($DATA_FILE_NAME)) , "/");
				$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$DATA_FILE_NAME);
				if (
					(strlen($FILE_NAME) > 1)
					&& ($FILE_NAME == "/".$DATA_FILE_NAME)
					&& $APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W"
				)
				{
					$f = $io->GetFile($_SERVER["DOCUMENT_ROOT"].$FILE_NAME);
					$file_id = $f->open("rb");
					$sContent = fread($file_id, 10000);
					fclose($file_id);
				}
			}
			?>
			<textarea name="data" rows="10" cols="80" style="width:100%"><?echo htmlspecialcharsbx($sContent); ?></textarea>
		</td>
	</tr>
	<?
}
$tabControl->EndTab();

$tabControl->BeginNextTab();
if ($STEP == 4)
{
?>
	<tr>
		<td>
		<? CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"MESSAGE" => !$bAllLinesLoaded? GetMessage("YANDEX_TYRBO_API_AUTO_REFRESH_CONTINUE"): GetMessage("YANDEX_TYRBO_API_SUCCESS"),
			"DETAILS" =>

			GetMessage("YANDEX_TYRBO_API_SU_ALL").' <b>'.$line_num.'</b><br>'
			.GetMessage("YANDEX_TYRBO_API_SU_CORR").' <b>'.$correct_lines.'</b><br>'
			.GetMessage("YANDEX_TYRBO_API_SU_ER").' <b>'.$error_lines.'</b><br>',
			"HTML" => true,
		))?>
		</td>
	</tr>
<?
}
$tabControl->EndTab();

$tabControl->Buttons();

if ($STEP < 4)
{
	?>
	<input type="hidden" name="STEP" value="<?echo $STEP + 1; ?>">
	<?echo bitrix_sessid_post(); ?>
	<?
	if ($STEP > 1)
	{
		?>
		<input type="hidden" name="URL_DATA_FILE" value="<?echo htmlspecialcharsbx($DATA_FILE_NAME); ?>">
		<?
	}
	if ($STEP <> 2)
	{
		?>
		<input type="hidden" name="delimiter_r" value="<?echo htmlspecialcharsbx($delimiter_r); ?>">
		<input type="hidden" name="delimiter_other_r" value="<?echo htmlspecialcharsbx($delimiter_other_r); ?>">
		<input type="hidden" name="first_names_r" value="<?echo htmlspecialcharsbx($first_names_r); ?>">
		<?
	}
	if ($STEP <> 3)
	{
		foreach ($_POST as $name => $value)
		{
			if (preg_match("/^field_(\\d+)$/", $name))
			{
				?>
				<input type="hidden" name="<?echo $name ?>" value="<?echo htmlspecialcharsbx($value); ?>">
				<?
			}
		} 
		?>
		<input type="hidden" name="max_execution_time" value="<?echo htmlspecialcharsbx($max_execution_time); ?>">
		<?
	}
	if ($STEP > 1)
	{
		?>
		<input type="submit" name="backButton" value="&lt;&lt; <?echo GetMessage("YANDEX_TYRBO_API_BACK"); ?>">
		<?
	}
	?>
	<input type="submit" value="<?echo ($STEP == 3) ? GetMessage("YANDEX_TYRBO_API_NEXT_STEP_F") : GetMessage("YANDEX_TYRBO_API_NEXT_STEP"); ?> &gt;&gt;" name="submit_btn" class="adm-btn-save">
	<?
}
else
{
	?>
	<input type="submit" name="backButton2" value="&lt;&lt; <?echo GetMessage("YANDEX_TYRBO_API_2_1_STEP"); ?>" class="adm-btn-save">
	<?
}
$tabControl->End();
?>
</form>
<script type="text/javascript">
<?if ($STEP < 2): ?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
tabControl.DisableTab("edit4");
<?elseif ($STEP == 2): ?>
tabControl.SelectTab("edit2");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit3");
tabControl.DisableTab("edit4");
<?elseif ($STEP == 3): ?>
tabControl.SelectTab("edit3");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit4");
<?elseif ($STEP > 3): ?>
tabControl.SelectTab("edit4");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
<?endif; ?>
</script>
<?require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");?>