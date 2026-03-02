<?php
/*************************************************************************************
 *                                                                                   *
 * Copyright (C) 2014-2022  Mercury Labs SAGL  <info@mercurylabs.ch>                 *
 * Copyright (C) 2014-2022  Reto Kessler       <reto.kessler@mercurylabs.ch>         *
 *                                                                                   *
 * Licence       : COMMERCIAL                                                        *
 * File          : htdocs/custom/swissbanking/admin/swissbanking.php                 *
 * Date          : 15 Apr 2014 - 25 Aug 2022                                         *
 * Description   : Page to Setup Module SwissBanking Settings                        *
 *                                                                                   *
 *************************************************************************************/
define('NOCSRFCHECK',1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include_once("../main.inc.php");
if (!$res && file_exists("../../main.inc.php")) $res = @include_once("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include_once("../../../main.inc.php");
if (!$res && strpos(str_replace('\\', '/', getcwd()), '/custom/swissbanking') !== FALSE) $res = @include_once(substr(str_replace('\\', '/', getcwd()), 0, strpos(str_replace('\\', '/', getcwd()), '/custom/swissbanking')) . '/main.inc.php');
if (!$res && strpos(str_replace('\\', '/', getcwd()), '/bitnami/dolibarr/htdocs') !== FALSE) $res = @include_once('/opt/bitnami/dolibarr/htdocs/main.inc.php');
if (!$res) die("Include of main lib failed !!!");

global $langs, $user;

require_once(DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("swissbanking@swissbanking");

if (!$user->admin) accessforbidden();

$cwd = explode('/', str_replace('\\', '/', getcwd()));
$pos = sizeof($cwd) - 3;
if ($cwd[$pos] != substr($dolibarr_main_url_root_alt, 1)) {
  echo 'Error ! Your installation does not respect the new Dolibarr Standards.<br /><br />
In order to resolve please do the following:<br /><br />
1. In your Dolibarr installation directory, edit the htdocs/conf/conf.php file<br />
- Find the following lines:<br />
&nbsp;&nbsp;//$=dolibarr_main_url_root_alt ...<br />
&nbsp;&nbsp;//$=dolibarr_main_document_root_alt ...<br />
- Uncomment these lines (delete the leading "//") and assign a sensible value according to your Dolibarr installation<br />
&nbsp;&nbsp;For example :<br />
&nbsp;&nbsp;- UNIX:<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_url_root = \'http://localhost/Dolibarr/htdocs\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_document_root = \'/var/www/Dolibarr/htdocs\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_url_root_alt = \'/custom\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_document_root_alt = \'/var/www/Dolibarr/htdocs/custom\';<br />
&nbsp;&nbsp;- Windows:<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_url_root = \'http://localhost/Dolibarr/htdocs\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_document_root = \'C:/My Web Sites/Dolibarr/htdocs\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_url_root_alt = \'/custom\';<br />
&nbsp;&nbsp;&nbsp;&nbsp;$dolibarr_main_document_root_alt = \'C:/My Web Sites/Dolibarr/htdocs/custom\';<br /><br />
2. Move the swissbanking directory from your "htdocs" directory into "htdocs/custom" directory.<br /><br />
3. Click <a href="/admin/modules.php?mainmenu=home">HERE</a>';
  die();
}

if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "110") {
  dolibarr_set_const($db, "SWISSBANKING_PRINTZEROTOTAL", "Yes", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 110
  dolibarr_set_const($db, "SWISSBANKING_ISRBGPRINT", "No", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 110
  dolibarr_set_const($db, "SWISSBANKING_ISRBGX", "No", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 110
  dolibarr_set_const($db, "SWISSBANKING_ISRBGY", "No", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 110
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "110", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 110
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 110"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "111") {
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_TRIGGERS", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_LOGIN", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_SUBSTITUTIONS", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_MENUS", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_THEME", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_TPL", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_BARCODE", "", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "MAIN_MODULE_SWISSBANKING_MODELS", "1", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "SWISSBANKING_WIPEDATAONDISABLE", "No", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 111
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "111", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 111
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 111"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "112") {
  dolibarr_del_const($db, "SWISSBANKING_ADDTEXTRESETH", $conf->entity); // New parameter removed in DB version 112
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "112", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 112
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 112"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "113") {
  dolibarr_set_const($db, "SWISSBANKING_SLIPTYPE", "ISR", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 113 for QR Invoice
  dolibarr_set_const($db, "SWISSBANKING_QRSLIP_SIZE", "3", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 113 for QR Invoice
  dolibarr_set_const($db, "SWISSBANKING_QRSLIP_FONT", "2", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 113 for QR Invoice
  $sqlupdate = "ALTER TABLE " . MAIN_DB_PREFIX . "swissbanking_accounts ADD COLUMN qriban varchar(30) NULL DEFAULT NULL AFTER ccp"; // New column added in DB version 113 for QR Invoice
  $resql = $db->query($sqlupdate);
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "113", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 113
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 113"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "114") {
  $sqlupdate = "ALTER TABLE " . MAIN_DB_PREFIX . "swissbanking_payments ADD COLUMN imported int(1) NOT NULL DEFAULT '1' AFTER file_md5"; // New column added in DB version 114 for imported line status
  $resql = $db->query($sqlupdate);
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "114", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 114
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 114"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "115") {
  $sqlupdate = "ALTER TABLE " . MAIN_DB_PREFIX . "swissbanking DROP PRIMARY KEY, ADD PRIMARY KEY ( `invoiceid`, `isrcode` ) USING BTREE"; // New index schema added in DB version 115
  $resql = $db->query($sqlupdate);
  dolibarr_set_const($db, "SWISSBANKING_PRINTQRREF", "Yes", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 115 for QR Invoice
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "115", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 115
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 115"), [], 'mesgs');
}
if(!isset($conf->global->SWISSBANKING_VERSION) || $conf->global->SWISSBANKING_VERSION < "116") {
  dolibarr_set_const($db, "SWISSBANKING_PRINT_A4QR_HEADER", "Yes", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 116 for QR Invoice
  dolibarr_set_const($db, "SWISSBANKING_PRINT_A4QR_ADDRESS", "Yes", 'chaine', 0, '', $conf->entity); // New parameter added in DB version 116 for QR Invoice
  dolibarr_set_const($db, "SWISSBANKING_VERSION", "116", 'chaine', 0, '', $conf->entity); // Set SwissBanking Module DB Version to 116
  setEventMessages($langs->transnoentities("DBUpdatedtoVersion" . " 116"), [], 'mesgs');
}

$swissbanking_dol_root = DOL_DOCUMENT_ROOT;
if (file_exists("../move-contents-to-fonts")) {
  $filemoved = true;
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arial.ctg.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ctg.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arial.ctg.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arial.php")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.php", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arial.php");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arial.ttf")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ttf", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arial.ttf");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arial.txt")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.txt", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arial.txt");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arial.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arial.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arialb.ctg.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ctg.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arialb.ctg.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arialb.php")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.php", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arialb.php");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arialb.ttf")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ttf", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arialb.ttf");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arialb.txt")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.txt", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arialb.txt");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/arialb.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/arialb.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutiger.ctg.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ctg.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutiger.ctg.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutiger.php")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.php", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutiger.php");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutiger.ttf")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ttf", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutiger.ttf");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutiger.txt")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.txt", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutiger.txt");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutiger.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutiger.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutigerb.ctg.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ctg.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutigerb.ctg.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutigerb.php")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.php", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutigerb.php");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutigerb.ttf")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ttf", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutigerb.ttf");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutigerb.txt")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.txt", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutigerb.txt");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/frutigerb.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/frutigerb.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/ocrb10.ctg.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ctg.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/ocrb10.ctg.z");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/ocrb10.php")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.php", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/ocrb10.php");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/ocrb10.ttf")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ttf", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/ocrb10.ttf");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/ocrb10.txt")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.txt", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/ocrb10.txt");
  if (!file_exists("../../../includes/tecnickcom/tcpdf/fonts/ocrb10.z")) $filemoved = $filemoved & @rename($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.z", $swissbanking_dol_root . "/includes/tecnickcom/tcpdf/fonts/ocrb10.z");
  if ($filemoved) {
    $filedeleted = true;
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ctg.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ctg.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.php")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.php");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ttf")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.ttf");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.txt")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.txt");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arial.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ctg.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ctg.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.php")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.php");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ttf")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.ttf");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.txt")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.txt");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/arialb.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ctg.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ctg.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.php")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.php");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ttf")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.ttf");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.txt")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.txt");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutiger.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ctg.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ctg.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.php")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.php");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ttf")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.ttf");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.txt")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.txt");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/frutigerb.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ctg.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ctg.z");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.php")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.php");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ttf")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.ttf");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.txt")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.txt");
    if(file_exists($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.z")) $filedeleted = $filedeleted & @unlink($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts/ocrb10.z");
    $filedeleted = $filedeleted & @rmdir($swissbanking_dol_root . $dolibarr_main_url_root_alt . "/swissbanking/move-contents-to-fonts");
    if (!$filedeleted) {
      echo 'Warning ! Your installation does not permit to delete some files or directories, please do it manually as follows:<br />
Delete the directory htdocs/' . $dolibarr_main_url_root_alt . '/swissbanking/move-contents-to-fonts/* with all the contents';
    }
  }
  else {
    echo 'Warning ! Your installation does not permit to move files across directories, please do it manually as follows:<br />
1. Move the contents of:<br />
&nbsp;&nbsp;- htdocs/' . $dolibarr_main_url_root_alt . '/swissbanking/move-contents-to-fonts/*<br />
&nbsp;&nbsp;to:<br />
&nbsp;&nbsp;- htdocs/includes/tecnickcom/tcpdf/fonts/.<br />
2. Delete the empty directory htdocs/' . $dolibarr_main_url_root_alt . '/swissbanking/move-contents-to-fonts';
  }
}

if (GETPOSTISSET('what') && $_GET['what']) {
  $what = $_GET['what'];
  switch($what) {
    case "a":
      if (GETPOSTISSET('action') && $_POST['action'] == "bankaccount") {
        $dolbankid = $_POST['dolbankid'];
        $label = $_POST['label'];
        $ccp = $_POST['ccp'];
        $qriban = $_POST['qriban'];
        $pvrbankid = $_POST['pvrbankid'];
        $printdet = $_POST['printdet'];

        $qribanCode = substr(preg_replace('/\s+/', '', $qriban), 4, 5);
        $qribanCountry = strtoupper(substr(preg_replace('/\s+/', '', $qriban), 0, 2));
        if (strlen(preg_replace('/\s+/', '', $qriban)) == 0) $qribanTest = true;
        elseif ((int) $qribanCode >= 30000 && (int) $qribanCode <= 31999 && ($qribanCountry == 'CH' || $qribanCountry == 'LI')) $qribanTest = true;
        else {
          $qribanTest = false;
          setEventMessages($langs->transnoentities("QrIbanNotValid"), [], 'errors');
        }

        if(strlen($label) && strlen($ccp) >= 5 && strlen($pvrbankid) == 6 && $qribanTest) {
          $sql = "INSERT INTO " . MAIN_DB_PREFIX . "swissbanking_accounts (dolbankid, label, ccp, qriban, bankid, printdet, isdefault, entity) VALUES ('" . $dolbankid . "', '" . $label . "', '" . $ccp . "', '" . $qriban . "', '" . $pvrbankid . "', '" . $printdet . "', '0', '" . $conf->entity . "')";
          $resql = $db->query($sql);
          if ($resql <= 0) {
            $error = $db->lasterror();
            dol_syslog($db, $error, LOG_ERR);
            return -1;
          }
          header("Location: " . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/admin/swissbanking.php");
        }
        else {
          // SwissBanking Configuration get
          llxHeader('', 'SwissBanking', $linktohelp);
          $linkback='<a href="' . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . '/swissbanking/admin/swissbanking.php">' . $langs->trans("BackToMainConfig") . '</a>';
          print_fiche_titre($langs->trans("SwissBankingSetup"), $linkback, 'setup');
          clearstatcache();
          dol_htmloutput_mesg($mesg, $mesgs);
?>
<br><br>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=a">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><?php echo $langs->trans("BankPVRParameters"); ?></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("BankAccountSelect"); ?></td>
      <td align="left">
<?php
          $sql = "SELECT rowid, label, bank, number FROM " . MAIN_DB_PREFIX . "bank_account WHERE courant='1' AND entity='" . $conf->entity . "' ORDER BY label ASC";
          $resql = $db->query($sql);
          if ($resql) {
            if($db->num_rows($resql)) {
?>
        <select class="flat" name="dolbankid">
<?php
              $i = 0;
              while ($i < $db->num_rows($resql)) {
                $objp = $db->fetch_object($resql);
?>
          <option value="<?php echo $objp->rowid; ?>"<?php if($objp->rowid == $dolbankid) echo " selected"; ?>><?php echo $objp->label . " - " . $objp->bank . " - " . $objp->number; ?></option>
<?php
                $i++;
              }
?>
        </select>
<?php
            }
            else echo $langs->trans("SetupBankingFirst");
          }
          else {
            $error = $db->lasterror();
            dol_syslog($db, $error, LOG_ERR);
            return -1;
          }
?>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("Label"); ?></td>
      <td align="left">
        <input type="text" name="label" size="20" class="flat" value="<?php echo $label; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentitiesnoconv("AccountLabelTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("CCP"); ?></td>
      <td align="left"><input type="text" name="ccp" size="10" class="flat" value="<?php echo $ccp; ?>"></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("QRIBAN"); ?></td>
      <td align="left">
        <input type="text" name="qriban" size="30" class="flat" value="<?php echo $qriban; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountQRIBANTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PVRBankID"); ?></td>
      <td align="left">
        <input type="text" name="pvrbankid" size="10" maxlength="6" class="flat" value="<?php echo $pvrbankid; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountPVBRBankIDTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PrintDet"); ?></td>
      <td align="left">
        <select class="flat" name="printdet">
          <option value="IBAN"<?php if($printdet == "IBAN") echo " selected"; ?>>IBAN</option>
          <option value="acct"<?php if($printdet == "acct") echo " selected"; ?>><?php echo $langs->trans("AccountNumber"); ?></option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if($db->num_rows($resql) < 1) echo " disabled"; ?>></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="bankaccount">
</form>
<br><br>
<?php
          llxFooter();
        }
      }
      else {
        // SwissBanking Configuration get
        llxHeader('', 'SwissBanking', $linktohelp);
        $linkback='<a href="' . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . '/swissbanking/admin/swissbanking.php">' . $langs->trans("BackToMainConfig") . '</a>';
        print_fiche_titre($langs->trans("SwissBankingSetup"), $linkback, 'setup');
        clearstatcache();
        dol_htmloutput_mesg($mesg, $mesgs);
?>
<br><br>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=a">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><?php echo $langs->trans("BankPVRParameters"); ?></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("BankAccountSelect"); ?></td>
      <td align="left">
<?php
        $sql = "SELECT rowid, label, bank, number FROM " . MAIN_DB_PREFIX . "bank_account WHERE courant='1' AND entity='" . $conf->entity . "' ORDER BY label ASC";
        $resql = $db->query($sql);
        if ($resql) {
          if($db->num_rows($resql)) {
?>
        <select class="flat" name="dolbankid">
<?php
            $i = 0;
            while ($i < $db->num_rows($resql)) {
              $objp = $db->fetch_object($resql);
?>
          <option value="<?php echo $objp->rowid; ?>"<?php if($objp->rowid == $dolbankid) echo " selected"; ?>><?php echo $objp->label . " - " . $objp->bank . " - " . $objp->number; ?></option>
<?php
              $i++;
            }
?>
        </select>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("Label"); ?></td>
      <td align="left">
        <input type="text" name="label" size="20" class="flat" value="">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentitiesnoconv("AccountLabelTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("CCP"); ?></td>
      <td align="left"><input type="text" name="ccp" size="10" class="flat" value="01-23456-78"></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("QRIBAN"); ?></td>
      <td align="left">
        <input type="text" name="qriban" size="30" class="flat" value="CH00 3000 0000 0000 0000 0">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountQRIBANTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PVRBankID"); ?></td>
      <td align="left">
        <input type="text" name="pvrbankid" size="10" maxlength="6" class="flat" value="000000">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountPVBRBankIDTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PrintDet"); ?></td>
      <td align="left">
        <select class="flat" name="printdet">
          <option value="IBAN">IBAN</option>
          <option value="acct"><?php echo $langs->trans("AccountNumber"); ?></option>
        </select>
<?php
          }
          else echo $langs->trans("SetupBankingFirst");
        }
        else {
          $error = $db->lasterror();
          dol_syslog($db, $error, LOG_ERR);
          return -1;
        }
?>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if($db->num_rows($resql) < 1) echo " disabled"; ?>></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="bankaccount">
</form>
<br><br>
<?php
        llxFooter();
      }
    break;

    case "m":
      if (GETPOSTISSET('action') && $_POST['action'] == "bankaccount") {
        if(GETPOSTISSET('id') && $_POST['id'] != "") {
          $id = $_POST['id'];
          $dolbankid = $_POST['dolbankid'];
          $label = $_POST['label'];
          $ccp = $_POST['ccp'];
          $qriban = $_POST['qriban'];
          $pvrbankid = $_POST['pvrbankid'];
          $printdet = $_POST['printdet'];

          $qribanCode = substr(preg_replace('/\s+/', '', $qriban), 4, 5);
          $qribanCountry = strtoupper(substr(preg_replace('/\s+/', '', $qriban), 0, 2));
          if (strlen(preg_replace('/\s+/', '', $qriban)) == 0) $qribanTest = true;
          elseif ((int) $qribanCode >= 30000 && (int) $qribanCode <= 31999 && ($qribanCountry == 'CH' || $qribanCountry == 'LI')) $qribanTest = true;
          else {
            $qribanTest = false;
            setEventMessages($langs->transnoentities("QrIbanNotValid"), [], 'errors');
          }

          if(strlen($label) && strlen($ccp) > 6 && strlen($pvrbankid) == 6 && $qribanTest) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "swissbanking_accounts SET dolbankid='" . $dolbankid . "',label='" . $label . "',ccp='" . $ccp . "',qriban='" . $qriban . "',bankid='" . $pvrbankid . "',printdet='" . $printdet . "' WHERE rowid='" . $id . "' AND entity='" . $conf->entity . "'";
            $resql = $db->query($sql);
            if ($resql <= 0) {
              $error = $db->lasterror();
              dol_syslog($db, $error, LOG_ERR);
              return -1;
            }
            header("Location: " . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/admin/swissbanking.php");
          }
          else {
            // SwissBanking Configuration get
            llxHeader('', 'SwissBanking', $linktohelp);
            $linkback = '<a href="' . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . '/swissbanking/admin/swissbanking.php">' . $langs->trans("BackToMainConfig") . '</a>';
            print_fiche_titre($langs->trans("SwissBankingSetup"), $linkback, 'setup');
            clearstatcache();
            dol_htmloutput_mesg($mesg, $mesgs);
?>
<br><br>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=m">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><?php echo $langs->trans("BankPVRParameters"); ?></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("BankAccountSelect"); ?></td>
      <td align="left">
<?php
            $sql = "SELECT rowid, label, bank, number FROM " . MAIN_DB_PREFIX . "bank_account WHERE courant='1' AND entity='" . $conf->entity . "' ORDER BY label ASC";
            $resql = $db->query($sql);
            if ($resql) {
              if($db->num_rows($resql)) {
?>
        <select class="flat" name="dolbankid">
<?php
                $i = 0;
                while ($i < $db->num_rows($resql)) {
                  $objp = $db->fetch_object($resql);
?>
          <option value="<?php echo $objp->rowid; ?>"<?php if($objp->rowid == $dolbankid) echo " selected"; ?>><?php echo $objp->label." - ".$objp->bank." - ".$objp->number; ?></option>
<?php
                  $i++;
                }
?>
        </select>
<?php
              }
              else echo $langs->trans("SetupBankingFirst");
            }
            else {
              $error = $db->lasterror();
              dol_syslog($db, $error, LOG_ERR);
              return -1;
            }
?>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("Label"); ?></td>
      <td align="left">
        <input type="text" name="label" size="20" class="flat" value="<?php echo $label; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentitiesnoconv("AccountLabelTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("CCP"); ?></td>
      <td align="left"><input type="text" name="ccp" size="10" class="flat" value="<?php echo $ccp; ?>"></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("QRIBAN"); ?></td>
      <td align="left">
        <input type="text" name="qriban" size="30" class="flat" value="<?php echo $qriban; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountQRIBANTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PVRBankID"); ?></td>
      <td align="left">
        <input type="text" name="pvrbankid" size="10" maxlength="6" class="flat" value="<?php echo $pvrbankid; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountPVBRBankIDTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PrintDet"); ?></td>
      <td align="left">
        <select class="flat" name="printdet">
          <option value="IBAN"<?php if($printdet == "IBAN") echo " selected"; ?>>IBAN</option>
          <option value="acct"<?php if($printdet == "acct") echo " selected"; ?>><?php echo $langs->trans("AccountNumber"); ?></option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Update"); ?>"></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="bankaccount">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
</form>
<br><br>
<?php
            llxFooter();
          }
        }
        else header("Location: ".$dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/admin/swissbanking.php");
      }
      else {
        if(GETPOSTISSET('id') && $_GET['id'] != "" && $_GET['id'] != "0") {
          $id=$_GET['id'];
          $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE rowid='" . $id . "' AND entity='" . $conf->entity . "' LIMIT 1";
          $resql = $db->query($sql);
          if ($resql) {
            if($db->num_rows($resql)) {
              $objp = $db->fetch_object($resql);
              $dolbankid = $objp->dolbankid;
              $label = $objp->label;
              $ccp = $objp->ccp;
              $qriban = $objp->qriban;
              $pvrbankid = $objp->bankid;
              $printdet = $objp->printdet;
              // SwissBanking Configuration get
              llxHeader('', 'SwissBanking', $linktohelp);
              $linkback='<a href="' . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . '/swissbanking/admin/swissbanking.php">' . $langs->trans("BackToMainConfig") . '</a>';
              print_fiche_titre($langs->trans("SwissBankingSetup"),$linkback,'setup');
              clearstatcache();
              dol_htmloutput_mesg($mesg, $mesgs);
?>
<br><br>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=m">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><?php echo $langs->trans("BankPVRParameters"); ?></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("BankAccountSelect"); ?></td>
      <td align="left">
<?php
              $sql = "SELECT rowid, label, bank, number FROM " . MAIN_DB_PREFIX . "bank_account WHERE courant='1' AND entity='" . $conf->entity . "' ORDER BY label ASC";
              $resql = $db->query($sql);
              if ($resql) {
                if($db->num_rows($resql)) {
?>
        <select class="flat" name="dolbankid">
<?php
                  $i = 0;
                  while ($i < $db->num_rows($resql)) {
                    $objp = $db->fetch_object($resql);
?>
          <option value="<?php echo $objp->rowid; ?>"<?php if($objp->rowid == $dolbankid) echo " selected"; ?>><?php echo $objp->label . " - " . $objp->bank . " - " . $objp->number; ?></option>
<?php
                    $i++;
                  }
?>
        </select>
<?php
                }
                else echo $langs->trans("SetupBankingFirst");
              }
              else {
                $error = $db->lasterror();
                dol_syslog($db, $error, LOG_ERR);
                return -1;
              }
?>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("Label"); ?></td>
      <td align="left">
        <input type="text" name="label" size="20" class="flat" value="<?php echo $label; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentitiesnoconv("AccountLabelTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("CCP"); ?></td>
      <td align="left"><input type="text" name="ccp" size="10" class="flat" value="<?php echo $ccp; ?>"></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("QRIBAN"); ?></td>
      <td align="left">
        <input type="text" name="qriban" size="30" class="flat" value="<?php echo $qriban; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountQRIBANTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PVRBankID"); ?></td>
      <td align="left">
        <input type="text" name="pvrbankid" size="10" maxlength="6" class="flat" value="<?php echo $pvrbankid; ?>">&nbsp;
        <a href="#" title="<?php echo $langs->transnoentities("AccountPVBRBankIDTooltip"); ?>" class="classfortooltip refurl">
          <span class="fas fa-info-circle paddingright classfortooltip"></span>
        </a>
      </td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("PrintDet"); ?></td>
      <td align="left">
        <select class="flat" name="printdet">
          <option value="IBAN"<?php if ($printdet == "IBAN") echo " selected"; ?>>IBAN</option>
          <option value="acct"<?php if ($printdet == "acct") echo " selected"; ?>><?php echo $langs->trans("AccountNumber"); ?></option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Update"); ?>"></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="bankaccount">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
</form>
<br><br>
<?php
              llxFooter();
            }
            else header("Location: /swissbanking/admin/swissbanking.php");
          }
          else {
            $error = $db->lasterror();
            dol_syslog($db, $error, LOG_ERR);
            return -1;
          }
        }
        else header("Location: /swissbanking/admin/swissbanking.php");
      }
    break;

    case "d":
      if (GETPOSTISSET('id') && $_GET['id'] != "" && $_GET['id'] != "0") {
        $id=$_GET['id'];
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE rowid='" . $id . "' LIMIT 1";
        $resql = $db->query($sql);
        if ($resql <= 0) {
          $error = $db->lasterror();
          dol_syslog($db, $error, LOG_ERR);
          return -1;
        }
      }
      header("Location: " . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/admin/swissbanking.php");
    break;

    default:
      header("Location: " . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/admin/swissbanking.php");
    break;
  }
}
else {
  if (GETPOSTISSET('action') && $_POST['action']=='updatecfg') {
    if(GETPOSTISSET('GENERATIONMETHOD')) {
      dolibarr_set_const($db, "SWISSBANKING_GENERATIONMETHOD", $_POST['GENERATIONMETHOD'], 'chaine', 0, '', $conf->entity); // PVR Generation Method (Append or New File)
      if (versioncompare(versiondolibarrarray(), array(3,8,0)) >= 0) {
        if ($_POST['GENERATIONMETHOD'] == 'New' && empty($conf->invoicetracking->enabled)) dolibarr_set_const($db, "PDF_SECURITY_ENCRYPTION", "1", 'chaine', 0, '', $conf->entity); // Enable PDF Security
        else dolibarr_del_const($db, "PDF_SECURITY_ENCRYPTION", $conf->entity); //Disable PDF Security. If it remains enabled, we can not append the ISR/QR
      }
    }
    if(GETPOSTISSET('SLIPTYPE')) dolibarr_set_const($db, "SWISSBANKING_SLIPTYPE", $_POST['SLIPTYPE'], 'chaine', 0, '', $conf->entity); // Slip Type to be generated. ISR or QR
    if(GETPOSTISSET('QRSLIP_SIZE')) dolibarr_set_const($db, "SWISSBANKING_QRSLIP_SIZE", $_POST['QRSLIP_SIZE'], 'chaine', 0, '', $conf->entity); // Slip Size for QR Invoice (1=DIN A4, 2=DIN A5, 3=DIN A6/5)
    if(GETPOSTISSET('QRSLIP_FONT')) dolibarr_set_const($db, "SWISSBANKING_QRSLIP_FONT", $_POST['QRSLIP_FONT'], 'chaine', 0, '', $conf->entity); // Slip Font for QR Invoice (1=Arial, 2=Frutiger)
    if(GETPOSTISSET('PRINTQRREF')) dolibarr_set_const($db, "SWISSBANKING_PRINTQRREF", $_POST['PRINTQRREF'], 'chaine', 0, '', $conf->entity); // Print Customer Reference on QR Slip
  
    if(GETPOSTISSET('PRINT_A4QR_HEADER')) dolibarr_set_const($db, "SWISSBANKING_PRINT_A4QR_HEADER", $_POST['PRINT_A4QR_HEADER'], 'chaine', 0, '', $conf->entity); // Print Invoice Header on A4 QR Slip
    if(GETPOSTISSET('PRINT_A4QR_ADDRESS')) dolibarr_set_const($db, "SWISSBANKING_PRINT_A4QR_ADDRESS", $_POST['PRINT_A4QR_ADDRESS'], 'chaine', 0, '', $conf->entity); // Print Invoice Address on A4  QR Slip
  
    if(GETPOSTISSET('FILENAMEPREFIX')) dolibarr_set_const($db, "SWISSBANKING_FILENAMEPREFIX", $_POST['FILENAMEPREFIX'], 'chaine', 0, '', $conf->entity); // Prefix of the New Filename
    if(GETPOSTISSET('FILENAMESUFFIX')) dolibarr_set_const($db, "SWISSBANKING_FILENAMESUFFIX", $_POST['FILENAMESUFFIX'], 'chaine', 0, '', $conf->entity); // Suffix of the New Filename
    if(GETPOSTISSET('PAGEORIENTATION')) dolibarr_set_const($db, "SWISSBANKING_PAGEORIENTATION", $_POST['PAGEORIENTATION'], 'chaine', 0, '', $conf->entity); // Page Orientation
    if(GETPOSTISSET('PAGEFORMATX')) dolibarr_set_const($db, "SWISSBANKING_PAGEFORMATX", $_POST['PAGEFORMATX'], 'chaine', 0, '', $conf->entity); // Page Format WIDTH
    if(GETPOSTISSET('PAGEFORMATY')) dolibarr_set_const($db, "SWISSBANKING_PAGEFORMATY", $_POST['PAGEFORMATY'], 'chaine', 0, '', $conf->entity); // Page Format HEIGHT
    if(GETPOSTISSET('PRINTCHARACTER')) dolibarr_set_const($db, "SWISSBANKING_PRINTCHARACTER", $_POST['PRINTCHARACTER'], 'chaine', 0, '', $conf->entity); // Character Type for the document
    
    // Background Parameters
    if(GETPOSTISSET('ISRBGPRINT')) dolibarr_set_const($db, "SWISSBANKING_ISRBGPRINT", $_POST['ISRBGPRINT'], 'chaine', 0, '', $conf->entity); // Print ISR Background
    if(GETPOSTISSET('ISRBGX')) dolibarr_set_const($db, "SWISSBANKING_ISRBGX", $_POST['ISRBGX'], 'chaine', 0, '', $conf->entity); // ISR Background Top-Left X Position
    if(GETPOSTISSET('ISRBGY')) dolibarr_set_const($db, "SWISSBANKING_ISRBGY", $_POST['ISRBGY'], 'chaine', 0, '', $conf->entity); // ISR Background Top-Left Y Position
  
    //Full PVR Parameters
    if(GETPOSTISSET('BANKADDRESSLEFTX')) dolibarr_set_const($db, "SWISSBANKING_BANKADDRESSLEFTX", $_POST['BANKADDRESSLEFTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKADDRESSLEFTY')) dolibarr_set_const($db, "SWISSBANKING_BANKADDRESSLEFTY", $_POST['BANKADDRESSLEFTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKADDRESSRIGHTX')) dolibarr_set_const($db, "SWISSBANKING_BANKADDRESSRIGHTX", $_POST['BANKADDRESSRIGHTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKADDRESSRIGHTY')) dolibarr_set_const($db, "SWISSBANKING_BANKADDRESSRIGHTY", $_POST['BANKADDRESSRIGHTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('COMPANYADDRESSLEFTX')) dolibarr_set_const($db, "SWISSBANKING_COMPANYADDRESSLEFTX", $_POST['COMPANYADDRESSLEFTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('COMPANYADDRESSLEFTY')) dolibarr_set_const($db, "SWISSBANKING_COMPANYADDRESSLEFTY", $_POST['COMPANYADDRESSLEFTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('COMPANYADDRESSRIGHTX')) dolibarr_set_const($db, "SWISSBANKING_COMPANYADDRESSRIGHTX", $_POST['COMPANYADDRESSRIGHTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('COMPANYADDRESSRIGHTY')) dolibarr_set_const($db, "SWISSBANKING_COMPANYADDRESSRIGHTY", $_POST['COMPANYADDRESSRIGHTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKACCOUNTLEFTX')) dolibarr_set_const($db, "SWISSBANKING_BANKACCOUNTLEFTX", $_POST['BANKACCOUNTLEFTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKACCOUNTLEFTY')) dolibarr_set_const($db, "SWISSBANKING_BANKACCOUNTLEFTY", $_POST['BANKACCOUNTLEFTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKACCOUNTRIGHTX')) dolibarr_set_const($db, "SWISSBANKING_BANKACCOUNTRIGHTX", $_POST['BANKACCOUNTRIGHTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('BANKACCOUNTRIGHTY')) dolibarr_set_const($db, "SWISSBANKING_BANKACCOUNTRIGHTY", $_POST['BANKACCOUNTRIGHTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('PVRREFERENCEX')) dolibarr_set_const($db, "SWISSBANKING_PVRREFERENCEX", $_POST['PVRREFERENCEX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('PVRREFERENCEY')) dolibarr_set_const($db, "SWISSBANKING_PVRREFERENCEY", $_POST['PVRREFERENCEY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('PVRREFERENCEFONTSIZE')) dolibarr_set_const($db, "SWISSBANKING_PVRREFERENCEFONTSIZE", $_POST['PVRREFERENCEFONTSIZE'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('PVRM10Y')) dolibarr_set_const($db, "SWISSBANKING_PVRM10Y", $_POST['PVRM10Y'], 'chaine', 0, '', $conf->entity);

    //Additional Text Parameters
    if(GETPOSTISSET('ADDTEXT')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXT", $_POST['ADDTEXT'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTWIDTH')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTWIDTH", $_POST['ADDTEXTWIDTH'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTHEIGHT')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTHEIGHT", $_POST['ADDTEXTHEIGHT'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTX')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTX", $_POST['ADDTEXTX'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTY')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTY", $_POST['ADDTEXTY'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTBORDER')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTBORDER", $_POST['ADDTEXTBORDER'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTALIGN')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTALIGN", $_POST['ADDTEXTALIGN'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDTEXTAUTOPADDING')) dolibarr_set_const($db, "SWISSBANKING_ADDTEXTAUTOPADDING", $_POST['ADDTEXTAUTOPADDING'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('ADDITIONALTEXT')) dolibarr_set_const($db, "SWISSBANKING_ADDITIONALTEXT", $_POST['ADDITIONALTEXT'], 'chaine', 0, '', $conf->entity);

    if(GETPOSTISSET('LEFTDIGITSCSIZE')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSCSIZE", $_POST['LEFTDIGITSCSIZE'], 'chaine', 0, '', $conf->entity); // Left Digits Character Size
    if(GETPOSTISSET('LEFTDIGITSMX')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSMX", $_POST['LEFTDIGITSMX'], 'chaine', 0, '', $conf->entity); // Left Digits Main X
    if(GETPOSTISSET('LEFTDIGITSMY')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSMY", $_POST['LEFTDIGITSMY'], 'chaine', 0, '', $conf->entity); // Left Digits Main Y
    if(GETPOSTISSET('LEFTDIGITSMD')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSMD", $_POST['LEFTDIGITSMD'], 'chaine', 0, '', $conf->entity); // Left Digits Main Distance
    if(GETPOSTISSET('LEFTDIGITSCX')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSCX", $_POST['LEFTDIGITSCX'], 'chaine', 0, '', $conf->entity); // Left Digits Cents X
    if(GETPOSTISSET('LEFTDIGITSCY')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSCY", $_POST['LEFTDIGITSCY'], 'chaine', 0, '', $conf->entity); // Left Digits Cents Y
    if(GETPOSTISSET('LEFTDIGITSCD')) dolibarr_set_const($db, "SWISSBANKING_LEFTDIGITSCD", $_POST['LEFTDIGITSCD'], 'chaine', 0, '', $conf->entity); // Left Digits Cents Distance
    if(GETPOSTISSET('RIGHTDIGITSCSIZE')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSCSIZE", $_POST['RIGHTDIGITSCSIZE'], 'chaine', 0, '', $conf->entity); // Right Digits Character Size
    if(GETPOSTISSET('RIGHTDIGITSMX')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSMX", $_POST['RIGHTDIGITSMX'], 'chaine', 0, '', $conf->entity); // Left Digits Main X
    if(GETPOSTISSET('RIGHTDIGITSMY')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSMY", $_POST['RIGHTDIGITSMY'], 'chaine', 0, '', $conf->entity); // Left Digits Main Y
    if(GETPOSTISSET('RIGHTDIGITSMD')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSMD", $_POST['RIGHTDIGITSMD'], 'chaine', 0, '', $conf->entity); // Left Digits Main Distance
    if(GETPOSTISSET('RIGHTDIGITSCX')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSCX", $_POST['RIGHTDIGITSCX'], 'chaine', 0, '', $conf->entity); // Left Digits Cents X
    if(GETPOSTISSET('RIGHTDIGITSCY')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSCY", $_POST['RIGHTDIGITSCY'], 'chaine', 0, '', $conf->entity); // Left Digits Cents Y
    if(GETPOSTISSET('RIGHTDIGITSCD')) dolibarr_set_const($db, "SWISSBANKING_RIGHTDIGITSCD", $_POST['RIGHTDIGITSCD'], 'chaine', 0, '', $conf->entity); // Left Digits Cents Distance
    if(GETPOSTISSET('ADDRESSPRINTOUT')) dolibarr_set_const($db, "SWISSBANKING_ADDRESSPRINTOUT", $_POST['ADDRESSPRINTOUT'], 'chaine', 0, '', $conf->entity); // Address Printout Syntax
    if(GETPOSTISSET('LEFTADDRESSCSIZE')) dolibarr_set_const($db, "SWISSBANKING_LEFTADDRESSCSIZE", $_POST['LEFTADDRESSCSIZE'], 'chaine', 0, '', $conf->entity); // Left Address X
    if(GETPOSTISSET('LEFTADDRESSX')) dolibarr_set_const($db, "SWISSBANKING_LEFTADDRESSX", $_POST['LEFTADDRESSX'], 'chaine', 0, '', $conf->entity); // Left Address X
    if(GETPOSTISSET('LEFTADDRESSY')) dolibarr_set_const($db, "SWISSBANKING_LEFTADDRESSY", $_POST['LEFTADDRESSY'], 'chaine', 0, '', $conf->entity); // Left Address Y
    if(GETPOSTISSET('LEFTADDRESSLD')) dolibarr_set_const($db, "SWISSBANKING_LEFTADDRESSLD", $_POST['LEFTADDRESSLD'], 'chaine', 0, '', $conf->entity); // Left Address Line Distance
    if(GETPOSTISSET('RIGHTADDRESSCSIZE')) dolibarr_set_const($db, "SWISSBANKING_RIGHTADDRESSCSIZE", $_POST['RIGHTADDRESSCSIZE'], 'chaine', 0, '', $conf->entity); // Right Address X
    if(GETPOSTISSET('RIGHTADDRESSX')) dolibarr_set_const($db, "SWISSBANKING_RIGHTADDRESSX", $_POST['RIGHTADDRESSX'], 'chaine', 0, '', $conf->entity); // Right Address X
    if(GETPOSTISSET('RIGHTADDRESSY')) dolibarr_set_const($db, "SWISSBANKING_RIGHTADDRESSY", $_POST['RIGHTADDRESSY'], 'chaine', 0, '', $conf->entity); // Right Address Y
    if(GETPOSTISSET('RIGHTADDRESSLD')) dolibarr_set_const($db, "SWISSBANKING_RIGHTADDRESSLD", $_POST['RIGHTADDRESSLD'], 'chaine', 0, '', $conf->entity); // Right Address Line Distance
    if(GETPOSTISSET('PRINTZEROTOTAL')) dolibarr_set_const($db, "SWISSBANKING_PRINTZEROTOTAL", $_POST['PRINTZEROTOTAL'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('WIPEDATAONDISABLE')) dolibarr_set_const($db, "SWISSBANKING_WIPEDATAONDISABLE", $_POST['WIPEDATAONDISABLE'], 'chaine', 0, '', $conf->entity);
    if(GETPOSTISSET('defaccount')) {
      $defaccount = $_POST['defaccount'];
      $sql  = "UPDATE " . MAIN_DB_PREFIX . "swissbanking_accounts SET isdefault='0' WHERE entity='" . $conf->entity . "'";
      $resql = $db->query($sql);
      if ($resql) {
        $sql  = "UPDATE " . MAIN_DB_PREFIX . "swissbanking_accounts SET isdefault='1' WHERE entity='" . $conf->entity . "' AND rowid='" . $defaccount . "'";
        $resql = $db->query($sql);
      }
      else {
        $error = $db->lasterror();
        dol_syslog($db, $error, LOG_ERR);
        return -1;
      }
    }
  }

  // SwissBanking Configuration get
  llxHeader('', 'SwissBanking', $linktohelp);
  $linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
  print_fiche_titre($langs->trans("SwissBankingSetup"), $linkback, 'setup');
  clearstatcache();
  dol_htmloutput_mesg($mesg, $mesgs);
?>
<br><br>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php">
  <script type="text/javascript">
    jQuery(document).ready(function () {
      jQuery(function() {
        <?php if ($conf->global->SWISSBANKING_GENERATIONMETHOD != 'New') { ?>
        $('.GenerationMethod').hide();
        <?php } if ($conf->global->SWISSBANKING_SLIPTYPE != 'ISR') { ?>
        $('.SlipISR').hide();
        $('.SlipQR').show();
        <?php } else { ?>
        $('.SlipISR').show();
        $('.SlipQR').hide();
        <?php } if($conf->global->SWISSBANKING_ADDTEXT != 'Yes') { ?>
        $('.AddText').hide();
        <?php } if ($conf->global->SWISSBANKING_ISRBGPRINT != 'Yes') { ?>
        $('.ISRBgPrint').hide();
        <?php } ?>
      });
    });
    function showgenerationmethod() {
      $('.GenerationMethod').show();
    }
    function hidegenerationmethod() {
      $('.GenerationMethod').hide();
    }
    function showISRParams() {
      $('.SlipISR').show();
    }
    function hideISRParams() {
      $('.SlipISR').hide();
    }
    function showQRParams() {
      $('.SlipQR').show();
    }
    function hideQRParams() {
      $('.SlipQR').hide();
    }
    function showaddtext() {
      $('.AddText').show();
    }
    function hideaddtext() {
      $('.AddText').hide();
    }
    function showisrbgprint() {
      $('.ISRBgPrint').show();
    }
    function hideisrbgprint() {
      $('.ISRBgPrint').hide();
    }
  </script>
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("BASEPVRParameters"); ?></strong></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("GenerationMethod"); ?></td>
      <td align="left"><input type="radio" name="GENERATIONMETHOD" value="New"<?php if($conf->global->SWISSBANKING_GENERATIONMETHOD == 'New') echo " checked"; ?> onClick="showgenerationmethod();"> <?php echo $langs->trans("Additional"); ?>&nbsp;&nbsp;<input type="radio" name="GENERATIONMETHOD" value="Append"<?php if($conf->global->SWISSBANKING_GENERATIONMETHOD != 'New') echo " checked"; ?> onClick="hidegenerationmethod();"> <?php echo $langs->trans("Append"); ?></td>
    </tr>
    <tr class="GenerationMethod">
      <td><?php echo $langs->trans("FileNamePrefix"); ?></td>
      <td align="left"><input type="text" name="FILENAMEPREFIX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_FILENAMEPREFIX; ?>"></td>
    </tr>
    <tr class="GenerationMethod">
      <td><?php echo $langs->trans("FileNameSuffix"); ?></td>
      <td align="left"><input type="text" name="FILENAMESUFFIX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_FILENAMESUFFIX; ?>"></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("SlipType"); ?></td>
      <td align="left"><input type="radio" name="SLIPTYPE" value="ISR"<?php if($conf->global->SWISSBANKING_SLIPTYPE == 'ISR') echo " checked"; ?> onClick="showISRParams();hideQRParams();"> <?php echo $langs->trans("SlipTypeISR"); ?>&nbsp;&nbsp;<input type="radio" name="SLIPTYPE" value="QR"<?php if($conf->global->SWISSBANKING_SLIPTYPE != 'ISR') echo " checked"; ?> onClick="showQRParams();hideISRParams();"> <?php echo $langs->trans("SlipTypeQR"); ?></td>
    </tr>
    <tr class="SlipQR">
      <td><?php echo $langs->trans("QRSlipSize"); ?></td>
      <td align="left">
        <select class="flat" name="QRSLIP_SIZE">
          <option value="1"<?php if($conf->global->SWISSBANKING_QRSLIP_SIZE == "1") echo " selected"; ?>>DIN A4</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_QRSLIP_SIZE == "2") echo " selected"; ?>>DIN A5</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_QRSLIP_SIZE == "3") echo " selected"; ?>>DIN A6/5</option>
        </select>
      </td>
    </tr>
    <tr class="SlipQR">
      <td><?php echo $langs->trans("QRSlipFont"); ?></td>
      <td align="left">
        <select class="flat" name="QRSLIP_FONT">
          <option value="1"<?php if($conf->global->SWISSBANKING_QRSLIP_FONT == "1") echo " selected"; ?>>Arial (Produces a big PDF)</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_QRSLIP_FONT == "2") echo " selected"; ?>>Frutiger</option>
        </select>
      </td>
    </tr>
    <tr class="SlipQR">
      <td><?php echo $langs->trans("PrintQRRef"); ?></td>
      <td align="left">
        <select class="flat" name="PRINTQRREF">
          <option value="Yes"<?php if($conf->global->SWISSBANKING_PRINTQRREF == "Yes") echo " selected"; ?>><?php echo $langs->trans("Yes"); ?></option>
          <option value="No"<?php if($conf->global->SWISSBANKING_PRINTQRREF == "No") echo " selected"; ?>><?php echo $langs->trans("No"); ?></option>
        </select>
      </td>
    </tr>
    <tr class="SlipQR">
      <td><?php echo $langs->trans("PrintA4QRHeader"); ?></td>
      <td align="left">
        <select class="flat" name="PRINT_A4QR_HEADER">
          <option value="Yes"<?php if($conf->global->SWISSBANKING_PRINT_A4QR_HEADER == "Yes") echo " selected"; ?>><?php echo $langs->trans("Yes"); ?></option>
          <option value="No"<?php if($conf->global->SWISSBANKING_PRINT_A4QR_HEADER == "No") echo " selected"; ?>><?php echo $langs->trans("No"); ?></option>
        </select>
      </td>
    </tr>
    <tr class="SlipQR">
      <td><?php echo $langs->trans("PrintA4QRAddress"); ?></td>
      <td align="left">
        <select class="flat" name="PRINT_A4QR_ADDRESS">
          <option value="Yes"<?php if($conf->global->SWISSBANKING_PRINT_A4QR_ADDRESS == "Yes") echo " selected"; ?>><?php echo $langs->trans("Yes"); ?></option>
          <option value="No"<?php if($conf->global->SWISSBANKING_PRINT_A4QR_ADDRESS == "No") echo " selected"; ?>><?php echo $langs->trans("No"); ?></option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PageOrientation"); ?></td>
      <td align="left"><input type="radio" name="PAGEORIENTATION" value="P"<?php if($conf->global->SWISSBANKING_PAGEORIENTATION == 'P') echo " checked"; ?>> <?php echo $langs->trans("Portrait"); ?>&nbsp;&nbsp;<input type="radio" name="PAGEORIENTATION" value="L"<?php if($conf->global->SWISSBANKING_PAGEORIENTATION != 'P') echo " checked"; ?>> <?php echo $langs->trans("Landscape"); ?></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PageFormatX"); ?></td>
      <td align="left"><input type="text" name="PAGEFORMATX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_PAGEFORMATX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PageFormatY"); ?></td>
      <td align="left"><input type="text" name="PAGEFORMATY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_PAGEFORMATY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PrintCharacter"); ?></td>
      <td align="left">
        <select class="flat" name="PRINTCHARACTER">
          <option value="dejavusans"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "dejavusans") echo " selected"; ?>>Dejavu Sans</option>
          <option value="freemono"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "freemono") echo " selected"; ?>>FreeMono</option>
          <option value="freemonoB"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "freemonoB") echo " selected"; ?>>FreeMono Bold</option>
          <option value="freemonoBI"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "freemonoBI") echo " selected"; ?>>FreeMono Bold Italic</option>
          <option value="freemonoI"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "freemonoI") echo " selected"; ?>>FreeMono Italic</option>
          <option value="helvetica"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "helvetica") echo " selected"; ?>>Helvetica</option>
          <option value="helveticaB"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "helveticaB") echo " selected"; ?>>Helvetica Bold</option>
          <option value="helveticaBI"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "helveticaBI") echo " selected"; ?>>Helvetica Bold Italic</option>
          <option value="helveticaI"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "helveticaI") echo " selected"; ?>>Helvetica Italic</option>
          <option value="OCRB10"<?php if($conf->global->SWISSBANKING_PRINTCHARACTER == "OCRB10") echo " selected"; ?>>OCR-B 10</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td width="200"><?php echo $langs->trans("ISRBgPrint"); ?></td>
      <td align="left"><input type="radio" name="ISRBGPRINT" value="Yes"<?php if($conf->global->SWISSBANKING_ISRBGPRINT == 'Yes') echo " checked"; ?> onClick="showisrbgprint();"> <?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="ISRBGPRINT" value="No"<?php if($conf->global->SWISSBANKING_ISRBGPRINT != 'Yes') echo " checked"; ?> onClick="hideisrbgprint();"> <?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr class="SlipISR ISRBgPrint">
      <td><?php echo $langs->trans("ISRBgX"); ?></td>
      <td align="left"><input type="text" name="ISRBGX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ISRBGX; ?>"></td>
    </tr>
    <tr class="SlipISR ISRBgPrint">
      <td><?php echo $langs->trans("ISRBgY"); ?></td>
      <td align="left"><input type="text" name="ISRBGY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ISRBGY; ?>"></td>
    </tr>
    <tr>
      <td style="vertical-align:top;"><?php echo $langs->trans("BankAccount"); ?></td>
      <td>
<?php
  $sql  = "SELECT rowid, dolbankid, label, ccp, qriban, bankid, printdet, isdefault FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE entity='" . $conf->entity . "' ORDER by label ASC";
  $resql = $db->query($sql);
  if ($resql) {
?>
      <table width="100%" class="noborder">
        <tr class="liste_titre"><td><strong><?php echo $langs->trans("Label"); ?></strong></td><td><strong><?php echo $langs->trans("CCP"); ?></strong></td><td><strong><?php echo $langs->trans("QRIBAN"); ?></strong></td><td><strong><?php echo $langs->trans("PVRBankID"); ?></strong></td><td><strong><?php echo $langs->trans("Print"); ?></strong></td><td><strong><?php echo $langs->trans("Default"); ?></strong></td><td><strong><?php echo $langs->trans("Action"); ?></strong></td></tr>
<?php
    $i = 0;
    while ($i < $db->num_rows($resql)) {
      $objp = $db->fetch_object($resql);
      if($objp->rowid == '0') {
?>
        <tr><td colspan="4"><?php echo $langs->trans("DoNotGeneratePVR"); ?></td><td><input type="radio" name="defaccount" value="0"<?php if($objp->isdefault) echo " checked"; ?>></td><td>&nbsp;</td></tr>
<?php
      }
      else {
?>
        <tr><td><?php if (strlen($objp->label)) echo $objp->label; else echo "&nbsp;"; ?></td><td><?php if (strlen($objp->ccp)) echo $objp->ccp; else echo "&nbsp;"; ?></td><td><?php if (strlen($objp->qriban)) echo $objp->qriban; else echo "&nbsp;";?></td><td><?php if (strlen($objp->bankid)) echo $objp->bankid; else echo "&nbsp;"; ?></td><td><?php echo $langs->trans($objp->printdet); ?></td><td><input type="radio" name="defaccount" value="<?php echo $objp->rowid; ?>"<?php if($objp->isdefault) echo " checked"; ?>></td><td><input type="button" class="button" value="<?php echo $langs->trans("Modify"); ?>" onClick="location.href='<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=m&id=<?php echo $objp->rowid; ?>'"> <input type="button" class="button" value="<?php echo $langs->trans("Delete"); ?>" onClick="location.href='<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=d&id=<?php echo $objp->rowid; ?>'"></td></tr>
<?php
      }
      $i++;
    }
?>
        <tr><td align="center" colspan="7"><input type="button" class="button" value="<?php echo $langs->trans("Add"); ?>" onClick="location.href='<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/admin/swissbanking.php?what=a'"></tr>
<?php
    $db->free($resql);
?>
      </table>
<?php
  }
  else {
    $error = $db->lasterror();
    dol_syslog($db, $error, LOG_ERR);
    return -1;
  }
?>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAddressLeftX"); ?></td>
      <td><input type="text" name="BANKADDRESSLEFTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKADDRESSLEFTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAddressLeftY"); ?></td>
      <td><input type="text" name="BANKADDRESSLEFTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKADDRESSLEFTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAddressRightX"); ?></td>
      <td><input type="text" name="BANKADDRESSRIGHTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKADDRESSRIGHTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAddressRightY"); ?></td>
      <td><input type="text" name="BANKADDRESSRIGHTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKADDRESSRIGHTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("CompanyAddressLeftX"); ?></td>
      <td><input type="text" name="COMPANYADDRESSLEFTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_COMPANYADDRESSLEFTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("CompanyAddressLeftY"); ?></td>
      <td><input type="text" name="COMPANYADDRESSLEFTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_COMPANYADDRESSLEFTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("CompanyAddressRightX"); ?></td>
      <td><input type="text" name="COMPANYADDRESSRIGHTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_COMPANYADDRESSRIGHTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("CompanyAddressRightY"); ?></td>
      <td><input type="text" name="COMPANYADDRESSRIGHTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_COMPANYADDRESSRIGHTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAccountLeftX"); ?></td>
      <td><input type="text" name="BANKACCOUNTLEFTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKACCOUNTLEFTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAccountLeftY"); ?></td>
      <td><input type="text" name="BANKACCOUNTLEFTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKACCOUNTLEFTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAccountRightX"); ?></td>
      <td><input type="text" name="BANKACCOUNTRIGHTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKACCOUNTRIGHTX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("BankAccountRightY"); ?></td>
      <td><input type="text" name="BANKACCOUNTRIGHTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_BANKACCOUNTRIGHTY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PVRReferenceX"); ?></td>
      <td><input type="text" name="PVRREFERENCEX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_PVRREFERENCEX; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PVRReferenceY"); ?></td>
      <td><input type="text" name="PVRREFERENCEY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_PVRREFERENCEY; ?>"></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PVRReferenceFontSize"); ?></td>
      <td>
        <select class="flat" name="PVRREFERENCEFONTSIZE">
          <option value="9"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9") echo " selected"; ?>>9</option>
          <option value="9.1"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.1") echo " selected"; ?>>9.1</option>
          <option value="9.2"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.2") echo " selected"; ?>>9.2</option>
          <option value="9.3"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.3") echo " selected"; ?>>9.3</option>
          <option value="9.4"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.4") echo " selected"; ?>>9.4</option>
          <option value="9.5"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.5") echo " selected"; ?>>9.5</option>
          <option value="9.6"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.6") echo " selected"; ?>>9.6</option>
          <option value="9.7"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.7") echo " selected"; ?>>9.7</option>
          <option value="9.8"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.8") echo " selected"; ?>>9.8</option>
          <option value="9.9"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "9.9") echo " selected"; ?>>9.9</option>
          <option value="10"<?php if($conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE == "10") echo " selected"; ?>>10</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("PVRM10Y"); ?></td>
      <td><input type="text" name="PVRM10Y" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_PVRM10Y; ?>"></td>
    </tr>
    <tr class="liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("AddTextSettings"); ?></strong></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("AddText"); ?></td>
      <td align="left"><input type="radio" name="ADDTEXT" value="Yes"<?php if($conf->global->SWISSBANKING_ADDTEXT == 'Yes') echo " checked"; ?> onClick="showaddtext();"> <?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="ADDTEXT" value="No"<?php if($conf->global->SWISSBANKING_ADDTEXT != 'Yes') echo " checked"; ?> onClick="hideaddtext();"> <?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Width"); ?></td>
      <td><input type="text" name="ADDTEXTWIDTH" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ADDTEXTWIDTH; ?>"></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Height"); ?></td>
      <td><input type="text" name="ADDTEXTHEIGHT" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ADDTEXTHEIGHT; ?>"></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("StartingX"); ?></td>
      <td><input type="text" name="ADDTEXTX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ADDTEXTX; ?>"></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("StartingY"); ?></td>
      <td><input type="text" name="ADDTEXTY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_ADDTEXTY; ?>"></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Border"); ?></td>
      <td><input type="radio" name="ADDTEXTBORDER" value="LRTB"<?php if($conf->global->SWISSBANKING_ADDTEXTBORDER == 'LRTB') echo " checked"; ?>><?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="ADDTEXTBORDER" value="0"<?php if($conf->global->SWISSBANKING_ADDTEXTBORDER != 'LRTB') echo " checked"; ?>><?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Align"); ?></td>
      <td>
        <select class="flat" name="ADDTEXTALIGN">
          <option value="L"<?php if($conf->global->SWISSBANKING_ADDTEXTALIGN == "L") echo " selected"; ?>><?php echo $langs->trans("Left"); ?></option>
          <option value="R"<?php if($conf->global->SWISSBANKING_ADDTEXTALIGN == "R") echo " selected"; ?>><?php echo $langs->trans("Right"); ?></option>
          <option value="C"<?php if($conf->global->SWISSBANKING_ADDTEXTALIGN == "C") echo " selected"; ?>><?php echo $langs->trans("Center"); ?></option>
        </select>
      </td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Autopadding"); ?></td>
      <td><input type="radio" name="ADDTEXTAUTOPADDING" value="true"<?php if($conf->global->SWISSBANKING_ADDTEXTAUTOPADDING == 'true') echo " checked"; ?>> <?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="ADDTEXTAUTOPADDING" value="false"<?php if($conf->global->SWISSBANKING_ADDTEXTAUTOPADDING != 'true') echo " checked"; ?>> <?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr class="AddText">
      <td><?php echo $langs->trans("Text"); ?></td>
      <td align="left" colspan="2">
<?php
  if($conf->global->MAIN_MODULE_FCKEDITOR != '1') {
    dolibarr_set_const($db, "MAIN_MODULE_FCKEDITOR", "1", 'yesno', 0, '', $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_DETAILS", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_MAIL", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_MAILING", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_SOCIETE", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_PRODUCTDES", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_PRODUCTDESC", $conf->entity);
    dolibarr_del_const($db, "FCKEDITOR_ENABLE_USERSIGN", $conf->entity);
  }
  $doleditor=new DolEditor('ADDITIONALTEXT', $conf->global->SWISSBANKING_ADDITIONALTEXT, '100%', 500, 'Full', 'In', TRUE, TRUE, TRUE, 0, 0, 0);
  $doleditor->Create();
?>
      </td>
    </tr>
    <tr class="SlipISR liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("ZeroesTotalPrintHeader"); ?></strong></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("ZeroesTotalPrint"); ?></td>
      <td align="left"><input type="radio" name="PRINTZEROTOTAL" value="Yes"<?php if($conf->global->SWISSBANKING_PRINTZEROTOTAL == 'Yes') echo " checked"; ?>> <?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="PRINTZEROTOTAL" value="No"<?php if($conf->global->SWISSBANKING_PRINTZEROTOTAL != 'Yes') echo " checked"; ?>> <?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr class="SlipISR liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("PVRLeftDigits"); ?></strong></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsCharSize"); ?></td>
      <td align="left">
        <select class="flat" name="LEFTDIGITSCSIZE">
          <option value="-3"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "-3") echo " selected"; ?>>-3</option>
          <option value="-2"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "-2") echo " selected"; ?>>-2</option>
          <option value="-1"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "-1") echo " selected"; ?>>-1</option>
          <option value="0"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "0") echo " selected"; ?>>0</option>
          <option value="1"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "1") echo " selected"; ?>>+1</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "2") echo " selected"; ?>>+2</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_LEFTDIGITSCSIZE == "3") echo " selected"; ?>>+3</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsMainX"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSMX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSMX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsMainY"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSMY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSMY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsMainDistance"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSMD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSMD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsCentsX"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSCX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSCX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsCentsY"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSCY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSCY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftDigitsCentsDistance"); ?></td>
      <td align="left"><input type="text" name="LEFTDIGITSCD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTDIGITSCD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("PVRRightDigits"); ?></strong></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsCharSize"); ?></td>
      <td align="left">
        <select class="flat" name="RIGHTDIGITSCSIZE">
          <option value="-3"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "-3") echo " selected"; ?>>-3</option>
          <option value="-2"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "-2") echo " selected"; ?>>-2</option>
          <option value="-1"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "-1") echo " selected"; ?>>-1</option>
          <option value="0"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "0") echo " selected"; ?>>0</option>
          <option value="1"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "1") echo " selected"; ?>>+1</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "2") echo " selected"; ?>>+2</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_RIGHTDIGITSCSIZE == "3") echo " selected"; ?>>+3</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsMainX"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSMX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSMX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsMainY"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSMY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSMY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsMainDistance"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSMD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSMD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsCentsX"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSCX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSCX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsCentsY"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSCY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSCY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightDigitsCentsDistance"); ?></td>
      <td align="left"><input type="text" name="RIGHTDIGITSCD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTDIGITSCD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("PVRAddress"); ?></strong></td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("AddressPrintout"); ?></td>
      <td align="left">
        <select class="flat" name="ADDRESSPRINTOUT">
          <option value="0"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "0") echo " selected"; ?>>ZIP Town</option>
          <option value="1"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "1") echo " selected"; ?>>ZIP Town / RegionCode</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "2") echo " selected"; ?>>ZIP Town, RegionCode</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "3") echo " selected"; ?>>ZIP Town / Region</option>
          <option value="4"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "4") echo " selected"; ?>>ZIP Town, Region</option>
          <option value="5"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "5") echo " selected"; ?>>ZIP Town / RegionCode / CountryCode</option>
          <option value="6"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "6") echo " selected"; ?>>ZIP Town, RegionCode, CountryCode</option>
          <option value="7"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "7") echo " selected"; ?>>ZIP Town / RegionCode / Country</option>
          <option value="8"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "8") echo " selected"; ?>>ZIP Town, RegionCode, Country</option>
          <option value="9"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "9") echo " selected"; ?>>CountryCode-ZIP Town</option>
          <option value="10"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "10") echo " selected"; ?>>CountryCode-ZIP Town / RegionCode</option>
          <option value="11"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "11") echo " selected"; ?>>CountryCode-ZIP Town, RegionCode</option>
          <option value="12"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "12") echo " selected"; ?>>CountryCode-ZIP Town / Region</option>
          <option value="13"<?php if($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "13") echo " selected"; ?>>CountryCode-ZIP Town, Region</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftAddressCharSize"); ?></td>
      <td align="left">
        <select class="flat" name="LEFTADDRESSCSIZE">
          <option value="-3"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "-3") echo " selected"; ?>>-3</option>
          <option value="-2"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "-2") echo " selected"; ?>>-2</option>
          <option value="-1"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "-1") echo " selected"; ?>>-1</option>
          <option value="0"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "0") echo " selected"; ?>>0</option>
          <option value="1"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "1") echo " selected"; ?>>+1</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "2") echo " selected"; ?>>+2</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_LEFTADDRESSCSIZE == "3") echo " selected"; ?>>+3</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftAddressX"); ?></td>
      <td align="left"><input type="text" name="LEFTADDRESSX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTADDRESSX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftAddressY"); ?></td>
      <td align="left"><input type="text" name="LEFTADDRESSY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTADDRESSY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("LeftAddressLineDistance"); ?></td>
      <td align="left"><input type="text" name="LEFTADDRESSLD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_LEFTADDRESSLD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightAddressCharSize"); ?></td>
      <td align="left">
        <select class="flat" name="RIGHTADDRESSCSIZE">
          <option value="-3"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "-3") echo " selected"; ?>>-3</option>
          <option value="-2"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "-2") echo " selected"; ?>>-2</option>
          <option value="-1"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "-1") echo " selected"; ?>>-1</option>
          <option value="0"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "0") echo " selected"; ?>>0</option>
          <option value="1"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "1") echo " selected"; ?>>+1</option>
          <option value="2"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "2") echo " selected"; ?>>+2</option>
          <option value="3"<?php if($conf->global->SWISSBANKING_RIGHTADDRESSCSIZE == "3") echo " selected"; ?>>+3</option>
        </select>
      </td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightAddressX"); ?></td>
      <td align="left"><input type="text" name="RIGHTADDRESSX" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTADDRESSX; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightAddressY"); ?></td>
      <td align="left"><input type="text" name="RIGHTADDRESSY" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTADDRESSY; ?>">&nbsp;mm</td>
    </tr>
    <tr class="SlipISR">
      <td><?php echo $langs->trans("RightAddressLineDistance"); ?></td>
      <td align="left"><input type="text" name="RIGHTADDRESSLD" size="10" class="flat" value="<?php echo $conf->global->SWISSBANKING_RIGHTADDRESSLD; ?>">&nbsp;mm</td>
    </tr>
    <tr class="liste_titre">
      <td colspan="2"><strong><?php echo $langs->trans("Extra"); ?></strong></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("WipeDataOnDisable"); ?></td>
      <td align="left"><input type="radio" name="WIPEDATAONDISABLE" value="Yes"<?php if($conf->global->SWISSBANKING_WIPEDATAONDISABLE == 'Yes') echo " checked"; ?>> <?php echo $langs->trans("Yes"); ?>&nbsp;&nbsp;<input type="radio" name="WIPEDATAONDISABLE" value="No"<?php if($conf->global->SWISSBANKING_WIPEDATAONDISABLE != 'Yes') echo " checked"; ?>> <?php echo $langs->trans("No"); ?></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Modify"); ?>"></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="updatecfg">
</form>
<br><br>
<?php
  llxFooter();
}
$db->close();
?>