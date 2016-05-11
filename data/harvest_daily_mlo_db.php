<?php
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        require 'J:\\Sharon\\db\\credentials\\credentials.php';
    } else {    
        require '/home/sclark/db/credentials/credentials.php';
    }
$sitecode = 'mlo';
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
    //Retrieved value is valid, processing
    
    // in form of value, DD-MMM-YYYY
    // so split and save in correct form
    $a_data = explode(',',$output);
    $a_new_date = explode('-',trim($a_data[1]));
    $date = date_parse($a_new_date[1]);
    $new_date = $a_new_date[2].'-'.str_pad($date['month'], 2, '0', STR_PAD_LEFT).'-'.$a_new_date[0].'T12:00:00';

    $co2_value = trim($a_data[0]);
    
    $date = new DateTime($new_date);
    $a_new_data[] = $date->getTimestamp();
    $a_new_data[] = $co2_value;
    
    //open db
    $server = mysql_connect($host, $username, $password);
    $connection = mysql_select_db($database, $server);

    // for each value check if it exists in db
    $myquery = "SELECT * FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded='$a_new_data[0]'";
    $query = mysql_query($myquery);
    $numrows = mysql_num_rows($query);
    
    // if value does not exit in db then add it
    if($numrows == 0){
        $myquery = "INSERT INTO climate_co2_data(sitecode, co2_value, timestamp_co2_recorded) VALUES('$sitecode', '$a_new_data[1]', '$a_new_data[0]')";
        $query = mysql_query($myquery);        
        echo "Added ".date('Y-m-d H:m:i',$a_new_data[0])." - $a_new_data[1] for $sitecode to db.\r\n";
    } else {
        echo "Value already exists on ".date('Y-m-d H:m:i',$a_new_data[0])." for $sitecode.\r\n";
    }
    if ( ! $query ) {
        echo mysql_error();
        die;
    }

    // close db
    mysql_close($server);
    
} else {
    echo "Value is empty or NaN\r\n";
}

?>