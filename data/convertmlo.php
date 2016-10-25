<?php
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
    exit();
} 
$a_final = array();
 $file = "mlo.tsv"; 
 $f = fopen($file, 'r+');
 $last_line = '';
    while(!feof($f))
    {
       
       $line = fgets($f, 4096);
        if(!preg_match('#DATE#',$line)){
       // split by space
        $a_ary = explode("\t",$line);
       // YYYY-MM-DDTHH:MM:SS
        
        //split date
        $a_date = explode('-',$a_ary[0]);
        
        $new_line = $a_date[2].'-'.$a_date[1].'-'.$a_date[0].'T12:00:00'."\t".$a_ary[1];
        $a_final[] = $new_line;
        }
        
    }
fclose($f); 




//open and put into file
$text = "";
foreach($a_final as $key => $value)
{
    echo $value;
    $text .= $value;
}
$fh = fopen($file, "w") or die("Could not open log file.");
fwrite($fh, $text) or die("Could not write file!");
fclose($fh);
?>