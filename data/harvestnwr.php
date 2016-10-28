<?php
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
    exit();
} 
$a_final = array();
 $file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_NWR_most_recent.lhr"; 
 //$file = 'nwr.txt';
 $f = fopen($file, 'r');
 $last_line = '';
 $lineCtr = 0;
    while(!feof($f))
    {
        $line = fgets($f, 4096);
        if($lineCtr > 29){
            $a_data = explode(" ",$line);
            $co2_value = trim($a_data[10]);
            
            if($co2_value != 'NaN' && $co2_value > 0){
                $month = str_pad($a_data[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($a_data[3], 2, '0', STR_PAD_LEFT);
                $hour = str_pad($a_data[4], 2, '0', STR_PAD_LEFT);
                $new_date = $a_data[1].'-'.$month.'-'.$day.'T'.$hour.':00:00';

                $new_value = $new_date."\t".$co2_value."\n";
               $a_final[] = $new_value;
            }
        }
        $lineCtr++;
        
    }
fclose($f); 



// save to new file
$file = 'newdata.tsv';
$f = fopen($file, 'a');

// cycle through the last $max_value_amt values
// if the value does not exist in the file and is valid
// then add to the bottom
foreach($a_final as $key=>$value){
    fwrite($f,$value);
    echo "Added $value to end of file $file.\r\n";
}
fclose($f); 

?>