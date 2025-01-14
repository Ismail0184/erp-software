<?php
require_once 'support_file.php';
$title='Production Report';
$now=time();
$unique='pi_no';
$table="production_issue_master";
$table_details="production_issue_detail";
$journal_item="journal_item";
$page='warehouse_manual_received_from_godown_view.php';
$crud      =new crud($table);
$$unique = $_GET[$unique];
$STO_master=find_all_field(''.$table.'','',''.$unique.'='.$$unique.'');
if(prevent_multi_submit()){
    if (isset($_POST['reprocess'])) {
        $up="UPDATE ".$table." SET verifi_status='MANUAL' where ".$unique."=".$$unique."";
        $update_table_master=mysqli_query($conn, $up);
        $up2="UPDATE ".$table_details." SET verifi_status='MANUAL',status='MANUAL' where ".$unique."=".$$unique."";
        $update_table_details=mysqli_query($conn, $up2);
        $_SESSION['pi_tr'] = $_GET[$unique];
        $_SESSION['initiate_production_transfer']=getSVALUE("".$table."", "custom_pi_no", " where ".$unique."=".$_GET[$unique]."");
        $type = 1;
        echo "<script>self.opener.location = 'warehouse_goods_transfer.php'; self.blur(); </script>";
        echo "<script>window.close(); </script>";
    }

    //for modify PS information ...........................
    if(isset($_POST['checked']))
    {
        $rs="Select d.*,i.*
from 
".$table_details." d,
item_info i
 where
 i.item_id=d.item_id  and 
 d.".$unique."=".$$unique."
 order by d.id";
        $pdetails=mysqli_query($conn, $rs);
        while($row=mysqli_fetch_array($pdetails)){
            $_POST['ji_date'] = date('Y-m-d');
            $_POST['item_id'] = $row['item_id'];
            $_POST['warehouse_id'] = $row['warehouse_from'];
            $_POST['relevant_warehouse'] = $row['warehouse_to'];
            $_POST['item_price'] = $row['unit_price'];
            $_POST['item_ex'] = $row['total_unit'];
            $_POST['total_amt'] = $_POST['item_ex']*$_POST['item_price'];
            $_POST['Remarks'] = $row['Remarks'];
            $_POST['batch'] = $row['batch'];
            $_POST['tr_from'] = 'GoodsTransfer';
            $_POST['tr_no'] = $_GET[$unique];
            $_POST['sr_no'] = $row['id'];
            $_POST['ip']=$ip;
            $sent_to_warehouse_at=date('Y-m-d H:s:i');
            $item_id=$row['item_id'];
            $_SESSION['bqty_STO']=$row['total_unit'];
            $create_date=date('Y-m-d');
            $crud = new crud($journal_item);
            $crud->insert();

        }


        $jv=next_journal_voucher_id();
        $total_transfer_in_amount=find_a_field('journal_item','SUM(total_amt)','tr_from="GoodsTransfer" and tr_no='.$_GET[$unique]);
        $warehouse_from=find_a_field('warehouse','ledger_id_FG','warehouse_id='.$STO_master->warehouse_from);
        $warehouse_to_ledger=find_all_field('warehouse','','warehouse_id='.$STO_master->warehouse_to);
        $narration='FG Transfer to '.$warehouse_to_ledger->warehouse_name.', IGT NO #'.$$unique.', Remarks # '.$STO_master->remarks;
        $transaction_date=$STO_master->pi_date;
        add_to_journal_new($transaction_date, $proj_id, $jv, $date, $warehouse_to_ledger->ledger_id, $narration, $total_transfer_in_amount, 0, 'GoodsTransfer', $$unique, $$unique, 0, 0, $_SESSION['usergroup'], $c_no, $c_date, $create_date, $ip, $now, $day, $thisday, $thismonth, $thisyear,'','','');
        add_to_journal_new($transaction_date, $proj_id, $jv, $date, $warehouse_from, $narration, 0, $total_transfer_in_amount, 'GoodsTransfer', $$unique, $$unique, 0, 0, $_SESSION['usergroup'], $c_no, $c_date, $create_date, $ip, $now, $day, $thisday, $thismonth, $thisyear,'','','');
        $up_master="UPDATE ".$table." SET verifi_status='CHECKED',verifi_by='$_SESSION[userid]',verify_at='$todayss',checked_by='".$_SESSION['userid']."',checked_at='$now' where ".$unique."=".$$unique."";
        $update_table_master=mysqli_query($conn, $up_master);
        $up_details="UPDATE ".$table_details." SET verifi_status='CHECKED',status='CHECKED',verifi_by='".$_SESSION['userid']."',verify_at='$todayss' where ".$unique."=".$unique."";
        $update_table_details=mysqli_query($conn, $up_details);
        $type=1;
        unset($_POST);
        echo "<script>self.opener.location = '".$page."'; self.blur(); </script>";
        echo "<script>window.close(); </script>";
    }

//for Delete..................................
    if(isset($_POST['deleted']))
    {

        $crud = new crud($table_details);
        $condition =$unique."=".$$unique;
        $crud->delete_all($condition);

        $crud = new crud($table);
        $condition=$unique."=".$$unique;
        $crud->delete($condition);
        unset($_POST);
        unset($$unique);
        echo "<script>self.opener.location = '$page'; self.blur(); </script>";
        echo "<script>window.close(); </script>";
    }}

// data query..................................
if(isset($_POST['viewreport'])){
    if($_POST['warehouse_from']>0) 			 $warehouse_from=$_POST['warehouse_from'];
    if(isset($warehouse_from))				{$warehouse_from_CON=' and m.warehouse_from='.$warehouse_from;}
    if($_POST['verifi_status']!=='') 		$verifi_status=$_POST['verifi_status'];
    if(isset($verifi_status))				{$verifi_status_CON=' and m.verifi_status in ("'.$verifi_status.'")';}

    $sql="Select m.pi_no,m.pi_no as STO_ID,m.custom_pi_no as STO_no,m.pi_date as STO_date,w.warehouse_name as 'Warehouse / CMU From',w2.warehouse_name as transfer_to,m.remarks,u.fname as entry_by,m.returned_remarks,m.verifi_status as status
from 
".$table." m,
warehouse w,
users u,
warehouse w2

 where
  m.entry_by=u.user_id and 
 w.warehouse_id=m.warehouse_from and  
 w2.warehouse_id=m.warehouse_to and 
 m.ISSUE_TYPE in ('IGR') and
 m.pi_date between '".$_POST['f_date']."' and '".$_POST['t_date']."' and
 m.warehouse_to='".$_POST['warehouse_to']."'  
  order by m.".$unique." DESC ";
}
?>


<?php require_once 'header_content.php'; ?>
    <script type="text/javascript">
        function DoNavPOPUP(lk)
        {myWindow = window.open("<?=$page?>?<?=$unique?>="+lk, "myWindow", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no,directories=0,toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=500,left = 280,top = -1");}
    </script>
<?php require_once 'body_content.php'; ?>



<?php if(isset($_GET[$unique])){ ?>
    <!-- input section-->
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">
                <form  name="addem" id="addem" class="form-horizontal form-label-left" method="post">
                    <?require_once 'support_html.php';?>
                    <table  class="table table-striped table-bordered" style="width:100%; font-size: 11px">
                        <thead>
                        <tr style="background-color: blanchedalmond">
                            <th>SL</th>
                            <th>Code / Barcode</th>
                            <th>Item Description</th>
                            <th style="text-align: center">Unit</th>
                            <th style="text-align: center">Pack Size</th>
                            <th style="text-align:center">Batch</th>
                            <th style="text-align:center">Qty in Pcs</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        $rs="Select d.*,i.*
from 
".$table_details." d,
item_info i
 where
 i.item_id=d.item_id  and 
 d.".$unique."=".$$unique."
 order by d.id";
                        $pdetails=mysqli_query($conn, $rs);
                        while($uncheckrow=mysqli_fetch_array($pdetails)){
                            ?>
                            <tr>
                                <td style="width:3%; vertical-align:middle"><?=$js=$js+1;?></td>
                                <td><?=$uncheckrow['custom_pi_no'];?></td>
                                <td style="text-align:left"><?=$uncheckrow['item_name'];?></td>
                                <td style="text-align:center"><?=$uncheckrow['unit_name'];?></td>
                                <td style="text-align:center"><?=$uncheckrow['pack_size'];?></td>
                                <td style="width:10%; text-align:center"><?=$uncheckrow['batch'];?></td>
                                <td align="right" style="width:15%; text-align:center"><?=$uncheckrow['total_unit'];?></td>
                            </tr>
                            <?php  $amountqty=$amountqty+$uncheckrow['total_unit'];  } ?>
                        <tr style="font-weight: bold"><td colspan="6" style="text-align: right">Total = </td>
                            <td style="text-align: center"><?=number_format($amountqty)?></td>
                        </tr>
                        </tbody></table>
                    <?php
                    $GET_status=find_a_field(''.$table.'','verifi_status',''.$unique.'='.$_GET[$unique]);
                    if($GET_status=='UNCHECKED' || $GET_status=='MANUAL' || $GET_status=='Manual' || $GET_status=='RETURNED'){
                        if($STO_master->entry_by==$_SESSION['userid']){ ?>
                            <p>
                                <button style="float: left; font-size: 12px" type="submit" name="reprocess" id="reprocess" class="btn btn-danger" onclick='return window.confirm("Are you confirm?");'>Re-process & Update</button>
                                <button style="float: right;font-size: 12px" type="submit" name="checked" id="checked" class="btn btn-primary" onclick='return window.confirm("Are you confirm?");'>Check & Confirm</button>
                            </p>
                        <? } else { echo '<h6 style="text-align: center;color: red;  font-weight: bold"><i>Oops!! This STO was created by another user. So you are not able to do anything here!!</i></h6>'; } ?>

                    <? } else {echo '<h6 style="text-align: center;color: red;  font-weight: bold"><i>This Stock Transfer has been checked by QC !!</i></h6>';}?>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php if(!isset($_GET[$unique])){ ?>
    <form  name="addem" id="addem" class="form-horizontal form-label-left" method="post" >
        <table align="center" style="width: 50%;">
            <tr><td>
                    <input type="date"  style="width:150px; font-size: 11px;"  value="<?=($_POST['f_date']!='')? $_POST['f_date'] : date('Y-m-01') ?>" required   name="f_date" class="form-control col-md-7 col-xs-12" >
                <td style="width:10px; text-align:center"> -</td>
                <td><input type="date"  style="width:150px;font-size: 11px;"  value="<?=($_POST['t_date']!='')? $_POST['t_date'] : date('Y-m-d') ?>" required   name="t_date" class="form-control col-md-7 col-xs-12" ></td>
                <td style="width:10px; text-align:center"> -</td>
                <td><select class="form-control" style="width:200px; font-size: 11px;" tabindex="-1" required="required"  name="warehouse_to">
                        <option selected></option>
                        <?=advance_foreign_relation(check_plant_permission($_SESSION['userid']),($_POST['warehouse_to']>0 ? $_POST['warehouse_to'] : $_SESSION['warehouse']));?>
                    </select></td>
                <td style="padding:10px"><button type="submit" style="font-size: 11px;" name="viewreport"  class="btn btn-primary">View IGR</button></td>
            </tr></table>
        <?=$crud->report_templates_with_status($sql,$title='IGR View');?>
    </form>
<?php } ?>
<?=$html->footer_content();?>