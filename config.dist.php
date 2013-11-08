<?php
if (!defined('APPROVED_ENDPOINT')) { die('Not allowed to run this on its own'); }

// Set the timezone
date_default_timezone_set('America/New_York');

// Where this is installed.  Set it to the base URL that maps to the webroot.
$BASE_URL = "http://example.com";

// Debug log: log things to this file, if set
$DEBUG_LOG = FALSE;

// The IT coordinator receives all emails sent by this webservice if set here
$IT_COORDINATOR_EMAIL = "";
// The phone number that should be called if everything is going wrong
$FALLBACK_PHONE = '';
// The email address from which this service appears to send emails
$FROM_EMAIL = 'ehhop.clinic@mssm.edu';

// Open the AMION google spreadsheet and extract the "key" parameter from its URL
$SPREADSHEET_KEY = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
// When this spreadsheet is published to the web this is how it can be grabbed as a CSV file
$SPREADSHEET_URL = 
  "https://docs.google.com/spreadsheet/pub?key=$SPREADSHEET_KEY&single=true&gid=%d&output=csv";

// Map the gid parameter in the URL above to the schedule and contact worksheets
$WORKSHEET_NUMS = array('schedule'=>0, 'TS'=>4, 'CM'=>5);
// Map the columns in the contact worksheets to fields
$CONTACT_COLS = array('name'=>1, 'email'=>2, 'phone'=>3, 'chief'=>4);

// What do the options in the phone tree mean?
$INTENTIONS = array(
  FALSE, // No option 0.
  array("patient coming in TODAY", "CM"), // 1) what goes in the subject 2) role receiving VM
  array("NEW patient", "CM"),
  array("ESTABLISHED patient", "TS"),
  array("ESTABLISHED patient", "CM"),
);

// How to construct the email to the CMs or TSes
$EMAIL_TEMPLATE = <<<EMAIL
This message can be played from the following URL:

%s
  
** Please DO NOT reply to this email--call the patient back using the contact information within the voicemail message. **

Thanks,
The EHHapp
EMAIL;
