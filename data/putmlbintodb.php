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
  set_time_limit(0);
$sitecode = 'mlb';
// read in data from tsv
$a_final = array();
$file = $sitecode.".tsv"; 
$f = fopen($file, 'r+');
$last_line = '';
$counter = 0;
    while(!feof($f))
    {
       
       $line = fgets($f, 4096);
        if(!preg_match('#DATE#',$line)){
            // split by \n
            $a_line = explode("\n",$line);

            // split by /t
            $a_ary = explode("\t",$a_line[0]);
            if(isset($a_ary[1])){
                $new_line = array();
                $date = new DateTime($a_ary[0]);
                $new_line[] =$date->getTimestamp();
                $new_line[] = $a_ary[1];
                $a_final[] = $new_line;
                
                $counter++;
            }
            //if($counter > 350){
            //    break;
            //}
        }
        
    }
fclose($f); 
$server = mysql_connect($host, $username, $password);
$connection = mysql_select_db($database, $server);
foreach($a_final as $data){
    
    // first check if these values already exist
    $myquery = "SELECT * FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded='$data[0]'";
    
    $query = mysql_query($myquery);
    $numrows = mysql_num_rows($query);
    
    if($numrows == 0){
        $myquery = "INSERT INTO climate_co2_data(sitecode, co2_value, timestamp_co2_recorded) VALUES('$sitecode', '$data[1]', '$data[0]')";
        $query = mysql_query($myquery);
    }
    if ( ! $query ) {
        echo mysql_error();
        die;
    }
}
   
mysql_close($server);
?>