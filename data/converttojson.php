<?php
if (php_sapi_name() != "cli") {
    // In cli-mode
    echo "Cannot execute.";
    exit();
} 
$a_final = array();
 $file = "nwr.tsv"; 
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




//open and put into file

//$file = "nwr.json"; 
echo json_encode($a_final);
//echo '[[1125007200, "378.173"], [1125010800, "379.096"], [1125014400, "379.483"], [1125018000, "379.735"], [1125021600, "379.118"], [1125025200, "379.225"], [1125028800, "379.478"], [1125032400, "379.187"], [1125036000, "378.96"], [1125039600, "379.096"], [1125043200, "379.576"], [1125046800, "379.367"], [1125050400, "379.169"], [1125054000, "377.665"], [1125057600, "377.004"], [1125061200, "376.746"]]';
//$fh = fopen($file, "w") or die("Could not open log file.");
//fwrite($fh, $text) or die("Could not write file!");
//fclose($fh);
?>