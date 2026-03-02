<?php
/*************************************************************************************
 *                                                                                   *
 * Copyright (C) 2014-2022  Mercury Labs SAGL  <info@mercurylabs.ch>                 *
 * Copyright (C) 2014-2022  Reto Kessler       <reto.kessler@mercurylabs.ch>         *
 *                                                                                   *
 * Licence       : COMMERCIAL                                                        *
 * File          : htdocs/custom/swissbanking/swissbanking.php                       *
 * Date          : 15 Apr 2014 - 25 Aug 2022                                         *
 * Description   : SwissBanking main page                                            *
 *                                                                                   *
 *************************************************************************************/
function startsWith($haystack, $needle) {
  return !strncmp($haystack, $needle, strlen($needle));
}
$swissbanking_debug = FALSE;

$res = 0;
if (!$res && file_exists('../main.inc.php')) $res = @include_once('../main.inc.php');
if (!$res && file_exists('../../main.inc.php')) $res = @include_once('../../main.inc.php');
if (!$res && strpos(str_replace('\\', '/', getcwd()), '/custom/swissbanking') !== FALSE) $res = @include_once(substr(str_replace('\\', '/', getcwd()), 0, strpos(str_replace('\\', '/', getcwd()), '/custom/swissbanking')) . '/main.inc.php');
if (!$res && strpos(str_replace('\\', '/', getcwd()), '/bitnami/dolibarr/htdocs') !== FALSE) $res = @include_once('/opt/bitnami/dolibarr/htdocs/main.inc.php');
if (!$res) die("Include of main lib failed !!!");

global $langs, $user;

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

$cwd = explode('/', str_replace('\\', '/', getcwd()));
$pos = sizeof($cwd) - 2;
if ($cwd[$pos] != substr($dolibarr_main_url_root_alt, 1)) {
  echo 'Error ! Your installation does not respect the new Dolibarr Standards.<br />
In order to resolve please ask your Administrator to visit the module main configuration pages.';
  die();
}

$langs->load("bills");
$langs->load("accountancy");
$langs->load("other");
$langs->load("swissbanking@swissbanking");

if (!isset($_GET['download'])) {
  llxHeader('', $langs->trans("SwissBankingPVBRImport"), $linktohelp);
  print_fiche_titre($langs->trans("SwissBankingPVBRImport"), $linkback, 'setup');
  clearstatcache();
  dol_htmloutput_mesg($mesg, $mesgs);
?>
<table class="noborder" width="100%">
  <tr class="liste_titre">
    <td width="10%"><a href="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>"><?php echo $langs->trans("Upload"); ?></a></td>
    <td width="10%"><a href="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>&list"><?php echo $langs->trans("List") ?></a></td>
    <td width="80%">&nbsp;</td>
  </tr>
</table>
<br><br>
<?php
}

if ($conf->global->SWISSBANKING_VERSION < "115") {
  echo 'Error : your SwissBanking module settings need to be updated. Please ask your Dolibarr Administrator to visit the SwissBanking module administration page.<br />The update procedure will be fully automatic.';
  die();
}

if (isset($_POST['action']) && $_POST['action']=='integratefile') {
  $filename = $_POST['filename'];
  $md5_file = $_POST['md5_file'];
  $dolbankid = $_POST['dolbankid'];
  $dolbanklabel =  $_POST['dolbanklabel'];
  $paymethod = explode("||$||", $_POST['paymethod']);
  $payaccount = explode("||$||", $_POST['payaccount']);
  $integrateline = $_POST['integrateline'];
  $payreference = explode("||$||", $_POST['payreference']);
  $payamount = explode("||$||", $_POST['payamount']);
  $payregdate = explode("||$||", $_POST['payregdate']);
  $paytax = explode("||$||", $_POST['paytax']);
  $paymentfound = explode("||$||", $_POST['paymentfound']);
  $facid = explode("||$||", $_POST['facid']);
  $facnumber = explode("||$||", $_POST['facnumber']);
  $socid = explode("||$||", $_POST['socid']);
  $socname = explode("||$||", $_POST['socname']);
  $payrefstatus = explode("||$||", $_POST['payrefstatus']);
  $importtime = time();
?>
<table class="noborder" width="100%">
  <tr class="liste_titre">
    <td width="1%">&nbsp;</td>
    <td width="79%"><?php echo $langs->trans("SwissBankingPVBRMessage"); ?></td>
    <td width="20%"><?php echo $langs->trans("Status"); ?></td>
  </tr>
<?php
  for($i=0; $i<count($paymentfound); $i++) {
    $thirdparty = new Societe($db);
    if ($socid[$i] > 0) $thirdparty->fetch($socid[$i]);
    if ($paymentfound[$i] == "1") {
      // Get chosen payment type and multicurrency data from original invoice
      $sql = "SELECT fk_mode_reglement, total_ttc, multicurrency_total_ttc FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $facid[$i] . " LIMIT 1";
      $resql = $db->query($sql);
      if ($resql) {
        if($db->num_rows($resql)) {
          $objp = $db->fetch_object($resql);
          $virid = $objp->fk_mode_reglement;
          $ttotal_ttc = $objp->total_ttc;
          $tmulticurrency_total_ttc = $objp->multicurrency_total_ttc;
        }
      }
      else {
        $error = $db->lasterror();
        dol_syslog($db, $error, LOG_ERR);
        return -1;
      }

      $payregdatey = substr($payregdate[$i], 0, 2);
      $payregdatem = substr($payregdate[$i], 2, 2);
      $payregdated = substr($payregdate[$i], 4, 2);
      $payregdatecalc = mktime(0, 0, 0, $payregdatem, $payregdated, $payregdatey);
      $paydatel = date('Y-m-d 12:00:00', $payregdatecalc);
      $paydates = date('Y-m-d', $payregdatecalc);
      $datenow = date("Y-m-d H:i:s");

      if ($integrateline[$i] > 0) {
        $amounts = Array();
        $amounts[$facid[$i]] = $payamount[$i];
        $multicurrency_amounts = Array();
        $multicurrency_amounts[$facid[$i]] = $payamount[$i] * $tmulticurrency_total_ttc / $ttotal_ttc;

        $paiement = new Paiement($db);
        $paiement->datepaye              = $payregdatecalc;
        $paiement->amounts               = $amounts; // Array(InvoiceID => InvoiceTotal)
        $paiement->multicurrency_amounts = $multicurrency_amounts; // Array(InvoiceID => Multicurrency InvoiceTotal)
        $paiement->paiementid            = $virid;
        $paiement->paiementcode          = 'VIR';
        $paiement->note_private          = $datenow . " - Imported by SwissBanking module for Dolibarr";
        $paiement->num_paiement          = $paiement->num_payment; // For backward compatibility
        $paiement->note                  = $paiement->note_private; // For backward compatibility

        if (!$error) {
          if ($payamount[$i] >= $ttotal_ttc) $paiement_id = $paiement->create($user, 1, $thirdparty); // This include closing invoices and regenerating documents
          else $paiement_id = $paiement->create($user, 0, $thirdparty); // This include closing invoices and regenerating documents
          if ($paiement_id < 0) {
            setEventMessages($paiement->error, $paiement->errors, 'errors');
            $error++;
          }
        }

        if (!$error) {
          $label = '(CustomerInvoicePayment)';
          $result = $paiement->addPaymentToBank($user, 'payment', $label, $dolbankid, $socname[$i], '');
          if ($result < 0) {
            setEventMessages($paiement->error, $paiement->errors, 'errors');
            $error++;
          }
        }

        if (!$error) $db->commit();
        else $db->rollback();
      }

      // insert into swissbanking_payments
      $sql = "INSERT INTO " . MAIN_DB_PREFIX . "swissbanking_payments (file_md5, imported, filename, importdate, amount, detail) VALUES ('" . $md5_file . "', '" . $integrateline[$i] . "', '" . $filename . "', '" . $importtime . "', " . $payamount[$i] . ", '" . $payreference[$i] . "||" . $facid[$i] . "||" . $facnumber[$i] . "||" . $socid[$i] . "||" . $socname[$i] . "||" . $payregdated . "." . $payregdatem . "." . $payregdatey . "||" . $paytax[$i] . "||" . $payrefstatus[$i] . "')";
      $resql = $db->query($sql);
      if ($resql) {
        // swissbanking_payments OK
        // update swissbanking status
        $sql = "UPDATE " . MAIN_DB_PREFIX . "swissbanking SET status='closed' WHERE isrcode='" . $payreference[$i] . "' LIMIT 1";
        $resql = $db->query($sql);
        if ($resql) {
          // swissbanking UPDATE OK
          // Display information
          if (versioncompare(versiondolibarrarray(), array(6, 0, 0)) >= 0) {
            if ($integrateline[$i] > 0) {
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" checked disabled></td>
<?php
              if ($payrefstatus[$i] == 'partial') {
?>
    <td><strong>Partial</strong> Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture/card.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has been imported in Dolibarr, <strong>check manually</strong> !</td>
    <td><img src="img/status_partial.png" alt="<?php echo $langs->trans("SwissBankingPayPartial"); ?>"></td>
<?php
              }
              else {
?>
    <td>Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture/card.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has been imported in Dolibarr !</td>
    <td><img src="img/status_ok.png" alt="<?php echo $langs->trans("SwissBankingPayOK"); ?>"></td>
<?php
              }
?>
  </tr>
<?php
            }
            else {
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" disabled></td>
    <td>Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture/card.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has NOT been imported in Dolibarr, <strong>do it manually</strong> !</td>
<?php
              if ($payrefstatus[$i] == 'duplicate') {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayDuplicate"); ?>"></td>
<?php
              }
              elseif ($payrefstatus[$i] == 'overpayment') {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayOver"); ?>"></td>
<?php
              }
              else {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayError"); ?>"></td>
<?php
              }
?>
  </tr>
<?php
            }
          }
          else {
            if ($integrateline[$i] > 0) {
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" checked disabled></td>
<?php
              if ($payrefstatus[$i] == 'partial') {
?>
    <td><strong>Partial</strong> Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has been imported in Dolibarr, <strong>check manually</strong> !</td>
    <td><img src="img/status_partial.png" alt="<?php echo $langs->trans("SwissBankingPayPartial"); ?>"></td>
<?php
              }
              else {
?>
    <td>Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has been imported in Dolibarr !</td>
    <td><img src="img/status_ok.png" alt="<?php echo $langs->trans("SwissBankingPayOK"); ?>"></td>
<?php
              }
?>
  </tr>
<?php
            }
            else {
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" disabled></td>
    <td>Payment <?php echo $payreference[$i]; ?> for Invoice <a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture.php?facid=<?php echo $facid[$i]; ?>" target="_blank"><?php echo $facnumber[$i]; ?></a> for Customer <a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $socid[$i]; ?>" target="_blank"><?php echo stripslashes($socname[$i]); ?></a> with amount <?php echo $payamount[$i]; ?> has been NOT imported in Dolibarr, <strong>do it manually</strong> !</td>
<?php
              if ($payrefstatus[$i] == 'duplicate') {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayDuplicate"); ?>"></td>
<?php
              }
              elseif ($payrefstatus[$i] == 'overpayment') {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayOver"); ?>"></td>
<?php
              }
              else {
?>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayError"); ?>"></td>
<?php
              }
?>
  </tr>
<?php
            }
          }
        }
        else {
          $error = $db->lasterror();
          dol_syslog($db, $error, LOG_ERR);
          return -1;
        }
      }
    }
    elseif ($payrefstatus[$i] == "notfound") {
      // insert into swissbanking_payments
      $sql = "INSERT INTO " . MAIN_DB_PREFIX . "swissbanking_payments (file_md5, imported, filename, importdate, amount, detail) VALUES ('" . $md5_file . "', '" . $integrateline[$i] . "', '" . $filename . "', '" . $importtime . "', " . $payamount[$i] . ", '" . $payreference[$i] . "||" . $facid[$i] . "||" . $facnumber[$i] . "||" . $socid[$i] . "||" . $socname[$i] . "||" . $payregdated . "." . $payregdatem . "." . $payregdatey . "||" . $paytax[$i] . "||" . $payrefstatus[$i] . "')";
      $resql = $db->query($sql);
      if ($resql) {
        // swissbanking_payments OK
        // Display information
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" disabled></td>
    <td>Payment <?php echo $payreference[$i]; ?> with amount <?php echo $payamount[$i]; ?> has not been found !</td>
    <td><img src="img/status_error.png" alt="<?php echo $langs->trans("SwissBankingPayError"); ?>"></td>
  </tr>
<?php
      }
      else {
        $error = $db->lasterror();
        dol_syslog($db, $error, LOG_ERR);
        return -1;
      }
    }
  }
?>
</table>
<?php
}
elseif (isset($_POST['action']) && $_POST['action']=='uploadedfile') { // File has been uploaded
  $uploaddir = $conf->mycompany->dir_output . '/../swissbanking/';
  if (!file_exists($uploaddir)) mkdir($uploaddir);

  if ($_FILES['UPLOADFILE']['tmp_name'] != "") {
    $md5_file = md5_file($_FILES['UPLOADFILE']['tmp_name'], FALSE);
    $uploadfile = $uploaddir . $md5_file;
  }

  $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking_payments WHERE file_md5='" . $md5_file . "'";
  $resql = $db->query($sql);
  if ($resql) {
    // Check if file was uploaded before...
    if($db->num_rows($resql)) {
?>
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td><?php echo $langs->trans("SwissBankingPVBRAnalysis"); ?></td>
    </tr>
    <tr>
      <td><?php echo $langs->trans("SwissBankingFileLoadedBefore"); ?>
      </td>
    </tr>
  </table>
<?php
    }
    else {
      // Uploading file
      if (move_uploaded_file($_FILES['UPLOADFILE']['tmp_name'], $uploadfile)) {
        if($swissbanking_debug) echo "File is valid, and was successfully uploaded.<br><br>";
        $totalpayed = 0;
        $dolbankid = '';
        $dolbanklabel = '';
        // Analyzing file...
        if ($xml=@simplexml_load_file($uploadfile)) { // Uploaded File is XML
          $ns = $xml->getNamespaces(true);
          if ($ns[''] == 'urn:iso:std:iso:20022:tech:xsd:camt.054.001.04') { // XML format is camt.054.001.04
            $endmethod = '999';
            $endreference = (string)str_pad($xml->BkToCstmrDbtCdtNtfctn->GrpHdr->MsgId, 27, '0', STR_PAD_LEFT);
            $endregdate = (string)date('dmy', strtotime(explode('T', (string)$xml->BkToCstmrDbtCdtNtfctn->GrpHdr->CreDtTm)[0]));
            $endamount = 0;
            $endrecords = 0;
            $endtax = 0;
            $line_num = 0;
            foreach($xml->BkToCstmrDbtCdtNtfctn->Ntfctn->Ntry as $Ntry) {
              $endaccount = (string)substr($Ntry->NtryRef, 0, 2) . '-' . ltrim(substr($Ntry->NtryRef, 2, 6), '0') . '-' . substr($Ntry->NtryRef, 8, 1);
              $endamount += floatval($Ntry->Amt);
              // BEGIN To be fixed, Raiffeisen single registration QRR Payment
              if (intval($Ntry->NtryDtls->Btch->NbOfTxs) > 0) $endrecords += intval($Ntry->NtryDtls->Btch->NbOfTxs);
              // else $endrecords += 1;
              // END To be fixed, Raiffeisen single registration QRR Payment
              $endtax += floatval($Ntry->Chrgs->TtlChrgsAndTaxAmt * 100);
              if (isset($Ntry->ValDt->Dt) && strlen($Ntry->ValDt->Dt) > 0) $ntrydate = (string)date('ymd', strtotime($Ntry->ValDt->Dt));
              else $ntrydate = '';
              foreach($Ntry->NtryDtls->TxDtls as $TxDtls) {
                // BEGIN To be fixed, Raiffeisen single registration QRR Payment
                // else $endrecords += 1; has been moved here
                // END To be fixed, Raiffeisen single registration QRR Payment
                if (intval($Ntry->NtryDtls->Btch->NbOfTxs) <= 0) $endrecords += 1;
                $paymethod[$line_num] = '002';
                $payaccount[$line_num] = $endaccount;
                $payreference[$line_num] = (string)$TxDtls->RmtInf->Strd->CdtrRefInf->Ref;
                $payamount[$line_num] = (string)number_format((float)$TxDtls->Amt, 2, '.', '');
                if (isset($TxDtls->RltdDts->AccptncDtTm) && strlen($TxDtls->RltdDts->AccptncDtTm) > 0 && $ntrydate == '') $payregdate[$line_num] = (string)date('ymd', strtotime(explode('T', (string)$TxDtls->RltdDts->AccptncDtTm)[0]));
                else $payregdate[$line_num] = $ntrydate;
                if (isset($TxDtls->Chrgs->TtlChrgsAndTaxAmt)) $paytax[$line_num] = (string)($TxDtls->Chrgs->TtlChrgsAndTaxAmt * 100);
                else $paytax[$line_num] = (string)($TxDtls->Chrgs->Rcrd->Amt * 100);
                $totalpayed += $payamount[$line_num];
                if($dolbankid == '') {
                  $bankcustomer = substr($payreference[$line_num], 0, 6);
                  $sql = "SELECT dolbankid,label FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE bankid='" . $bankcustomer . "' AND entity='" . $conf->entity . "'";
                  $resql = $db->query($sql);
                  if ($resql) {
                    if($db->num_rows($resql)) {
                      $objp = $db->fetch_object($resql);
                      $dolbankid = $objp->dolbankid;
                      $dolbanklabel = $objp->label;
                    }
                  }
                  else {
                    $error = $db->lasterror();
                    dol_syslog($db, $error, LOG_ERR);
                    return -1;
                  }
                }
                $line_num++;
              }
            }
            $endamount = (string)number_format($endamount, 2, '.', '');
            $endrecords = (string)$endrecords;
            $endtax = (string)$endtax;
          }
          else { // XML format is NOT camt.054.001.04
            die("ERROR: XML File can not be parsed, please contact developer !");
          }
        }
        else { // Uploaded File is NOT XML
          $pvbrlines = file($uploadfile, FILE_SKIP_EMPTY_LINES);
          foreach($pvbrlines as $line_num => $line) {
            if($swissbanking_debug) echo $line;
            if(startsWith($line, "999")) {
              $endmethod = substr($line, 0, 3);
              $endaccount = substr($line, 3, 2) . '-' . ltrim(substr($line, 5, 6), '0') . '-' . substr($line, 11, 1);
              $endreference = substr($line, 12, 27);
              $endamount = number_format(substr($line, 39, 12)/100, 2, '.', '');
              $endrecords = number_format(substr($line, 51, 12), 0, '', '');
              $endregdate = substr($line, 63, 6);
              $endtax = substr($line, 69, 9);
            }
            else {
              $paymethod[$line_num] = substr($line, 0, 3);
              $payaccount[$line_num] = substr($line, 3, 2) . '-' . ltrim(substr($line, 5, 6), '0') . '-' . substr($line, 11, 1);
              $payreference[$line_num] = substr($line, 12, 27);
              $payamount[$line_num] = number_format(substr($line, 39, 10)/100, 2, '.', '');
              $payregdate[$line_num] = substr($line, 71, 6);
              $paytax[$line_num] = substr($line, 96, 4);
              $totalpayed += $payamount[$line_num];
              if($dolbankid == '') {
                $bankcustomer = substr($payreference[$line_num], 0, 6);
                $sql = "SELECT dolbankid,label FROM " . MAIN_DB_PREFIX . "swissbanking_accounts WHERE bankid='" . $bankcustomer . "' AND entity='" . $conf->entity . "'";
                $resql = $db->query($sql);
                if ($resql) {
                  if($db->num_rows($resql)) {
                    $objp = $db->fetch_object($resql);
                    $dolbankid = $objp->dolbankid;
                    $dolbanklabel = $objp->label;
                  }
                }
                else {
                  $error = $db->lasterror();
                  dol_syslog($db, $error, LOG_ERR);
                  return -1;
                }
              }
            }
          }
        }
        // Print Out parameters:
        for($i=0;$i<$line_num;$i++) {
          if($swissbanking_debug) echo "$paymethod[$i] $payaccount[$i] $payreference[$i] $payamount[$i] $payregdate[$i] $paytax[$i]<br><br>";
        }
        $totalpayed = number_format($totalpayed, 2, '.', '');
        if($totalpayed - $endamount == 0) $totalmatches = "YES"; else $totalmatches = "NO";
        if($i - $endrecords == 0) $recordsmatches = "YES"; else $recordsmatches = "NO";
?>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root.$dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td><?php echo $langs->trans("SwissBankingPVBRAnalysis"); ?></td>
    </tr>
    <tr>
      <td>
        <b>PVBR file for : <?php if($dolbanklabel != '') echo $dolbanklabel; else echo '<font color="#FF0000">BANK ACCOUNT NOT FOUND</font>'; ?></b><br>
        Calculated Total : <?php echo $totalpayed; ?><br>
        Check Total in PVBR File : <?php echo $endamount; ?><br>
        MATCHES : <b><?php if ($totalmatches == "YES") echo "<font color=\"#00FF00\">YES</font>"; else echo "<font color=\"#FF0000\">NO</font>"; ?></b><br><br>
        Read Records : <?php echo $i; ?><br>
        Check Records : <?php echo $endrecords; ?><br>
        MATCHES : <b><?php if ($recordsmatches == "YES") echo "<font color=\"#00FF00\">YES</font>"; else echo "<font color=\"#FF0000\">NO</font>"; ?></b>
<?php
        if($swissbanking_debug) echo "<pre><br>$endmethod $endaccount $endreference $endamount $endrecords $endregdate $endtax<br></pre>";
?>
      </td>
    </tr>
  </table>
  <br>
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="8"><?php echo $langs->trans("SwissBankingPVBRList"); ?></td>
    </tr>
<?php
        if($totalmatches=="YES" && $recordsmatches=="YES" && $dolbankid != '') {
          $referenceduplicates = array_diff_assoc($payreference, array_unique($payreference));
?>
    <tr class="liste_titre">
      <td>&nbsp;</td>
      <td><?php echo $langs->trans("SwissBankingPVBRID"); ?></td>
      <td><?php echo $langs->trans("SwissBankingInvoiceID"); ?></td>
      <td><?php echo $langs->trans("SwissBankingCustomer"); ?></td>
      <td><?php echo $langs->trans("SwissBankingPayDate"); ?></td>
      <td><?php echo $langs->trans("SwissBankingPayedAmount"); ?></td>
      <td><?php echo $langs->trans("SwissBankingDueAmount"); ?></td>
      <td><?php echo $langs->trans("Status"); ?></td>
    </tr>
<?php
          // Search in the Database
          for($i=0;$i<$endrecords;$i++) {
            $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "swissbanking WHERE isrcode='" . $payreference[$i] . "' AND entity='" . $conf->entity . "'";
            $resql = $db->query($sql);
            if ($resql) {
              if($db->num_rows($resql)) {
                $objp = $db->fetch_object($resql);
                $payreferencestatus = $objp->status;
                $paidsql= "(SELECT SUM(amount) FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture='" . $objp->invoiceid . "') paidamount";
                if (versioncompare(versiondolibarrarray(), array(10, 0, 0)) >= 0) $sqlfac = "SELECT ref AS facnumber, fk_soc, paye, " . $paidsql . " FROM " . MAIN_DB_PREFIX . "facture WHERE rowid='" . $objp->invoiceid . "' AND entity='" . $conf->entity . "'";
                else $sqlfac = "SELECT facnumber AS facnumber, fk_soc, paye, " . $paidsql . " FROM " . MAIN_DB_PREFIX . "facture WHERE rowid='" . $objp->invoiceid . "' AND entity='" . $conf->entity . "'";
                $resqlfac = $db->query($sqlfac);
                if ($resqlfac) {
                  if($db->num_rows($resqlfac)) {
                    $objpfac = $db->fetch_object($resqlfac);
                    $sqlsoc = "SELECT nom FROM " . MAIN_DB_PREFIX . "societe WHERE rowid='" . $objpfac->fk_soc . "' AND entity='" . $conf->entity . "'";
                    $resqlsoc = $db->query($sqlsoc);
                    if ($resqlsoc) {
                      if($db->num_rows($resqlsoc)) $objpsoc = $db->fetch_object($resqlsoc);
                    }
                    else {
                      $error = $db->lasterror();
                      dol_syslog($db, $error, LOG_ERR);
                      return -1;
                    }
                  }
                }
                else {
                  $error = $db->lasterror();
                  dol_syslog($db, $error, LOG_ERR);
                  return -1;
                }

                $due_amount = number_format($objp->amount - $objpfac->paidamount, 2, '.', '');
                $paymentfound[$i] = 1;
                $facid[$i] = $objp->invoiceid;
                $facnumber[$i] = $objpfac->facnumber;
                $socid[$i] = $objpfac->fk_soc;
                $facpayed[$i] = $objpfac->paye;
                $socname[$i] = $objpsoc->nom;
                if ($facpayed[$i] > 0) {
                  $payrefstatus[$i] = "closed";
                  $printstatus = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayPresent") . '"> ' . $langs->trans("SwissBankingInvoiceClosed");
                }
                else {
                  if (isset($referenceduplicates[$i])) {
                    $payrefstatus[$i] = "duplicate";
                    $printstatus = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayDuplicate") . '"> ' . $langs->trans("SwissBankingPayDuplicate");
                  }
                  elseif ($payreferencestatus == "open") {
                    if ($payamount[$i] < $due_amount) {
                      $payrefstatus[$i] = "partial";
                      $printstatus = '<img src="img/status_partial.png" alt="' . $langs->trans("SwissBankingPayPartial") . '"> ' . $langs->trans("SwissBankingPayPartial");
                    }
                    elseif ($payamount[$i] > $due_amount) {
                      $payrefstatus[$i] = "overpayment";
                      $printstatus = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayOver") . '"> ' . $langs->trans("SwissBankingPayOver");
                    }
                    else {
                      $payrefstatus[$i] = "open";
                      $printstatus = '<img src="img/status_ok.png" alt="' . $langs->trans("SwissBankingPayOK") . '"> '.$langs->trans("SwissBankingPayOK");
                    }
                  }
                  else {
                    $payrefstatus[$i] = "closed";
                    $printstatus = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayPresent") . '"> ' . $langs->trans("SwissBankingPayPresent");
                  }
                }
?>
    <tr>
<?php
                if ($facpayed[$i] > 0 || isset($referenceduplicates[$i]) || $payamount[$i] > $due_amount) {
?>
      <td align="center"><input type="hidden" name="integrateline[<?php echo $i; ?>]" value="0"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="0" disabled></td>
<?php
                }
                else {
?>
      <td align="center"><input type="hidden" name="integrateline[<?php echo $i; ?>]" value="0"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" checked></td>
<?php
                }
?>
      <td><?php echo $payreference[$i]; ?></td>
<?php
                if (versioncompare(versiondolibarrarray(), array(6,0,0)) >= 0) {
?>
      <td><a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture/card.php?ref=<?php echo $objpfac->facnumber; ?>" target="_blank"><?php echo $objpfac->facnumber; ?></a></td>
<?php
                }
                else {
?>
      <td><a href="<?php echo $dolibarr_main_url_root; ?>/compta/facture.php?ref=<?php echo $objpfac->facnumber; ?>" target="_blank"><?php echo $objpfac->facnumber; ?></a></td>
<?php
                }
                if (versioncompare(versiondolibarrarray(), array(3,7,0)) >= 0) {
?>
      <td><a href="<?php echo $dolibarr_main_url_root; ?>/comm/card.php?socid=<?php echo $objpfac->fk_soc; ?>" target="_blank"><?php echo $objpsoc->nom; ?></a></td>
<?php
                }
                else {
?>
      <td><a href="<?php echo $dolibarr_main_url_root; ?>/comm/fiche.php?socid=<?php echo $objpfac->fk_soc; ?>" target="_blank"><?php echo $objpsoc->nom; ?></a></td>
<?php
                }
?>
      <td><?php echo $payregdate[$i]; ?></td>
      <td><?php echo $payamount[$i]; ?></td>
      <td><?php echo $due_amount; ?></td>
      <td><?php echo $printstatus; ?></td>
    </tr>
<?php
              }
              else {
                $printstatus = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingCheckManually") . '"> ' . $langs->trans("SwissBankingCheckManually");
?>
    <tr>
      <td align="center"><input type="hidden" name="integrateline[<?php echo $i; ?>]" value="0"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1" disabled></td>
      <td><?php echo $payreference[$i]; ?></td>
      <td colspan="2"><?php echo $langs->trans("SwissBankingNoPayMentsForThisRef"); ?></td>
      <td><?php echo $payregdate[$i]; ?></td>
      <td><?php echo $payamount[$i]; ?></td>
      <td>N/A</td>
      <td><?php echo $printstatus; ?></td>
    </tr>
<?php
                $paymentfound[$i] = -1;
                $facid[$i] = -1;
                $facnumber[$i] = -1;
                $socid[$i] = -1;
                $socname[$i] = -1;
                $payrefstatus[$i] = "notfound";
              }
            }
            else {
              $error = $db->lasterror();
              dol_syslog($db, $error, LOG_ERR);
              return -1;
            }
          }
          $paymethod = htmlspecialchars(implode("||$||", $paymethod));
          $payaccount = htmlspecialchars(implode("||$||", $payaccount));
          $payreference = htmlspecialchars(implode("||$||", $payreference));
          $payamount = htmlspecialchars(implode("||$||", $payamount));
          $payregdate = htmlspecialchars(implode("||$||", $payregdate));
          $paytax = htmlspecialchars(implode("||$||", $paytax));
          $paymentfound = htmlspecialchars(implode("||$||", $paymentfound));
          $facid = htmlspecialchars(implode("||$||", $facid));
          $facnumber = htmlspecialchars(implode("||$||", $facnumber));
          $socid = htmlspecialchars(implode("||$||", $socid));
          $socname = addslashes(htmlspecialchars(implode("||$||", $socname)));
          $payrefstatus = htmlspecialchars(implode("||$||", $payrefstatus));
?>
    <tr>
      <td colspan="8" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("IntegratePayments"); ?>"></td>
    </tr>
<?php
        }
        elseif($totalmatches=="YES" && $recordsmatches=="YES") {
?>
    <tr>
      <td colspan="8" align="center"><?php echo $langs->trans("SwissBankingPVBRIDNotconfigured"); ?></td>
    </tr>
<?php
        }
        else {
?>
    <tr>
      <td colspan="8" align="center"><font color="#FF0000"><b>Something wrong with your file, please correct the issue or contact developer!</b></font></td>
    </tr>
<?php
        }
?>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="integratefile">
  <input type="hidden" name="filename" value="<?php echo basename($_FILES['UPLOADFILE']['name']); ?>">
  <input type="hidden" name="md5_file" value="<?php echo $md5_file; ?>">
  <input type="hidden" name="dolbankid" value="<?php echo $dolbankid; ?>">
  <input type="hidden" name="dolbanklabel" value="<?php echo $dolbanklabel; ?>">
  <input type="hidden" name="paymethod" value="<?php echo $paymethod; ?>">
  <input type="hidden" name="payaccount" value="<?php echo $payaccount; ?>">
  <input type="hidden" name="payreference" value="<?php echo $payreference; ?>">
  <input type="hidden" name="payamount" value="<?php echo $payamount; ?>">
  <input type="hidden" name="payregdate" value="<?php echo $payregdate; ?>">
  <input type="hidden" name="paytax" value="<?php echo $paytax; ?>">
  <input type="hidden" name="paymentfound" value="<?php echo $paymentfound; ?>">
  <input type="hidden" name="facid" value="<?php echo $facid; ?>">
  <input type="hidden" name="facnumber" value="<?php echo $facnumber; ?>">
  <input type="hidden" name="socid" value="<?php echo $socid; ?>">
  <input type="hidden" name="socname" value="<?php echo $socname; ?>">
  <input type="hidden" name="payrefstatus" value="<?php echo $payrefstatus; ?>">
</form>
<?php
      } else {
?>
<font color="#FF0000"><b>File NOT Uploaded!</b></font>
<?php
      }
    }
  }
  else {
    $error = $db->lasterror();
    dol_syslog($db,$error, LOG_ERR);
    return -1;
  }
}
elseif (isset($_GET['list'])) { // List past uploads
?>
<table class="noborder" width="100%">
  <tr class="liste_titre">
    <td colspan="4"><?php echo $langs->trans("SwissBankingLog"); ?></td>
  </tr>
  <tr class="liste_titre">
    <td><?php echo $langs->trans("SwissBankingImportDate"); ?></td>
    <td><?php echo $langs->trans("SwissBankingFileName"); ?></td>
    <td><?php echo $langs->trans("SwissBankingImportedRecords"); ?></td>
    <td><?php echo $langs->trans("SwissBankingPayedAmount"); ?></td>
  </tr>
<?php
  $sql = "SELECT file_md5,filename,importdate,count(*) as grouprecords,sum(amount) as amounttotal FROM ".MAIN_DB_PREFIX."swissbanking_payments GROUP BY file_md5 ORDER BY importdate DESC";
  $resql = $db->query($sql);
  if ($resql) {
    if($db->num_rows($resql)) {
      $i = 0;
      while ($i < $db->num_rows($resql)) {
        $objp = $db->fetch_object($resql);
        $md5_file = $objp->file_md5;
        $filename = $objp->filename;
        $importtime = $objp->importdate;
        $grouprecords = $objp->grouprecords;
        $amounttotal = $objp->amounttotal;
?>
  <tr>
    <td><a href="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>&detail&id=<?php echo $md5_file; ?>"><?php echo date("d.m.Y - H:i:s", $importtime); ?></a></td>
    <td><a href="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>&download&id=<?php echo $md5_file; ?>" target="_blank"><?php echo $filename; ?></a></td>
    <td><?php echo $grouprecords; ?></td>
    <td><?php echo number_format($amounttotal, 2, '.', ''); ?></td>
  </tr>
<?php
        $i++;
      }
    }
    else {
?>
  <tr>
    <td colspan="4"><?php echo $langs->trans("SwissBankingLogEmpty"); ?></td>
  </tr>
<?php
    }
  }
  else {
    $error = $db->lasterror();
    dol_syslog($db, $error, LOG_ERR);
    return -1;
  }
?>
</table>
<?php
}
elseif (isset($_GET['detail'])) { // $md5_file,$filename,$importtime,$amount,,,,$payreference,$facid,$facnumber,$socid,$socname,$paytax,$payrefstatus
  if (isset($_GET['id'])) {
?>
<table class="noborder" width="100%">
  <tr class="liste_titre">
    <td colspan="8"><?php echo $langs->trans("SwissBankingDetail"); ?></td>
  </tr>
  <tr class="liste_titre">
    <td>&nbsp;</td>
    <td><?php echo $langs->trans("SwissBankingImportDate"); ?></td>
    <td><?php echo $langs->trans("SwissBankingPVBRID"); ?></td>
    <td><?php echo $langs->trans("SwissBankingInvoiceID"); ?></td>
    <td><?php echo $langs->trans("SwissBankingCustomer"); ?></td>
    <td><?php echo $langs->trans("SwissBankingPayDate"); ?></td>
    <td><?php echo $langs->trans("SwissBankingPayedAmount"); ?></td>
    <td><?php echo $langs->trans("Status"); ?></td>
  </tr>
<?php
  $sql = "SELECT * FROM ".MAIN_DB_PREFIX."swissbanking_payments WHERE file_md5='".$_GET['id']."' ORDER BY rowid ASC";
  $resql = $db->query($sql);
  if ($resql) {
    if($db->num_rows($resql)) {
      $i = 0;
      while ($i < $db->num_rows($resql)) {
        $objp = $db->fetch_object($resql);
        $md5_file = $objp->file_md5;
        $integrateline = $objp->imported;
        $filename = $objp->filename;
        $importtime = $objp->importdate;
        $amount = $objp->amount;
        list($payreference, $facid, $facnumber, $socid, $socname, $payregdate, $paytax, $payrefstatus) = explode("||", $objp->detail);
        if($integrateline > 0) {
          if ($payrefstatus == "open") $payrefstatusout = '<img src="img/status_ok.png" alt="' . $langs->trans("SwissBankingPayOK") . '"> ' . $langs->trans("SwissBankingPayOK");
          elseif ($payrefstatus == "partial") $payrefstatusout = '<img src="img/status_partial.png" alt="' . $langs->trans("SwissBankingPayPartial") . '"> ' . $langs->trans("SwissBankingPayPartial");
          else $payrefstatusout = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingCheckManually") . '"> ' . $langs->trans("SwissBankingCheckManually");
        }
        else {
          if ($payrefstatus == "duplicate") $payrefstatusout = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayDuplicate") . '"> ' . $langs->trans("SwissBankingPayDuplicate");
          elseif ($payrefstatus == "overpayment") $payrefstatusout = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingPayOver") . '"> ' . $langs->trans("SwissBankingPayOver");
          else $payrefstatusout = '<img src="img/status_error.png" alt="' . $langs->trans("SwissBankingCheckManually") . '"> ' . $langs->trans("SwissBankingCheckManually");
        }
        if ($facnumber == "-1") $facture = '&nbsp';
        else {
          if (versioncompare(versiondolibarrarray(), array(6, 0, 0)) >= 0) $facture = '<a href="' . $dolibarr_main_url_root . '/compta/facture/card.php?facid=' . $facid . '" target="_blank">' . $facnumber . '</a>';
          else $facture = '<a href="' . $dolibarr_main_url_root . '/compta/facture.php?facid=' . $facid . '" target="_blank">' . $facnumber . '</a>';
        }
        if ($socname == "-1") $fiche = '&nbsp;';
        else {
          if (versioncompare(versiondolibarrarray(), array(3, 7, 0)) >= 0) $fiche = '<a href="' . $dolibarr_main_url_root . '/comm/card.php?socid=' . $socid . '" target="_blank">' . $socname . '</a>';
          else $fiche = '<a href="' . $dolibarr_main_url_root . '/comm/fiche.php?socid=' . $socid . '" target="_blank">' . $socname . '</a>';
        }
?>
  <tr>
    <td align="center"><input class="flat" size="1" type="checkbox" name="integrateline[<?php echo $i; ?>]" value="1"<?php if($integrateline > 0) echo " checked"; ?> disabled></td>
    <td><?php echo date("d.m.Y - H:i:s", $importtime); ?></td>
    <td><?php echo $payreference; ?></td>
    <td><?php echo $facture; ?></td>
    <td><?php echo $fiche; ?></td>
    <td><?php echo $payregdate; ?></td>
    <td><?php echo number_format($amount, 2, '.', ''); ?></td>
    <td><?php echo $payrefstatusout; ?></td>
  </tr>
<?php
        $i++;
      }
    }
    else {
?>
  <tr>
    <td colspan="8"><?php echo $langs->trans("SwissBankingDetailEmpty"); ?></td>
  </tr>
<?php
    }
  }
  else {
    $error = $db->lasterror();
    dol_syslog($db, $error, LOG_ERR);
    return -1;
  }
?>
</table>
<?php
  }
}
elseif (isset($_GET['download'])) {
  if (isset($_GET['id'])) {
    $file = $conf->mycompany->dir_output . '/../swissbanking/' . $_GET['id'];
    $sql = "SELECT filename FROM " . MAIN_DB_PREFIX . "swissbanking_payments WHERE file_md5='" . $_GET['id'] . "' LIMIT 1";
    $resql = $db->query($sql);
    if ($resql) {
      if($db->num_rows($resql)) {
        $objp = $db->fetch_object($resql);
        $filename = $objp->filename;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $objp->filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
      }
    }
    else {
      $error = $db->lasterror();
      dol_syslog($db ,$error, LOG_ERR);
      return -1;
    }
  }
  else header("Location: " . $dolibarr_main_url_root . $dolibarr_main_url_root_alt . "/swissbanking/swissbanking.php?idmenu=" . $_GET['idmenu']);
}
else {
?>
<form id="swissbanking" method="post" action="<?php echo $dolibarr_main_url_root . $dolibarr_main_url_root_alt; ?>/swissbanking/swissbanking.php?idmenu=<?php echo $_GET['idmenu']; ?>" enctype="multipart/form-data">
  <table class="noborder" width="100%">
    <tr class="liste_titre">
      <td colspan="2"><?php echo $langs->trans("Upload"); ?></td>
    </tr>
    <tr>
      <td width="200"><?php echo $langs->trans("PVBRFileUpload"); ?></td>
      <td align="left"><input type="file" name="UPLOADFILE"></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Upload"); ?>"></td>
    </tr>
  </table>
  <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
  <input type="hidden" name="action" value="uploadedfile">
</form>
<?php
}
if (!isset($_GET['download'])) llxFooter();
$db->close();
?>