<?php 
/**
*   This creates images that are based off the most granular data intervals (approx every 3 mins)
*   from db: climate_co2_data2
**/
if (php_sapi_name() != "cli") {
    // In cli-mode
    //echo "Cannot execute.";
   // exit();
} 
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '1024M'); // or you could use 1G
$tempdir = 'temp/';
// vars
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $baseurl = 'J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\';
    $path = 'J:\\Sharon\\xampp\\htdocs\\libraries\\php\\pchart\\';
    $fontpath = 'J:\\Sharon\\xampp\\htdocs\\libraries\\php\\fonts\\';
    require 'J:\\Sharon\\db\\credentials\\credentials.php';
    $imagedir = "J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\assets\\";
} else {    
   $host = 'sql.ucar.edu';
    $baseurl = '/web/sparkapps/climate_exhibit_co2/';
    $path = "/web/sparkapps/libraries/php/pchart/";
    $fontpath = "/web/sparkapps/libraries/php/fonts/";
    $imagedir = "/web/sparkapps/climate_exhibit_co2/assets/";
  
    require '/home/sclark/db/credentials/credentials.php';
}

if(isset($argv)){
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
// require
/* Include all the classes */
include($path."class/pDraw.class.php");
include($path."class/pImage.class.php");
include($path."class/pData.class.php");
include($path."class/pScatter.class.php");
date_default_timezone_set ("Etc/GMT");

// set up params
// height
if(array_key_exists('height',$_GET) && !empty($_GET['height'])){
    $height = $_GET['height'];
} else {
    $height = 850;
}
// width
if(array_key_exists('width',$_GET) && !empty($_GET['width'])){
    $width = $_GET['width'];
} else {
    $width = 1350;
}
//margin top
if(array_key_exists('top',$_GET) && !empty($_GET['top'])){
    $margin_top = $_GET['top'];
} else {
    $margin_top = 20;
}
// margin left
if(array_key_exists('left',$_GET) && !empty($_GET['left'])){
    $margin_left = $_GET['left'];
} else {
    $margin_left = 100;
}
// margin bottom
if(array_key_exists('bottom',$_GET) && !empty($_GET['bottom'])){
    $margin_bottom = $_GET['bottom'];
} else {
    $margin_bottom = 100;
}
// margin right
if(array_key_exists('right',$_GET) && !empty($_GET['right'])){
    $margin_right = $_GET['right'];
} else {
    $margin_right = 100;
}

// range
if(array_key_exists('range',$_GET) && !empty($_GET['range'])){
    $range = $_GET['range'];
} else {
    $range = "oneweek";
}

switch($range){
    case "all":
        $a_range['x_low'] = strtotime("January 1, 1950");
        $a_range['x_high'] = time();
        // Set the labels every 5 year (i.e. 157680000seconds) and minor ticks every year
        $majortick = 157680000;
        $minortick = 31536000;
        break;
    case "tenyear":
        $a_range['x_low'] = strtotime("today midnight -10 years");
        $a_range['x_high'] = time();
        // Set the labels every year (i.e. 31536000seconds) and minor ticks every mmonth
        $majortick = 31536000;
        $minortick = 2628000;
        break;    
    case "oneyear":
        $a_range['x_low'] = strtotime("today midnight -1 year");
        $a_range['x_high'] = time();
        // Set the labels every month (i.e. 2628000seconds) and minor ticks every day
        $majortick = 2628000;
        $minortick = 86400;
        break;
    case "onemonth":
        $a_range['x_low'] = strtotime("today midnight -1 month");
        $a_range['x_high'] = time();
        // Set the labels every day (i.e. 86400 seconds) and minor ticks every hour
        $majortick = 86400;
        $minortick = 3600;
        break;
    case "oneweek":        
        $a_range['x_low'] = strtotime("today midnight -1 week");
        $a_range['x_high'] = time();
        // Set the labels every day (i.e. 3600seconds) and minor ticks every hour
        $majortick = 21600;
        $minortick = 60;
        break;
    case "oneday":        
        $a_range['x_low'] = strtotime("today midnight -1 day");
        $a_range['x_high'] = time();
        $majortick = 3600;
        $minortick = 60;        
        break;    
}


function readData($mysqli,$sitecode, $a_range, &$aXData, &$aYData,&$a_numrows){
    $myquery = "SELECT co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data2 WHERE sitecode='$sitecode' AND timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
    
    $query = $mysqli->query($myquery);
    if ( ! $query ) {
        //echo $mysqli->error;
        die;
    }

    $numrows = $query->num_rows;
    $a_numrows[$sitecode] = $numrows;

    if($numrows > 0){
        
        for ($x = 0; $x < $numrows; $x++) {
            $f_add = true;
            $data = $query->fetch_assoc();
            if(!empty($a_range['x_low'])){
              if($data['timestamp'] < $a_range['x_low']) {
                  $f_add = false;
              } 
            }
            if(!empty($a_range['x_high'])){
              if($data['timestamp'] > $a_range['x_high']) {
                  $f_add = false;
              } 
            }
           
            if($f_add == true){
                // convert to denver time
                if($sitecode == 'nwr' || $sitecode == 'mlb'){
                    $aXData[] = $data['timestamp'] - (60*60*6);
                } else {
                    $aXData[] = $data['timestamp'];
                }
                    $aYData[] = $data['value'];
            }
        }    
    $query->free(); 
    }

}
$xdata0 = $xdata1 = $xdata2 = array();
$ydata0 = $ydata1 = $ydata2 = array();


$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    // The connection failed. What do you want to do? 
    // You could contact yourself (email?), log the error, show a nice page, etc.
    // You do not want to reveal sensitive information

    // Let's try this:
    //echo "Sorry, this website is experiencing problems.";

    // Something you should not do on a public site, but this example will show you
    // anyways, is print out MySQL error related information -- you might log this
    //echo "Error: Failed to make a MySQL connection, here is why: \n";
    //echo "Errno: " . $mysqli->connect_errno . "\n";
    //echo "Error: " . $mysqli->connect_error . "\n";
    
    // You might want to show them something nice, but we will simply exit
    exit;
}


// get max and min values over all
$yMax = 450;
$yMin = 0;
$myquery = "SELECT MIN(co2_value) as ymin, MAX(co2_value) as ymax FROM climate_co2_data2 WHERE timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
$query = $mysqli->query($myquery);
if ( ! $query ) {
    //echo $mysqli->errno;
    exit;
}
$numrows = $query->num_rows;
if($numrows == 1){
    $data = $query->fetch_assoc();
    $yMin = $data['ymin'];
    $yMax = $data['ymax'];
    unset($data);
    $query->free();
 }

/* Create the pData object */
$myData = new pData();  

readData($mysqli,'mlb',$a_range, $xdata2,$ydata2,$a_numrows);
if($a_numrows['mlb'] > 0){
    // x axis
    $myData->addPoints($xdata2, "Timestamp");
    $myData->setAxisName(0,"Timestamp");
    $myData->setAxisXY(0,AXIS_X);
    $myData->setAxisPosition(0,AXIS_POSITION_BOTTOM);
    
    // y axis
    $myData->addPoints($ydata2, "MLB");
    $myData->setSerieOnAxis("MLB",1);
    $myData->setAxisName(1,"PPM");
    $myData->setAxisXY(1,AXIS_Y);
    $myData->setAxisUnit(1,"");
    $myData->setAxisPosition(1,AXIS_POSITION_LEFT);
}
$myData->setScatterSerie("Timestamp","MLB",0);
$myData->setScatterSerieDescription(0,"MLB");
$myData->setScatterSerieColor(0,array("R"=>0,"G"=>0,"B"=>0));


/* Create the X axis and the binded series */
/*for ($i=0;$i<=360;$i=$i+10) { $myData->addPoints(cos(deg2rad($i))*20,"Probe 1"); }
for ($i=0;$i<=360;$i=$i+10) { $myData->addPoints(sin(deg2rad($i))*20,"Probe 2"); }
$myData->setAxisName(0,"Index");
$myData->setAxisXY(0,AXIS_X);
$myData->setAxisPosition(0,AXIS_POSITION_BOTTOM);

/* Create the Y axis and the binded series */
/*for ($i=0;$i<=360;$i=$i+10) { $myData->addPoints($i,"Probe 3"); }
$myData->setSerieOnAxis("Probe 3",1);
$myData->setAxisName(1,"Degree");
$myData->setAxisXY(1,AXIS_Y);
$myData->setAxisUnit(1,"°");
$myData->setAxisPosition(1,AXIS_POSITION_RIGHT);

/* Create the 1st scatter chart binding */
/*$myData->setScatterSerie("Probe 1","Probe 3",0);
$myData->setScatterSerieDescription(0,"This year");
$myData->setScatterSerieColor(0,array("R"=>0,"G"=>0,"B"=>0));

/* Create the 2nd scatter chart binding */
/*$myData->setScatterSerie("Probe 2","Probe 3",1);
$myData->setScatterSerieDescription(1,"Last Year");

/* Create the pChart object */
$myPicture = new pImage($width,$height,$myData);

/* Set the default font */
$myPicture->setFontProperties(array("FontName"=>$fontpath."pf_arma_five.ttf","FontSize"=>12));

/* Set the graph area */
$myPicture->setGraphArea($margin_left,$margin_top,$width-$margin_right,$height-$margin_bottom);

/* Create the Scatter chart object */
$myScatter = new pScatter($myPicture,$myData);

/* Draw the scale */
$myScatter->drawScatterScale();

/* Draw a scatter plot chart */
$myScatter->drawScatterPlotChart();


/* Render the picture (choose the best way) */
$myPicture->stroke();
/*
// save to dir
$filename = $imagedir.$imagename.'2.jpg';
$temp_filename = $imagedir.$tempdir.$imagename.'2.jpg';

// before rendering, delete the existing, if it exists
if(file_exists($temp_filename)){
    unlink($temp_filename);
}
// render graph
try {
    $myPicture->render('../assets/temp/');
} catch( JpGraphException $e ) {
    echo 'Error: ' . $e->getMessage()."\n";
}
// after create graph copy to final location
if (!copy($temp_filename, $filename)) {
    //echo "failed to copy $temp_filename...\n";
} else {
    //echo "copying ".$temp_filename." to ".$filename."\n";
}
*/

?>