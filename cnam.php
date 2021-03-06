<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once __DIR__."/utils.php";

$settings = array();
$settings['authorized_hosts'] = array("127.0.0.1");
$settings['caller_id_number'] = "caller_id_number";

$result = array("error" => "Something is VERY broken!");

if(file_exists("./settings.php")) {
	if(isset($_REQUEST['debug'])) {
		error_log("Including ./settings.php\n");
	}
	require_once "./settings.php";
}

$external_lookup_sources = array();
if(isset($settings['providers'])) {
	// read $settings['providers'] in order, to allow prioritization
	foreach($settings['providers'] as $provider) {
		if(is_file("providers/".$provider.".php")) {
			if(isset($_REQUEST['debug'])) {
				error_log("Including providers/".$provider.".php\n");
			}
			require("providers/".$provider.".php");
		}
	}
} else {
	foreach(scandir("providers/") as $provider) {
		if(is_file("providers/".$provider)) {
			if(isset($_REQUEST['debug'])) {
				error_log("Including providers/".$provider."\n");
			}
			require("providers/".$provider);
		}
	}
}

function do_external_lookup($number) {
	global $external_lookup_sources;
	foreach($external_lookup_sources as $provider=>$lookup_function) {
		$external_result = $lookup_function($number);
		if(isset($_REQUEST['debug'])) {
			error_log("Got result from $provider for $number.");
			error_log(print_r($external_result, true));
		}

		if(is_array($external_result) && !isset($external_result['provider'])) {
			$external_result['provider'] = $provider;
			return $external_result;
		}
	}
}

function do_lookup($number, $domain, $call_uuid=NULL) {
	global $db, $settings;
	$number = ltrim($number, '+');
	$lookup_result = array("error" => "Something went wrong in do_lookup()");
	if(substr($number, 0, 1) != "1") {
		$number = "1".$number;
	}
	// First check if we already know about them
	$db_lookup = do_sql($db, "SELECT v_contacts.contact_name_given, v_contacts.contact_name_family FROM v_contacts, v_contact_phones WHERE v_contact_phones.contact_uuid = v_contacts.contact_uuid AND v_contact_phones.phone_number = :number LIMIT 1", array(':number' => $number));
	if(count($db_lookup) > 0) {
		$lookup_result = $db_lookup[0];
		$lookup_result['effective_caller_id_name'] = $db_lookup[0]['contact_name_given']." ".$db_lookup[0]['contact_name_family'];
	} else {
		$lookup_result = do_external_lookup($number);
		if(!is_null($domain) && $domain != "_undef_" && trim($cnam) != "") {
			try {
				$note = "Automatically added by the Contact Importer. Data from ".$lookup_result['provider'];
				$contact_uuid = uuid();
				do_sql($db, "INSERT INTO v_contacts(contact_uuid, domain_uuid, contact_name_given, contact_name_family, contact_note) VALUES (:contact_uuid, :domain_uuid, :contact_name_given, :contact_name_family, :note)", array(
					':contact_uuid' => $contact_uuid,
					':domain_uuid' => $domain,
					':contact_name_given' => $lookup_result['first_name'],
					':contact_name_family' => $lookup_result['last_name'],
					':note' => $note
				));
				do_sql($db, "INSERT INTO v_contact_phones (contact_phone_uuid, domain_uuid, contact_uuid, phone_primary, phone_number) VALUES (:contact_phone_uuid, :domain_uuid, :contact_uuid, 1, :phone_numer)", array(
					':contact_uuid' => $contact_uuid,
					':contact_phone_uuid' => uuid(),
					':domain_uuid' => $domain,
					':phone_numer' => $number
				));
			} catch(Exception $e) {
				error_log("Failed to insert $fname $lname into the database: ".$e->getMessage());
			}
		}
	}
	return $lookup_result;
}

$result = array("error" => "Something is broken");
if(in_array($_SERVER['REMOTE_ADDR'], $settings['authorized_hosts'])) {
	if(isset($_REQUEST['number'])) {
		if(substr($number, 0, 2) == "1 ") {
			$number = "+1".substr($number, 2);
		}
		$result = do_lookup($_REQUEST['number'], $settings['default_domain']);
	} elseif(isset($_REQUEST['call'])) {
		$result = array("error" => "Error doing CNAM lookup!");
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if (!$fp) {
			$result['error'] = "Failed to connect to event socket";
		} else {
			$number = trim(event_socket_request($fp, "api uuid_getvar ".$_REQUEST['call']." ".$settings['caller_id_number']));
			$domain = trim(event_socket_request($fp, "api uuid_getvar ".$_REQUEST['call']." domain_uuid"));
			if(isset($_GET['outbound'])) {
				$number = trim(event_socket_request($fp, "api uuid_getvar ".$_REQUEST['call']." callee_id_number"));
			}
			if(substr($number, 0, 4) == "-ERR") {
				error_log("Error from freeswitch when getting caller_id_number (".$number.") for call ".$_REQUEST['call']);
				die("UNKNOWN");
			} if(substr($domain, 0, 4) == "-ERR") {
				error_log("Error getting domain for call ".$_REQUEST['call']);
				if(isset($settings['default_domain'])) {
					$domain = $settings['default_domain'];
				} else {
					$domain = NULL;
					error_log("No default domain! We wont store this result :(");
				}
			}
			$result = do_lookup($number, $domain, $_REQUEST['call']);
		}
	} else {
		$result = array("error" => "neither number nor call specified");
	}
} else {
	$result = array("error" => $_SERVER['REMOTE_ADDR']." not whitelisted!");
}

$format = "pbx";
if(isset($_REQUEST['format'])) {
	$format = $_REQUEST['format'];
}
switch($format) {
	case "json":
		echo json_encode($result);
	break;
	default:
		if(isset($result['error'])) {
			echo "ERROR: ".$result['error'];
		} elseif(isset($result['effective_caller_id_name'])) {
			echo $result['effective_caller_id_name'];
		}
	break;
}
