<?php
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
date_default_timezone_set("Etc/GMT");
$sitecode = 'nwr';
$max_value_amt = 300;
$file = "http://www.eol.ucar.edu/homes/stephens/RACCOON/NCAR_NWR_most_recent.lme"; 
//$file = "http://www.eol.ucar.edu/homes/stephens/RACCOON/NCAR_NWR_most_recent.lin"; 
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
                $a_new_data = array();
                $year = str_pad($a_data[1], 4, '0', STR_PAD_LEFT);
                $month = str_pad($a_data[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($a_data[3], 2, '0', STR_PAD_LEFT);
                $hour = str_pad($a_data[4], 2, '0', STR_PAD_LEFT);
                $min = str_pad($a_data[5], 2, '0', STR_PAD_LEFT);
                $sec = str_pad($a_data[6], 2, '0', STR_PAD_LEFT);
                
                $new_date = $year.'-'.$month.'-'.$day.'T'.$hour.':'.$min.':'.$sec;

                $date = new DateTime($new_date);
                $date->setTimeZone(new DateTimeZone("Etc/GMT"));
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
$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    // The connection failed. What do you want to do? 
    // You could contact yourself (email?), log the error, show a nice page, etc.
    // You do not want to reveal sensitive information

    // Let's try this:
    echo "Sorry, this website is experiencing problems.";

    // Something you should not do on a public site, but this example will show you
    // anyways, is print out MySQL error related information -- you might log this
    //echo "Error: Failed to make a MySQL connection, here is why: \n";
    //echo "Errno: " . $mysqli->connect_errno . "\n";
    //echo "Error: " . $mysqli->connect_error . "\n";
    
    // You might want to show them something nice, but we will simply exit
    exit;
}

foreach($a_last_lines as $key=>$value_new){
    // for each value check if it exists in db
    $myquery1 = "SELECT * FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded='$value_new[0]'";
    $query1 = $mysqli->query($myquery1);
    if ( ! $query1 ) {
        echo $mysqli->error;
        exit;
    }
    $numrows1 = $query1->num_rows;
    // if value does not exit in db then add it
    if($numrows1 == 0){
        $myquery2 = "INSERT INTO climate_co2_data(sitecode, co2_value, timestamp_co2_recorded) VALUES('$sitecode', '$value_new[1]', '$value_new[0]')";
        $query2 = $mysqli->query($myquery2);
        if ( ! $query2 ) {
            echo $mysqli->error;
            exit;
        }
        echo "Added ".date('Y-m-d H:i:s',$value_new[0])." - $value_new[1] for $sitecode to db.\r\n";
    } else {
        echo "Value already exists on ".date('Y-m-d H:i:s',$value_new[0])." for $sitecode.\r\n";
        $data = $query1->fetch_assoc();
        if($value_new[1] != $data['co2_value']){
            
            $myquery3 = "UPDATE climate_co2_data SET co2_value='$value_new[1]' WHERE sitecode='$sitecode' AND timestamp_co2_recorded='$value_new[0]'";
            
            $query3 = $mysqli->query($myquery3);
             if ( ! $query3 ) {
                echo $mysqli->error;
                exit;
            }
            
            echo "Updated ".date('Y-m-d H:i:s',$value_new[0])." - $value_new[1] for $sitecode to db.\r\n";
        } 
    }
}

// close db
$mysqli->close();

unset($a_last_lines);
?>