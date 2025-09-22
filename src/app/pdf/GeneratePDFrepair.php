<?php
require('./fpdf/fpdf.php');
require_once '../dbconfig.php';

///////////  แสดงตำแหน่ง
function name_position($id) {
  global $conn; // Assuming $conn is your database connection variable

  $sql_position  = "SELECT position FROM account WHERE id_person = '$id' LIMIT 1";
  $result_position  = mysqli_query($conn, $sql_position );

  if ($row_position  = mysqli_fetch_assoc($result_position )) {
      $position_durable = $row_position ['position'];
      return $position_durable;
  } else {
      $position_durable = '';
      return $position_durable;
  }
}
///////////  แสดงชื่อหน่วยงาน
function name_department($id) {
    global $conn; // Assuming $conn is your database connection variable

    $sql_department = "SELECT name FROM department WHERE id_department = '$id' LIMIT 1";
    $result_department = mysqli_query($conn, $sql_department);

    if ($row_department = mysqli_fetch_assoc($result_department)) {
        $department_durable = $row_department['name'];
        return $department_durable;
    } else {
        $department_durable = '';
        return $department_durable;
    }
}
///////////  แสดงชื่อด้วยบัตร ปชช id_person 
function name_person($id) {
    global $conn; // Assuming $conn is your database connection variable
  
    $sql_person = "SELECT name_title,first_name,last_name FROM account WHERE id_person = '$id' LIMIT 1";
    $result_person = mysqli_query($conn, $sql_person);
  
    if ($row_person = mysqli_fetch_assoc($result_person)) {
      $person_name = $row_person['name_title'] . $row_person['first_name'].' ' . $row_person['last_name'];
        return $person_name;
    } else {
      $person_name = '';
        return$person_name;
    }
  }
  
  function thaiMonth($date) {
    $months = array(
        '01' => 'ม.ค.',
        '02' => 'ก.พ.',
        '03' => 'มี.ค.',
        '04' => 'เม.ย.',
        '05' => 'พ.ค.',
        '06' => 'มิ.ย.',
        '07' => 'ก.ค.',
        '08' => 'ส.ค.',
        '09' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    );

    $dateTime = date_create($date);
    $formattedDate = date_format($dateTime, 'd').' '.$months[date_format($dateTime, 'm')].' '.(date_format($dateTime, 'Y')+543);

    return $formattedDate;
}

$id=$_GET['id'];
$sql = "SELECT *  FROM repair_report_pd05 WHERE id_repair = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$department_name = isset($row['department_id']) ? name_department($row['department_id']) : '';
$date_report_in = isset($row['date_report_in']) ?thaiMonth($row['date_report_in'])  : '';
$reasons = isset($row['reasons']) ? $row['reasons'] : '';
$asset_name = isset($row['asset_name']) ? $row['asset_name'] : '';
$asset_id = isset($row['asset_id']) ? $row['asset_id'] : '';
$asset_detail = isset($row['asset_detail']) ? $row['asset_detail'] : '';
$amount = isset($row['amount']) ? $row['amount'] : '';
$last_amount = isset($row['last_amount']) ? $row['last_amount'] : '';
$recomment = isset($row['recomment']) ? $row['recomment'] : '';
$report_signature = $row['report_signature'];
$report_name = isset($row['report_name']) ? $row['report_name'] : '';
$signature_tech = $row['signature_tech'];
$tech_name = isset($row['tech_id']) ? name_person($row['tech_id']) : '';
$date_tech_confirm = isset($row['date_tech_confirm']) ? thaiMonth($row['date_tech_confirm']) : '';
$inspector_name1 = isset($row['inspector_name1']) ? $row['inspector_name1'] : '';
$inspector_name2 = isset($row['inspector_name2']) ? $row['inspector_name2'] : '';
$inspector_name3 = isset($row['inspector_name3']) ? $row['inspector_name3'] : '';
$cancel_comment = $row['cancel_comment'];
$id_head =  name_person($row['id_head']);
$signature_head = $row['signature_head'];
$position_head = name_position( $row['id_head']);
$date_update_head = isset($row['date_update_head']) ?thaiMonth($row['date_update_head'])  : '';
$signature_head_klung = $row['signature_head_klung'];
$id_head_klung = name_person( $row['id_head_klung']);
$position_head_klung = name_position($row['id_head_klung']);
$cancel_comment_head_klung =$row['cancel_comment_head_klung'];
$date_head_klung_update = isset($row['date_head_klung_update']) ? thaiMonth($row['date_head_klung_update']) : '';
$signature_director = $row['signature_director'];
$cancel_comment_director =$row['cancel_comment_director'];
$id_director = name_person($row['id_director']);
$position_director = name_position($row['id_director']);
$date_director_update = isset($row['date_director_update']) ? thaiMonth($row['date_director_update']) : '';
$cancel_comment_dean =$row['cancel_comment_dean'];
$signature_dean = $row['signature_dean'];
$cancel_comment_dean =$row['cancel_comment_dean'];
$id_dean = name_person($row['id_dean']);
$position_dean = name_position($row['id_dean']);
$date_dean_update = isset($row['date_dean_update']) ? thaiMonth($row['date_dean_update']) : '';


$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->AddFont('sara', '', 'THSarabun.php');
$pdf->AddFont('sara', 'B', 'THSarabun Bold.php');
$pdf->SetMargins(0, 0, 0);
$pdf->Image('./form_pd05.png', 0, 0,-200);

//วันที่แจ้งซ่อม
$pdf->SetY(43);
$pdf->SetX(98);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(55, 5, iconv('utf-8', 'cp874', $date_report_in), 0, 1, 'C');

//ชื่อหน่วยงาน
$pdf->SetY(60);
$pdf->SetX(52);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(75, 5, iconv('utf-8', 'cp874', $department_name), 0, 1, 'C');


//เหตุผลความจำเป็น
$pdf->SetY(67);
$pdf->SetX(88);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(110, 5, iconv('utf-8', 'cp874', $reasons), 0, 1, 'C');


$pdf->SetY(92.5);
$pdf->SetX(23);
$pdf->SetFont('sara', '', 14);
$pdf->MultiCell(47, 7, iconv('utf-8', 'cp874', $asset_name),0,'L');

$pdf->SetY(92.5);
$pdf->SetX(72);
$pdf->SetFont('sara', '', 14);
$pdf->SetXY(72, 92.5);
$pdf->MultiCell(35, 8, iconv('utf-8', 'cp874', $asset_id), 0, 'C');

$pdf->SetY(93);
$pdf->SetX(110);
$pdf->SetFont('sara', '', 14);
$pdf->MultiCell(50, 7, iconv('utf-8', 'cp874', $asset_detail),0,'L');

$pdf->SetY(98);
$pdf->SetX(163);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(17, 5, iconv('utf-8', 'cp874', $amount), 0, 1, 'C');

$pdf->SetY(98);
$pdf->SetX(181);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(17, 5, iconv('utf-8', 'cp874', $last_amount), 0, 1, 'C');

$pdf->SetY(125);
$pdf->SetX(79);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(120, 5, iconv('utf-8', 'cp874', $recomment), 0, 1, 'C');

if(isset($signature_tech)){
$pdf->Image("../image_signature/$signature_tech", 25, 129, 25, 25);
}else{

}
$pdf->SetY(148);
$pdf->SetX(13);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(45, 5, iconv('utf-8', 'cp874', $tech_name), 0, 1, 'C');

$pdf->SetY(153);
$pdf->SetX(13);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(45, 5, iconv('utf-8', 'cp874', $date_tech_confirm), 0, 1, 'C');

if(isset($report_signature)){
$pdf->Image("../image_signature/$report_signature", 140, 129, 25, 25);
}else{

}

$pdf->SetY(150.5);
$pdf->SetX(130.5);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(45, 5, iconv('utf-8', 'cp874', $report_name), 0, 1, 'C');

$pdf->SetY(166.5);
$pdf->SetX(25);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(57, 5, iconv('utf-8', 'cp874', $inspector_name1), 0, 1, 'C');

$pdf->SetY(166.5);
$pdf->SetX(83);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(57, 5, iconv('utf-8', 'cp874', $inspector_name2), 0, 1, 'C');

$pdf->SetY(166.5);
$pdf->SetX(137);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(57, 5, iconv('utf-8', 'cp874', $inspector_name3), 0, 1, 'C');


if(isset($signature_head)){
  $pdf->Image("../image_signature/$signature_head", 50, 189, 25, 25); 
  
$pdf->SetY(210);
$pdf->SetX(34);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(52, 5, iconv('utf-8', 'cp874', "( $id_head ) "), 0, 1, 'C');
}


if(isset($cancel_comment)){
  $pdf->SetY(177.5);
$pdf->SetX(17);
$pdf->SetFont('sara', 'B', 16);
$pdf->SetDrawColor(255, 0, 0);
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(75, 5, iconv('utf-8', 'cp874', "ยกเลิกเนื่องจาก $cancel_comment"), 0, 1, 'L');
}


$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(216);
$pdf->SetX(34);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(52, 5, iconv('utf-8', 'cp874',$position_head ), 0, 1, 'C');

$pdf->SetY(222.5);
$pdf->SetX(34);
$pdf->SetFont('sara', '', 14);
$pdf->Cell(52, 5, iconv('utf-8', 'cp874',$date_update_head ), 0, 1, 'C');



if(isset($signature_head_klung)){
  $pdf->Image("../image_signature/$signature_head_klung",145, 199, 25, 25);

  if(!isset($cancel_comment_head_klung)){
  $pdf->SetY(189);
  $pdf->SetX(91);
  $pdf->SetFont('sara', '', 25);
  $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');
  }else{
    $pdf->SetY(196);
    $pdf->SetX(91);
    $pdf->SetFont('sara', '', 25);
    $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');

    $pdf->SetY(194);
    $pdf->SetX(163);
    $pdf->SetFont('sara', '', 13);
    $pdf->Cell(40, 10, iconv('utf-8', 'cp874', $cancel_comment_head_klung), 0, 1, 'R');
  }


  $pdf->SetY(218);
  $pdf->SetX(131);
  $pdf->SetFont('sara', '', 14);
  $pdf->Cell(54, 5, iconv('utf-8', 'cp874', "( $id_head_klung )"), 0, 1, 'C');

  $pdf->SetY(223);
  $pdf->SetX(122);
  $pdf->SetFont('sara', '', 14);
  $pdf->MultiCell(72, 5, iconv('utf-8', 'cp874', "$position_head_klung"), 0, 'C');

  $pdf->SetY(232.5);
  $pdf->SetX(122);
  $pdf->SetFont('sara', '', 14);
  $pdf->MultiCell(72, 5, iconv('utf-8', 'cp874', $date_head_klung_update), 0, 'C');

}


if(isset($signature_director)){
  $pdf->Image("../image_signature/$signature_director", 54, 262, 20, 20);
  if(!isset($cancel_comment_director)){
  $pdf->SetY(255);
  $pdf->SetX(6.5);
  $pdf->SetFont('sara', '', 25);
  $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');
  } else{
    $pdf->SetY(260.5);
    $pdf->SetX(6.5);
    $pdf->SetFont('sara', '', 25);
    $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');

    $pdf->SetY(259);
    $pdf->SetX(65);
    $pdf->SetFont('sara', '', 13);
    $pdf->Cell(40, 10, iconv('utf-8', 'cp874', $cancel_comment_director), 0, 1, 'R');
  }
  $pdf->SetAutoPageBreak(false);
  $pdf->SetY(275);
  $pdf->SetX(35);
  $pdf->SetFont('sara', '', 14);
  $pdf->Cell(62, 5, iconv('utf-8', 'cp874', "( $id_director )"), 0, 1, 'C');

  $pdf->SetY(281);
  $pdf->SetX(32);
  $pdf->SetFont('sara', '', 14); 
  $pdf->MultiCell(72, 5, iconv('utf-8', 'cp874', " $position_director "), 0, 'C');
 
  $pdf->SetY(287);
  $pdf->SetX(32);
  $pdf->SetFont('sara', '', 14); 
  $pdf->MultiCell(72, 5, iconv('utf-8', 'cp874', " $date_director_update "), 0, 'C');
}

if(isset($signature_dean)){
  $pdf->Image("../image_signature/$signature_dean", 150, 252, 20, 20);
  if(!isset($cancel_comment_dean)){
    $pdf->SetY(250);
    $pdf->SetX(82);
    $pdf->SetFont('sara', '', 25);
    $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');
  }else{
    $pdf->SetY(255);
    $pdf->SetX(83);
    $pdf->SetFont('sara', '', 25);
    $pdf->Cell(52, 5, iconv('utf-8', 'cp874',"/" ), 0, 1, 'C');

    $pdf->SetY(252);
    $pdf->SetX(140);
    $pdf->SetFont('sara', '', 13);
    $pdf->Cell(55, 10, iconv('utf-8', 'cp874', $cancel_comment_dean), 0, 1, 'C');
  }
  $pdf->SetY(269);
  $pdf->SetX(130);
  $pdf->SetFont('sara', '', 14);
  $pdf->Cell(62, 5, iconv('utf-8', 'cp874', " ( $id_dean ) "), 0 ,1, 'C');

  $pdf->SetY(274.5);
  $pdf->SetX(130);
  $pdf->SetFont('sara', '', 14);
  $pdf->Cell(62, 5, iconv('utf-8', 'cp874', " $position_dean "), 0, 1, 'C');

  $pdf->SetY(280);
  $pdf->SetX(130);
  $pdf->SetFont('sara', '', 14);
  $pdf->Cell(62, 5, iconv('utf-8', 'cp874', " $date_dean_update "), 0, 1, 'C');
}

$pdf->Output();
?>