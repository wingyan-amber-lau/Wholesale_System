<?php

$link = mysqli_connect("127.0.0.1", "user", "password", "schema");
$job_name = 'stock_and_product_flow';
$current_datetime = date('Y-m-d H:i:s');
if ($link){
    //1: get date of last schedule job
    $sql = "SELECT DATE(start_datetime) last_run_date
            FROM schedule_jobs 
            WHERE job_name = '$job_name' 
            AND status = 'I'";
    if ($rs = mysqli_query($link,$sql)){
        if(mysqli_num_rows($rs)==0){
            echo "Job is running!";
            exit;
        }
        while ($row = mysqli_fetch_array($rs)){
                $last_run_date = $row['last_run_date'];
        }
    }
    else exit;
    //2: update status of job
    $sql = "UPDATE schedule_jobs 
            SET status ='A',
            start_datetime = '$current_datetime' 
            WHERE job_name = '$job_name'";
    if(!mysqli_query($link,$sql))
        exit;

    //3.0: get all stock into array, array structure: array ('BB001' => array(array(stock=>10,cost=>100,last_stock_date=>2019-01-02),array(stock=>10,cost=>120,last_stock_date=>2019-01-09)))
    $sql = "SELECT product_code,
    stock,
    cost,
    last_stock_date 
    FROM stocks 
    ORDER BY product_code,
    last_stock_date";
    if($rs = mysqli_query($link,$sql)){
        while ($row = mysqli_fetch_array($rs)){
            $stocks[$row['product_code']][] = array(
                                                'last_stock_date' => $row['last_stock_date'],
                                                'stock' => $row['stock'],
                                                'cost' => $row['cost']
                                            );
        }
    } 
    else{
        exit;
    }  
    //var_dump($stocks);
    //3: get all product sold from last schedule date to ytd 
    $sql = "SELECT o.product_code,
            o.amount,
            o.unit_cost,
            o.unit_price,
            o.discount,
            o.invoice_code,
            i.delivery_date,
            i.customer_code,
            p.count_inventory
            FROM invoices i,
            orders o,
            products p 
            WHERE i.invoice_code = o.invoice_code
            AND i.status <> 'VOID'
            AND p.product_code = o.product_code
            AND i.delivery_date < CURDATE() 
            AND i.delivery_date >= '$last_run_date'";
    //echo $sql;
    //exit;
    if($rs = mysqli_query($link,$sql)){
        $update_stock = array();
        while ($row = mysqli_fetch_array($rs)){
            //TODO4:if have any changes on stock, put into new array, then use the new array for update/delete
            //TODO5: insert 'product_flows' table, create a function(?),call the function in above code
            if ($row['count_inventory']){                
                //no record in stocks table
                if (!isset($stocks[$row['product_code']])){
                    $stocks[$row['product_code']][] = array(
                        'last_stock_date' => date('Y-m-d'),
                        'stock' => 0,
                        'cost' => $row['unit_cost']
                    );
                }

                $num_of_lot = count($stocks[$row['product_code']]);
                $remainder = $row['amount'];
                foreach ($stocks[$row['product_code']] as $ind=>&$stock){
                    if ($stock['stock'] == 0 && $ind != $num_of_lot - 1)
                        continue;
                    //case 1: enough stock
                    //case 2: not enough stock
                    if ($stock['stock'] - $remainder >= 0 ){
                        $new_amount = $remainder;
                        $stock['stock'] = $stock['stock'] - $remainder;
                        //$remainder = 0;
                        $update_stock[$row['product_code']][$stock['last_stock_date']] = array('stock'=>$stock['stock'],'cost'=>$stock['cost']);
                        insertProgramFlow($link,$row,$new_amount,$stock['cost']);
                        break;
                    }
                    else{
                        if ($stock['stock'] < 0){
                            $new_amount = $remainder;
                            $stock['stock'] = $stock['stock'] - $remainder;
                            $update_stock[$row['product_code']][$stock['last_stock_date']] = array('stock'=>$stock['stock'],'cost'=>$stock['cost']);
                            insertProgramFlow($link,$row,$new_amount,$stock['cost']);
                            //$remainder = 0;
                            break;
                        }
                        else{
                            $new_amount = $stock['stock'];
                            $remainder = $remainder - $stock['stock'];
                            $stock['stock'] = 0;
                            $update_stock[$row['product_code']][$stock['last_stock_date']] = $stock['stock'];
                            if ($ind == $num_of_lot - 1){
                                $stock['stock'] = $stock['stock'] - $remainder;
                                $new_amount = $new_amount + $remainder;
                                $update_stock[$row['product_code']][$stock['last_stock_date']] = array('stock'=>$stock['stock'],'cost'=>$stock['cost']);
                                insertProgramFlow($link,$row,$new_amount,$stock['cost']);
                                //$remainder = 0;
                                break;
                            }
                            insertProgramFlow($link,$row,$new_amount,$stock['cost']);
                        }
                    }
                }
            }
            else{
                insertProgramFlow($link,$row);
            }
            
        }
        //TODO6: if stock=0 and more than one row, delete from 'stock' table, else update 'stocks' table
        insertStock($link,$update_stock);
    }


    //6.9: stock in from supplier
    $sql = "SELECT ri.product_code,
    ri.amount,
    ri.unit_cost,
    ri.receipt_code,
    r.delivery_date,
    r.supplier_code,
    p.count_inventory
    FROM receipts r,
    receipt_items ri,
    products p 
    WHERE r.receipt_code = ri.receipt_code
    AND p.product_code = ri.product_code
    AND r.receipt_date < CURDATE() 
    AND r.receipt_date >= '$last_run_date'";
    if($rs = mysqli_query($link,$sql)){
        $update_stock = array();
        while ($row = mysqli_fetch_array($rs)){
            insertProductFlowStockSupply($link,$row);
        }
    }
   
    //7: update job status
    $sql = "UPDATE schedule_jobs 
        SET end_datetime = now(), 
        status = 'I' 
        WHERE job_name = '$job_name'";
    mysqli_query($link,$sql);

}




function insertProgramFlow($link,$row,$new_amount = null,$cost = null){
    $product_code = $row['product_code'];
    $delivery_date = $row['delivery_date'];
    $customer_code = $row['customer_code'];
    $invoice_code = $row['invoice_code'];
    $amount = is_null($new_amount)?$row['amount']:$new_amount;
    $cost = is_null($cost)?$row['unit_cost']:$cost;
    $unit_price = $row['unit_price'];
    $discount = $row['discount'];
    
    $sql = "INSERT INTO product_flows 
            VALUES (
                '',
                '$product_code',
                '$delivery_date',
                'OUT',
                '$customer_code',
                '$invoice_code',
                '$amount',
                '$cost',
                '$unit_price',
                '$discount',
                now(),
                now())";
    //echo "insertProgramFlow:". $sql ."\n";
    mysqli_query($link,$sql);
    
    
}

function insertStock($link,$update_stock){
    foreach($update_stock as $product_code=>$stock_lots){
        $num_of_lot = count($stock_lots);
        $ind = 0;
        foreach($stock_lots as $date=>$stock_lot){
            if ($stock_lot['stock'] == 0 && $ind < $num_of_lot - 1){
                //delete
                $sql = "DELETE FROM stocks WHERE  product_code = '$product_code' AND last_stock_date='".$date."' AND cost='".$stock_lot['cost']."'";
            }
            else {
                //insert or update
                $sql = "INSERT INTO stocks (
                    product_code,
                    cost,
                    last_stock_date,
                    stock) 
                    VALUES ('$product_code',
                    '".$stock_lot['cost']."',
                    '$date',
                    '".$stock_lot['stock']."'
                    )
                    ON DUPLICATE KEY UPDATE
                        stock = '".$stock_lot['stock']."'";
            }
            mysqli_query($link,$sql);
            //echo "insertStock: $sql";
            $ind++;
        }
    }
}

function insertProductFlowStockSupply($link,$row){
    $product_code = $row['product_code'];
    $delivery_date = $row['delivery_date'];
    $supplier_code = $row['supplier_code'];
    $receipt_code = $row['receipt_code'];
    $amount = $row['amount'];
    $unit_cost = $row['unit_cost'];

    $sql = "INSERT INTO product_flows VALUES (
            null,
            '$product_code',
            '$delivery_date',
            'IN',
            '$supplier_code',
            '$receipt_code',
            '$amount',
            '$unit_cost',
            0,
            0,
            now(),
            now()
            )";
    mysqli_query($link,$sql);

    $sql = "INSERT INTO stocks VALUES (
        null,
        '$product_code',
        '$amount',
        '$unit_cost',
        '$delivery_date',
        now(),
        now() 
    )
    ON DUPLICATE KEY UPDATE
        stock = stock+$amount"
                ;
    mysqli_query($link,$sql);
}

?>
