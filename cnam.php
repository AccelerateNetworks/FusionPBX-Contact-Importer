<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once __DIR__."/utils.php";

$settings = json_decode(file_get_contents(__DIR__."/settings.json"), true);

if(in_array($_SERVER['REMOTE_ADDR'], $settings['authorized_hosts']) && isset($_REQUEST['number'])) {
	$number = $_REQUEST['number'];
	if(substr($_REQUEST['number'], 0, 1) != "1") {
		$number = "1".$_REQUEST['number'];
	}
	// First check if we already know about them
	$result = do_sql($db, "SELECT v_contacts.contact_name_given, v_contacts.contact_name_family FROM v_contacts, v_contact_phones WHERE v_contact_phones.contact_uuid = v_contacts.contact_uuid AND v_contact_phones.phone_number = :number LIMIT 1", array(':number' => $_REQUEST['number']));
	if(count($result) > 0) {
		echo $result[0]['contact_name_given']." ".$result[0]['contact_name_family'];
	} else {
		// Gotta do a lookup :/
		$lookup = new SimpleXMLElement(file_get_contents(sprintf($settings['cnam_api'], $number)));
		$cnam = $lookup->results->result->name;
		$fname = $cnam;
		$lname = "";
		$exploded = explode(" ", $cnam);
		if(count($exploded) == 2) {
			$fname = ucfirst($exploded[0]);
			$lname = ucfirst($exploded[1]);
		}
		$contact_uuid = uuid();
		do_sql($db, "INSERT INTO v_contacts(contact_uuid, domain_uuid, contact_name_given, contact_name_family) VALUES (:contact_uuid, :domain_uuid, :contact_name_given, :contact_name_family)", array(
			':contact_uuid' => $contact_uuid,
			':domain_uuid' => $settings['default_domain'],
			':contact_name_given' => $fname,
			':contact_name_family' => $lname
		));
		do_sql($db, "INSERT INTO v_contact_phones (contact_phone_uuid, domain_uuid, contact_uuid, phone_primary, phone_number) VALUES (:contact_phone_uuid, :domain_uuid, :contact_uuid, 1, :phone_numer)", array(
			':contact_uuid' => $contact_uuid,
			':contact_phone_uuid' => uuid(),
			':domain_uuid' => $settings['default_domain'],
			':phone_numer' => $number
		));
		echo $fname." ".$lname;
	}
} else {
	echo "UNAUTHORIZED";
}
