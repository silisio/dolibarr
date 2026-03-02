<?php
/*************************************************************************************
 *                                                                                   *
 * Copyright (C) 2014-2022  Mercury Labs SAGL  <info@mercurylabs.ch>                 *
 * Copyright (C) 2014-2022  Reto Kessler       <reto.kessler@mercurylabs.ch>         *
 *                                                                                   *
 * Licence       : COMMERCIAL                                                        *
 * File          : htdocs/custom/swissbanking/class/actions_swissbanking.class.php   *
 * Date          : 15 Apr 2014 - 25 Aug 2022                                         *
 * Description   : Class File that generates the PDF (Additional or by               *
 *                 concatenating with the Invoice PDF) to be printed on an           *
 *                 empty Orange ISR/PVR/ESR/BVR                                      *
 *                                                                                   *
 *************************************************************************************/
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
use Sprain\SwissQrBill as QrBill;
require_once __DIR__ . '/../includes/SwissQR/vendor/autoload.php';

if (!file_exists(DOL_DOCUMENT_ROOT . '/custom/swissbanking/class/actions_swissbanking.class.php')) {
  echo 'Error ! Your installation does not respect the new Dolibarr Standards.<br />
In order to resolve please ask your Administrator to visit the SwissBanking module main configuration pages for further instructions.';
  die();
}

class ActionsSwissBanking{
  var $db;
  var $error;
  var $errors = array();

  /**
  * Creates Modulo10 recursive check digit
  * found on http://www.developers-guide.net/forums/5431,modulo10-rekursiv, THANK YOU!
  *
  * @param string $number
  * @return int
  */
  private function modulo10($number) {
    $table = array(0, 9, 4, 6, 8, 2, 7, 1, 3, 5);
    $next = 0;
    for ($i = 0; $i < strlen($number); $i++) {
      $next = $table[($next + substr($number, $i, 1)) % 10];
    }
    return (10 - $next) % 10;
  }

  public function __construct($db) {
    global $langs, $conf;
    $langs->loadLangs(array("main", "bills", "other", "swissbanking@swissbanking"));
    $this->db = $db;
  }

  public function formBuilddocOptions($parameters, &$object) {
    global $langs, $user, $conf, $db;
    if ($parameters['modulepart'] == 'invoice' || $parameters['modulepart'] == 'facture') {
      $langs->load("swissbanking@swissbanking");
      $form = new Form($this->db);
      $bankaccounts = array(0 => $langs->trans("DoNotGeneratePVR"));
      $defaultaccount = 0;
      $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE NOT rowid='0' AND entity='" . $conf->entity . "' ORDER BY isdefault DESC, label ASC";
      $resql = $db->query($sql);
      if ($resql) {
        if ($db->num_rows($resql)) {
          $i = 0;
          while ($i < $db->num_rows($resql)) {
            $objp = $db->fetch_object($resql);
            if ($objp->isdefault) $defaultaccount = $objp->rowid;
            $bankaccounts += array($objp->rowid => $objp->label);
            $i++;
          }
        }
      }
      else {
        $this->error = $this->db->lasterror();
        dol_syslog($this->db, $this->error, LOG_ERR);
        return -1;
      }

      $out = '<tr class="liste_titre">';
      $facid = $parameters['id'];
      $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking WHERE invoiceid='" . $facid . "' AND entity='" . $conf->entity . "' LIMIT 1";
      $resql = $db->query($sql);
      if ($resql) {
        if ($db->num_rows($resql)) {
          $objp = $db->fetch_object($resql);
          $preselected = $objp->lastgenwith;
        }
        else $preselected = $defaultaccount;
      }
      else {
        $this->error = $this->db->lasterror();
        dol_syslog($this->db, $this->error, LOG_ERR);
        return -1;
      }
      $out .= '<th align="right" class="formdoc liste_titre" colspan="5">';
      $out .= $langs->trans("BankAccountSelect").' ';
      $out .= $form->selectarray('pvrbankaccount', $bankaccounts, $preselected);
    }
    $out .= '</th></tr>';
    $this->resprints = $out;
    return 0;
  }

  public function afterPDFCreation($parameters, &$object, &$action) {
    global $langs, $conf, $db, $user, $mysoc;
    global $hookmanager;

    // Loads customer specific languages is available
    if (!is_object($parameters['outputlangs'])) $outputlangs = $langs;
    else $outputlangs = $parameters['outputlangs'];
    $langs->loadLangs(array("main", "bills", "products", "dict", "companies", "other", "swissbanking@swissbanking"));
    $outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "other", "swissbanking@swissbanking"));

    $element = '';
    if ($parameters['object']->element == 'invoice' || $parameters['object']->element == 'facture') {
      require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
      $element = 'facture';
      $invoiceid = $parameters['object']->id;
      $reference = $parameters['object']->ref;
      $filereference = dol_sanitizeFileName($reference);
      $customercode = $parameters['object']->client->code_client;

      if ($conf->global->SWISSBANKING_VERSION < "116") {
        echo 'Error : your SwissBanking module settings need to be updated. Please ask your Dolibarr Administrator to visit the SwissBanking module administration page.<br />The update procedure will be fully automatic.';
        die();
      }
      else {
        if ($parameters['object']->modelpdf == "A4PVR" || $parameters['object']->modelpdf == "Swissbanking1Page") {
          // Deletes old generated Payment Slip if exists
          $outputdirectory = $conf->$element->dir_output . '/' . $filereference . '/';
          $myvarrr = "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf, " . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}";
          $pdffiles = glob($outputdirectory . "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf," . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}", GLOB_BRACE);
          if (is_array($pdffiles) && count($pdffiles) > 0) {
            foreach($pdffiles as $pdffile) unlink($pdffile);
          }
        }
        else {
          if (!GETPOSTISSET('pvrbankaccount')) {
            $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE isdefault='1' AND entity='" . $conf->entity . "' LIMIT 1";
            $resql = $db->query($sql);
            if ($resql) {
              if ($db->num_rows($resql)) {
                $objp = $db->fetch_object($resql);
                $pvrbankaccount = $objp->rowid;
              }
              else $pvrbankaccount = '0';
            }
            else {
              $this->error = $this->db->lasterror();
              dol_syslog($this->db, $this->error, LOG_ERR);
              return -1;
            }
          }
          else {
            $check = 'alpha';
            $pvrbankaccount = GETPOST('pvrbankaccount', $check);
          }

          if ($pvrbankaccount == '0') {
            // Deletes old generated Payment Slip if exists
            $outputdirectory = $conf->$element->dir_output . '/' . $filereference . '/';
            $myvarrr = "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf," . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}";
            $pdffiles = glob($outputdirectory . "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf," . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}", GLOB_BRACE);
            if (is_array($pdffiles) && count($pdffiles) > 0) {
              foreach($pdffiles as $pdffile) unlink($pdffile);
            }
          }
          else {
            $default_font_size = pdf_getPDFFontSize($outputlangs);
            dol_syslog(get_class($this) . '::executeHooks action=' . $action);

            $company = $parameters['object']->thirdparty->name;
            $address = $parameters['object']->thirdparty->address;
            $zip = $parameters['object']->thirdparty->zip;
            $town = $parameters['object']->thirdparty->town;
            $region = $parameters['object']->thirdparty->state;
            $regioncode = $parameters['object']->thirdparty->state_code;
            $country = $parameters['object']->thirdparty->country;
            $countrycode = $parameters['object']->thirdparty->country_code;

            if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $invoicetotal = $parameters['object']->multicurrency_total_ttc;
            else $invoicetotal = $parameters['object']->total_ttc;

            // Loop on each deposits and credit notes included
            $sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva, re.amount_ttc, re.multicurrency_amount_ttc,";
            $sql .= " re.description, re.fk_facture_source,";
            $sql .= " f.type, f.datef";
            $sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re, " . MAIN_DB_PREFIX . "facture as f";
            $sql .= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = " . $invoiceid;
            $resql = $this->db->query($sql);
            if ($resql) {
              $num = $this->db->num_rows($resql);
              $i = 0;
              $invoice = new Facture($this->db);
              while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $invoice->fetch($obj->fk_facture_source);
                $invoicetotal = $invoicetotal - (($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $obj->multicurrency_amount_ttc : $obj->amount_ttc);
                $i++;
              }
            }
            else {
              $this->error = $this->db->lasterror();
              dol_syslog($this->db, $this->error, LOG_ERR);
              return -1;
            }

            // Loop on each payment
            $sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
            $sql .= " cp.code";
            $sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "paiement as p";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON p.fk_paiement = cp.id AND cp.entity IN (" . getEntity('c_paiement') . ")";
            $sql .= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = " . $invoiceid;
            $sql .= " ORDER BY p.datep";
            $resql = $this->db->query($sql);
            if ($resql) {
              $num = $this->db->num_rows($resql);
              $i = 0;
              while ($i < $num) {
                $row = $this->db->fetch_object($resql);
                if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) {
                  if(isset($row->multicurrency_amount) && $row->multicurrency_amount != 0) $invoicetotal = $invoicetotal - $row->multicurrency_amount;
                  else $invoicetotal = $invoicetotal - $row->amount;
                }
                else $invoicetotal = $invoicetotal - $row->amount;
                $i++;
              }
            }
            else {
              $this->error = $this->db->lasterror();
              dol_syslog($this->db, $this->error, LOG_ERR);
              return -1;
            }

            /*if (isset($parameters['object']->multicurrency_total_ttc)) $totalprint = (string)number_format($parameters['object']->multicurrency_total_ttc, 2, '.', '');
            else $totalprint = (string)number_format($invoicetotal, 2, '.', '');*/
            $totalprint = (string)number_format($invoicetotal, 2, '.', '');

            if ($conf->global->SWISSBANKING_GENERATIONMETHOD == 'Append' && ($conf->global->SWISSBANKING_SLIPTYPE == 'ISR' || ($conf->global->SWISSBANKING_SLIPTYPE == 'QR' && $totalprint > '0.00'))) {
              $outpdf = $parameters['file'];

              if ($outpdf != '') {
                $pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                if (class_exists('TCPDF')) {
                  $pdf->setPrintHeader(false);
                  $pdf->setPrintFooter(false);
                }
                $pagecount = $pdf->setSourceFile($outpdf);
                for ($i = 1; $i <= $pagecount; $i++) {
                  $tplidx = $pdf->ImportPage($i);
                  $s = $pdf->getTemplateSize($tplidx);
                  $pdf->AddPage('P', array($s['w'], $s['h']));
                  $pdf->useTemplate($tplidx);
                }
              }
            }
            elseif ($conf->global->SWISSBANKING_SLIPTYPE == 'ISR' || ($conf->global->SWISSBANKING_SLIPTYPE == 'QR' && $totalprint > '0.00')) {
              $outpdf = $conf->$element->dir_output . '/' . $filereference . '/' . $conf->global->SWISSBANKING_FILENAMEPREFIX . $filereference . $conf->global->SWISSBANKING_FILENAMESUFFIX . '.pdf';
              $pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
              if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
              }
              // Deletes old generated PVR
              if (file_exists($pdffile)) unlink($pdffile);
            }

            if ($conf->global->SWISSBANKING_GENERATIONMETHOD == 'New' && ($conf->global->SWISSBANKING_SLIPTYPE == 'ISR' || ($conf->global->SWISSBANKING_SLIPTYPE == 'QR' && $totalprint > '0.00'))) {
              $pdf->SetAuthor($user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')');
              $pdf->SetSubject('Invoice # ' . $reference);
              if ($conf->global->SWISSBANKING_SLIPTYPE == 'QR') $pdf->SetCreator('Mercury Labs SAGL Invoice QR PDF Generator');
              else $pdf->SetCreator('Mercury Labs SAGL Invoice PVR PDF Generator');
              if ($conf->global->SWISSBANKING_SLIPTYPE == 'QR') $pdf->SetTitle('QR for Invoice # ' . $reference);
              else $pdf->SetTitle('PVR for Invoice # ' . $reference);
            }

            if ($conf->global->SWISSBANKING_SLIPTYPE == 'ISR' || ($conf->global->SWISSBANKING_SLIPTYPE == 'QR' && $totalprint > '0.00')) {
              $pdf->SetDisplayMode("real", "continuous");
              $pdf->SetMargins(0, 0, 0);
              $pdf->SetHeaderMargin(0);
              $pdf->SetFooterMargin(0);
              $pdf->SetAutoPageBreak(false, 0);

              $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE rowid = '" . $pvrbankaccount . "' LIMIT 1";
              $resql = $this->db->query($sql);
              if ($resql) {
                if ($this->db->num_rows($resql)) {
                  $objp = $db->fetch_object($resql);
                  $dolbankid = $objp->dolbankid;
                  $ccp = $objp->ccp;
                  $qriban = $objp->qriban;
                  $bankid = $objp->bankid;
                  $printdetail = $objp->printdet;
                }
              }

              $account = new Account($db);
              $result = $account->fetch($dolbankid);
            }

            if ($conf->global->SWISSBANKING_SLIPTYPE == 'QR') { // Generation of the new Swiss QR Bill
              if ($totalprint > '0.00') {
                $qrBill = QrBill\QrBill::create();
                // Who will receive the payment and to which bank account?
                // Create Company Address in 2 Lines
                $companyaddress = explode(PHP_EOL, $account->owner_address);
                if (count($companyaddress) == 1) {
                  $qraddress = ' ';
                  $qrcity = $companyaddress[0];
                }
                elseif (count($companyaddress) == 2) {
                  $qraddress = $companyaddress[0];
                  $qrcity = $companyaddress[1];
                }
                elseif (count($companyaddress) > 2) {
                  $companyaddresscount = count($companyaddress);
                  $qraddress = '';
                  $companyaddresscounter = 0;
                  while ($companyaddresscounter < $companyaddresscount - 1) {
                    $qraddress .= $companyaddress[$companyaddresscounter] . ' / ';
                    $companyaddresscounter++;
                  }
                  $qraddress = substr($qraddress, 0, -3);
                  $qrcity = $companyaddress[$companyaddresscount - 1];
                }

                // Add creditor information (fallback to company values if bank account owner fields are empty)
                $creditorName = trim((string) $account->proprio);
                if ($creditorName === '' && !empty($mysoc->name)) $creditorName = trim((string) $mysoc->name);
                $creditorAddress = trim((string) $qraddress);
                if ($creditorAddress === '' && !empty($mysoc->address)) $creditorAddress = trim((string) $mysoc->address);
                $creditorCity = trim((string) $qrcity);
                if ($creditorCity === '' && (!empty($mysoc->zip) || !empty($mysoc->town))) $creditorCity = trim((string) ($mysoc->zip . ' ' . $mysoc->town));
                $creditorCountry = trim((string) $account->country_code);
                if ($creditorCountry === '' && !empty($mysoc->country_code)) $creditorCountry = trim((string) $mysoc->country_code);

                $qrBill->setCreditor(
                  QrBill\DataGroup\Element\CombinedAddress::create(
                    $creditorName,
                    $creditorAddress,
                    $creditorCity,
                    $creditorCountry
                  )
                );

                $qriban = str_replace(' ', '', $qriban);
                $qrBill->setCreditorInformation(
                  QrBill\DataGroup\Element\CreditorInformation::create(
                    $qriban
                  )
                );

                // Truncate Address at 70 characters or QR Class will throw an error
                if (strlen($address) > 70) {
                  setEventMessages($outputlangs->transnoentities("SwissBankingQRAddressTruncated"), [], 'warnings');
                  $debtqraddress = substr($address, 0, 70);
                }
                else $debtqraddress = $address;
                // Add debtor information (optional). Avoid sending blank values to validator.
                $debtorName = trim((string) $company);
                $debtorStreet = trim((string) $debtqraddress);
                $debtorCity = trim((string) ($zip . ' ' . $town));
                $debtorCountry = trim((string) $countrycode);
                if ($debtorName !== '' && $debtorStreet !== '' && $debtorCity !== '' && $debtorCountry !== '') {
                  $qrBill->setUltimateDebtor(
                    QrBill\DataGroup\Element\CombinedAddress::create(
                      $debtorName,
                      $debtorStreet,
                      $debtorCity,
                      $debtorCountry
                    )
                  );
                }

                // Add payment amount information
                // What amount is to be paid?
                if (isset($parameters['object']->multicurrency_code)) $qrcurrency = $parameters['object']->multicurrency_code;
                else $qrcurrency = 'CHF';
                $qrBill->setPaymentAmountInformation(
                  QrBill\DataGroup\Element\PaymentAmountInformation::create(
                    $qrcurrency,
                    $totalprint
                    )
                );

                // Add payment reference
                // This is what you will need to identify incoming payments.
                $QRBankID = preg_replace('/[^0-9]*/', '', $bankid);
                $QRRefNum = str_pad(preg_replace('/[^0-9]*/', '', substr($customercode, -5)) . substr($invoiceid, -5) . preg_replace('/[^0-9]*/', '', substr($reference, -10)), 20 ,'0', STR_PAD_LEFT);

                $referencenumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
                  $QRBankID,
                  $QRRefNum
                );

                $qrBill->setPaymentReference(
                  QrBill\DataGroup\Element\PaymentReference::create(
                    QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
                      $referencenumber
                  )
                );

                // Optionally, add some human-readable information about what the bill is for.
                if (isset($conf->global->SWISSBANKING_PRINTQRREF) && $conf->global->SWISSBANKING_PRINTQRREF == "Yes") {
                  $printqradditionalinformation = $outputlangs->transnoentities("QRInvoiceNum").' '.$parameters['object']->ref.' '.$outputlangs->transnoentities("QRInvoiceDate").' '.date('d.m.Y',$parameters['object']->date);
                  if (strlen($parameters['object']->ref_client) > 0) {
                    $printqradditionalinformation .= '
'.$outputlangs->convToOutputCharset($parameters['object']->ref_client);
                  }
                  $qrBill->setAdditionalInformation(
                    QrBill\DataGroup\Element\AdditionalInformation::create(
                      $printqradditionalinformation
                    )
                  );
                }

                // Generate the QR code image in PNG format
                try {
                  $outputdirectory = $conf->$element->dir_output . '/' . $filereference . '/';
                  $qrBill->getQrCode()->writeFile($conf->$element->dir_output . '/' . $filereference . '/qr.png');
                }
                catch (Exception $e) {
                  $errorMessage = $e->getMessage();
                  foreach ($qrBill->getViolations() as $violation) {
                    $errorMessage .= ' ' . $violation->getMessage();
                  }
                  dol_syslog('SwissBanking QR generation failed: '.$errorMessage, LOG_ERR);
                  setEventMessages($outputlangs->transnoentities("Error") . ' SwissBanking QR: ' . $errorMessage, array(), 'errors');
                  return 0;
                }

                if ($conf->global->SWISSBANKING_QRSLIP_FONT == "1") { $qrfont = 'arial'; $qrfontb = 'arialb'; }
                elseif ($conf->global->SWISSBANKING_QRSLIP_FONT == "2") { $qrfont = 'frutiger'; $qrfontb = 'frutigerb'; }
                $pdf->AddFont($qrfont);
                $pdf->AddFont($qrfontb);
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                if ($conf->global->SWISSBANKING_QRSLIP_SIZE == "1") { // DIN A4
                  $qrpagex = 210;
                  $qrpagey = 297;
                  $qrpageo = 'P';
                  dol_syslog('SwissBanking : QR-Bill page format is DIN A4.', LOG_INFO);
                }
                elseif ($conf->global->SWISSBANKING_QRSLIP_SIZE == "2") { // DIN A5
                  $qrpagex = 210;
                  $qrpagey = 148;
                  $qrpageo = 'L';
                  dol_syslog('SwissBanking : QR-Bill page format is DIN A5.', LOG_INFO);
                }
                elseif ($conf->global->SWISSBANKING_QRSLIP_SIZE == "3") { // DIN A6/5
                  $qrpagex = 210;
                  $qrpagey = 105;
                  $qrpageo = 'L';
                  dol_syslog('SwissBanking : QR-Bill page format is DIN A6/5.', LOG_INFO);
                }
                $resolution = array($qrpagex, $qrpagey);
                $pdf->AddPage($qrpageo, $resolution);

                // Additional prints ONLY if paper size is A4
                if ($conf->global->SWISSBANKING_QRSLIP_SIZE == "1") {
                  // Print original invoice Header/Address
                  if ($conf->global->SWISSBANKING_PRINT_A4QR_HEADER == 'Yes') {
                    dol_syslog('SwissBanking : Adding Header to DIN A4 QR Bill.', LOG_INFO);
                    $modelpdf = 'pdf_' . $parameters['object']->modelpdf;
                    $invoicePdfHeader = new ReflectionMethod($modelpdf, '_pagehead');
                    $invoicePdfHeader->setAccessible(true);

                    if ($conf->global->SWISSBANKING_PRINT_A4QR_ADDRESS == 'Yes') {
                      $invoicePdfHeader->invokeArgs(new $modelpdf($db), array(&$pdf, $parameters['object'], true, $outputlangs, null));
                      dol_syslog('SwissBanking : Adding Address to DIN A4 QR Bill.', LOG_INFO);
                    }
                    else {
                      $invoicePdfHeader->invokeArgs(new $modelpdf($db), array(&$pdf, $parameters['object'], false, $outputlangs, null));
                      dol_syslog('SwissBanking : NOT Adding Address to DIN A4 QR Bill.', LOG_INFO);
                    }
                  }

                  // Print Invoice Custom Additional Text
                  if ($conf->global->SWISSBANKING_ADDTEXT == 'Yes') {
                    $addtexttcellh = floatval($conf->global->SWISSBANKING_ADDTEXTY);
                    $addtextmcellh = floatval($conf->global->SWISSBANKING_ADDTEXTHEIGHT);
                    $addtextlcellw = floatval($conf->global->SWISSBANKING_ADDTEXTX);
                    $addtextccellw = floatval($conf->global->SWISSBANKING_ADDTEXTWIDTH);
                    $addtextrcellw = floatval($conf->global->SWISSBANKING_PAGEFORMATX) - $addtextlcellw - $addtextccellw;
                    if ($conf->global->SWISSBANKING_ADDTEXTALIGN == 'C') $addtextalign = 'center';
                    elseif ($conf->global->SWISSBANKING_ADDTEXTALIGN == 'R') $addtextalign = 'right';
                    else $addtextalign = 'left';
                    if ($conf->global->SWISSBANKING_ADDTEXTBORDER != '0') $addtextborder = '1px solid #000000';
                    else $addtextborder = '0px solid #FFFFFF';
                    if ($conf->global->SWISSBANKING_ADDTEXTAUTOPADDING) $addtextautopadding = '3';
                    else $addtextautopadding = '0';

                    $addtexttext = $conf->global->SWISSBANKING_ADDITIONALTEXT;
                    $imgtagpos = strpos($addtexttext, '<img ');
                    if ($imgtagpos !== False) {
                      $srcvalpos = strpos($addtexttext, 'src="', $imgtagpos);
                      $phpvalpos = strpos($addtexttext, '/viewimage.php?modulepart=medias&amp;entity=1&amp;file=', $srcvalpos);
                      $dolibarrindex = substr($addtexttext, $srcvalpos + 5, $phpvalpos - ($srcvalpos + 5));
                    }
                    else $dolibarrindex = '';

                    $addtext = '<table width="' . $conf->global->SWISSBANKING_PAGEFORMATX . ' mm" border="0" cellpadding="0" cellspacing="0">';
                    $addtext .= '<tr><td colspan="3" style="height:' . strval($addtexttcellh) . ' mm !important; min-height:' . strval($addtexttcellh) . ' mm !important;"></td></tr>';
                    $addtext .= '<tr><td style="width:' . strval($addtextlcellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important;"></td><td style="width:' . strval($addtextccellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important; text-align:' . $addtextalign . '; border: ' . $addtextborder . '; padding=' . $addtextautopadding . ' mm;">' . str_replace($dolibarrindex . '/viewimage.php?modulepart=medias&amp;entity=1&amp;file=', DOL_DATA_ROOT . '/medias/', $addtexttext) . '</td><td style="width:' . strval($addtextrcellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important;"></td></tr></table>';
                    $pdf->writeHTML($addtext, true, 0, false, false, '');
                    dol_syslog('SwissBanking : Adding Custom Additional Text to DIN A4 QR Bill.', LOG_INFO);
                  }
                }

                //Place Cursor at beginning
                $pdf->SetFont($qrfont, '', $default_font_size);
                $xpos = 0;
                $ypos = $qrpagey - 105;

                /************************/
                /* Draw Common Graphics */
                /************************/
                // Draw Cutting Line if not DIN A6/5
                if ($conf->global->SWISSBANKING_QRSLIP_SIZE != "3") {
                  $linestyle = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
                  $pdf->Line($xpos, $ypos, 210, $ypos, $linestyle);
                }

                // Draw Vertical Separation Line
                $pdf->Line($xpos + 62, $ypos, $xpos + 62, $ypos + 105, $linestyle);

                /************************/
                /* Left Side Data Print */
                /************************/
                // Write Text "Receipt" in Arial Bold/Frutiger 11pt
                $xpos += 5;
                $ypos += 5;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '11pt');
                $pdf->MultiCell(52, 7, $outputlangs->transnoentities("QRReceipt"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);

                // Write Text "Account / Payable to" in Arial Bold/Frutiger 6pt
                $ypos += 7;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '6pt');
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRAccountPayableto"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);

                // Write Text Company Data
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '8pt');
                $pdf->MultiCell(52, 3.06, trim(chunk_split($qriban, 4, ' ')), 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, $account->proprio, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, $qraddress, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, $qrcity, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 6.12; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '6pt');
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRReference"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '8pt');
                $pdf->MultiCell(52, 3.06, strrev(trim(chunk_split(strrev($referencenumber), 5, ' '))), 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 6.12; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '6pt');
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRPayableby"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
                $ypos += 3.06; // Line Spacing 9pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '8pt');
                $pdf->MultiCell(52, 3.06, $company, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos += 3.06; // Line Spacing 9pt
                $address = explode(PHP_EOL, $address);
                for($addresslines = 0; $addresslines < count($address); $addresslines++) {
                  $pdf->SetXY($xpos - 1, $ypos);
                  $pdf->MultiCell(52, 3.06, $address[$addresslines], 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                  $ypos += 3.06; // Line Spacing 9pt
                }
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, $zip.' '.$town, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $ypos = $qrpagey - 105 + 5 + 7 + 56; // Move on Amount Section
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '6pt');
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRCurrency"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);
                $xpos += 13;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRAmount"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);
                $xpos -= 13;
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '8pt');
                $pdf->MultiCell(52, 3.06, $qrcurrency, 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $xpos += 13;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(52, 3.06, number_format($totalprint, 2, '.', ' '), 0, 'L', false, 0, '', '', true, 0, false, true, 3.53, 'T', false);
                $xpos -= 13;
                $ypos = $qrpagey - 105 + 5 + 7 + 56 + 14; // Move on Acceptance Point Section
                $pdf->SetXY($xpos + 1, $ypos);
                $pdf->SetFont($qrfontb, '', '6pt');
                $pdf->MultiCell(52, 3.06, $outputlangs->transnoentities("QRAcceptancepoint"), 0, 'R', false, 0, '', '', true, 0, false, true, 0, 'T', false);

                /*************************/
                /* Right Side Data Print */
                /*************************/
                $xpos = 0 + 5 + 52 + 5 + 5;
                $ypos = $qrpagey - 105 + 5;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '11pt');
                $pdf->MultiCell(51, 7, $outputlangs->transnoentities("QRPaymentpart"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);

                // Draw Swiss QR Code Image
                $pdf->Image($conf->$element->dir_output . '/' . $filereference . '/qr.png', $xpos, $ypos + 7 + 5, 46, 46, 'PNG', '', '', true, 300, '', false, false, 0, false, false, false);

                $ypos = $qrpagey - 105 + 5 + 7 + 5 + 46 + 5; // Move on Amount Section
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '8pt');
                $pdf->MultiCell(51, 3.9, $outputlangs->transnoentities("QRCurrency"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);
                $xpos += 16;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(51, 3.9, $outputlangs->transnoentities("QRAmount"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);
                $xpos -= 16;
                $ypos += 4.2; // Line Spacing 13pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '10pt');
                $pdf->MultiCell(51, 4.2, $qrcurrency, 0, 'L', false, 0, '', '', true, 0, false, true, 4.67, 'T', false);
                $xpos += 16;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(51, 4.2, number_format($totalprint, 2, '.', ' '), 0, 'L', false, 0, '', '', true, 0, false, true, 4.67, 'T', false);

                // Write Text "Account / Payable to" in Arial Bold/Frutiger 6pt
                $xpos = 0 + 5 + 52 + 5 + 5 + 51;
                $ypos = $qrpagey - 105 + 5;
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '8pt');
                $pdf->MultiCell(82, 3.9, $outputlangs->transnoentities("QRAccountPayableto"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);

                // Write Text Company Data
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '10pt');
                $pdf->MultiCell(82, 3.9, trim(chunk_split($qriban, 4, ' ')), 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(82, 3.9, $account->proprio, 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(82, 3.9, $qraddress, 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(82, 3.9, $qrcity, 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                $ypos += 7.8; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '8pt');
                $pdf->MultiCell(82, 3.9, $outputlangs->transnoentities("QRReference"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '10pt');
                $pdf->MultiCell(82, 3.9, strrev(trim(chunk_split(strrev($referencenumber), 5, ' '))), 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);

                if (isset($conf->global->SWISSBANKING_PRINTQRREF) && $conf->global->SWISSBANKING_PRINTQRREF == "Yes") {
                  $ypos += 7.8; // Line Spacing 11pt
                  $pdf->SetXY($xpos - 1, $ypos);
                  $pdf->SetFont($qrfontb, '', '8pt');
                  $pdf->MultiCell(82, 3.9, $outputlangs->transnoentities("QRAdditionalinformation"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
                  $ypos += 3.9; // Line Spacing 11pt
                  $pdf->SetXY($xpos - 1, $ypos);
                  $pdf->SetFont($qrfont, '', '10pt');
                  $pdf->MultiCell(82, 3.9, $outputlangs->transnoentities("QRInvoiceNum").' '.$parameters['object']->ref.' '.$outputlangs->transnoentities("QRInvoiceDate").' '.date('d.m.Y',$parameters['object']->date), 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);

                  // Add custom invoice reference if exists
                  if (strlen($parameters['object']->ref_client) > 0) {
                    $ypos += 3.9; // Line Spacing 11pt
                    $pdf->SetXY($xpos - 1, $ypos);
                    $pdf->SetFont($qrfont, '', '10pt');
                    $pdf->MultiCell(82, 3.9, $outputlangs->convToOutputCharset($parameters['object']->ref_client), 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                  }
                }

                $ypos += 7.8; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfontb, '', '8pt');
                $pdf->MultiCell(82, 3.9, $outputlangs->transnoentities("QRPayableby"), 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
                $ypos += 3.9; // Line Spacing 11pt
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->SetFont($qrfont, '', '10pt');
                $pdf->MultiCell(82, 3.9, $company, 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                $ypos += 3.9; // Line Spacing 11pt
                for($addresslines = 0; $addresslines < count($address); $addresslines++) {
                  $pdf->SetXY($xpos - 1, $ypos);
                  $pdf->MultiCell(82, 3.9, $address[$addresslines], 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);
                  $ypos += 3.9; // Line Spacing 11pt
                }
                $pdf->SetXY($xpos - 1, $ypos);
                $pdf->MultiCell(82, 3.9, $zip . ' ' . $town, 0, 'L', false, 0, '', '', true, 0, false, true, 4.50, 'T', false);

                // Delete generated QR Image
                if (file_exists($conf->$element->dir_output . '/' . $filereference . '/qr.png')) unlink($conf->$element->dir_output . '/' . $filereference . '/qr.png');
              }
            }
            else { // Generation of the classic ISR Payment Slip
              if ($printdetail == 'IBAN') $printacc = trim(chunk_split(str_replace(" ", "", str_replace(".", "", str_replace(",", "", $account->iban))), 4, ' '));
              else $printacc = $account->number;
              $bankaddress = '';
              if (stripos($account->bank,'post') === False) {
                $bankaddress = $account->bank . '
' . $account->domiciliation;
                $companyaddress = $printacc . '
' . $account->proprio . '
' . $account->owner_address;
              }
              else $companyaddress = $account->proprio . '
' . $account->owner_address;

              $leftdigits = 8;
              $rightdigits = 8;

              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $default_font_size);

              $resolution = array($conf->global->SWISSBANKING_PAGEFORMATX, $conf->global->SWISSBANKING_PAGEFORMATY);
              $pdf->AddPage($conf->global->SWISSBANKING_PAGEORIENTATION, $resolution);

              // Set page Background
              if (isset($conf->global->SWISSBANKING_ISRBGPRINT) && $conf->global->SWISSBANKING_ISRBGPRINT == 'Yes') {
                $bvrbackgroundheight = $conf->global->SWISSBANKING_PAGEFORMATX / 1600 * 807;
                $pdf->Image(DOL_DOCUMENT_ROOT . '/custom/swissbanking/img/ISR_Background.png', $conf->global->SWISSBANKING_ISRBGX, $conf->global->SWISSBANKING_ISRBGY, $conf->global->SWISSBANKING_PAGEFORMATX, $bvrbackgroundheight, 'PNG', '', '', true, 300, '', false, false, 0, false, false, true);
              }

              $refsize = $default_font_size + (int)$conf->global->SWISSBANKING_INVREFCSIZE;
              $leftdsize = $default_font_size + (int)$conf->global->SWISSBANKING_LEFTDIGITSCSIZE;
              $rightdsize = $default_font_size + (int)$conf->global->SWISSBANKING_RIGHTDIGITSCSIZE;
              $leftasize = $default_font_size + (int)$conf->global->SWISSBANKING_LEFTADDRESSCSIZE;
              $rightasize = $default_font_size + (int)$conf->global->SWISSBANKING_RIGHTADDRESSCSIZE;

              // Invoice Additional Text
              if ($conf->global->SWISSBANKING_ADDTEXT == 'Yes') {
                $addtexttcellh = floatval($conf->global->SWISSBANKING_ADDTEXTY);
                $addtextmcellh = floatval($conf->global->SWISSBANKING_ADDTEXTHEIGHT);
                $addtextlcellw = floatval($conf->global->SWISSBANKING_ADDTEXTX);
                $addtextccellw = floatval($conf->global->SWISSBANKING_ADDTEXTWIDTH);
                $addtextrcellw = floatval($conf->global->SWISSBANKING_PAGEFORMATX) - $addtextlcellw - $addtextccellw;
                if ($conf->global->SWISSBANKING_ADDTEXTALIGN == 'C') $addtextalign = 'center';
                elseif ($conf->global->SWISSBANKING_ADDTEXTALIGN == 'R') $addtextalign = 'right';
                else $addtextalign = 'left';
                if ($conf->global->SWISSBANKING_ADDTEXTBORDER != '0') $addtextborder = '1px solid #000000';
                else $addtextborder = '0px solid #FFFFFF';
                if ($conf->global->SWISSBANKING_ADDTEXTAUTOPADDING) $addtextautopadding = '3';
                else $addtextautopadding = '0';

                $addtexttext = $conf->global->SWISSBANKING_ADDITIONALTEXT;
                $imgtagpos = strpos($addtexttext, '<img ');
                if ($imgtagpos !== False) {
                  $srcvalpos = strpos($addtexttext, 'src="', $imgtagpos);
                  $phpvalpos = strpos($addtexttext, '/viewimage.php?modulepart=medias&amp;entity=1&amp;file=', $srcvalpos);
                  $dolibarrindex = substr($addtexttext, $srcvalpos + 5, $phpvalpos - ($srcvalpos + 5));
                }
                else $dolibarrindex = '';

                $addtext = '<table width="' . $conf->global->SWISSBANKING_PAGEFORMATX . ' mm" border="0" cellpadding="0" cellspacing="0">';
                $addtext .= '<tr><td colspan="3" style="height:' . strval($addtexttcellh) . ' mm !important; min-height:' . strval($addtexttcellh) . ' mm !important;"></td></tr>';
                $addtext .= '<tr><td style="width:' . strval($addtextlcellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important;"></td><td style="width:' . strval($addtextccellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important; text-align:' . $addtextalign . '; border: ' . $addtextborder . '; padding=' . $addtextautopadding . ' mm;">' . str_replace($dolibarrindex . '/viewimage.php?modulepart=medias&amp;entity=1&amp;file=', DOL_DATA_ROOT . '/medias/', $addtexttext) . '</td><td style="width:' . strval($addtextrcellw) . ' mm; height: ' . strval($addtextmcellh) . ' mm !important;"></td></tr></table>';
                $pdf->writeHTML($addtext, true, 0, false, false, '');
              }

              // Prints Out FULL PVR
              // Add the needed OCRB Font
              $pdf->AddFont('OCRB10');

              // Left Part Bank Address
              $xpos = $conf->global->SWISSBANKING_BANKADDRESSLEFTX;
              $ypos = $conf->global->SWISSBANKING_BANKADDRESSLEFTY;
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $default_font_size - 2);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $bankaddress, 0, 'L');

              // Right Part Bank Address
              $xpos = $conf->global->SWISSBANKING_BANKADDRESSRIGHTX;
              $ypos = $conf->global->SWISSBANKING_BANKADDRESSRIGHTY;
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $default_font_size - 2);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $bankaddress, 0, 'L');

              // Left Part Company Address
              if (stripos($account->bank, 'post') === False) {
                $xpos = $conf->global->SWISSBANKING_COMPANYADDRESSLEFTX;
                $ypos = $conf->global->SWISSBANKING_COMPANYADDRESSLEFTY;
              }
              else {
                $xpos = $conf->global->SWISSBANKING_BANKADDRESSLEFTX;
                $ypos = $conf->global->SWISSBANKING_BANKADDRESSLEFTY;
              }
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $default_font_size - 2);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $companyaddress, 0, 'L');

              // Right Part Company Address
              if (stripos($account->bank, 'post') === False) {
                $xpos = $conf->global->SWISSBANKING_COMPANYADDRESSRIGHTX;
                $ypos = $conf->global->SWISSBANKING_COMPANYADDRESSRIGHTY;
              }
              else {
              $xpos = $conf->global->SWISSBANKING_BANKADDRESSRIGHTX;
                $ypos = $conf->global->SWISSBANKING_BANKADDRESSRIGHTY;
              }
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $default_font_size - 2);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $companyaddress, 0, 'L');

              // Left Part Account Number
              $xpos = $conf->global->SWISSBANKING_BANKACCOUNTLEFTX;
              $ypos = $conf->global->SWISSBANKING_BANKACCOUNTLEFTY;
              $pdf->SetFont('OCRB10', '', 10);
              $pdf->SetXY($xpos, $ypos);
              $pdf->Cell(0, 0, $ccp, 0, 'L');

              // Right Part Account Number
              $xpos = $conf->global->SWISSBANKING_BANKACCOUNTRIGHTX;
              $ypos = $conf->global->SWISSBANKING_BANKACCOUNTRIGHTY;
              $pdf->SetFont('OCRB10', '', 10);
              $pdf->SetXY($xpos, $ypos);
              $pdf->Cell(0, 0, $ccp, 0, 'L');

              // Calculate Upper Reference Number
              $referencenumber = str_pad(preg_replace('/[^0-9]*/', '', substr($customercode, -5)) . substr($invoiceid, -5) . preg_replace('/[^0-9]*/', '', substr($reference, -10)), 20 ,'0', STR_PAD_LEFT);
              $referencenumber = preg_replace('/[^0-9]*/', '', $bankid) . $referencenumber;
              $referencenumber .= $this->modulo10($referencenumber);
              $upperreferencenumber = strrev(trim(chunk_split(strrev($referencenumber), 5, ' ')));

              //Print Upper Reference Number
              $xpos = $conf->global->SWISSBANKING_PVRREFERENCEX;
              $ypos = $conf->global->SWISSBANKING_PVRREFERENCEY;
              $pdf->SetFont('OCRB10', '', 10);
              $pdf->SetXY($xpos, $ypos);
              $pdf->Cell(0, 0, $upperreferencenumber, 0, 'L');

              $xpos = $conf->global->SWISSBANKING_LEFTADDRESSX;
              $ypos = $conf->global->SWISSBANKING_LEFTADDRESSY - 3;
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', 6);
              $pdf->SetXY($xpos, $ypos);
              $pdf->Cell(0, 0, $upperreferencenumber, 0, 'L');

              //create bottom line string
              if ($totalprint <= 0 && $conf->global->SWISSBANKING_PRINTZEROTOTAL != 'Yes') $lowerreferencenumber = "042>";
              else {
                $totalparts = explode(".", $totalprint);
                $lowerreferencenumber = "01";
                $lowerreferencenumber .= str_pad($totalparts[0], 8 ,'0', STR_PAD_LEFT);
                $lowerreferencenumber .= str_pad($totalparts[1], 2 ,'0', STR_PAD_RIGHT);
                $lowerreferencenumber .= $this->modulo10($lowerreferencenumber);
                $lowerreferencenumber .= ">";
              }

              $lowerreferencenumber .= $referencenumber."+ ";
              $ccpparts = explode("-", $ccp);
              $lowerreferencenumber .= str_pad($ccpparts[0], 2 ,'0', STR_PAD_LEFT);
              $lowerreferencenumber .= str_pad($ccpparts[1], 6 ,'0', STR_PAD_LEFT);
              $lowerreferencenumber .= str_pad($ccpparts[2], 1 ,'0', STR_PAD_LEFT);
              $lowerreferencenumber .= ">";

              $ypos = $conf->global->SWISSBANKING_PVRM10Y;
              $pdf->SetFont('OCRB10', '', $conf->global->SWISSBANKING_PVRREFERENCEFONTSIZE);
              $pdf->SetXY(7, $ypos);
              $pdf->Cell(196, 4, $lowerreferencenumber, 0, 0, 'R');

              $addressprintout = $zip.' '.$town;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "0") $addressprintout = $zip . ' ' . $town;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "1") $addressprintout = $zip . ' ' . $town . ' / ' . $regioncode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "2") $addressprintout = $zip . ' ' . $town . ', ' . $regioncode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "3") $addressprintout = $zip . ' ' . $town . ' / ' . $region;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "4") $addressprintout = $zip . ' ' . $town . ', ' . $region;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "5") $addressprintout = $zip . ' ' . $town . ' / ' . $regioncode . ' / ' . $countrycode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "6") $addressprintout = $zip . ' ' . $town . ', ' . $regioncode . ', ' . $countrycode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "7") $addressprintout = $zip . ' ' . $town . ' / ' . $regioncode . ' / ' . $country;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "8") $addressprintout = $zip . ' ' . $town . ', ' . $regioncode . ', ' . $country;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "9") $addressprintout = $countrycode . '-' . $zip . ' ' . $town;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "10") $addressprintout = $countrycode . '-' . $zip . ' ' . $town . ' / ' . $regioncode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "11") $addressprintout = $countrycode . '-' . $zip . ' ' . $town . ', ' . $regioncode;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "12") $addressprintout = $countrycode . '-' . $zip . ' ' . $town . ' / ' . $region;
              if ($conf->global->SWISSBANKING_ADDRESSPRINTOUT == "13") $addressprintout = $countrycode . '-' . $zip . ' ' . $town . ', ' . $region;

              // Left Part Address
              $address = explode("\n", $address);
              $xpos = $conf->global->SWISSBANKING_LEFTADDRESSX;
              $ypos = $conf->global->SWISSBANKING_LEFTADDRESSY;
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $leftasize);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $company, 0, 'L');
              $ypos += $conf->global->SWISSBANKING_LEFTADDRESSLD;
              for ($linecounter = 0; $linecounter < count($address); $linecounter++) {
                $pdf->SetXY($xpos, $ypos);
                $pdf->MultiCell(0, 0, $address[$linecounter], 0, 'L');
                $ypos += $conf->global->SWISSBANKING_LEFTADDRESSLD;
              }
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $addressprintout, 0, 'L');

              // Right Part Address
              $xpos = $conf->global->SWISSBANKING_RIGHTADDRESSX;
              $ypos = $conf->global->SWISSBANKING_RIGHTADDRESSY;
              $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $rightasize);
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $company, 0, 'L');
              $ypos += $conf->global->SWISSBANKING_RIGHTADDRESSLD;
              for ($linecounter = 0; $linecounter < count($address); $linecounter++) {
                $pdf->SetXY($xpos, $ypos);
                $pdf->MultiCell(0, 0, $address[$linecounter], 0, 'L');
                $ypos += $conf->global->SWISSBANKING_RIGHTADDRESSLD;
              }
              $pdf->SetXY($xpos, $ypos);
              $pdf->MultiCell(0, 0, $addressprintout, 0, 'L');

              if ($totalprint > 0 || $conf->global->SWISSBANKING_PRINTZEROTOTAL != 'No') {
                list($total, $centstotal) = explode(".", $totalprint);
                $totall = strlen($total);

                // Left part Total
                $lefttotal = $total;
                $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $leftdsize);
                for ($i = $leftdigits; $i>$totall; $i--) $lefttotal = ' '.$lefttotal;
                $xpos = $conf->global->SWISSBANKING_LEFTDIGITSMX;
                $ypos = $conf->global->SWISSBANKING_LEFTDIGITSMY;
                $pdf->SetXY($xpos, $ypos);
                for ($i = 0; $i < $leftdigits; $i++) {
                  $pdf->Cell(0 ,0, $lefttotal[$i], 0, 'L');
                  $xpos += $conf->global->SWISSBANKING_LEFTDIGITSMD;
                  $pdf->SetXY($xpos, $ypos);
                }
                $xpos = $conf->global->SWISSBANKING_LEFTDIGITSCX;
                $ypos = $conf->global->SWISSBANKING_LEFTDIGITSCY;
                $pdf->SetXY($xpos, $ypos);
                for ($i = 0; $i <= 2; $i++) {
                  $pdf->Cell(0, 0, $centstotal[$i], 0, 'L');
                  $xpos += $conf->global->SWISSBANKING_LEFTDIGITSCD;
                  $pdf->SetXY($xpos, $ypos);
                }

                // Right Part Total
                $righttotal = $total;
                $pdf->SetFont($conf->global->SWISSBANKING_PRINTCHARACTER, '', $rightdsize);
                for ($i = $rightdigits; $i > $totall; $i--) $righttotal = ' '.$righttotal;
                $xpos = $conf->global->SWISSBANKING_RIGHTDIGITSMX;
                $ypos = $conf->global->SWISSBANKING_RIGHTDIGITSMY;
                $pdf->SetXY($xpos, $ypos);
                for ($i = 0; $i < $rightdigits; $i++) {
                  $pdf->Cell(0, 0, $righttotal[$i], 0, 'L');
                  $xpos += $conf->global->SWISSBANKING_RIGHTDIGITSMD;
                  $pdf->SetXY($xpos, $ypos);
                }
                $xpos = $conf->global->SWISSBANKING_RIGHTDIGITSCX;
                $ypos = $conf->global->SWISSBANKING_RIGHTDIGITSCY;
                $pdf->SetXY($xpos, $ypos);
                for ($i = 0; $i <= 2; $i++) {
                  $pdf->Cell(0, 0, $centstotal[$i], 0, 'L');
                  $xpos += $conf->global->SWISSBANKING_RIGHTDIGITSCD;
                  $pdf->SetXY($xpos, $ypos);
                }
              }
            }

            // Output PDF
            if ($conf->global->SWISSBANKING_SLIPTYPE == 'QR' && $totalprint <= '0.00') {
              // Print popup warning if Type = QR and total <= 0.00
              // setEventMessages($outputlangs->transnoentities("SwissBankingQRNotGenerated0"), [], 'warnings');
              // Deletes old generated Payment Slip if exists
              $outputdirectory = $conf->$element->dir_output . '/' . $filereference . '/';
              $myvarrr = "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf," . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}";
              $pdffiles = glob($outputdirectory . "{" . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".pdf," . $conf->global->SWISSBANKING_FILENAMEPREFIX . "*" . $conf->global->SWISSBANKING_FILENAMESUFFIX . ".PDF}", GLOB_BRACE);
              if (is_array($pdffiles) && count($pdffiles) > 0) foreach($pdffiles as $pdffile) unlink($pdffile);
              dol_syslog('SwissBanking : PDF not generated because Grand Total is not greater than 0.00', LOG_INFO);
            }
            else $pdf->Output($outpdf, 'F');
          }

          //Inserts or Updates ISR Database Tables
          if ($invoiceid != '' && $referencenumber != '') {
            // Insert/Update SwissBanking Tables if this is not a Draft
            if((int) $parameters['object']->brouillon != 1) {
              $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking WHERE invoiceid = '" . $invoiceid . "' AND isrcode='" . $referencenumber . "' LIMIT 1";
              $resql = $this->db->query($sql);
              if ($resql) {
                if ($this->db->num_rows($resql)) $sql = "UPDATE " . MAIN_DB_PREFIX . "swissbanking SET isrcode='" . $referencenumber . "', amount='" . $invoicetotal . "', lastgenwith='" . $pvrbankaccount . "', status='open' WHERE invoiceid='" . $invoiceid . "' AND isrcode='" . $referencenumber . "'";
                else $sql = "INSERT INTO " . MAIN_DB_PREFIX . "swissbanking (invoiceid, isrcode, amount, lastgenwith, status, entity) VALUES ('" . $invoiceid . "', '" . $referencenumber . "', '" . $invoicetotal . "', '" . $pvrbankaccount . "', 'open', '" . $conf->entity . "')";
                $resql = $this->db->query($sql);
                if ($resql <= 0) {
                  $this->error = $this->db->lasterror();
                  dol_syslog($this->db, $this->error, LOG_ERR);
                  return -1;
                }
              }
              else {
                $this->error = $this->db->lasterror();
                dol_syslog($this->db, $this->error, LOG_ERR);
                return -1;
              }
            }
            else {
              dol_syslog('SwissBanking : DB Table not updated because invoice is still in DRAFT.', LOG_INFO);
            }
          }
        }
      }
    }
    return 0;
  }
}
?>