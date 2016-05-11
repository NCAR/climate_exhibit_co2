<?php 
// vars
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $baseurl = 'J:\\Sharon\\xampp\\htdocs\\climate_exhibit_co2\\';
    $path = 'J:\\Sharon\\xampp\\htdocs\\libraries\\php\\jpgraph\\src\\';
    require 'J:\\Sharon\\db\\credentials\\credentials.php';
} else {    
    $host = $_SERVER['SERVER_NAME'];
    if($host == 'test.apps.spark.ucar.edu'){
        $baseurl = '/test/sparkapps/climate_exhibit_co2/';
        $path = "/test/sparkapps/libraries/php/jpgraph/src/";
    } else {
        $baseurl = '/web/sparkapps/climate_exhibit_co2/';
        $path = "/web/sparkapps/libraries/php/jpgraph/src/";
    }
    require '/home/sclark/db/credentials/credentials.php';
}

// require
require_once ($path.'jpgraph.php');
require_once ($path.'jpgraph_line.php');
require_once ($path.'jpgraph_date.php');
require_once ($path.'jpgraph_utils.inc.php');


// set up params
// file source
if(array_key_exists('source',$_GET) && !empty($_GET['source'])){
    $source = $_GET['source'];
} else {
    $source = '';
}
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
    $width = 1920;
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
    $margin_left = 40;
}
// margin bottom
if(array_key_exists('bottom',$_GET) && !empty($_GET['bottom'])){
    $margin_bottom = $_GET['bottom'];
} else {
    $margin_bottom = 50;
}
// margin right
if(array_key_exists('right',$_GET) && !empty($_GET['right'])){
    $margin_right = $_GET['right'];
} else {
    $margin_right = 0;
}
// graph title
if(array_key_exists('title',$_GET) && !empty($_GET['title'])){
    $title = $_GET['title'];
} else {
    $title = "";
}
// y axis label
if(array_key_exists('axis_y',$_GET) && !empty($_GET['axis_y'])){
    $axis_y = $_GET['axis_y'];
} else {
    $axis_y = "";
}
// x axis label
if(array_key_exists('axis_x',$_GET) && !empty($_GET['axis_x'])){
    $axis_x = $_GET['axis_x'];
} else {
    $axis_x = "";
}
// x axis low range
if(array_key_exists('x_range_low',$_GET) && !empty($_GET['x_range_low'])){
    $a_range['x_low'] = $_GET['x_range_low'];
} else {
    $a_range['x_low'] = "";
}

// x axis high range
if(array_key_exists('x_range_high',$_GET) && !empty($_GET['x_range_high'])){
    $a_range['x_high'] = $_GET['x_range_high'];
} else {
    $a_range['x_high'] = "";
}

$dateUtils = new DateScaleUtils();

function readData($sitecode, $a_range, &$aXData, &$aYData){
    $myquery = "SELECT co2_value as value, timestamp_co2_recorded as timestamp FROM climate_co2_data WHERE sitecode='$sitecode' AND active='1'";
    $query = mysql_query($myquery);
    if ( ! $query ) {
        echo mysql_error();
        die;
    }

    $numrows = mysql_num_rows($query);
    if($numrows > 0){
        
        for ($x = 0; $x < $numrows; $x++) {
            $f_add = true;
            $data = mysql_fetch_assoc($query);
            if(!empty($a_range['x_low'])){
              if($data['timestamp'] < $a_range['x_low']) {
                  $f_add = false;
              } 
            }
            if(!empty($a_range['x_high'])){
              if($data['timestamp'] < $a_range['x_high']) {
                  $f_add = false;
              } 
            }
           
            if($f_add == true){
                $aXData[] = $data['timestamp'];
                $aYData[] = $data['value'];
            }
        }    
            
    }

}
$xdata = array();
$ydata = array();
$server = mysql_connect($host, $username, $password);
$connection = mysql_select_db($database, $server);

// get max and min values over all
$yMax = 450;
$yMin = 0;
$myquery = "SELECT MIN(co2_value) as ymin, MAX(co2_value) as ymax FROM climate_co2_data WHERE active='1'";
$query = mysql_query($myquery);
if ( ! $query ) {
    echo mysql_error();
    die;
}
$numrows = mysql_num_rows($query);
if($numrows == 1){
    $data = mysql_fetch_assoc($query);
    $yMin = $data['ymin'];
    $yMax = $data['ymax'];
 }
//readData($file,$a_range, $xdata,$ydata);
readData($source,$a_range, $xdata,$ydata);
 
    mysql_close($server);
// Create a graph instance
$graph = new Graph($width,$height);
$graph->SetMargin($margin_left,$margin_right,$margin_top,$margin_bottom);
 
// Specify what scale we want to use,
// int = integer scale for the X-axis
// int = integer scale for the Y-axis
$graph->SetScale('datlin');
 
// Setup a title for the graph
if(!empty($title)){
    $graph->title->Set('CO2 example');
    $graph->title->SetFont(FF_ARIAL,FS_BOLD,14);
}

 
// Setup titles and X-axis labels
//$graph->xaxis->SetLabelFormatCallback('year_callback');
$graph->xaxis->SetLabelFormatString('Y',true);
$graph->xaxis->SetLabelAngle(90);
 // Get manual tick every second year
list($tickPos,$minTickPos) = $dateUtils->getTicks($xdata,DSUTILS_YEAR1);
$graph->xaxis->SetTickPositions($tickPos,$minTickPos);

// Setup Y-axis title
if(!empty($axis_y)){
    $graph->yaxis->title->Set($axis_y);
}
$graph->yaxis->scale->SetAutoMax($yMax); 
$graph->yaxis->scale->SetAutoMin($yMin); 
// Create the linear plot
$lineplot=new LinePlot($ydata,$xdata);
 
// Add the plot to the graph
$graph->Add($lineplot);
 
// Display the graph
$graph->Stroke();



?>