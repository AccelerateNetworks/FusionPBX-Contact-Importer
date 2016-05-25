<?php
if(isset($settings['ldap'])) {
  $external_lookup_sources['ldap'] = function($number) {
    global $settings;
    $ds = ldap_connect($settings['ldap']['server']);
    $r = ldap_bind($ds);
    $query = "(|(".implode("=*$number)(", $settings['ldap']['number_fields'])."=*".$number."*))";
    if(isset($_REQUEST['debug'])) {error_log("LDAP query: $query");}
    $sr = ldap_search($ds, $settings['ldap']['ou'], $query);
    $results = ldap_get_entries($ds, $sr);
    if($results['count'] > 0) {
      $result = $results[0];
      if(isset($settings['ldap']['first_name_field']) && isset($result[$settings['ldap']['first_name_field']])) {
        $result['first_name'] = $result[$settings['ldap']['first_name_field']];
      }
      if(isset($settings['ldap']['last_name_field']) && isset($result[$settings['ldap']['last_name_field']])) {
        $result['last_name'] = $result[$settings['ldap']['last_name_field']];
        $result['effective_caller_id_name'] = $result['first_name']." ".$result['last_name'];
      }
      return $results[0];
    } else {
      return false;
    }
  };
}
