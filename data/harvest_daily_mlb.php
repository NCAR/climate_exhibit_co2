<?php
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
    exit();
} 
$max_value_amt = 500;
$file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_MLB_most_recent.lhr"; 
//$file = '/web/sparkapps/climate_exhibit_co2/data/nwr.txt'; // for testing
$f = fopen($file, 'r');

$lineCtr = 0;
$a_last_lines = array();
// must skip text at top of file
$lineCtr = 0;

while(!feof($f))
{
    $line = fgets($f, 4096);
    if($lineCtr > 29){
        // format the value
        $a_data = explode(" ",$line);
        // only proceed if the array is the proper lenth
        if(isset($a_data[8])){
            $co2_value = trim($a_data[8]);
            if($co2_value != 'NaN' && $co2_value > 0){       
                $month = str_pad($a_data[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($a_data[3], 2, '0', STR_PAD_LEFT);
                $hour = str_pad($a_data[4], 2, '0', STR_PAD_LEFT);
                $new_date = $a_data[1].'-'.$month.'-'.$day.'T'.$hour.':00:00';

                // the formatted value to be saved
                $new_value = $new_date."\t".$co2_value."\n";

                // add into holder array, but only keep most recent $max_value_amt
                array_push($a_last_lines, $new_value);
                if (count($a_last_lines)>$max_value_amt){
                   array_shift($a_last_lines);
                }
            }
        } else {
            echo 'offset does not exist: '.print_r($a_data);    
        }
    }
    $lineCtr++;
}
fclose($f); 

// save to local data if valid
$file = "/web/sparkapps/climate_exhibit_co2/data/mlb.tsv"; 
// get our data for comparison
$f = fopen($file, 'r');
$a_data_tail = array();
while(!feof($f))
{
    $line = fgets($f, 4096);
    // just get hour
    $a_parts = explode("\t",$line);
    array_push($a_data_tail, $a_parts[0]);
}
fclose($f); 

// check to see if any of the values in $a_last_lines are in $a_data_tail
// if so, remove them from $a_last_lines
foreach($a_last_lines as $key=>$value_new){
    // just compare hour
    $a_parts = explode("\t",$value_new);
    if(in_array($a_parts[0],$a_data_tail)){
        // it exists so remove it
        $found_key = array_search($value_new,$a_last_lines);
        unset($a_last_lines[$key]);
    }
}
unset($a_data_tail);
echo "Opening local data for writing.\r\n";
$f = fopen($file, 'a');

// cycle through the last $max_value_amt values
// if the value does not exist in the file and is valid
// then add to the bottom
if(!empty($a_last_lines)){
    foreach($a_last_lines as $key=>$value){
         fwrite($f,$value);
         echo "Added $value to end of file $file.\r\n";
    }
} else {
    echo "There are no valid values to add.\r\n";
}

fclose($f); 

unset($a_last_lines);
?>