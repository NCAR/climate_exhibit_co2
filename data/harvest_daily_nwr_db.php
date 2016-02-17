<?php
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        require 'J:\\Sharon\\db\\credentials\\credentials.php';
    } else {    
        require '/home/sclark/db/credentials/credentials.php';
    }
$sitecode = 'nwr';
$max_value_amt = 50;
$file = "http://www.eol.ucar.edu/homes/stephens/RACCOON/NCAR_NWR_most_recent.lhr"; 
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
            $co2_value = trim($a_data[10]);
            if($co2_value != 'NaN' && $co2_value > 0){   
                $a_new_data = array();
                $month = str_pad($a_data[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($a_data[3], 2, '0', STR_PAD_LEFT);
                $hour = str_pad($a_data[4], 2, '0', STR_PAD_LEFT);
                $new_date = $a_data[1].'-'.$month.'-'.$day.'T'.$hour.':00:00';

                $date = new DateTime($new_date);
                $a_new_data[] = $date->getTimestamp();
                $a_new_data[] = $co2_value;
                    
                array_push($a_last_lines, $a_new_data);
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


//open db
$server = mysql_connect($host, $username, $password);
$connection = mysql_select_db($database, $server);

foreach($a_last_lines as $key=>$value_new){
    // for each value check if it exists in db
    $myquery = "SELECT * FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded='$value_new[0]'";
    $query = mysql_query($myquery);
    $numrows = mysql_num_rows($query);

    // if value does not exit in db then add it
    if($numrows == 0){
        $myquery = "INSERT INTO climate_co2_data(sitecode, co2_value, timestamp_co2_recorded) VALUES('$sitecode', '$value_new[1]', '$value_new[0]')";
        $query = mysql_query($myquery);
        echo "Added ".date('Y-m-d H:m:i',$value_new[0])." - $value_new[1] for $sitecode to db.\r\n";
    } else {
        echo "Value already exists on ".date('Y-m-d H:m:i',$value_new[0])." for $sitecode.\r\n";
    }
    if ( ! $query ) {
        echo mysql_error();
        die;
    }
}

// close db
mysql_close($server);

unset($a_last_lines);
?>