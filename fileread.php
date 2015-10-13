<?php
$a_final = array("DATE\tCO2");
$txt_file    = file_get_contents('data/nwr.txt');
$rows        = explode("\n", $txt_file);
array_shift($rows);
array_shift($rows);
foreach($rows as $row => $data)
{
    //get row data
    $row_data = explode(' ', $data);
    
    if($row_data[0] != ""){
        // before process, must check for valid values
        // TODO: Britt will add a flag for me to check
        if($row_data[8] > 0 && $row_data[8] < 500){
            // form a date a pull out the co2 and save in a new array if it is valid data
            $year = $row_data[1];
            $mon = $row_data[2];
            $day = $row_data[3];
            $hour = $row_data[4];
            $co2 = $row_data[8];
            $ts = mktime($hour, '0','0', $mon, $day, $year);

            $date = date('d-m-Y H:i:s',$ts);

            $a_final[] = $date."\t".$co2;
        }
    }
}
$s_final = implode("\n",$a_final);
$file = 'data\nwr.tsv';
file_put_contents($file, $s_final,LOCK_EX);