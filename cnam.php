<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once __DIR__."/utils.php";

$settings = json_decode(file_get_contents(__DIR__."/settings.json"), true);

function do_lookup($number, $domain) {
	error_log("Doing lookup for $number on domain $domain");
	global $db;
	if(substr($number, 0, 1) != "1") {
		$number = "1".$number;
	}
	// First check if we already know about them
	$result = do_sql($db, "SELECT v_contacts.contact_name_given, v_contacts.contact_name_family FROM v_contacts, v_contact_phones WHERE v_contact_phones.contact_uuid = v_contacts.contact_uuid AND v_contact_phones.phone_number = :number LIMIT 1", array(':number' => $number));
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
			':domain_uuid' => $domain,
			':contact_name_given' => $fname,
			':contact_name_family' => $lname
		));
		do_sql($db, "INSERT INTO v_contact_phones (contact_phone_uuid, domain_uuid, contact_uuid, phone_primary, phone_number) VALUES (:contact_phone_uuid, :domain_uuid, :contact_uuid, 1, :phone_numer)", array(
			':contact_uuid' => $contact_uuid,
			':contact_phone_uuid' => uuid(),
			':domain_uuid' => $domain,
			':phone_numer' => $number
		));
		echo $fname." ".$lname;
	}
}

if(in_array($_SERVER['REMOTE_ADDR'], $settings['authorized_hosts'])) {
	if(isset($_REQUEST['number'])) {
		do_lookup($_REQUEST['number'], $settings['default_domain']);
	} elseif(isset($_REQUEST['call'])) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if (!$fp) {
			die("ERROR");
		}
		$number = trim(event_socket_request($fp, "uuid_getvar ".$_REQUEST['call']." caller_id_number"));
		$domain = trim(event_socket_request($fp, "uuid_getvar ".$_REQUEST['call']." domain_uuid"));
		do_lookup($number, $domain);
	}
} else {
	echo "UNAUTHORIZED";
}
