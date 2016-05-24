<?php // content="text/plain; charset=utf-8"
error_reporting(E_ALL);
ini_set('memory_limit', '1024M'); // or you could use 1G
// Example on how to treat and format timestamp as human readable labels
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $baseurl = 'J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\';
    $path = 'J:\\Sharon\\xampp\\htdocs\\libraries\\php\\jpgraph\\src\\';
    require 'J:\\Sharon\\db\\credentials\\credentials.php';
    $imagedir = "J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\assets\\";
} else {    
   $host = 'sql.ucar.edu';
    $baseurl = '/web/sparkapps/climate_exhibit_co2/';
    $path = "/web/sparkapps/libraries/php/jpgraph/src/";
    $imagedir = "/web/sparkapps/climate_exhibit_co2/assets/";
  
    require '/home/sclark/db/credentials/credentials.php';
}
date_default_timezone_set ("America/Denver");
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

function doit($mysqli,$sitecode){
    $myquery = "SELECT sitecode, co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data WHERE active='1' AND sitecode='".$sitecode."' ORDER BY sitecode";
    $query = $mysqli->query($myquery);

    if ( ! $query ) {
        echo $mysqli->error;
        exit;
    }
    $numrows = $query->num_rows;
    echo '<table><tr><th>Site</th><th>Value</th><th>Timestamp</th></tr>';
    if($numrows > 0){
        while ($data = $query->fetch_array()) {
            echo '<tr><td>'.$data['sitecode'].'</td>';   
            echo '<td>'.$data['value'].'</td>';   
            echo '<td>'.date('Y-m-d H:i:s', $data['timestamp']).'</td></tr>';   
        }
    }
    echo '</table>';
}
?>
<style>
    th, td {
        border:1px solid black;
        padding:4 2;
    }
</style>
<?php
//doit($mysqli,'mlb');
doit($mysqli,'nwr');
//doit($mysqli,'mlo');
?>