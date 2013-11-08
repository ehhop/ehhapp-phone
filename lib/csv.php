<?php
/**
 * Helper class for parsing CSV files in an MS Excel-compatible manner
 * 
 */
class CSV {

  public static function row($fields, $separator=',', $enclosure='"') {
    $sepregex = preg_quote($separator);
    $encregex = preg_quote($enclosure);
    foreach ($fields as $index=>$field) {
      if (preg_match("/^\\s+|$sepregex|$encregex|\\n|\\s+$/", $field)) {
        $fields[$index] = $enclosure . str_replace($enclosure, $enclosure.$enclosure, $field) . $enclosure;
      }
    }
    return join($fields, $separator)."\n";
  }
  
  public static function parse($csv, $check_headers=FALSE, $separator=FALSE, $quote=FALSE) {
    $lines = explode("\n", $csv);
    $handle = fopen("php://memory", 'r+'); 
    fwrite($handle, $csv); 
    rewind($handle);
    
    $fulltext = implode('', $lines);
    if (strlen($fulltext) - strlen(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $fulltext)) > 2) {
      throw new CSVException('BadCSV: Your CSV file must contain only plain text.');
    }
    $separator = $separator===FALSE ? ((strpos($lines[0], "\t") !== FALSE) ? "\t" : ',') : $separator;
     
    try {
      $rows = array();
      while (($data = fgetcsv($handle, 0, $separator, $quote===FALSE ? '"' : $quote)) !== FALSE) {
        while (end($data)==='' && count($data)) { array_pop($data); }
        // Convert any non-UTF-8 strings to UTF-8; assume they are ISO-8859-1 (latin)
        foreach ($data as $index=>$raw_string) {
          if (mb_detect_encoding($raw_string, array('ASCII', 'UTF-8', 'ISO-8859-1'))=='ISO-8859-1') { 
            $data[$index] = utf8_encode($raw_string);
          }
        }
        $rows[] = $data;
      }
      fclose($handle);
    } catch (Exception $e) {
      throw new CSVException($e->getMessage());
    }
    
    return $rows;
  }

}

class CSVException extends Exception { 
}
