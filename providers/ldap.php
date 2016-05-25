<?php
if(isset($settings['ldap'])) {
  $external_lookup_sources[] = function($number) {
    $ds = ldap_connect($settings['ldap']['server']);
    $r = ldap_bind($ds);
    $query = "(|(".implode("), (", $settings['ldap']['number_fields'])."=*".$number."*))";
    $sr = ldap_search($ds, $settings['ldap']['ou'], $query);
    $results = ldap_get_entries($ds, $sr);
    if($results['count'] > 0) {
      $result = $results[0];
      if(isset($settings['ldap']['first_name_field']) && isset($result[$settings['ldap']['first_name_field']])) {
        $result['first_name'] = $result[$settings['ldap']['first_name_field']];
      }
      if(isset($settings['ldap']['last_name_field']) && isset($result[$settings['ldap']['last_name_field']])) {
        $result['last_name'] = $result[$settings['ldap']['last_name_field']];
      }
      return $results[0];
    } else {
      return false;
    }
  };
} elseif(isset($_REQUEST['debug'])) {
  error_log("Not using ldap.php");
}
