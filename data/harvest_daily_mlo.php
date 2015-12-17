<?php

$source = "http://bluemoon.ucsd.edu/NOAA/mlo_daily.csv";
echo "Retrieving value from $source\r\n";
//open url and get value
// create curl resource 
$ch = curl_init(); 
// set url 
curl_setopt($ch, CURLOPT_URL, $source); 
//return the transfer as a string 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
// $output contains the output string 
$output = curl_exec($ch); 
// close curl resource to free up system resources 
curl_close($ch);   



// only bother if there is a value and it is not NAN
if(!empty($output) && !preg_match("/NaN/",$output)){
    
    echo "Retrieved value is valid, processing.\r\n";
    // in form of value, DD-MMM-YYYY
    // so split and save in correct form
    $a_data = explode(',',$output);
    $a_new_date = explode('-',trim($a_data[1]));
    $date = date_parse($a_new_date[1]);
    $new_date = $a_new_date[0].'-'.$date['month'].'-'.$a_new_date[2];
    $new_value = $new_date."\t".trim($a_data[0])."\n";
    // open local file
    $file = "/web/sparkapps/climate_exhibit_co2/data/mlo.tsv"; 

    // if this value isn't the last value in the file then append
    $line = '';

    echo "Opening local data for writing.\r\n";
    $f = fopen($file, 'r+');
    $last_line = '';
    while(!feof($f))
    {
       $last_line = $line;
       $line = fgets($f, 4096);
    }
    if($last_line != $new_value){
         fwrite($f,$new_value);
         echo "Added $new_value to end of file $file.\r\n";
    } else {
        echo "Value $new_value already exists at end of $file.  Skip adding.\r\n";
    }
    fclose($f); 
} else {
    echo "Value is empty or NaN\r\n";
}

?>