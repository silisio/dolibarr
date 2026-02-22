<?php
/* Copyright (C) 2026 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';

class ActionsMassSubscriptionBatch extends CommonHookActions
{
	public $db;
	public $error = '';
	public $errors = array();
	public $results = array();
	public $resprints;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $massaction, $user;

		$context = isset($parameters['currentcontext']) ? $parameters['currentcontext'] : (isset($parameters['context']) ? $parameters['context'] : '');
		if (!in_array('memberlist', explode(':', (string) $context))) {
			return 0;
		}
		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			return 0;
		}

		$langs->load('masssubscriptionbatch@masssubscriptionbatch');
		$this->resprints = '<option value="masssubinvoiceemail">'.img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans('MassSubInvoiceEmailAction').'</option>';
		if (getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_SETENDDATE')) {
			$this->resprints .= '<option value="presetsubscriptionenddate">'.img_picto('', 'date', 'class="pictofixedwidth"').$langs->trans('MassSubSetEndDateAction').'</option>';
		}
		if (getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_MASSMAIL')) {
			$this->resprints .= '<option value="presend">'.img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans('MassSubSendMailAction').'</option>';
		}
		return 0;
	}



	public function doPreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $form, $langs, $user;

		$massaction = $parameters['massaction'] ?? '';
		if ($massaction === 'presend' && getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_MASSMAIL')) {
			$allowupload = (int) getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_MASSMAIL_UPLOAD');
			$note = json_encode($langs->transnoentitiesnoconv('MassSubSendMailNoMainDocNote'));
			$addfilelabel = json_encode($langs->transnoentitiesnoconv('MailingAddFile'));
			$uploadhint = json_encode($langs->transnoentitiesnoconv('MassSubSendMailUploadHint'));
			$placeholdersTitle = json_encode($langs->transnoentitiesnoconv('MassSubSendMailPlaceholdersTitle'));
			$placeholdersList = json_encode('__MEMBER_FULLNAME__, __MEMBER_FIRSTNAME__, __MEMBER_LASTNAME__, __MEMBER_EMAIL__, __MEMBER_ID__, __MEMBER_TYPE__, __MEMBER_LAST_SUBSCRIPTION_DATE_END__');
			$this->resprints = '<script>document.addEventListener("DOMContentLoaded",function(){'
				.'var cb=document.querySelector("input[name=\"addmaindocfile\"]");if(cb){cb.checked=false;cb.disabled=true;}'
				.'var row=cb?(cb.closest(".tagtr")||cb.closest("tr")):null;'
				.'var mailform=document.getElementById("mailform");if(mailform){mailform.enctype="multipart/form-data";mailform.encoding="multipart/form-data";}var target=row?((row.querySelector(".tagtd:last-child")||row.querySelector("td:last-child")||row)):mailform;'
				.'var n=document.createElement("div");n.className="opacitymedium small";n.textContent='.$note.';if(target){target.appendChild(n);}'
				.'if(target && '.$allowupload.'){var wrap=document.createElement("div");wrap.className="margintoponly";'
					.'wrap.innerHTML="<input type=\"file\" class=\"flat\" id=\"addedfile\" name=\"addedfile\" form=\"mailform\"> '
						.'<input type=\"submit\" class=\"button smallpaddingimp\" id=\"addfile\" name=\"addfile\" form=\"mailform\" formnovalidate value="+'.$addfilelabel.'+"/> '
				.'<span class=\"opacitymedium\">"+'.$uploadhint.'+"</span>";target.appendChild(wrap);}'
				.'var body=document.getElementById("message");if(body){var box=document.createElement("div");box.className="opacitymedium small margintop";'
				.'box.innerHTML="<b>"+'.$placeholdersTitle.'+"</b><br><code>"+'.$placeholdersList.'+"</code>";body.parentNode.insertBefore(box, body);}'
				.'});</script>';
			return 0;
		}

		if ($massaction !== 'presetsubscriptionenddate') {
			return 0;
		}
		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
			return 0;
		}

		$toselect = is_array($parameters['toselect'] ?? null) ? $parameters['toselect'] : array();
		$formquestion = array(
			array('type' => 'date', 'name' => 'msb_enddate', 'label' => $langs->trans('MassSubSetEndDateField'), 'value' => dol_time_plus_duree(dol_now(), -1, 'd')),
		);
		$this->resprints = $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('MassSubSetEndDateConfirmTitle'), $langs->trans('MassSubSetEndDateConfirmQuestion', count($toselect)), 'setsubscriptionenddate', $formquestion, 1, 0, 200, 500, 1);

		return 0;
	}

	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $massaction, $user;

		if ($action === 'confirm_presend' && !getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_MASSMAIL')) {
			setEventMessages($langs->trans('MassSubSendMailDisabled'), null, 'errors');
			$action = 'list';
			return 1;
		}
		if ($action === 'confirm_presend' && getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_MASSMAIL')) {
			$_POST['addmaindocfile'] = 0;
			$_REQUEST['addmaindocfile'] = 0;
		}
		if ($action !== 'setsubscriptionenddate' || GETPOST('confirm', 'aZ09') !== 'yes') {
			return 0;
		}
		if (!getDolGlobalInt('MASSSUBSCRIPTIONBATCH_ENABLE_SETENDDATE')) {
			setEventMessages($langs->trans('MassSubSetEndDateDisabled'), null, 'errors');
			return 1;
		}
		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
			return 1;
		}

		$day = GETPOSTINT('msb_enddateday');
		$month = GETPOSTINT('msb_enddatemonth');
		$year = GETPOSTINT('msb_enddateyear');
		$dateend = dol_mktime(0, 0, 0, $month, $day, $year);
		if (empty($dateend)) {
			setEventMessages($langs->trans('MassSubSetEndDateInvalid'), null, 'errors');
			return 1;
		}

		$toselect = GETPOST('toselect', 'array');
		$toselect = is_array($toselect) ? $toselect : array();

		$member = new Adherent($this->db);
		$nbupdated = 0;
		$nberrors = 0;
		$this->db->begin();
		foreach ($toselect as $id) {
			if ($member->fetch((int) $id) <= 0) {
				$nberrors++;
				continue;
			}
			$res = $member->setValueFrom('datefin', dol_print_date($dateend, '%Y-%m-%d'), '', null, 'date');
			if ($res > 0) {
				$nbupdated++;
			} else {
				$nberrors++;
			}
		}

		if ($nberrors > 0) {
			$this->db->rollback();
			setEventMessages($langs->trans('MassSubSetEndDateErrors', $nberrors), null, 'errors');
		} else {
			$this->db->commit();
			setEventMessages($langs->trans('MassSubSetEndDateDone', $nbupdated, dol_print_date($dateend, 'day')), null, 'mesgs');
		}

		$action = 'list';
		return 1;
	}

	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		if (($parameters['massaction'] ?? '') !== 'masssubinvoiceemail') {
			return 0;
		}

		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
			return 0;
		}

		$langs->loadLangs(array('members', 'masssubscriptionbatch@masssubscriptionbatch'));
		$toselect = is_array($parameters['toselect']) ? $parameters['toselect'] : array();
		$sendmailenabled = (int) getDolGlobalInt('MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL');
		if (empty($sendmailenabled)) {
			setEventMessages($langs->trans('MassSubInvoiceEmailDisabled'), null, 'warnings');
		}
		$mailfrom = getDolGlobalString('ADHERENT_MAIL_FROM', $conf->email_from);
		if ($sendmailenabled && empty($mailfrom)) {
			$sendmailenabled = 0;
			setEventMessages($langs->trans('MassSubInvoiceEmailSenderMissing'), null, 'errors');
		}
		$emailmaxperrun = max(0, (int) getDolGlobalInt('MASSSUBSCRIPTIONBATCH_EMAILS_PER_RUN', 100));
		$emaildelayms = max(0, (int) getDolGlobalInt('MASSSUBSCRIPTIONBATCH_EMAIL_DELAY_MS', 200));
		if ($sendmailenabled && $emailmaxperrun > 0 && count($toselect) > $emailmaxperrun) {
			setEventMessages($langs->trans('MassSubInvoiceEmailLimitExceededAbort', count($toselect), $emailmaxperrun), null, 'errors');
			return 0;
		}
		$templatelabel = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION');
		$formmail = new FormMail($this->db);
		$templatecache = array();

		$member = new Adherent($this->db);
		$membertype = new AdherentType($this->db);

		$nbcreated = 0;
		$nbsentemail = 0;
		$nbemailfailed = 0;
		$nbemailmissing = 0;
		$nberrors = 0;
		$datesubscription = dol_now();

		foreach ($toselect as $id) {
			if ($member->fetch((int) $id) <= 0) {
				$nberrors++;
				continue;
			}
			if ($membertype->fetch($member->typeid) <= 0) {
				$nberrors++;
				continue;
			}

			$amount = is_numeric($membertype->amount) ? (float) $membertype->amount : 0.0;
			if (!empty($member->last_subscription_amount)) {
				$amount = max($amount, (float) $member->last_subscription_amount);
			}

			$durationvalue = !empty($membertype->duration_value) ? $membertype->duration_value : 1;
			$durationunit = !empty($membertype->duration_unit) ? $membertype->duration_unit : 'y';
			$datesubend = dol_time_plus_duree(dol_time_plus_duree($datesubscription, $durationvalue, $durationunit), -1, 'd');
			$label = $langs->transnoentitiesnoconv('MembershipPaid', dol_print_date($datesubend, 'day'));

			$this->db->begin();

			$subscriptionid = $member->subscription($datesubscription, $amount, 0, '', $label, '', '', '', $datesubend);
			if ($subscriptionid <= 0) {
				$this->db->rollback();
				$nberrors++;
				continue;
			}

			$rescomplement = $member->subscriptionComplementaryActions($subscriptionid, 'invoiceonly', 0, $datesubscription, '', '', $label, $amount, '', '', '', 1);
			if ($rescomplement < 0) {
				$this->db->rollback();
				$nberrors++;
				continue;
			}

			$this->db->commit();
			$nbcreated++;

			if ($sendmailenabled && !empty($member->email)) {
				$listofpaths = array();
				$listofnames = array();
				$listofmimes = array();

				if (is_object($member->invoice)) {
					$invoicediroutput = $conf->facture->dir_output;
					$fileparams = dol_most_recent_file($invoicediroutput.'/'.$member->invoice->ref, preg_quote($member->invoice->ref, '/').'[^\-]+');
					if (!empty($fileparams['fullname'])) {
						$file = $fileparams['fullname'];
						$listofpaths = array($file);
						$listofnames = array(basename($file));
						$listofmimes = array(dol_mimetype($file));
					}
				}

				$subject = $langs->transnoentitiesnoconv('MembershipPaid', dol_print_date($datesubend, 'day'));
				$text = $membertype->getMailOnSubscription();

				$langcode = empty($member->default_lang) ? $langs->defaultlang : $member->default_lang;
				if (empty($templatecache[$langcode])) {
					$templatecache[$langcode] = array('subject' => '', 'content' => '');
					if (!empty($templatelabel)) {
						$outputlangs = new Translate('', $conf);
						$outputlangs->setDefaultLang($langcode);
						$outputlangs->loadLangs(array('main', 'members'));
						$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'member', $user, $outputlangs, 0, 1, $templatelabel);
						if (is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
							$templatecache[$langcode]['subject'] = (string) $arraydefaultmessage->topic;
							$templatecache[$langcode]['content'] = (string) $arraydefaultmessage->content;
						}
					}
				}

				$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $member);
				complete_substitutions_array($substitutionarray, $langs, $member);
				if (!empty($templatecache[$langcode]['subject'])) {
					$subject = make_substitutions($templatecache[$langcode]['subject'], $substitutionarray, $langs);
				}
				if (!empty($templatecache[$langcode]['content'])) {
					$text = make_substitutions(dol_concatdesc($templatecache[$langcode]['content'], $membertype->getMailOnSubscription()), $substitutionarray, $langs);
				}

				$ressend = $member->sendEmail($text, $subject, $listofpaths, $listofmimes, $listofnames);
				if ($ressend > 0) {
					$nbsentemail++;
				} else {
					$nbemailfailed++;
					$nberrors++;
					setEventMessages($langs->trans('MassSubInvoiceEmailSendFailed', $member->email), array($member->error), 'warnings');
				}
				if ($emaildelayms > 0) {
					usleep($emaildelayms * 1000);
				}
			} elseif ($sendmailenabled) {
				$nbemailmissing++;
				setEventMessages($langs->trans('MassSubInvoiceEmailNoRecipient', $member->ref), null, 'warnings');
			}
		}

		setEventMessages($langs->trans('XSubsriptionCreated', $nbcreated), null, 'mesgs');
		if ($sendmailenabled) {
			setEventMessages($langs->trans('XEmailSent', $nbsentemail), null, 'mesgs');
			if ($nbemailfailed > 0) {
				setEventMessages($langs->trans('MassSubInvoiceEmailFailedCount', $nbemailfailed), null, 'warnings');
			}
			if ($nbemailmissing > 0) {
				setEventMessages($langs->trans('MassSubInvoiceEmailMissingCount', $nbemailmissing), null, 'warnings');
			}
		}
		if ($nberrors > 0) {
			setEventMessages($langs->trans('MassSubInvoiceEmailErrors', $nberrors), null, 'warnings');
		}

		return 0;
	}
}
