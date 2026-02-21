<?php
/* Copyright (C) 2026 */

/**
 * Prepare admin pages header.
 *
 * @return array<int,array<int,string>>
 */
function masssubscriptionbatchAdminPrepareHead()
{
	global $langs;

	$langs->load('masssubscriptionbatch@masssubscriptionbatch');

	$head = array();
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/custom/masssubscriptionbatch/admin/setup.php';
	$head[$h][1] = $langs->trans('Settings');
	$head[$h][2] = 'settings';
	$h++;

	return $head;
}
