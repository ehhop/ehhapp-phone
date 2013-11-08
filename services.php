<?php
define('APPROVED_ENDPOINT', TRUE);

require 'lib/Wsdl_webservice.php';
require 'lib/csv.php';
if (!file_exists('config.php')) {
  echo 'Copy config.dist.php --> config.php and ensure appropriate values have been set.';
  exit;
}
require 'config.php';

class Webservice { 

  private $worksheets = array();
  
  function __construct() {
    $this->_fetch_worksheets();
  }

  /**
   * Sends an email to the oncall CMs or TSes about an incoming voicemail from a patient.
   *
   * @param int $intent
   * @param string $ani
   * @param string $nearest_sat
   * @param string $recording_url
   * @return bool
   **/
  function voicemail_alert($intention, $ani, $nearest_saturday, $recording_url) {
    global $DEBUG_LOG, $IT_COORDINATOR_EMAIL, $INTENTIONS, $EMAIL_TEMPLATE, $FROM_EMAIL;
    
    if (isset($DEBUG_LOG) && $DEBUG_LOG) {
      file_put_contents($DEBUG_LOG, var_export(func_get_args(), TRUE), FILE_APPEND);
    }
    
    if ($intention >= count($INTENTIONS) || !is_array($INTENTIONS[$intention])) {
      return FALSE;
    }
    if (!$recording_url) { return FALSE; }
    
    list($patient_type, $target_type) = $INTENTIONS[$intention];
    // IT coordinator, by default, gets all emails
    $to = $IT_COORDINATOR_EMAIL ? array($IT_COORDINATOR_EMAIL) : array();
    $subject = "EHHOP voicemail from $patient_type, caller ID $ani";
    $on_call = $this->_get_oncall_people($target_type, $nearest_saturday);
    $target_name = $on_call[array_rand($on_call)];
    if ($target_name) {
      $subject = "$target_name assigned $subject";
      $to[] = $this->_get_email_for($target_name);
    }
    $chief_email = $this->_get_chief_email($target_type);
    if ($chief_email) { $to[] = $chief_email; }
    $to = implode(', ', array_unique($to));
    $message = sprintf($EMAIL_TEMPLATE, $recording_url);
    
    // $to      = $IT_COORDINATOR_EMAIL; // temporary override while testing
    $headers = "From: EHHOP Clinic <$FROM_EMAIL>\r\n" .
               "Reply-To: EHHOP IT Coordinator <$IT_COORDINATOR_EMAIL>\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    if (isset($DEBUG_LOG) && $DEBUG_LOG) {
      file_put_contents($DEBUG_LOG, "Sent email to $to; subject $subject", FILE_APPEND);
    }
    
    return mail($to, $subject, $message, $headers);
  }
  
  /**
   * Returns a phone number for a CM that is currently on call for the date specified
   *
   * @param string $nearest_saturday
   * @return string
   **/
  function get_oncall_CM_phone($nearest_saturday) {
    global $FALLBACK_PHONE;
    $on_call = $this->_get_oncall_people("CM", $nearest_saturday);
    if (count($on_call) == 0) { return $FALLBACK_PHONE; }
    $phone_num = '';
    while (!$phone_num && count($on_call) > 0) {
      $i = array_rand($on_call);
      $target_name = reset(array_splice($on_call, $i, 1));
      $phone_num = $this->_get_phone_for("CM", $target_name);
    }
    return $phone_num ? $phone_num : $FALLBACK_PHONE;
  }
  
  
  private function _fetch_worksheets() {
    global $SPREADSHEET_URL, $WORKSHEET_NUMS;
    foreach ($WORKSHEET_NUMS as $label=>$num) {
      $url = sprintf($SPREADSHEET_URL, $num);
      $csv = file_get_contents($url); // grab contents at URL as string
      $this->worksheets[$label] = CSV::parse($csv);
    }
  }
  
  private function _get_oncall_people($type, $nearest_sat) {
    $nearest_sat = DateTime::createFromFormat('n/j/Y', $nearest_sat);
    $ret = "";
    
    if (count($this->worksheets['schedule']) < 4) {
      throw new WebserviceException('The AMION spreadsheet is formatted incorrectly: too few rows!');
    }
    $col_nums = array();
    $on_call = array();
    $date_col = 0;
    $min_diff = PHP_INT_MAX;
    
    // Find column numbers for this oncall person type
    foreach ($this->worksheets['schedule'][3] as $col_num => $header) {
      if (strpos($header, "On Call Medical Clinic $type") !== FALSE) {
        $col_nums[] = $col_num;
      }
      if (strtolower($header) == "date") { $date_col = $col_num; }
    }
    
    foreach ($this->worksheets['schedule'] as $row) {
      $date = DateTime::createFromFormat('n/j/Y', $row[$date_col]);
      if ($date !== FALSE && $date->diff($nearest_sat)->days < $min_diff) {
        $min_diff = $date->diff($nearest_sat)->days;
        foreach ($col_nums as $i => $col_num) {
          if (strlen(trim($row[$col_num])) > 0) { $on_call[$i] = $row[$col_num]; }
        }
      }
    }
    
    return array_filter($on_call);
  }
  
  private function _get_contact_row_for($type, $name) {
    global $CONTACT_COLS;
    $min_diff = PHP_INT_MAX;
    $best_row = NULL;
    if (!isset($this->worksheets[$type])) { return FALSE; }
    foreach ($this->worksheets[$type] as $row) {
      $trimmed = strtolower(trim($row[$CONTACT_COLS['name']]));
      if (strlen($trimmed) > 0) {
        // Allow for some small typos (people drop hyphens, add spaces)
        $levenshtein_distance = levenshtein(strtolower($name), $trimmed);
        if ($levenshtein_distance < $min_diff) { 
          $best_row = $row;
          $min_diff = $levenshtein_distance;
        }
      }
    }
    return $best_row;
  }
  
  private function _get_chief_row($type) {
    global $CONTACT_COLS;
    $best_row = NULL;
    if (!isset($this->worksheets[$type])) { return FALSE; }
    foreach ($this->worksheets[$type] as $row) {
      $trimmed = strtoupper(trim($row[$CONTACT_COLS['chief']]));
      if (strlen($trimmed) > 0 && "CHIEF $type" == $trimmed) { $best_row = $row; }
    }
    return $best_row;
  }
  
  private function _get_phone_for($type, $name) {
    global $CONTACT_COLS;
    $best_row = $this->_get_contact_row_for($type, $name);
    return $best_row ? $best_row[$CONTACT_COLS['phone']] : '';
  }
  
  private function _get_email_for($type, $name) {
    global $CONTACT_COLS;
    $best_row = $this->_get_contact_row_for($type, $name);
    return $best_row ? $best_row[$CONTACT_COLS['email']] : '';
  }
  
  private function _get_chief_email($type) {
    global $CONTACT_COLS;
    $best_row = $this->_get_chief_row($type);
    return $best_row ? $best_row[$CONTACT_COLS['email']] : '';
  }
  
}

class WebserviceException extends Exception {}

$params = array(
  'namespace'=>$BASE_URL, 
  'description'=>'EHHOP phone-related webservices', 
  'options'=>array('uri'=>$BASE_URL, 'encoding'=>SOAP_ENCODED), 
  'classname'=>'Webservice'
);
$server = new WSDL_Webservice($params);
$server->handle($_SERVER['QUERY_STRING']);

// $ws = new Webservice;
// echo $ws->get_oncall_CM_phone('9/14/2013');

?>
