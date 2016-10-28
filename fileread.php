<?php
$f_good = FALSE;
$a_final = array("DATE\tCO2");

switch($_GET['datasource']){
    case 'spl':
        $file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_SPL_most_recent.lhr";
        $save_name = "spl";
        break;
    case 'efs':
        $file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_FEF_most_recent.lhr";
        $save_name = "efs";
        break;
    case 'nwr':
    default:
        $file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_NWR_most_recent.lhr";
        $save_name = "nwr";
}



$curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, $file);
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    $txt_file = curl_exec($curlSession);
    curl_close($curlSession);
$rows = explode("\n", $txt_file);

// shift off the first XX lines which have notes
for($i=0;$i<=29;$i++){
    array_shift($rows);
}

foreach($rows as $row => $data)
{
    //get row data
    if($row != ""){
        $row_data = explode(' ', $data);

        if($row_data[0] != ""){
            // before process, must check for valid values
            // TODO: Britt will add a flag for me to check
            
            // for not only get midnight
            if($row_data[8] > 0 && $row_data[8] < 500 && $row_data[4] == 0){
                if($f_good == false){
                    $f_good = true;   
                }
                // form a date a pull out the co2 and save in a new array if it is valid data
                $year = $row_data[1];
                $mon = $row_data[2];
                $day = $row_data[3];
                //$hour = $row_data[4];
                $co2 = $row_data[8];
                $ts = mktime('0', '0','0', $mon, $day, $year);

               // $date = date('d-m-Y H:i:s',$ts);
                $date = date('d-m-Y',$ts);

                $a_final[] = $date."\t".$co2;
            }
        }
    }
}
if($f_good == TRUE){
    $s_final = implode("\n",$a_final);
    $file = 'data\\'.$save_name.'.tsv';
    file_put_contents($file, $s_final,LOCK_EX);
}