<?php
if(isset($settings['ldap'])) {
  $external_lookup_sources['ldap'] = function($number) {
    global $settings;
    $ds = ldap_connect($settings['ldap']['server']);
    $r = ldap_bind($ds);
    $query = "(|(".implode("=*$number)(", $settings['ldap']['number_fields'])."=*".$number."))";
    if(isset($_REQUEST['debug'])) {error_log("LDAP query: $query");}
    $sr = ldap_search($ds, $settings['ldap']['base_dn'], $query);
    $results = ldap_get_entries($ds, $sr);
    if(isset($_REQUEST['debug'])) {error_log("LDAP returned ".var_export($results, true));}
    if($results['count'] > 0) {
      foreach($results[0] as $key=>$values) {
        $result[$key] = $values[0];
      }
      if(isset($settings['ldap']['first_name_field']) && isset($result[$settings['ldap']['first_name_field']])) {
        $result['first_name'] = $result[$settings['ldap']['first_name_field']];
      }
      if(isset($settings['ldap']['last_name_field']) && isset($result[$settings['ldap']['last_name_field']])) {
        $result['last_name'] = $result[$settings['ldap']['last_name_field']];
        $result['effective_caller_id_name'] = $result['first_name']." ".$result['last_name'];
      }
      return $result;
    } else {
      return false;
    }
  };
}
