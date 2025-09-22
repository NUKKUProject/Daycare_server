<?php
require_once('../../../pdf/WriteHTML.php');
require_once(__DIR__ . '../../../../../config/database.php');

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    die('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage());
}

// รับค่าจาก URL parameters
$academic_year = $_GET['academic_year'] ?? '';
$doctor = $_GET['doctor'] ?? '';

// ดึงข้อมูลจากฐานข้อมูล
if ($doctor === 'all') {
    // ไม่กรอง doctor_name
    $sql = "SELECT * FROM health_tooth_external 
            WHERE academic_year = :year";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year
    ]);
} else {
    // กรอง doctor_name ตามค่าที่รับมา
    $sql = "SELECT * FROM health_tooth_external 
            WHERE academic_year = :year AND doctor_name = :doctor";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year,
        ':doctor' => $doctor
    ]);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function drawDottedUnderlineTextFull($pdf, $text, $cellWidth = 70, $fontSize = 14, $new = 0, $font = 'sara', $fontStyle = '', $align = 'C')
{
    $pdf->SetFont($font, $fontStyle, $fontSize);
    $textConv = iconv('UTF-8', 'cp874', $text ?? '');

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // วาดข้อความในเซลล์
    $pdf->Cell($cellWidth, 7, $textConv, 0, $new, $align);

    // ปรับความสูงของเส้นให้ชิดกับตัวหนังสือ (ลองปรับ offset ตาม fontSize)
    $lineY = $y + 6.5; // ปรับตรงนี้ให้เหมาะ เช่น 6.8 - 7.4
    $startX = $x + 1;
    $endX = $x + $cellWidth - 2;

    // วาดเส้นประใต้ข้อความ
    for ($i = $startX; $i < $endX; $i += 1) {
        $pdf->Line($i, $lineY, $i, $lineY);
    }
}

function thaiMonth($date)
{
    $months = array(
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    );

    $dateTime = date_create($date);
    $formattedDate = date_format($dateTime, 'd') . ' ' . $months[date_format($dateTime, 'm')] . ' ' . (date_format($dateTime, 'Y') + 543);

    return $formattedDate;
}

function thaiMonthEnd($date)
{
    $months = array(
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    );

    $dateTime = date_create($date);
    $formattedDate = 'วันที่ '.date_format($dateTime, 'd') . ' เดือน ' . $months[date_format($dateTime, 'm')] . ' พ.ศ. ' . (date_format($dateTime, 'Y') + 543);

    return $formattedDate;
}

function DrawBigSlashCheckbox($pdf, $checked = true, $boxFont = 18, $slashFont = 28, $slashYOffset = -1)
{
    // 1. บันทึกตำแหน่ง X,Y ตอนนี้
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // 2. วาด [
    $pdf->SetFont('sara', '', $boxFont);
    $pdf->Cell(3, 7, '[', 0, 0, 'L'); // X+4

    if ($checked) {
        // 3. ปิ๊ง! เลื่อน Y ขึ้นเฉพาะตัว /
        $pdf->SetXY($x + 3, $y + $slashYOffset); // X ตำแหน่งหลัง [   //  Y ลดลงตามที่ต้องการ
        $pdf->SetFont('sara', '', $slashFont);
        $pdf->Cell(3, 7, '/', 0, 0, 'C');

        // 4. กลับตำแหน่ง X,Y ไปด้านหลัง /
        $pdf->SetXY($x + 5, $y);
    } else {
        $pdf->SetFont('sara', '', $boxFont);
        $pdf->Cell(2, 7, '', 0, 0, 'C');
    }
    // 5. วาด ]
    $pdf->SetFont('sara', '', $boxFont);
    $pdf->Cell(4, 7, ']', 0, 0, 'L');
}

function checkbox($pdf, $checked = true, $boxFont = 18, $slashFont = 24, $cellH = 7, $slashYOffset = -1)
{
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // วาด [
    $pdf->SetFont('sara', '', $boxFont);
    $pdf->Cell(3, $cellH, '[', 0, 0, 'L');

    if ($checked) {
        // วาด /
        $pdf->SetFont('sara', '', $slashFont);
        $pdf->SetXY($x + 3, $y + $slashYOffset);
        $pdf->Cell(3, $cellH, '/', 0, 0, 'C');
        $pdf->SetXY($x + 5, $y);
    } else {
        $pdf->Cell(2, $cellH, ' ', 0, 0, 'C');
    }
    // วาด ]
    $pdf->SetFont('sara', '', $boxFont);
    $pdf->Cell(4, $cellH, ']', 0, 0, 'L');
}


$pdf = new PDF_HTML('P', 'mm', 'A4');
$pdf->AddFont('sara', '', 'THSarabun.php');
$pdf->AddFont('sara', 'B', 'THSarabun Bold.php');
$pdf->SetMargins(10, 0, 10);
$pdf->SetAutoPageBreak(true, 10); // ตั้งขอบล่างเป็น 10 มม.
$pdf->SetFont('sara', '', 14);


foreach ($data as $index => $row) {
    $decayed = json_decode($row['decayed_teeth_positions'], true);
    $fullName = "{$row['prefix_th']} {$row['first_name']} {$row['last_name']}";
    $Date = (!empty($row['updated_at'])) ? thaiMonth($row['updated_at']) : '-';
    $DateEnd = (!empty($row['updated_at'])) ? thaiMonthEnd($row['updated_at']) : '-';
    $pdf->AddPage();

    // เริ่มต้นสร้าง PDF
    $pdf->SetFont('sara', '', 14);

    // โลโก้และหัวข้อ
    $pdf->Ln(20);
    $pdf->Image('../../../../public/assets/images/logo.png', 95.5, 3, 19, 20); // ปรับ path และขนาดโลโก้ตามต้องการ
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ใบรับรองการตรวจสุขภาพช่องปาก ประจำปีการศึกษา ' . $academic_year ?? ''), 0, 1, 'C');
    $pdf->Ln(10);

    // บรรทัดที่ 1
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'C');
    $pdf->Cell(60, 7, iconv('UTF-8', 'cp874', 'ทันตแพทย์ผู้ตรวจ ทพ./ทพ.หญิง ชื่อ-นามสกุล'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['doctor_name'], 110, 14, 1, '', '', 'C'); // ช่องชื่อ-นามสกุล


    // บรรทัดที่ 2
    $pdf->SetFont('sara', '', 14);

    $pdf->Cell(170, 10, iconv('UTF-8', 'cp874', 'สถานที่ปฏิบัติงานประจำภาควิชาทันตกรรมสำหรับเด็ก คณะทันตแพทย์ศาสตร์ มหาวิทยาลัยขอนแก่น'), 0, 1, 'L');
    // บรรทัดที่ 3

    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'C');
    $pdf->Cell(170, 7, iconv('UTF-8', 'cp874', 'หนังสือรับรองฉบับนี้ ขอรับรองว่า ข้าพเจ้า ทันตแพทย์ผู้ตรวจมีชื่อข้างต้นได้ทำการตรวจสุขภาพช่องปาก'), 0, 1, 'L');

    $pdf->Ln(1);
    drawDottedUnderlineTextFull($pdf, $fullName, 63, 14);
    $pdf->Cell(9, 7, iconv('UTF-8', 'cp874', 'ชื่อเล่น'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $row['nickname'], 25, 14);
    $pdf->Cell(7, 7, iconv('UTF-8', 'cp874', 'อายุ'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $row['age_year'] ?? '', 12, 14);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ปี'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $row['age_month'] ?? '', 12, 14);
    $pdf->Cell(8, 7, iconv('UTF-8', 'cp874', 'เดือน'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $row['age_day'] ?? '', 12, 14);
    $pdf->Cell(12, 7, iconv('UTF-8', 'cp874', 'ห้องเรียน'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $row['classroom'] ?? '', 25, 14, 1);

    $pdf->Ln(2);
    // บรรทัดที่ 5
    $pdf->Cell(129, 7, iconv('UTF-8', 'cp874', 'ณ ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย คณะพยาบาลศาสตร์ มหาวิทยาลัยขอนแก่น เมื่อวันที่'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $Date, 61, 14, 1);

    $pdf->Ln(2);
    // บรรทัดที่ 6
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ผลการตรวจเป็นดังนี้'), 0, 1, 'L');
    $pdf->Ln(2);

    // ช่องลายเซ็น 2 คอลัมน์
    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(25, 7, iconv('UTF-8', 'cp874', 'จำนวนฟันทั้งหมด'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['total_teeth'], 25, 14, '', '', '', 'C');
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 0, 'L');
    $pdf->Cell(25, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(27, 7, iconv('UTF-8', 'cp874', 'จำนวนฟันผุทั้งหมด'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['decayed_teeth'], 25, 14);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 0, 'L');
    $pdf->Ln(9);

    // ส่วนประกอบของปาก
    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(57, 7, iconv('UTF-8', 'cp874', 'ส่วนประกอบของปาก (เหงือก/ลิ้น/เพดาน)'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['oral_components'] ?? '-', 123, 14, '', '', '', 'L');
    $pdf->Ln(9);

    // ตัวเลือก checkbox แรก
    $pdf->SetFont('sara', '', 18);
    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    if ($row['teeth_status'] == 'abnormal') {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->SetFont('sara', '', 25);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + (-1));
        $pdf->Cell(3, 7, '/', 0, 0, 'C');
        $pdf->SetXY($x + 2, $y);
        $pdf->SetFont('sara', '', 18);
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    } else {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->Cell(2, 7, ' ', 0, 0, 'L');
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    }

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'มีฟันผุที่บริเวณ'), 0, 0, 'L');
    $pdf->Cell(36, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->SetFont('sara', '', 18);
    if ($row['teeth_status'] == 'normal') {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->SetFont('sara', '', 25);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + (-1));
        $pdf->Cell(3, 7, '/', 0, 0, 'C');
        $pdf->SetXY($x + 2, $y);
        $pdf->SetFont('sara', '', 18);
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    } else {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->Cell(2, 7, ' ', 0, 0, 'L');
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(17, 7, iconv('UTF-8', 'cp874', 'ยังไม่มีพันผุ'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['missing_teeth_detail'], 89, 14, '', '', '', 'L');
    $pdf->Ln(10);

    // รายละเอียดฟัน

    // ฟันหน้าบน

    $count = $decayed['upper_front_teeth'] > 0 ? $decayed['upper_front_teeth'] : '';
    // ฟันหน้าล่าง
    $count_lower_front_teeth = $decayed['lower_front_teeth'] > 0 ? $decayed['lower_front_teeth'] : '';

    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    DrawBigSlashCheckbox($pdf, $decayed['upper_front_teeth'] > 0);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันหน้าบน'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count, 25);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 0, 'L');
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');

    DrawBigSlashCheckbox($pdf, $decayed['lower_front_teeth'] > 0);

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันหน้าล่าง'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count_lower_front_teeth, 25);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 1, 'L');

    // ฟันกราม
    $count_upper_right_molar = $decayed['upper_right_molar'] > 0 ? $decayed['upper_right_molar'] : '';
    $count_upper_left_molar = $decayed['upper_left_molar'] > 0 ? $decayed['upper_left_molar'] : '';

    $pdf->Ln(1);
    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');

    DrawBigSlashCheckbox($pdf, $decayed['upper_right_molar'] > 0);

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันกรามบนขวา'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count_upper_right_molar, 25, 14);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 0, 'C');
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');

    DrawBigSlashCheckbox($pdf, $decayed['upper_left_molar'] > 0);

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันกรามบนซ้าย'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count_upper_left_molar, 25);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 1, 'C');

    // ฟันกรามล่าง

    $count_lower_right_molar = $decayed['lower_right_molar'] > 0 ? $decayed['lower_right_molar'] : '';
    $count_lower_left_molar = $decayed['lower_left_molar'] > 0 ? $decayed['lower_left_molar'] : '';

    $pdf->Ln(1);
    $pdf->Cell(10, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');

    DrawBigSlashCheckbox($pdf, $decayed['lower_right_molar'] > 0);

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันกรามล่างขวา'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count_lower_right_molar, 25, 14);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 0, 'C');
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');

    DrawBigSlashCheckbox($pdf, $decayed['lower_left_molar'] > 0);

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'ฟันกรามล่างซ้าย'), 0, 0, 'L');
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'จำนวน'), 0, 0, 'R');
    drawDottedUnderlineTextFull($pdf, $count_lower_left_molar, 25, 14);
    $pdf->Cell(5, 7, iconv('UTF-8', 'cp874', 'ซี่'), 0, 1, 'C');

    $pdf->Ln(5);

    // จำเป็นต้องได้รับการรักษา
    $pdf->Cell(37, 7, iconv('UTF-8', 'cp874', 'จำเป็นต้องได้รับการรักษา'), 0, 0, 'L');
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ดังต่อไปนี้'), 0, 1, 'L');


    $treatments = $row['treatments'];

    $treatments = is_array($row['treatments'])
        ? $row['treatments']
        : json_decode($row['treatments'], true);

    if ($treatments === null) $treatments = []; // เผื่อ decode ไม่สำเร็จ


    $pdf->Ln(1);
    // รายละเอียดการรักษา
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('filling', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'อุดฟัน'), 0, 0, 'L');
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('root_canal', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(50, 7, iconv('UTF-8', 'cp874', 'รักษาคลองรากฟัน'), 0, 0, 'L');
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('crown', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ครอบฟัน'), 0, 1, 'L');

    $pdf->Ln(1);
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('fluoride', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'เคลือบฟลูออไรด์'), 0, 0, 'L');
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('fluoride_molar', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(50, 7, iconv('UTF-8', 'cp874', 'เคลือบหลุมร่องฟันที่ฟันกราม'), 0, 0, 'L');
    $pdf->SetFont('sara', '', 18);
    checkbox($pdf, in_array('extraction', $treatments), 18, 24, 7, -1);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ถอนฟัน'), 0, 1, 'L');
    $pdf->Ln(1);
    $pdf->Cell(15, 7, iconv('UTF-8', 'cp874', 'อื่นๆ ได้แก่'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['other_treatment_detail'], 175, 14, 1, '', '', 'L');
    $pdf->Ln(10);

    // ความเห็ดเพิ่มเติม
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ความเร่งด่วนในการรักษา'), 0, 1, 'L');
    $pdf->Ln(1);
    if ($row['urgency'] == 'urgent') {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->SetFont('sara', '', 25);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + (-1));
        $pdf->Cell(3, 7, '/', 0, 0, 'C');
        $pdf->SetXY($x + 2, $y);
        $pdf->SetFont('sara', '', 18);
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    } else {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->Cell(2, 7, ' ', 0, 0, 'L');
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    }

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(60, 7, iconv('UTF-8', 'cp874', 'ควรรีบไปรับการรักษาโดยด่วน'), 0, 0, 'L');
    if ($row['urgency'] == 'not_urgent') {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->SetFont('sara', '', 25);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + (-1));
        $pdf->Cell(3, 7, '/', 0, 0, 'C');
        $pdf->SetXY($x + 2, $y);
        $pdf->SetFont('sara', '', 18);
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    } else {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->Cell(2, 7, ' ', 0, 0, 'L');
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(50, 7, iconv('UTF-8', 'cp874', 'ไม่เร่งด่วน'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 18);
    $pdf->Ln(1);
    if ($row['urgency'] == 'preventable') {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->SetFont('sara', '', 25);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + (-1));
        $pdf->Cell(3, 7, '/', 0, 0, 'C');
        $pdf->SetXY($x + 2, $y);
        $pdf->SetFont('sara', '', 18);
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    } else {
        $pdf->Cell(3, 7, '[', 0, 0, 'L');
        $pdf->Cell(2, 7, ' ', 0, 0, 'L');
        $pdf->Cell(4, 7, ']', 0, 0, 'L');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(60, 7, iconv('UTF-8', 'cp874', 'สามารถผัดผ่อนได้ในระยะเวลาไม่นานนัก'), 0, 1, 'L');

    $pdf->Ln(5);

    // ลายเซ็นผู้ตรวจ
    $pdf->Cell(65, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(32, 7, iconv('UTF-8', 'cp874', 'ลงชื่อทันตแพทย์ผู้ตรวจ'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, '', 93);
    $pdf->Ln(10);

    $pdf->Cell(65, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(49, 7, iconv('UTF-8', 'cp874', 'ลงชื่อผู้ปกครองรับทราบผลการตรวจ'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, '', 76);
    $pdf->Ln(10);

    // วันที่
    $pdf->Cell(65, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'L');
    $pdf->Cell(0, 7, iconv('UTF-8', 'cp874', $DateEnd), 0, 1, 'L');
    $pdf->Ln(10);

    // หมายเหตุ
    $pdf->SetFont('sara', '');
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'หมายเหตุ : การตรวจทำภายใต้ข้อจำกัด ควรได้รับการตรวจซ้ำก่อนทำการรักษาและควรตรวจฟันซ้ำทุก 6 เดือน'), 0, 0, 'L');


    // โลโก้ INU ด้านล่าง
    $pdf->Ln(20);
    $pdf->Image('../../../../public/assets/images/end_paper.png', 50, 270, 110, 20); // ปรับ path และขนาดโลโก้ตามต้องการ
}

$filename = "รายงานตรวจสุขภาพฟัน_" . $doctor . ".pdf";

// Clean/sanitize เพื่อความปลอดภัย และเตรียมส่ง UTF-8
$clean_filename = preg_replace('/[^\wก-๙เแโใไ\s\.]/u', '', $filename); // เอาอักขระพิเศษออก
$encoded_filename = rawurlencode($clean_filename);

// ส่งออก PDF เป็น string
$pdfContent = $pdf->Output('', 'S');

// ส่ง header ด้วยตนเอง เพื่อรองรับ UTF-8 ชื่อภาษาไทย
header("Content-Type: application/pdf");
header("Content-Length: " . strlen($pdfContent));

// ส่งชื่อไฟล์แบบ UTF-8 ที่ browser รองรับ (RFC 6266)
header("Content-Disposition: attachment; filename*=UTF-8''$encoded_filename");

// ส่งข้อมูล PDF
echo $pdfContent;
exit;
