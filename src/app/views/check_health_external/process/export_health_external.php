<?php

require_once('../../../pdf/WriteHTML.php');
require_once(__DIR__ . '../../../../../config/database.php');
include __DIR__ . '../../../../include/auth/auth.php';
$pdf1 = new PDF_HTML();

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    die('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage());
}

// ตรวจสอบสิทธิ์การเข้าถึง
checkUserRole(['admin', 'teacher', 'doctor']);

// รับค่าจาก URL parameters
$academic_year = $_GET['academic_year'] ?? '';
$doctor = $_GET['doctor'] ?? '';

// ดึงข้อมูลจากฐานข้อมูล
if ($doctor === 'all') {
    // ไม่กรอง doctor_name
    $sql = "SELECT * FROM health_data_external 
            WHERE academic_year = :year";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year
    ]);
} else {
    // กรอง doctor_name ตามค่าที่รับมา
    $sql = "SELECT * FROM health_data_external 
            WHERE academic_year = :year AND doctor_name = :doctor";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':year' => $academic_year,
        ':doctor' => $doctor
    ]);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

//print_r($data); // สำหรับดีบัก
$pdf = new PDF_HTML('P', 'mm', 'A4');
$pdf->AddFont('sara', '', 'THSarabun.php');
$pdf->AddFont('sara', 'B', 'THSarabun Bold.php');
$pdf->SetMargins(10, 0, 10);
$pdf->SetAutoPageBreak(true, 10); // ตั้งขอบล่างเป็น 10 มม.
$pdf->SetFont('sara', '', 14);
function safeIconv($text)
{
    return @iconv('UTF-8', 'cp874//IGNORE', $text);
}
function thaiMonth($date)
{
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
    $formattedDate = date_format($dateTime, 'd') . ' ' . $months[date_format($dateTime, 'm')] . ' ' . (date_format($dateTime, 'Y') + 543);

    return $formattedDate;
}
function drawDottedUnderlineTextFull($pdf, $text, $cellWidth = 70, $fontSize = 14, $new = 0, $font = 'sara', $fontStyle = '', $align = 'C')
{
    $pdf->SetFont($font, $fontStyle, $fontSize);
    $textConv = iconv('UTF-8', 'cp874', $text ?? '');

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // วาดข้อความในเซลล์
    $pdf->Cell($cellWidth, 8, $textConv, 0, $new, $align);

    // ปรับความสูงของเส้นให้ชิดกับตัวหนังสือ (ลองปรับ offset ตาม fontSize)
    $lineY = $y + 7.2; // ปรับตรงนี้ให้เหมาะ เช่น 6.8 - 7.4
    $startX = $x + 2;
    $endX = $x + $cellWidth - 2;

    // วาดเส้นประใต้ข้อความ
    for ($i = $startX; $i < $endX; $i += 2) {
        $pdf->Line($i, $lineY, $i + 1, $lineY);
    }
}
if (!function_exists('drawOptionCell')) {
    function drawOptionCell($pdf, $selected, $current, $cellWidth = 38)
    {
        if (empty($selected)) {
            $mark = '';
        } else {
            $mark = ($selected === $current) ? ' / ' : '';
        }
        $pdf->SetFont('sara', '', 22);
        $pdf->Cell($cellWidth, 12, safeIconv($mark), 1, 0, 'C');
    }
}
foreach ($data as $index => $row) {
    $pdf->AddPage();

    // แปลงค่าจาก JSON
    $vitalSigns = json_decode($row['vital_signs'], true);
    $bpDate = (!empty($vitalSigns['bp_date'])) ? thaiMonth($vitalSigns['bp_date']) : '-';
    $behavior = json_decode($row['behavior'], true);
    $detail = $behavior['detail'] ?? '';
    $status = $behavior['status'] ?? '';
    $physicalMeasures = json_decode($row['physical_measures'], true);
    $development = json_decode($row['development_assessment'], true);
    $physicalExam = json_decode($row['physical_exam'], true);
    $neuro = json_decode($row['neurological'], true);



    // ข้อมูลทั่วไป
    $examDate = (!empty($row['exam_date'])) ? thaiMonth(($row['exam_date'])) : '-';
    $academicYear = $row['academic_year'] ?? '-';
    $fullName = "{$row['prefix_th']} {$row['first_name']} {$row['last_name_th']}";
    $nickname = $row['nickname'] ?? '-';
    $classroom = $row['classroom'] ?? '-';
    $birth_date = (!empty($row['birth_date'])) ? thaiMonth($row['birth_date']) : '-';
    $age_year = !empty($row['age_year']) ? ($row['age_year']) : '-';
    $age_month = !empty($row['age_month']) ? ($row['age_month']) : '-';
    $age_day = !empty($row['age_day']) ? ($row['age_day']) : '-';
    $age_day = !empty($row['age_day']) ? ($row['age_day']) : '-';
    // echo "<pre>";
    // print_r($physicalMeasures['height_for_age'][0]);
    // echo "</pre>";




    // --- ส่วนหัว ---
    $pdf->Ln(5);

    $pdf->Image('../../../../public/assets/images/logo.png', 22, 3, 25, 26); // ปรับ path และขนาดโลโก้ตามต้องการ

    $pdf->SetFont('sara', 'B', 18);
    $pdf->Cell(0, 8, iconv('UTF-8', 'cp874', 'แบบบันทึกการตรวจสุขภาพเด็ก โดยกุมารแพทย์'), 0, 1, 'C');
    $pdf->SetFont('sara', 'B', 16);
    $pdf->Cell(0, 7, iconv('UTF-8', 'cp874', 'ณ ศูนย์ความเป็นเลิศในการพัฒนาเด็กปฐมวัย'), 0, 1, 'C');


    $pdf->Cell(40, 7, iconv('UTF-8', 'cp874', ''), 0, 0, 'C');
    $pdf->Cell(20, 7, iconv('UTF-8', 'cp874', 'วัน/เดือน/ปี'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $examDate ?? '', 30, 16); // ช่องกว้าง 70, ฟอนต์ 16pt
    $pdf->SetFont('sara', 'B', 16);
    $pdf->Cell(30, 7, iconv('UTF-8', 'cp874', 'ประจำปีการศึกษา'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $academicYear ?? '', 30, 16); // ช่องกว้าง 70, ฟอนต์ 16pt
    $pdf->Cell(40, 7, iconv('UTF-8', 'cp874', ''), 0, 1, 'C');
    $pdf->Ln(2);
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(15, 8, iconv('UTF-8', 'cp874', 'ข้าพเจ้า'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $row['doctor_name'] ?? '', 80, 14); // ช่องกว้าง 70, ฟอนต์ 16pt


    $pdf->Cell(95, 8, iconv('UTF-8', 'cp874', 'จากภาควิชากุมารเวชศาสตร์ คณะแพทยศาสตร์ มหาวิทยาลัยขอนแก่น'), 0, 1, 'C');

    $pdf->Cell(25, 8, iconv('UTF-8', 'cp874', 'ได้ตรวจร่างกาย'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $fullName ?? '', 60, 14, 0);


    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', 'ชื่อเล่น'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $nickname ?? '', 30, 14, 0);


    $pdf->Cell(25, 8, iconv('UTF-8', 'cp874', 'ห้องเรียน'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $classroom ?? '', 30, 14, 1);


    $pdf->Cell(25, 8, iconv('UTF-8', 'cp874', 'วัน/เดือน/ปีเกิด'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, $birth_date ?? '', 45, 14, 0);


    $pdf->Cell(30, 8, iconv('UTF-8', 'cp874', 'อายุ ณ วันตรวจ'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $age_year ?? '', 20, 14, 0);
    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', 'ปี'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $age_month ?? '', 20, 14, 0);
    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', 'เดือน'), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, $age_day ?? '', 20, 14, 0);
    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', 'วัน'), 0, 1, 'C');


    $pdf->Ln(2);

    $pdf->SetFont('sara', 'B', 14); // ขีดเส้นใต้
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', 'สัญญาณชีพ'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 14); // ขีดเส้นใต้

    $pdf->Cell(26, 8, iconv('UTF-8', 'cp874', 'อุณหภูมิร่างกาย'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, ($vitalSigns['temperature'] ?? '-') . " C" ?? 'C', 30, 14, 0);

    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', '(ก่อนตรวจ)'), 0, 1, 'C');

    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', 'วันที่'), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, ($bpDate ?? '-') ?? '-', 35, 14, 1);


    $pdf->Cell(27, 8, iconv('UTF-8', 'cp874', 'ความดันโลหิต ='), 0, 0, 'L');
    drawDottedUnderlineTextFull($pdf, ($vitalSigns['bp'] ?? '-') ?? '-', 25, 14, 0);

    $pdf->Cell(18, 8, iconv('UTF-8', 'cp874', 'ครั้ง/นาที'), 0, 0, 'C');
    $pdf->Cell(18, 8, iconv('UTF-8', 'cp874', 'ชีพจร ='), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, ($vitalSigns['pulse'] ?? '-') ?? '-', 18, 14, 0);

    $pdf->Cell(18, 8, iconv('UTF-8', 'cp874', 'ครั้ง/นาที'), 0, 0, 'C');
    $pdf->Cell(30, 8, iconv('UTF-8', 'cp874', 'อัตราการหายใจ ='), 0, 0, 'C');
    drawDottedUnderlineTextFull($pdf, ($vitalSigns['respiration'] ?? '-') ?? '-', 18, 14, 0);
    $pdf->Cell(18, 8, iconv('UTF-8', 'cp874', 'ครั้ง/นาที'), 0, 1, 'C');

    $pdf->Ln(2);

    $pdf->SetFont('sara', 'B', 14); // ขีดเส้นใต้
    $pdf->Cell(40, 8, iconv('UTF-8', 'cp874', 'ผลการตรวจสุขภาพ ดังนี้'), 0, 1, 'L');
    $pdf->SetFont('sara', 'BU', 14); // ขีดเส้นใต้
    $pdf->Cell(48, 8, iconv('UTF-8', 'cp874', 'การประเมินพฤติกรรมของเด็ก'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 14); // กลับเป็นปกติ 
    $pdf->Cell(190, 8, iconv('UTF-8', 'cp874', 'ปัญหาด้านพฤติกรรม เช่น ความสนใจสั้น ก้าวร้าว ไม่สบตา ไม่สามารถสื่อสารและอื่นๆ'), 0, 1, 'L');

    // รูปแบบแสดงผลสถานะ
    // มี = (✓) มี    ไม่มี = (✓) ไม่มี
    if ($status == 'has') {
        $no_left = '(';
        $no_mark = ' ';
        $no_right = ')';

        $yes_left = '(';
        $yes_mark = '/'; // ขีดเฉียงใหญ่
        $yes_right = ')';
    } else if ($status == 'none') {
        $no_left = '(';
        $no_mark = '/';
        $no_right = ')';

        $yes_left = '(';
        $yes_mark = ' ';
        $yes_right = ')';
    } else {
        $no_left = '(';
        $no_mark = '';
        $no_right = ')';

        $yes_left = '(';
        $yes_mark = ' ';
        $yes_right = ')';
    }

    // ช่อง "ไม่มี"
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(3, 8, $no_left, 0, 0, 'R');

    $pdf->SetFont('sara', '', ($no_mark == '/' ? 22 : 14)); // ขนาดใหญ่ (=22) ถ้าเป็น "/"
    $pdf->Cell(3, 8, $no_mark, 0, 0, 'C');

    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(3, 8, $no_right, 0, 0, 'L');
    $pdf->Cell(15, 8, safeIconv('ไม่มี'), 0, 1, 'L');


    $pdf->Cell(3, 8, $yes_left, 0, 0, 'R');
    $pdf->SetFont('sara', '', ($yes_mark == '/' ? 22 : 14));
    $pdf->Cell(3, 8, $yes_mark, 0, 0, 'C');
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(3, 8, $yes_right, 0, 0, 'L');
    $pdf->Cell(10, 8, safeIconv('มี'), 0, 0, 'L');

    // ช่อง "ระบุ:" กับรายละเอียด
    $pdf->Cell(10, 8, safeIconv('ระบุ:'), 0, 0, 'L');

    if (!function_exists('formatTextToTwoLines')) {
        function formatTextToTwoLines($text, $charsPerLine = 120)
        {
            $text = strip_tags(trim($text));

            if (mb_strlen($text, 'UTF-8') <= $charsPerLine * 2) {
                return $text;
            }

            $maxLength = $charsPerLine * 2 - 3;
            $truncated = mb_substr($text, 0, $maxLength, 'UTF-8');

            $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
            if ($lastSpace !== false) {
                $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
            }

            return $truncated . '...';
        }
    }

    if (!empty($detail)) {
        $formattedDetail = formatTextToTwoLines($detail);
        $pdf->SetXY(40, $pdf->GetY()); // กำหนดตำแหน่ง X
        $pdf->MultiCell(170, 8, safeIconv($formattedDetail), 0, 'L');
    } else {
        drawDottedUnderlineTextFull($pdf, '', 160, 14, 1);
        drawDottedUnderlineTextFull($pdf, '', 190, 14, 1);
    }


    $pdf->Ln(2);

    $pdf->SetFont('sara', 'B', 14);
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', 'การเจริญเติบโต'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', "ส่วนสูง:"), 0, 0);
    drawDottedUnderlineTextFull($pdf, $physicalMeasures['height'] ?? '-', 20, 14, 0);
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', "เซนติเมตร"), 0, 0);
    $pdf->Cell(10, 8, iconv('UTF-8', 'cp874', "น้ำหนัก:"), 0, 0);
    drawDottedUnderlineTextFull($pdf, $physicalMeasures['weight'] ?? '-', 20, 14, 0);
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', "กิโลกรัม"), 0, 1);


    // แถวหัวตาราง
    $pdf->SetFont('sara', 'B', 14);
    $pdf->Cell(190, 8, safeIconv('น้ำหนักตามเกณฑ์อายุ'), 1, 1, 'C');
    $pdf->Cell(38, 8, safeIconv('น้อยกว่าเกณฑ์'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ค่อนข้างน้อย'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ตามเกณฑ์'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ค่อนข้างมาก'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('มากเกินเกณฑ์"'), 1, 1, 'C');
    $pdf->SetFont('sara', '', 14);

    $selected = $physicalMeasures['weight_for_age'][0] ?? '';
    drawOptionCell($pdf, $selected, 'น้อยกว่าเกณฑ์');
    drawOptionCell($pdf, $selected, 'ค่อนข้างน้อย');
    drawOptionCell($pdf, $selected, 'ตามเกณฑ์');
    drawOptionCell($pdf, $selected, 'ค่อนข้างมาก');
    drawOptionCell($pdf, $selected, 'มากกว่าเกณฑ์');
    $pdf->Ln();


    // ส่วนสูงตามเกณฑ์อายุ
    $pdf->SetFont('sara', 'B', 14);
    $pdf->Cell(190, 8, safeIconv('ส่วนสูงตามเกณฑ์อายุ'), 1, 1, 'C');
    $pdf->Cell(38, 8, safeIconv('เตี้ย'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ค่อนข้างเตี้ย'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ตามเกณฑ์'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('ค่อนข้างสูง'), 1, 0, 'C');
    $pdf->Cell(38, 8, safeIconv('สูง'), 1, 1, 'C');
    $pdf->SetFont('sara', '', 14);


    // แถวว่างสำหรับวงหรือกรอกค่า
    // วาดแถวช่องเลือกตามค่า
    $selected = $physicalMeasures['height_for_age'][0] ?? '';
    drawOptionCell($pdf, $selected, 'เตี้ย');
    drawOptionCell($pdf, $selected, 'ค่อนข้างเตี้ย');
    drawOptionCell($pdf, $selected, 'ตามเกณฑ์');
    drawOptionCell($pdf, $selected, 'ค่อนข้างสูง');
    drawOptionCell($pdf, $selected, 'สูง');
    $pdf->Ln();




    // น้ำหนักตามเกณฑ์ส่วนสูง (3 ช่อง)
    $pdf->SetFont('sara', 'B', 14);
    $pdf->Cell(190, 8, safeIconv('น้ำหนักตามเกณฑ์ส่วนสูง'), 1, 1, 'C');
    $pdf->Cell(31.67, 8, safeIconv('ผอม'), 1, 0, 'C');
    $pdf->Cell(31.67, 8, safeIconv('ค่อนข้างผอม'), 1, 0, 'C');
    $pdf->Cell(31.67, 8, safeIconv('สมส่วน'), 1, 0, 'C');
    $pdf->Cell(31.67, 8, safeIconv('ท้วม'), 1, 0, 'C');
    $pdf->Cell(31.67, 8, safeIconv('เริ่มอ้วน'), 1, 0, 'C');
    $pdf->Cell(31.67, 8, safeIconv('อ้วน'), 1, 1, 'C');
    $pdf->SetFont('sara', '', 14);

    $selected = $physicalMeasures['weight_for_height'][0] ?? '';
    drawOptionCell($pdf, $selected, 'ผอม', 31.67);
    drawOptionCell($pdf, $selected, 'ค่อนข้างผอม', 31.67);
    drawOptionCell($pdf, $selected, 'สมส่วน', 31.67);
    drawOptionCell($pdf, $selected, 'ท้วม', 31.67);
    drawOptionCell($pdf, $selected, 'เริ่มอ้วน', 31.67);
    drawOptionCell($pdf, $selected, 'อ้วน', 31.67);
    $pdf->Ln();


    $pdf->SetFont('sara', '', 14);

    // แสดงข้อมูลเส้นรอบศรีษะ
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', "เส้นรอบศรีษะ:"), 0, 0);
    drawDottedUnderlineTextFull($pdf, $physicalMeasures['weight'] ?? '-', 20, 14, 0);
    $pdf->Cell(20, 8, iconv('UTF-8', 'cp874', "เซนติเมตร"), 0, 1);

    // เตรียมข้อมูล
    $texts = [
        'น้อยกว่า/เปอร์เซ็นไทล์ที่/3',
        'อยู่ระหว่าง/เปอร์เซ็นไทล์ที่/3 - 15',
        'อยู่ระหว่าง/เปอร์เซ็นไทล์ที่/15 - 50',
        'อยู่ระหว่าง/เปอร์เซ็นไทล์ที่/50 - 85',
        'อยู่ระหว่าง/เปอร์เซ็นไทล์ที่/85 - 97',
        'มากกว่า/เปอร์เซ็นไทล์ที่/97',
        '/มีความเสี่ยง/'
    ];

    // ตั้งค่าขนาด
    $w = 27.14;
    $h = 8;

    // ตำแหน่งเริ่มต้น
    $xStart = $pdf->GetX();
    $yStart = $pdf->GetY();

    // หาความสูงสูงสุดของ cell
    $maxLines = 0;
    foreach ($texts as $text) {
        $lines = substr_count($text, '/') + 1;
        $maxLines = max($maxLines, $lines);
    }
    $totalHeight = $maxLines * $h;

    // วาดทีละ cell แบบควบคุมตำแหน่งเอง
    foreach ($texts as $i => $text) {
        $textFormatted = safeIconv(str_replace('/', "\n", $text));
        $lineCount = substr_count($text, '/') + 1;
        $cellHeight = $lineCount * $h;
        $offsetY = ($totalHeight - $cellHeight) / 2;

        $x = $xStart + ($i * $w);
        $y = $yStart + $offsetY;

        // พิมพ์ข้อความ
        $pdf->SetXY($x, $y);
        $pdf->SetFont('sara', 'B', 12); // ปรับขนาดฟอนต์ตามต้องการ
        $pdf->MultiCell($w, $h, $textFormatted, 0, 'C');

        // วาดกรอบ cell
        $pdf->SetXY($x, $yStart);
        $pdf->Cell($w, $totalHeight, '', 1);
    }

    // เคลื่อน Y ลงหลังตาราง
    $pdf->SetY($yStart + $totalHeight);


    $selected = $physicalMeasures['head_percentile'][0] ?? '';
    drawOptionCell($pdf, $selected, 'น้อยกว่า 3', 27.14);
    drawOptionCell($pdf, $selected, '3-15', 27.14);
    drawOptionCell($pdf, $selected, '15-50', 27.14);
    drawOptionCell($pdf, $selected, '50-85', 27.14);
    drawOptionCell($pdf, $selected, '85-97', 27.14);
    drawOptionCell($pdf, $selected, 'มากกว่า 97', 27.14);
    drawOptionCell($pdf, $selected, 'ความเสี่ยง', 27.14);
    $pdf->Ln();

    $pdf->Ln(10);
    $pdf->SetFont('sara', 'BU', 14);
    $pdf->Cell(190, 8, safeIconv('ประเมินพัฒนาการทั้ง 5 ด้าน'), 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('sara', 'B', 12);
    $pdf->Cell(38, 8, iconv('UTF-8', 'cp874', 'การเคลื่อนไหว(GM)'), 1, 0, 'C');
    $pdf->Cell(38, 8, iconv('UTF-8', 'cp874', 'มัดเล็กและสติปัญญา(FM)'), 1, 0, 'C');
    $pdf->Cell(38, 8, iconv('UTF-8', 'cp874', 'เข้าใจภาษา(RL)'), 1, 0, 'C');
    $pdf->Cell(38, 8, iconv('UTF-8', 'cp874', 'ใช้ภาษา(EL)'), 1, 0, 'C');
    $pdf->Cell(38, 8, iconv('UTF-8', 'cp874', 'ช่วยเหลือตัวเองและสังคม(PS)'), 1, 1, 'C');

    if (!function_exists('MultiLineCell')) {
        function MultiLineCell($pdf, $w, $h, $txt, $border = 0, $ln = 0, $align = 'L')
        {
            $lines = explode('/', $txt);
            $lineHeight = $h / count($lines);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // วาดกรอบก่อน
            $pdf->Cell($w, $h, '', $border, 0, $align);

            // เขียนข้อความ
            foreach ($lines as $i => $line) {
                $pdf->SetXY($x, $y + ($i * $lineHeight));
                $pdf->Cell($w, $lineHeight, iconv('UTF-8', 'cp874', trim($line)), 0, 0, $align);
            }

            // กำหนดตำแหน่งสำหรับ cell ถัดไป
            if ($ln == 1) {
                $pdf->SetXY($x, $y + $h);
            } else {
                $pdf->SetXY($x + $w, $y);
            }
        }
    }

    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ผ่าน'), 1, 0, 'C');
    MultiLineCell($pdf, 12.66, 10, 'ส่งสัย/ล่าช้า', 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ข้อที่'), 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ผ่าน'), 1, 0, 'C');
    MultiLineCell($pdf, 12.66, 10, 'ส่งสัย/ล่าช้า', 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ข้อที่'), 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ผ่าน'), 1, 0, 'C');
    MultiLineCell($pdf, 12.66, 10, 'ส่งสัย/ล่าช้า', 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ข้อที่'), 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ผ่าน'), 1, 0, 'C');
    MultiLineCell($pdf, 12.66, 10, 'ส่งสัย/ล่าช้า', 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ข้อที่'), 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ผ่าน'), 1, 0, 'C');
    MultiLineCell($pdf, 12.66, 10, 'ส่งสัย/ล่าช้า', 1, 0, 'C');
    $pdf->Cell(12.66, 10, iconv('UTF-8', 'cp874', 'ข้อที่'), 1, 1, 'C');

    // ฟังก์ชันสำหรับสร้าง checkbox
    if (!function_exists('createCheckboxDeverlop')) {
        function createCheckboxDeverlop($pdf, $status)
        {
            // กำหนดเครื่องหมาย checkbox
            if ($status == 'pass') {
                $pass_mark = '/';
                $delay_mark = ' ';
            } else {
                $pass_mark = ' ';
                $delay_mark = '/';
            }

            // สร้างช่อง "ผ่าน"
            $pdf->SetFont('sara', '', 22);
            $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', "$pass_mark"), 1, 0, 'C');
            // สร้างช่อง "ส่งสัย/ล่าช้า"
            $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', "$delay_mark"), 1, 0, 'C');
        }
    }

    if (!empty($development['gm']['status'])) {
        $status = $development['gm']['status'] == 'delay' ? 'delay' : 'pass';
        createCheckboxDeverlop($pdf, $status);
    } else {
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(12.66, 8, !empty($development['gm']['score']) ? $development['gm']['score'] : '', 1, 0, 'C');

    if (!empty($development['fm']['status'])) {
        $status = $development['fm']['status'] == 'delay' ? 'delay' : 'pass';
        createCheckboxDeverlop($pdf, $status);
    } else {
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(12.66, 8, !empty($development['fm']['score']) ? $development['fm']['score'] : '', 1, 0, 'C');


    if (!empty($development['rl']['status'])) {
        $status = $development['rl']['status'] == 'delay' ? 'delay' : 'pass';
        createCheckboxDeverlop($pdf, $status);
    } else {
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(12.66, 8, !empty($development['rl']['score']) ? $development['rl']['score'] : '', 1, 0, 'C');

    if (!empty($development['el']['status'])) {
        $status = $development['el']['status'] == 'delay' ? 'delay' : 'pass';
        createCheckboxDeverlop($pdf, $status);
    } else {
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(12.66, 8, !empty($development['el']['score']) ? $development['el']['score'] : '', 1, 0, 'C');

    if (!empty($development['ps']['status'])) {
        $status = $development['ps']['status'] == 'delay' ? 'delay' : 'pass';
        createCheckboxDeverlop($pdf, $status);
    } else {
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
        $pdf->Cell(12.66, 8, iconv('UTF-8', 'cp874', ''), 1, 0, 'C');
    }
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(12.66, 8, !empty($development['ps']['score']) ? $development['ps']['score'] : '', 1, 0, 'C');




    $pdf->Ln(8);
    $pdf->SetFont('sara', 'BU', 14);
    $pdf->Cell(0, 8, iconv('UTF-8', 'cp874', 'ผลการตรวจร่างกาย'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 14);


    // ฟังก์ชันสำหรับสร้าง checkbox
    if (!function_exists('createCheckbox')) {
        function createCheckbox($pdf, $status = null, $normalText = 'ปกติ', $abnormalText = 'ผิดปกติ')
        {
            // Default เป็นว่าง ถ้าไม่ตรงเงื่อนไข
            $normal_mark = ' ';
            $abnormal_mark = ' ';

            if ($status === 'normal') {
                $normal_mark = '/';
            } elseif ($status === 'abnormal') {
                $abnormal_mark = '/';
            }

            $pdf->SetFont('sara', '', 14);
            // ช่อง "ปกติ"         
            $pdf->Cell(3, 8, '(', 0, 0, 'L');
            $pdf->SetFont('sara', '', ($normal_mark === '/' ? 22 : 14));
            $pdf->Cell(3, 8, $normal_mark, 0, 0, 'C');
             $pdf->SetFont('sara', '', 14);
            $pdf->Cell(3, 8, ')', 0, 0, 'L');
           
            $pdf->Cell(12, 8, safeIconv($normalText), 0, 0, 'L');


            // ช่อง "ผิดปกติ"
            $pdf->SetFont('sara', '', 14);
            $pdf->Cell(3, 8, '(', 0, 0, 'L');
            $pdf->SetFont('sara', '', ($abnormal_mark === '/' ? 22 : 14));
            $pdf->Cell(3, 8, $abnormal_mark, 0, 0, 'C');
             $pdf->SetFont('sara', '', 14);
            $pdf->Cell(3, 8, ')', 0, 0, 'L');
           
            $pdf->Cell(10, 8, safeIconv($abnormalText), 0, 0, 'L');
        }
    }


    // ฟังก์ชันสำหรับแสดงรายการตรวจร่างกาย
    if (!function_exists('displayBodyExamination')) {
        function displayBodyExamination($pdf, $data)
        {

            // 1. ลูกบิด (general)
            $pdf->Cell(25, 8, safeIconv('สภาพทั่วไป'), 0, 0, 'L');

            $status = isset($data['general'][0]) ? $data['general'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['general_detail']) ? $data['general_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // 2. ผิวหนัง (skin)
            $pdf->Cell(25, 8, safeIconv('ผิวหนัง'), 0, 0, 'L');
            $status = isset($data['skin'][0]) ? $data['skin'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['skin_detail']) ? $data['skin_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');

            // 3. ศีรษะ (head)
            $pdf->Cell(25, 8, safeIconv('ศีรษะ'), 0, 0, 'L');
            $status = isset($data['head'][0]) ? $data['head'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['head_detail']) ? $data['head_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // 4. ใบหน้า (face)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('ใบหน้า'), 0, 0, 'L');
            $status = isset($data['face'][0]) ? $data['face'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['face_detail']) ? $data['face_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 5. ตา (eyes)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('ตา'), 0, 0, 'L');
            $status = isset($data['eyes'][0]) ? $data['eyes'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['eyes_detail']) ? $data['eyes_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 6. หูและการได้ยิน (ears)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('หูและการได้ยิน'), 0, 0, 'L');
            $status = isset($data['ears'][0]) ? $data['ears'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['ears_detail']) ? $data['ears_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 7. จมูก (nose)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('จมูก'), 0, 0, 'L');
            $status = isset($data['nose'][0]) ? $data['nose'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['nose_detail']) ? $data['nose_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 8. ปากและช่องปาก (mouth)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('ปากและช่องปาก'), 0, 0, 'L');
            $status = isset($data['mouth'][0]) ? $data['mouth'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['mouth_detail']) ? $data['mouth_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 9. คอ (neck)
            $pdf->Cell(25, 8, safeIconv('คอ'), 0, 0, 'L');
            $status = isset($data['neck'][0]) ? $data['neck'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['neck_detail']) ? $data['neck_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // 10. ทรวงอกและปอด (breast)
            $pdf->Cell(25, 8, safeIconv('ทรวงอกและปอด'), 0, 0, 'L');
            $status = isset($data['breast'][0]) ? $data['breast'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['breast_detail']) ? $data['breast_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // 11. การหายใจ (breathe)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('การหายใจ'), 0, 0, 'L');
            $status = isset($data['breathe'][0]) ? $data['breathe'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['breathe_detail']) ? $data['breathe_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 12. ปอด (lungs)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('ปอด'), 0, 0, 'L');
            $status = isset($data['lungs'][0]) ? $data['lungs'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['lungs_detail']) ? $data['lungs_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 13. หัวใจ (heart)
            $pdf->Cell(25, 8, safeIconv('หัวใจ'), 0, 0, 'L');
            $status = isset($data['heart'][0]) ? $data['heart'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['heart_detail']) ? $data['heart_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // 14. เสียงหัวใจ (heart_sound)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('เสียงหัวใจ'), 0, 0, 'L');
            $status = isset($data['heart_sound'][0]) ? $data['heart_sound'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['heart_sound_detail']) ? $data['heart_sound_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 15. ชีพจร (pulse)
            $pdf->SetX($pdf->GetX() + 10);
            $pdf->Cell(25, 8, safeIconv('ชีพจร'), 0, 0, 'L');
            $status = isset($data['pulse'][0]) ? $data['pulse'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['pulse_detail']) ? $data['pulse_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');


            // 16. ช่องท้อง (abdomen)
            $pdf->Cell(25, 8, safeIconv('ช่องท้อง'), 0, 0, 'L');
            $status = isset($data['abdomen'][0]) ? $data['abdomen'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['abdomen_detail']) ? $data['abdomen_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');



            // 17. อื่น ๆ (others)
            $pdf->Cell(25, 8, safeIconv('อื่น ๆ'), 0, 0, 'L');
            $status = isset($data['others'][0]) ? $data['others'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['others_detail']) ? $data['others_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');
        }
    }

    displayBodyExamination($pdf, $physicalExam);

    // เฉพาะส่วนที่เพิ่มใหม่
    if (!function_exists('displayNeurologicalExam')) {
        function displayNeurologicalExam($pdf, $data)
        {
            $pdf->Ln(5);
            $pdf->SetFont('sara', 'BU', 14);
            $pdf->Cell(0, 8, safeIconv('ระบบประสาท (Neurological Examination)'), 0, 1, 'L');
            $pdf->SetFont('sara', '', 14);


            // ระบบประสาท
            $pdf->Cell(25, 8, safeIconv('ปฏิกิริยารีเฟล็กซ'), 0, 0, 'L');
            $status = isset($data['neuro'][0]) ? $data['neuro'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['neuro_detail']) ? $data['neuro_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 115, 14, 1, 'sara', '', 'L');


            // การเคลื่อนไหว
            $pdf->Cell(35, 8, safeIconv('การเคลื่อนไหวร่างกาย'), 0, 0, 'L');
            $status = isset($data['movement'][0]) ? $data['movement'][0] : null;
            createCheckbox($pdf, $status);
            $pdf->Cell(10, 8, safeIconv('ระบุ'), 0, 0, 'L');
            $detail = isset($data['movement_detail']) ? $data['movement_detail'] : '';
            drawDottedUnderlineTextFull($pdf, $detail, 105, 14, 1, 'sara', '', 'L');
        }
    }

    // คำแนะนำ
    displayNeurologicalExam($pdf, $neuro);

    $pdf->Ln(5);
    $pdf->SetFont('sara', 'BU', 14);
    $pdf->Cell(0, 8, safeIconv('คำแนะนำ'), 0, 1, 'L');
    $pdf->SetFont('sara', '', 14);

    if (!function_exists('truncateTextToMaxLines')) {
        function truncateTextToMaxLines($text, $charsPerLine = 90, $maxLines = 3)
        {
            $text = strip_tags(trim($text));
            $maxChars = $charsPerLine * $maxLines;

            if (mb_strlen($text, 'UTF-8') <= $maxChars) {
                return $text;
            }

            // ตัดให้พอดีไม่เกิน maxChars แล้วเติม ...
            $truncated = mb_substr($text, 0, $maxChars - 3, 'UTF-8');

            // ตัดจนถึงช่องว่างสุดท้ายเพื่อไม่ให้ขาดคำ
            $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
            if ($lastSpace !== false) {
                $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
            }

            return $truncated . '...';
        }
    }
    $recommendText = truncateTextToMaxLines($row['recommendation'] ?? '-', 90, 3);


    $pdf->MultiCell(190, 8, safeIconv($recommendText), 0, 'L');


    // ลายเซ็น
    $bottomMargin = 20; // ระยะห่างจากขอบล่าง (เช่น 20 มม.)

    // คำนวณตำแหน่ง Y
    $pageHeight = $pdf->GetPageHeight();  // ความสูงกระดาษ
    $y = $pageHeight - $bottomMargin;

    $pdf->SetY($y); // ตั้งตำแหน่ง Y ให้ใกล้ขอบล่าง

    // พิมพ์ลายเซ็น
    $pdf->SetFont('sara', '', 14);
    $pdf->Cell(0, 6, iconv('UTF-8', 'cp874', 'ลงชื่อ') . str_repeat('.', 70) . iconv('UTF-8', 'cp874', 'แพทย์ผู้ตรวจ'), 0, 1, 'R');
}

$filename = "รายงาน_" . $doctor . ".pdf";

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
