<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//additional includes
require_once "resources/header.php";
require_once "resources/paging.php";
require_once __DIR__."/../billing/resources/utils.php";

if(isset($_FILES["csv"])) {
	echo "<pre>";
	$fp = fopen($_FILES['csv']['tmp_name'], 'r+');
	while(($row = fgetcsv($fp, null, ",")) !== FALSE) {
		$contact_uuid = uuid();
		do_sql($db, "INSERT INTO v_contacts(contact_uuid, domain_uuid, contact_name_given) VALUES (:contact_uuid, :domain_uuid, :contact_name_given)", array(
			':contact_uuid' => $contact_uuid,
			':domain_uuid' => $domain_uuid,
			':contact_name_given' => $row[2]
		));
		do_sql($db, "INSERT INTO v_contact_phones (contact_phone_uuid, domain_uuid, contact_uuid, phone_primary, phone_number) VALUES (:contact_phone_uuid, :domain_uuid, :contact_uuid, 1, :phone_numer)", array(
			':contact_uuid' => $contact_uuid,
			':contact_phone_uuid' => uuid(),
			':domain_uuid' => $domain_uuid,
			':phone_numer' => $row[0]
		));
		print_r($row);
	}
	echo "</pre>";
	echo "Well that was inefficient!";
}


require 'footer.php';
