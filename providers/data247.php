<?php
if(isset($settings['data247'])) {
  $external_lookup_sources['data247'] = function($number) {
    global $settings;
    try {
      $xml = file_get_contents(sprintf($settings['data247']['url'], $number));
    } catch (Exception $e) {
      error_log("Exception while requesting data 24/7: ".$e->getMessage()." call_uuid=".$call_uuid." number=".$number." domain=".$domain." url=".$url);
      return false;
    }
    try {
      $lookup = new SimpleXMLElement($xml);
      $cnam = $lookup->results->result->name;
    } catch(Exception $e) {
      error_log("Exception parsing CNAM result: ".$e->getMessage()." call_uuid=".$call_uuid." number=".$number." domain=".$domain." url=".$url." response=".$xml);
      return false;
    }
    $fname = $cnam;
    $lname = "";
    $exploded = explode(" ", $cnam);
    if(count($exploded) == 2) {
      $fname = ucfirst($exploded[0]);
      $lname = ucfirst($exploded[1]);
    }
    return array("first_name" => $fname, "last_name" => $lname);
  };
}
