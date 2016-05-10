<?php 
error_reporting(E_ALL);
ini_set('memory_limit', '1024M'); // or you could use 1G
// vars
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $baseurl = 'J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\';
    $path = 'J:\\Sharon\\xampp\\htdocs\\libraries\\php\\jpgraph\\src\\';
    require 'J:\\Sharon\\db\\credentials\\credentials.php';
    
    
    $imagedir = "J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\assets\\";
} else {    
    $host = $_SERVER['SERVER_NAME'];
    if($host == 'test.apps.spark.ucar.edu'){
        $baseurl = '/test/sparkapps/climate_exhibit_co2/';
        $path = "/test/sparkapps/libraries/php/jpgraph/src/";
        
        $imagedir = "/test/sparkapps/climate_exhibit_co2/assets/";
    } else {
        $baseurl = '/web/sparkapps/climate_exhibit_co2/';
        $path = "/web/sparkapps/libraries/php/jpgraph/src/";
        $imagedir = "/web/sparkapps/climate_exhibit_co2/assets/";
    }
    require '/home/sclark/db/credentials/credentials.php';
}

// require
require_once ($path.'jpgraph.php');
require_once ($path.'jpgraph_scatter.php');
require_once ($path.'jpgraph_date.php');
require_once ($path.'jpgraph_utils.inc.php');

// set up params
// height
if(array_key_exists('height',$_GET) && !empty($_GET['height'])){
    $height = $_GET['height'];
} else {
    $height = 1080;
}
// width
if(array_key_exists('width',$_GET) && !empty($_GET['width'])){
    $width = $_GET['width'];
} else {
    $width = 1620;
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
    $margin_bottom = 0;
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
    $range = "tenyear";
}

$imagename = $range;
// Create a graph instance
$graph = new Graph($width,$height);
$graph->img->SetImgFormat('jpeg');
$graph->SetMargin($margin_left,$margin_right,$margin_top,$margin_bottom);
// Specify what scale we want to use,
$graph->SetScale('datint');

switch($range){
    case "sixtyyear":
        $a_range['x_low'] = strtotime("-60 year");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_YEAR1;
        $graph->xaxis->SetLabelFormatString('Y',true);
        break;
    case "tenyear":
        $a_range['x_low'] = strtotime("-10 year");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_YEAR1;
        $graph->xaxis->SetLabelFormatString('Y',true);
        break;    
    case "oneyear":
        $a_range['x_low'] = strtotime("-1 year");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_MONTH1;
        $graph->xaxis->SetLabelFormatString('M-Y',true);
        break;
    case "onemonth":
        $a_range['x_low'] = strtotime("-1 month");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_DAY1;
        $graph->xaxis->SetLabelFormatString('M-d-Y',true);
        $graph->xaxis->scale->ticks->Set(60*60*24);
        break;
    case "oneweek":        
        $a_range['x_low'] = strtotime("-1 week");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_DAY1;
        $graph->xaxis->SetLabelFormatString('M-d-Y',true);
        $graph->xaxis->scale->ticks->Set(60*60*24);
        break;
    case "oneday":        
        $a_range['x_low'] = strtotime("-1 week");
        $a_range['x_high'] = time();
        $tickCond = DSUTILS_DAY1;
        $graph->xaxis->SetLabelFormatString('M-d H:i',true);
        $graph->xaxis->scale->ticks->Set(60*5);
        break;    
}


$dateUtils = new DateScaleUtils();

function readData($mysqli,$sitecode, $a_range, &$aXData, &$aYData){
    $myquery = "SELECT co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data WHERE sitecode='$sitecode' AND timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
    
    $query = $mysqli->query($myquery);
    if ( ! $query ) {
        echo $mysqli->error;
        die;
    }

    $numrows = $query->num_rows;

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
                $aXData[] = $data['timestamp'];
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
$myquery = "SELECT MIN(co2_value) as ymin, MAX(co2_value) as ymax FROM climate_co2_data WHERE timestamp_co2_recorded >= ".$a_range['x_low']." AND timestamp_co2_recorded <= ".$a_range['x_high']." AND active='1'";
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
 
// Setup titles and X-axis labels
$graph->xaxis->SetLabelAngle(90);

// Setup Y-axis title
$graph->yaxis->scale->SetAutoMax($yMax); 
$graph->yaxis->scale->SetAutoMin($yMin); 
$graph->yaxis->SetFont(FF_FONT2,FS_NORMAL,48);


// for mesa lab data
readData($mysqli,'mlb',$a_range, $xdata2,$ydata2);
$fillcolor2 = '#FFFF00';
$bordercolor2 = '#7f7f00';
$sp2 = new ScatterPlot($ydata2,$xdata2);
$sp2->mark->SetType(MARK_UTRIANGLE);
$sp2->mark->SetFillColor($fillcolor2);
$sp2->mark->SetColor($bordercolor2);
$sp2->mark->SetWidth(5);
$graph->Add($sp2);


// for nwr data
readData($mysqli,'nwr',$a_range, $xdata1,$ydata1);

$fillcolor1 = '#0000FF';
$bordercolor1 = '#000066';
$sp1 = new ScatterPlot($ydata1,$xdata1);
$sp1->mark->SetType(MARK_SQUARE);
$sp1->mark->SetFillColor($fillcolor1);
$sp1->mark->SetColor($bordercolor1);
$sp1->mark->SetWidth(5);
$graph->Add($sp1);

// for mlo data

readData($mysqli,'mlo',$a_range, $xdata0,$ydata0);
$mysqli->close();
$fillcolor0 = "#CC0000";
$bordercolor0 = "#660000";
$sp0 = new ScatterPlot($ydata0,$xdata0);
$sp0->mark->SetType(MARK_FILLEDCIRCLE);
$sp0->mark->SetFillColor($fillcolor0);
$sp0->mark->SetColor($bordercolor0);
$sp0->mark->SetWidth(5); 
$graph->Add($sp0);


$sp0->SetLegend("Mauna Loa");
$sp1->SetLegend("Niwot Ridge");
$sp2->SetLegend("Mesa Lab");
$graph->legend->SetFrameWeight(1);
$graph->legend->SetFont(FF_FONT2,FS_NORMAL,96);
$graph->legend->SetColumns(3);
$graph->legend->SetColor('#4E4E4E','#00A78A');
$graph->legend->SetAbsPos($width/2-100,$height-100,'right','bottom');


if($range == 'oneweek' || $range == 'oneday' || $range == 'onemonth'){
    $myscale = $xdata1;
} else {
    $myscale = $xdata0;
}
list($tickPos,$minTickPos) = $dateUtils->getTicks($myscale,$tickCond);
$graph->xaxis->SetPos('min');
$graph->xaxis->SetMajTickPositions($tickPos);
$graph->xaxis->scale->SetTimeAlign( MINADJ_1 );
$graph->xaxis->SetFont(FF_FONT2,FS_NORMAL,48);


unset($xdata2,$ydata2);
unset($xdata1,$ydata1);
unset($xdata0,$ydata0);
$filename = $imagedir.$imagename.'.jpg';

// before rendering, delete the existing, if it exists
unlink($filename);
echo "writing to ".$filename;
// render graph
$graph->Stroke($filename);

?>