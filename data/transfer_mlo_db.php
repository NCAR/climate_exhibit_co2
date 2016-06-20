<?php
/**
*   This harvests MLO (Mauna Loa) data
*   from db: climate_co2_data and climate_co2_data2
**/
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
    exit();
} 
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        require 'J:\\Sharon\\db\\credentials\\credentials.php';
    } else {    
        require '/home/sclark/db/credentials/credentials.php';
    }
$sitecode = 'mlo';


    //Retrieved value is valid, processing
    
    // in form of value, DD-MMM-YYYY
    // so split and save in correct form
   
    //open db
    $server = mysql_connect($host, $username, $password);
    $connection = mysql_select_db($database, $server);

    // retrieve all data from db 1
    $a_data = array();
    $a_new_data = array();
    $myquery = "SELECT * FROM climate_co2_data WHERE sitecode='$sitecode'";
    $query = mysql_query($myquery);
    $numrows = mysql_num_rows($query);
    if($numrows > 0){
        // put into array
        $a_new_data = array();
        while ($row = mysql_fetch_assoc($query)) {
            $a_new_data['timestamp_co2_recorded'] = $row['timestamp_co2_recorded'];
            $a_new_data['co2_value'] = $row['co2_value'];
            $a_new_data['sitecode'] = $row['sitecode'];
        }
        $a_data[] = $a_new_data;
    }
    
    // cycle through and check
    $arylen = count($a_data);
    for($i=0;$i<$arylen;$i++){
        // check new db to see if value exists
        $myquery = "SELECT * FROM climate_co2_data2 WHERE sitecode='".$a_data[$i]['sitecode']."' AND timestamp_co2_recorded='".$a_data[$i]['timestamp_co2_recorded']."'";
        $query = mysql_query($myquery);
        $numrows = mysql_num_rows($query);
        // if value does not exit in db then add it
        if($numrows == 0){
            $myquery = "INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES('".$a_data[$i]['sitecode']."', '".$a_data[$i]['co2_value']."', '".$a_data['timestamp_co2_recorded']."')";
            //$query = mysql_query($myquery);
            echo "Added ".date('Y-m-d H:m:i',$a_data['timestamp_co2_recorded'])." - $a_data[$i]['co2_value'] for ".$a_data[$i]['sitecode']." to db.\r\n";
        } else {
            echo "Value already exists on ".date('Y-m-d H:m:i',$a_data['timestamp_co2_recorded'])." for ".$a_data[$i]['sitecode'].".\r\n";
        }
        if ( ! $query ) {
            echo mysql_error();
            die;
        }
    }
    // close db
    mysql_close($server);
?>