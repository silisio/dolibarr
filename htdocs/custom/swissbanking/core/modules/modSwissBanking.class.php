<?php
 /************************************************************************************
 *                                                                                   *
 * Copyright (C) 2014-2022  Mercury Labs SAGL  <info@mercurylabs.ch>                 *
 * Copyright (C) 2014-2022  Reto Kessler       <reto.kessler@mercurylabs.ch>         *
 *                                                                                   *
 * Licence       : COMMERCIAL                                                        *
 * File          : htdocs/custom/swissbanking/core/modules/modSwissBanking.class.php *
 * Date          : 15 Apr 2014 - 25 Aug 2022                                         *
 * Description   : Description and Activation file for Module SwissBanking           *
 *                                                                                   *
 *************************************************************************************/

include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

class modswissbanking extends DolibarrModules{
  public $db;
  public $numero = 969696;
  public $rights_class = 'swissbanking';
  public $family = 'financial';
  public $module_position = 500;
  public $familyinfo = array();
  public $name = "SwissBanking";
  public $description = "Complete Swiss Banking Solution";
  public $descriptionlong = "Complete Swiss Banking Solution";
  public $editor_name = "Mercury Labs SAGL";
  public $editor_url = "http://www.mercurylabs.ch";
  public $version = '1.5.14';
  public $const_name = 'MAIN_MODULE_SWISSBANKING';
  public $picto = 'swissbanking@swissbanking';
  public $module_parts = array(
    'triggers' => false,
    'login' => false,
    'substitutions' => false,
    'menus' => false,
    'theme' => false,
    'tpl' => false,
    'barcode' => false,
    'models' => true,
    'css' => array(),
    'js' => array(),
    'hooks' => array('invoicecard', 'pdfgeneration'),
    'dir' => array(),
    'workflow' => array(),
  );
  public $dirs = array();
  public $config_page_url = 'swissbanking.php@swissbanking';
  public $hidden = false;
  public $depends = array();
  public $requiredby = array();
  public $conflictwith = array();
  public $phpmin = array(7, 3);
  public $need_dolibarr_version = array(13, 0);
  public $max_dolibarr_version = array(22, 99);
  public $langfiles = array('swissbanking@swissbanking');
  public $const = array();
  public $tabs = array();
  public $dictionaries = array();
  public $boxes = array();
  public $cronjobs = array();
  public $rights = array();
  public $menu = array();
  public $export_code = array();
  public $export_label = array();
  public $export_enabled = array();
  public $export_permission = array();
  public $export_fields_array = array();
  public $export_entities_array = array();
  public $export_sql_start = array();
  public $export_sql_end = array();
  public $core_enabled = false;

  public function __construct($db)
  {
    global $langs, $conf;
    if (is_callable('parent::__construct')) {
      parent::__construct($db);
    } else {
      global $db;
      $this->db = $db;
    }

    if (! isset($conf->mymodule->enabled)) {
      $conf->mymodule=new stdClass();
      $conf->mymodule->enabled = 0;
    }

    $this->menu[]=array(
      'fk_menu' => 'fk_mainmenu=bank',
      'type' => 'left',                                                 // This is a Left menu entry
      'titre' => 'SwissBanking',
      'mainmenu' => 'bank',
      'leftmenu' => 'swissbanking',
      'url' => '/swissbanking/swissbanking.php',
      'position' => 100,
      'enabled' => '$conf->swissbanking->enabled',                      // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
      'perms' => '1',                                                   // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
      'target' => '',
      'user' => 0);
  }

  public function init($options = '') {
    global $conf;
    $this->remove($options);
    $sql = array(
      "DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'SWISSBANKING_%' AND entity=" . $conf->entity,
      "INSERT INTO " . MAIN_DB_PREFIX . "const (name, entity, value, type, visible, note) VALUES
      ('SWISSBANKING_ADDTEXTFILL', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTLN', " . $conf->entity . ", '10', 'chaine', 0, ''),
      ('SWISSBANKING_GENERATIONMETHOD', " . $conf->entity . ", 'New', 'chaine', 0, ''),
      ('SWISSBANKING_FILENAMESUFFIX', " . $conf->entity . ", '_PVR', 'chaine', 0, ''),
      ('SWISSBANKING_PAGEORIENTATION', " . $conf->entity . ", 'L', 'chaine', 0, ''),
      ('SWISSBANKING_PAGEFORMATX', " . $conf->entity . ", '210', 'chaine', 0, ''),
      ('SWISSBANKING_PAGEFORMATY', " . $conf->entity . ", '106', 'chaine', 0, ''),
      ('SWISSBANKING_PRINTCHARACTER', " . $conf->entity . ", 'dejavusans', 'chaine', 0, ''),
      ('SWISSBANKING_BANKADDRESSLEFTX', " . $conf->entity . ", '6', 'chaine', 0, ''),
      ('SWISSBANKING_BANKADDRESSLEFTY', " . $conf->entity . ", '8', 'chaine', 0, ''),
      ('SWISSBANKING_BANKADDRESSRIGHTX', " . $conf->entity . ", '62', 'chaine', 0, ''),
      ('SWISSBANKING_BANKADDRESSRIGHTY', " . $conf->entity . ", '8', 'chaine', 0, ''),
      ('SWISSBANKING_COMPANYADDRESSLEFTX', " . $conf->entity . ", '6', 'chaine', 0, ''),
      ('SWISSBANKING_COMPANYADDRESSLEFTY', " . $conf->entity . ", '22', 'chaine', 0, ''),
      ('SWISSBANKING_COMPANYADDRESSRIGHTX', " . $conf->entity . ", '62', 'chaine', 0, ''),
      ('SWISSBANKING_COMPANYADDRESSRIGHTY', " . $conf->entity . ", '22', 'chaine', 0, ''),
      ('SWISSBANKING_BANKACCOUNTLEFTX', " . $conf->entity . ", '28', 'chaine', 0, ''),
      ('SWISSBANKING_BANKACCOUNTLEFTY', " . $conf->entity . ", '42', 'chaine', 0, ''),
      ('SWISSBANKING_BANKACCOUNTRIGHTX', " . $conf->entity . ", '88', 'chaine', 0, ''),
      ('SWISSBANKING_BANKACCOUNTRIGHTY', " . $conf->entity . ", '42', 'chaine', 0, ''),
      ('SWISSBANKING_PVRREFERENCEX', " . $conf->entity . ", '124', 'chaine', 0, ''),
      ('SWISSBANKING_PVRREFERENCEY', " . $conf->entity . ", '34', 'chaine', 0, ''),
      ('SWISSBANKING_PVRREFERENCEFONTSIZE', " . $conf->entity . ", '9.5', 'chaine', 0, ''),
      ('SWISSBANKING_PVRM10Y', " . $conf->entity . ", '85', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXT', " . $conf->entity . ", 'No', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTWIDTH', " . $conf->entity . ", '190', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTHEIGHT', " . $conf->entity . ", '40', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTX', " . $conf->entity . ", '10', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTY', " . $conf->entity . ", '10', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTBORDER', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTALIGN', " . $conf->entity . ", 'L', 'chaine', 0, ''),
      ('SWISSBANKING_ADDTEXTAUTOPADDING', " . $conf->entity . ", 'true', 'chaine', 0, ''),
      ('SWISSBANKING_ADDITIONALTEXT', " . $conf->entity . ", '<p style=\"text-align: center;\">\r\n  <span style=\"color: rgb(255, 255, 255);\"><strong><span style=\"background-color: rgb(255, 0, 0);\">&nbsp;This is just an example Text you can put in your printed A4 QR Bill.</span></strong></span></p>\r\n<p style=\"text-align: justify;\">\r\n  You generally display additional text on A4 paper. This area is provided to generally print out your Terms and Conditions if you need to. If you don&#39;t need this kind of service, just disable this area and your generated QR Bill will be as neutral as needed.<br />\r\n  You can customize this module upon your needs, we tried to let you setup every parameter in order to achieve your needed result.</p>\r\n<p style=\"text-align: justify;\">\r\n  If you print your QR Bill on a DIN A5 or a DIN A6/5 Slip, this text is useless.</p>\r\n', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSCSIZE', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSMX', " . $conf->entity . ", '2.5', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSMY', " . $conf->entity . ", '50.5', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSMD', " . $conf->entity . ", '5', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSCX', " . $conf->entity . ", '48', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSCY', " . $conf->entity . ", '50.5', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTDIGITSCD', " . $conf->entity . ", '5', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSCSIZE', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSMX', " . $conf->entity . ", '63', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSMY', " . $conf->entity . ", '50.5', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSMD', " . $conf->entity . ", '5', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSCX', " . $conf->entity . ", '108.5', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSCY', " . $conf->entity . ", '50.5', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTDIGITSCD', " . $conf->entity . ", '5', 'chaine', 0, ''),
      ('SWISSBANKING_ADDRESSPRINTOUT', " . $conf->entity . ", '9', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTADDRESSCSIZE', " . $conf->entity . ", '-2', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTADDRESSX', " . $conf->entity . ", '6', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTADDRESSY', " . $conf->entity . ", '64', 'chaine', 0, ''),
      ('SWISSBANKING_LEFTADDRESSLD', " . $conf->entity . ", '4.2', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTADDRESSCSIZE', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTADDRESSX', " . $conf->entity . ", '126', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTADDRESSY', " . $conf->entity . ", '48', 'chaine', 0, ''),
      ('SWISSBANKING_RIGHTADDRESSLD', " . $conf->entity . ", '6.5', 'chaine', 0, ''),
      ('SWISSBANKING_PRINTZEROTOTAL', " . $conf->entity . ", 'Yes', 'chaine', 0, ''),
      ('SWISSBANKING_ISRBGPRINT', " . $conf->entity . ", 'No', 'chaine', 0, ''),
      ('SWISSBANKING_ISRBGX', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_ISRBGY', " . $conf->entity . ", '0', 'chaine', 0, ''),
      ('SWISSBANKING_WIPEDATAONDISABLE', " . $conf->entity . ", 'No', 'chaine', 0, ''),
      ('SWISSBANKING_SLIPTYPE', " . $conf->entity . ", 'ISR', 'chaine', 0, ''),
      ('SWISSBANKING_QRSLIP_SIZE', " . $conf->entity . ", '3', 'chaine', 0, ''),
      ('SWISSBANKING_QRSLIP_FONT', " . $conf->entity . ", '2', 'chaine', 0, ''),
      ('SWISSBANKING_VERSION', " . $conf->entity . ", '114', 'chaine', 0, '')",
      "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "swissbanking (invoiceid int(11) NOT NULL DEFAULT '0', isrcode varchar(255) NOT NULL DEFAULT '', amount float NOT NULL DEFAULT '0', lastgenwith int(11) NOT NULL, status varchar(20) NOT NULL DEFAULT '', entity int(11) NOT NULL, PRIMARY KEY (invoiceid)) ENGINE=MyISAM DEFAULT CHARSET=utf8",
      "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "swissbanking_accounts (rowid int(11) NOT NULL AUTO_INCREMENT, dolbankid int(11) NOT NULL DEFAULT '0', label varchar(255) NOT NULL, ccp varchar(255) NOT NULL, qriban varchar(30) NULL DEFAULT NULL, bankid varchar(6) NOT NULL, printdet varchar(255) NOT NULL, isdefault tinyint(1) NOT NULL DEFAULT '0', pvbrtype INT(8) NOT NULL DEFAULT '1', entity int(11) NOT NULL, PRIMARY KEY (rowid)) ENGINE=MyISAM DEFAULT CHARSET=latin1",
      "INSERT INTO " . MAIN_DB_PREFIX . "swissbanking_accounts (dolbankid, label, ccp, bankid, printdet, isdefault, pvbrtype, entity) VALUES (0, '', '', '', '', 0, 1, 1)",
      "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "swissbanking_payments (rowid int(32) NOT NULL AUTO_INCREMENT, file_md5 varchar(32) NOT NULL, imported int(1) NOT NULL DEFAULT '1', filename varchar(255) NOT NULL, importdate int(32) NOT NULL, amount double(24, 2) NOT NULL DEFAULT '0.00', detail text NOT NULL, PRIMARY KEY (rowid)) ENGINE=MyISAM DEFAULT CHARSET=latin1",
    );
    return $this->_init($sql, $options);
  }

function remove($options='') {
    global $conf;
    if($conf->global->SWISSBANKING_WIPEDATAONDISABLE == 'Yes') $sql = array("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'SWISSBANKING_%' AND entity=" . $conf->entity, "DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "swissbanking_accounts", "DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "swissbanking", "DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "swissbanking_payments");
    else $sql = array("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'SWISSBANKING_%' AND entity=" . $conf->entity);
    return $this->_remove($sql, $options);
  }
}