<?php
if(isset($settings['hangouts'])) {
  $external_lookup_sources['hangouts'] = function($number) {
    exec(__DIR__."/../env/bin/python ".__DIR__."/../caller-id.py +".$number, $hangouts_json);
    $hangouts_result = json_decode(implode("\n", $hangouts_json), true);
    if(isset($hangouts_result['entity'][0]['properties']['display_name'])) {
      $out = array();
      $out['effective_caller_id_name'] = $hangouts_result['entity'][0]['properties']['display_name'];
      $out['first_name'] = $hangouts_result['entity'][0]['properties']['first_name'];
      $exploded = explode($out['first_name'], $out['effective_caller_id_name']);
      $out['last_name'] = trim($exploded[count($exploded)-1]);
      if(isset($_REQUEST['debug'])) {error_log(print_r($out, true));}
      return $out;
    } else {
      return false;
    }
  };
}
