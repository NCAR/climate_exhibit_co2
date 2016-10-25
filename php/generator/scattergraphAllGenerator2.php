<?php 
/**
*   This creates images that are based off the most granular data intervals (approx every 3 mins)
*   from db: climate_co2_data2
**/
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
   // exit();
} 
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '1024M'); // or you could use 1G
$tempdir = 'temp/';
// vars
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

if(isset($argv)){
        parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
// require
require_once ($path.'jpgraph.php');
require_once ($path.'jpgraph_scatter.php');
require_once ($path.'jpgraph_line.php');
require_once ($path.'jpgraph_date.php');
require_once ($path.'jpgraph_utils.inc.php');
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
//track amount returned - cannot graph if no values
$a_numrows = array();

$imagename = $range;
// Create a graph instance
$graph = new Graph($width,$height);
$graph->img->SetImgFormat('jpeg');
$graph->SetMargin($margin_left,$margin_right,$margin_top,$margin_bottom);
// Specify what scale we want to use,
$graph->SetScale('datint');
$graph->SetTickDensity(TICKD_VERYSPARSE);
switch($range){
    case "all":
        $a_range['x_low'] = strtotime("January 1, 1950");
        $a_range['x_high'] = time();
       //$tickCond = DSUTILS_YEAR1;
        $graph->xaxis->scale->SetDateFormat('Y');
        // Set the labels every 5 year (i.e. 157680000seconds) and minor ticks every year
        $majortick = 157680000;
        $minortick = 31536000;
        break;
    case "tenyear":
        $a_range['x_low'] = strtotime("today midnight -10 years");
        $a_range['x_high'] = time();
       // $tickCond = DSUTILS_YEAR1;
        $graph->xaxis->scale->SetDateFormat('Y');
        // Set the labels every year (i.e. 31536000seconds) and minor ticks every mmonth
        $majortick = 31536000;
        $minortick = 2628000;
        break;    
    case "oneyear":
        $a_range['x_low'] = strtotime("today midnight -1 year");
        $a_range['x_high'] = time();
       // $tickCond = DSUTILS_MONTH1;
        $graph->xaxis->scale->SetDateFormat('M-Y');
        // Set the labels every month (i.e. 2628000seconds) and minor ticks every day
        $majortick = 2628000;
        $minortick = 86400;
        break;
    case "onemonth":
        $a_range['x_low'] = strtotime("today midnight -1 month");
        $a_range['x_high'] = time();
       // $tickCond = DSUTILS_DAY1;
        $graph->xaxis->scale->SetDateFormat('M-d H:i');
        //$graph->xaxis->scale->ticks->Set(60*60*24);
        // Set the labels every day (i.e. 86400 seconds) and minor ticks every hour
        $majortick = 86400;
        $minortick = 3600;
        break;
    case "oneweek":        
        $a_range['x_low'] = strtotime("today midnight -1 week");
        $a_range['x_high'] = time();
       // $tickCond = DSUTILS_DAY1;
        $graph->xaxis->scale->SetDateFormat('M-d H:i');
        //$graph->xaxis->scale->ticks->Set(60*60*24);
        // Set the labels every day (i.e. 3600seconds) and minor ticks every hour
        $majortick = 21600;
        $minortick = 60;
        break;
    case "oneday":        
        $a_range['x_low'] = strtotime("today midnight -1 day");
        $a_range['x_high'] = time();
       // $tickCond = DSUTILS_DAY1;
        $graph->xaxis->scale->SetDateFormat('H:i');
        $majortick = 3600;
        $minortick = 60;        
        break;    
}
$graph->xaxis->scale->ticks->Set($majortick,$minortick);
$graph->SetTickDensity( TICKD_NORMAL );
//make tick density dense
$dateUtils = new DateScaleUtils();

function readData($mysqli,$sitecode, $a_range, &$aXData, &$aYData,&$a_numrows){
    $myquery = "SELECT co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data2 WHERE sitecode='$sitecode' AND timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
    
    $query = $mysqli->query($myquery);
    if ( ! $query ) {
        echo $mysqli->error;
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
                
              /*  if($sitecode == 'mlo' && ($range = 'onemonth' || $range == 'oneweek')){
                    $aXData[] = $data['timestamp'] - (60*60*11);
                    $aYData[] = $data['value'];
                    
                    
                    $aXData[] = $data['timestamp'] + (60*60*11);
                    $aYData[] = $data['value'];
                    
                } else {*/
                // TODO: convert to denver time
                if($sitecode == 'nwr' || $sitecode == 'mlb'){
                    $aXData[] = $data['timestamp'] - (60*60*6);
                } else {
                    $aXData[] = $data['timestamp'];
                }
                    $aYData[] = $data['value'];
               // }
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
    echo "Sorry, this website is experiencing problems.";

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
    echo $mysqli->errno;
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
 


// Setup Y-axis title
$graph->yaxis->scale->SetAutoMax($yMax); 
$graph->yaxis->scale->SetAutoMin($yMin); 
$graph->yaxis->SetFont(FF_FONT2,FS_NORMAL,12);

// for mesa lab data
readData($mysqli,'mlb',$a_range, $xdata2,$ydata2,$a_numrows);
if($a_numrows['mlb'] > 0){
    $fillcolor2 = '#FFFF00';
    $bordercolor2 = '#7f7f00';
    $sp2 = new ScatterPlot($ydata2,$xdata2);
    $sp2->mark->SetType(MARK_UTRIANGLE);
    $sp2->mark->SetFillColor($fillcolor2);
    $sp2->mark->SetColor($bordercolor2);
    $sp2->mark->SetWidth(5);
    $graph->Add($sp2);
}

// for nwr data
readData($mysqli,'nwr',$a_range, $xdata1,$ydata1,$a_numrows);
if($a_numrows['nwr'] > 0){
    $fillcolor1 = '#0000FF';
    $bordercolor1 = '#000066';
    $sp1 = new ScatterPlot($ydata1,$xdata1);
    $sp1->mark->SetType(MARK_SQUARE);
    $sp1->mark->SetFillColor($fillcolor1);
    $sp1->mark->SetColor($bordercolor1);
    $sp1->mark->SetWidth(5);
    $graph->Add($sp1);
}

// for mlo data
readData($mysqli,'mlo',$a_range, $xdata0,$ydata0,$a_numrows);
$mysqli->close();
if($a_numrows['mlo'] > 0){
    $fillcolor0 = "#CC0000";
    $bordercolor0 = "#660000";
    $sp0 = new ScatterPlot($ydata0,$xdata0);
    $sp0->mark->SetType(MARK_FILLEDCIRCLE);
    $sp0->mark->SetFillColor($fillcolor0);
    $sp0->mark->SetColor($bordercolor0);
    $sp0->mark->SetWidth(5); 
    $graph->Add($sp0);
}

/*$sp0->SetLegend("Mauna Loa");
$sp1->SetLegend("Niwot Ridge");
$sp2->SetLegend("Mesa Lab");
$graph->legend->SetFrameWeight(1);
$graph->legend->SetFont(FF_FONT2,FS_NORMAL,15);
$graph->legend->SetMarkAbsSize(10);
$graph->legend->SetColumns(3);
$graph->legend->SetColor('#4E4E4E','#00A78A');
$graph->legend->SetAbsPos($width/2-100,$height-100,'right','bottom');
*/

if($range == 'oneweek' || $range == 'oneday' || $range == 'onemonth'){
    if(count($xdata1) > 0){
        $myscale = $xdata1;
    } else if(count($xdata2) > 0){
        $myscale = $xdata2;
    } else {
        $myscale = $xdata0;
    }
} else {
    $myscale = $xdata0;
}
$graph ->xaxis->scale->SetDateAlign( DAYADJ_1);
//$graph->xaxis->SetFont(FF_FONT2,FS_NORMAL,12);
// Make sure that the X-axis is always at the bottom of the scale
// (By default the X-axis is alwys positioned at Y=0 so if the scale
// doesn't happen to include 0 the axis will not be shown)
// Setup titles and X-axis labels
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetPos('min');
 
// Now set the tic positions
//list($tickPositions,$minTickPositions) = $dateUtils->GetTicks($myscale);
//$graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);

// Add a X-grid
//$graph->xgrid->Show();

unset($xdata2,$ydata2);
unset($xdata1,$ydata1);
unset($xdata0,$ydata0);

$filename = $imagedir.$imagename.'2.jpg';
$temp_filename = $imagedir.$tempdir.$imagename.'2.jpg';

// before rendering, delete the existing, if it exists
if(file_exists($temp_filename)){
    unlink($temp_filename);
}
echo "writing to ".$temp_filename."\n";
// render graph
try {
    $graph->Stroke($temp_filename);
} catch( JpGraphException $e ) {
    echo 'Error: ' . $e->getMessage()."\n";
}
// after create graph copy to final location
if (!copy($temp_filename, $filename)) {
    echo "failed to copy $temp_filename...\n";
} else {
    echo "copying ".$temp_filename." to ".$filename."\n";
}
?>