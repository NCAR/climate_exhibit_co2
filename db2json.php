<?php
if (php_sapi_name() != "cli") {
    // In cli-mode
 //   echo "Cannot execute.";
   // exit();
} 
error_reporting(E_ALL);
ini_set('memory_limit', '1024M'); // or you could use 1G
// vars
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $baseurl = 'J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\';
    require 'J:\\Sharon\\db\\credentials\\credentials.php';
    
    
    $imagedir = "J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\assets\\";
} else {    
   $host = 'sql.ucar.edu';
    
        $baseurl = '/web/sparkapps/climate_exhibit_co2/';
        $imagedir = "/web/sparkapps/climate_exhibit_co2/assets/";
  
    require '/home/sclark/db/credentials/credentials.php';
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
 
$a_range['x_low'] = strtotime("-1 month");
$a_range['x_high'] = time();
$a_data = [];

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


function readData($mysqli,$sitecode, $a_range, &$a_data){
    //$myquery = "SELECT sitecode, co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
    $myquery = "SELECT sitecode, co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data WHERE active='1'";
    $query = $mysqli->query($myquery);
    if ( ! $query ) {
        echo $mysqli->error;
        die;
    }

    $numrows = $query->num_rows;

    if($numrows > 0){
        
        for ($x = 0; $x < $numrows; $x++) {
            $a_temp = [];
            $f_add = true;
            $data = $query->fetch_assoc();
           /* if(!empty($a_range['x_low'])){
              if($data['timestamp'] < $a_range['x_low']) {
                  $f_add = false;
              } 
            }
            if(!empty($a_range['x_high'])){
              if($data['timestamp'] > $a_range['x_high']) {
                  $f_add = false;
              } 
            }
           */
            if($f_add == true){
                $a_temp['timestamp'] =$data['timestamp'];
                $a_temp['value'] = $data['value'];
                $a_temp['source'] = $data['sitecode'];
                $a_data[] = $a_temp;
            }
        }    
    $query->free(); 
    }

}

readData($mysqli,'mlo',$a_range, $a_data);

//open and put into file

//$file = "nwr.json"; 
echo json_encode($a_data);
//echo '[[1125007200, "378.173"], [1125010800, "379.096"], [1125014400, "379.483"], [1125018000, "379.735"], [1125021600, "379.118"], [1125025200, "379.225"], [1125028800, "379.478"], [1125032400, "379.187"], [1125036000, "378.96"], [1125039600, "379.096"], [1125043200, "379.576"], [1125046800, "379.367"], [1125050400, "379.169"], [1125054000, "377.665"], [1125057600, "377.004"], [1125061200, "376.746"]]';
//$fh = fopen($file, "w") or die("Could not open log file.");
//fwrite($fh, $text) or die("Could not write file!");
//fclose($fh);
?>