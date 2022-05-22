<?php

//namespace App\Http\Controllers;
//Use DB;
$link = mysqli_connect("127.0.0.1", "user", "password", "schema");
//var_dump($link);
//exit;
$weekday_map = array(
    1 => 'mon',
    2 => 'tue',
    3 => 'wed',
    4 => 'thu',
    5 => 'fri',
    6 => 'sat',
);
if ($link){
$sql = "SELECT * FROM invoices";
//$invoices = DB::select($sql);
//$invoices = stdClassArrToArray($invoices);
if ($rs = mysqli_query($link,$sql)){
    while ($row = $rs->fetch_assoc()){
        $weekday = $weekday_map[date('w',strtotime($row['delivery_date']))];
        $district = $row['district_code'];
        $sql2 = "SELECT car_no_$weekday car_no,order_in_car_$weekday order_in_car FROM districts WHERE district_code = '$district'";
        $rs2 = mysqli_query($link,$sql2);
        $car_no_info = $rs2->fetch_assoc();
        //$car_no_info = stdClassArrToArray($car_no_info);
        $update = "UPDATE invoices 
        SET car_no = ".$car_no_info['car_no'].", 
        order_in_car=".$car_no_info['order_in_car']."
        WHERE invoice_code='".$row['invoice_code']."'";
        //echo $update;
        mysqli_query($link,$update);
        
    }
    
}
mysqli_close($link);
}

?>