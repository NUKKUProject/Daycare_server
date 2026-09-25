<?php
require_once(__DIR__ . '/../../../../vendor/autoload.php');
require_once(__DIR__ . '/../../../../config/database.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ─── รับพารามิเตอร์จาก GET ───
$childGroup       = $_GET['child_group'] ?? '';
$classroom        = $_GET['classroom'] ?? '';
$studentYear      = $_GET['student_year'] ?? '';   // ปีการศึกษาของเด็ก
$examYear         = $_GET['exam_year'] ?? '';       // ตรวจประจำปี

$students = [];
$contextLine = '';

if ($childGroup || $classroom || $studentYear || $examYear) {
    try {
        $pdo = getDatabaseConnection();

        $sql = "SELECT studentid, prefix_th, firstname_th, lastname_th, nickname, child_group, classroom
                FROM children
                WHERE status = 'กำลังศึกษา'";
        $params = [];

        if (!empty($childGroup)) {
            $sql .= " AND child_group = :cg";
            $params[':cg'] = $childGroup;
        }
        if (!empty($classroom)) {
            $sql .= " AND classroom = :cr";
            $params[':cr'] = $classroom;
        }
        if (!empty($studentYear)) {
            $sql .= " AND academic_year = :sy";
            $params[':sy'] = $studentYear;
        }
        $sql .= " ORDER BY child_group, classroom, firstname_th";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึง check_round ล่าสุดของแต่ละเด็ก สำหรับ exam_year ที่เลือก
        foreach ($students as &$s) {
            $s['check_round'] = 1;
            if (!empty($examYear)) {
                $r = $pdo->prepare("SELECT MAX(check_round) as mx FROM health_data_external WHERE student_id = :sid AND academic_year = :ey");
                $r->execute([':sid' => $s['studentid'], ':ey' => $examYear]);
                $row = $r->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['mx']) {
                    $s['check_round'] = (int)$row['mx'] + 1;
                }
            }
        }
        unset($s);

    } catch (Exception $e) {
        // ถ้าดึงไม่ได้ ให้เป็น template ว่าง
        $students = [];
    }
}

$contextLine = 'เด็กปีการศึกษา ' . ($studentYear ?: 'ทั้งหมด')
             . '  ตรวจประจำปี ' . ($examYear ?: 'ทั้งหมด');

$spreadsheet = new Spreadsheet();

// ─── Sheet1: คําชี้แจง ───
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('คําชี้แจง');

$sheet->mergeCells('A1:B1');
$sheet->setCellValue('A1', 'คําแนะนําการใช้งาน Template สําหรับ Import ข้อมูลการตรวจสุขภาพ');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$instructions = [
    ['', ''],
    ['', ''],
    ['ข้อมูลการตรวจ', $contextLine],
    ['', ''],
    ['ข้อควรปฏิบัติ', ''],
    ['1. ห้ามลบหรือแก้ไขหัวคอลัมน์ใน Sheet "ข้อมูล"'],
    ['2. คอลัมน์ที่มี * คือ必需ต้องกรอก (รหัสนักเรียน, วันที่ตรวจ, ปีการศึกษา)'],
    ['3. คอลัมน์ที่มี Dropdown ให้เลือกจากรายการที่กําหนดให้เท่านั้น'],
    ['4. รหัสนักเรียนต้องมีอยู่ในระบบเท่านั้น'],
    ['5. วันที่ตรวจ ต้องกรอกในรูปแบบ YYYY-MM-DD (เช่น 2026-07-09)'],
    ['6. หากกรอกข้อมูลผิดพลาด แถวนั้นจะถูกข้ามและแสดงในรายงานผล'],
    ['7. คอลัมน์ "รหัสนักเรียน *", "ปีการศึกษา *" และ "ชื่อ-นามสกุล" ได้ถูกเติมข้อมูลมาให้แล้ว'],
    ['8. คอลัมน์ "ตรวจรอบที่" คือลำดับการตรวจที่จะบันทึก (คำนวณอัตโนมัติ)'],
    ['', ''],
    ['คําอธิบายคอลัมน์', ''],
    ['รหัสนักเรียน *', 'รหัสประจําตัวนักเรียน (เติมมาให้แล้ว ห้ามแก้ไข)'],
    ['วันที่ตรวจ *', 'วันที่ทําการตรวจสุขภาพ (YYYY-MM-DD)'],
    ['ปีการศึกษา *', 'ปีที่ตรวจประจำปี (เติมมาให้แล้ว ห้ามแก้ไข)'],
    ['พฤติกรรม', 'เลือก "none" (ปกติ) หรือ "has" (ผิดปกติ)'],
    ['พัฒนาการ GM, FM, RL, EL, PS', 'เลือก "pass" (ผ่าน) หรือ "delay" (สงสัยล่าช้า)'],
    ['การตรวจร่างกาย', 'เลือก "normal" (ปกติ) หรือ "abnormal" (ผิดปกติ)'],
    ['', ''],
    ['ช่องที่เลือก "abnormal" หรือ "delay"', 'กรุณากรอกรายละเอียดในช่อง "รายละเอียด" ที่อยู่ถัดไป'],
    ['', ''],
    ['ชื่อ-นามสกุล', 'แสดงเพื่อตรวจสอบความถูกต้อง (ไม่ถูกนำเข้าระบบ)'],
    ['ตรวจรอบที่', 'ลำดับการตรวจที่จะบันทึก (แสดงเพื่อตรวจสอบ)'],
];

$row = 1;
foreach ($instructions as $data) {
    $sheet->setCellValue('A' . $row, $data[0]);
    $sheet->setCellValue('B' . $row, $data[1] ?? '');
    $row++;
}

$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A3')->getFont()->getColor()->setRGB('C00000');
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A15')->getFont()->setBold(true)->setSize(14);
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);

// ─── Sheet2: ข้อมูล ───
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('ข้อมูล');

// Header row (คอลัมน์แสดงผล ชื่อ-นามสกุล และ ตรวจรอบที่ อยู่หลัง รหัสนักเรียน)
$headers = [
    'รหัสนักเรียน *',
    // คอลัมน์แสดงผล (ไม่ถูกนำเข้า) อยู่หลังรหัสนักเรียน
    'ชื่อ-นามสกุล',
    'ตรวจรอบที่',
    'วันที่ตรวจ *',
    'ปีการศึกษา *',
    'อุณหภูมิ (°C)',
    'ความดันโลหิต',
    'ส่วนสูง (ซม.)',
    'นํ้าหนัก (กก.)',
    'รอบศีรษะ (ซม.)',
    'นํ้าหนักตามอายุ',
    'ส่วนสูงตามอายุ',
    'นํ้าหนักตามส่วนสูง',
    'รอบศีรษะเปอร์เซ็นไทล์',
    'พฤติกรรม',
    'รายละเอียดพฤติกรรม',
    'GM (การเคลื่อนไหว)',
    'GM ข้อที่',
    'FM (มัดเล็กสติปัญญา)',
    'FM ข้อที่',
    'RL (เข้าใจภาษา)',
    'RL ข้อที่',
    'EL (ใช้ภาษา)',
    'EL ข้อที่',
    'PS (ช่วยเหลือตนเอง)',
    'PS ข้อที่',
    'สภาพทั่วไป',
    'สภาพทั่วไป รายละเอียด',
    'ผิวหนัง',
    'ผิวหนัง รายละเอียด',
    'ศีรษะ',
    'ศีรษะ รายละเอียด',
    'ใบหน้า',
    'ใบหน้า รายละเอียด',
    'ตา',
    'ตา รายละเอียด',
    'หูและการได้ยิน',
    'หูและการได้ยิน รายละเอียด',
    'จมูก',
    'จมูก รายละเอียด',
    'ปากและช่องปาก',
    'ปากและช่องปาก รายละเอียด',
    'คอ',
    'คอ รายละเอียด',
    'ทรวงอกและปอด',
    'ทรวงอกและปอด รายละเอียด',
    'การหายใจ',
    'การหายใจ รายละเอียด',
    'ปอด',
    'ปอด รายละเอียด',
    'หัวใจ',
    'หัวใจ รายละเอียด',
    'เสียงหัวใจ',
    'เสียงหัวใจ รายละเอียด',
    'ชีพจร (ร่างกาย)',
    'ชีพจร (ร่างกาย) รายละเอียด',
    'ช่องท้อง',
    'ช่องท้อง รายละเอียด',
    'อื่นๆ',
    'อื่นๆ รายละเอียด',
    'ปฏิกิริยาขั้นพื้นฐาน',
    'ปฏิกิริยาขั้นพื้นฐาน รายละเอียด',
    'การเคลื่อนไหวร่างกาย',
    'การเคลื่อนไหวร่างกาย รายละเอียด',
    'คําแนะนำ',
];

$headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';

foreach ($headers as $colIdx => $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
    $sheet2->setCellValue($colLetter . '1', $header);
}

// Style header
$sheet2->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 10,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2F5496'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
]);

$sheet2->getRowDimension('1')->setRowHeight(30);

// Auto-size columns
foreach ($headers as $colIdx => $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
    $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
}

// ไฮไลต์คอลัมน์แสดงผล (B=2: ชื่อ-นามสกุล, C=3: ตรวจรอบที่)
$displayStart = Coordinate::stringFromColumnIndex(2);
$displayEnd   = Coordinate::stringFromColumnIndex(3);
$sheet2->getStyle($displayStart . '1:' . $displayEnd . '1')->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFF2CC'],
    ],
]);

// ─── กรอกข้อมูลเด็ก (ถ้ามี) ───
$dataStartRow = 2;
if (!empty($students)) {
    $r = $dataStartRow;
    foreach ($students as $s) {
        $sheet2->setCellValue('A' . $r, $s['studentid']);                    // รหัสนักเรียน
        // คอลัมน์แสดงผล (อยู่หลังรหัสนักเรียน)
        $fullName = trim(($s['prefix_th'] ?? '') . ' ' . ($s['firstname_th'] ?? '') . ' ' . ($s['lastname_th'] ?? ''));
        $sheet2->setCellValue('B' . $r, $fullName);                          // ชื่อ-นามสกุล
        $sheet2->setCellValue('C' . $r, $s['check_round']);                  // ตรวจรอบที่
        $sheet2->setCellValue('D' . $r, date('Y-m-d'));                      // วันที่ตรวจ (default วันนี้)
        $sheet2->setCellValue('E' . $r, $examYear ?: '');                    // ปีการศึกษา (ตรวจประจำปี)
        $r++;
    }
    $lastDataRow = $r - 1;
} else {
    $lastDataRow = 1001; // ไม่มีข้อมูล ให้แถวว่าง 1000 แถว
}

// ─── Data Validation: Dropdowns ───
$lastRow = max($lastDataRow, 1001); // Support up to 1000 rows of data

// Pass/Delay dropdown (ค่าต้องตรงกับฟอร์ม: pass, delay)
$devValidation = new DataValidation();
$devValidation->setType(DataValidation::TYPE_LIST);
$devValidation->setFormula1('"pass,delay"');
$devValidation->setAllowBlank(true);
$devValidation->setShowDropDown(true);

$devColumns = ['Q', 'S', 'U', 'W', 'Y']; // GM, FM, RL, EL, PS
foreach ($devColumns as $col) {
    $sheet2->setDataValidation($col . '2:' . $col . $lastRow, $devValidation);
}

// Normal/Abnormal dropdown (ค่าต้องตรงกับฟอร์ม: normal, abnormal)
$examValidation = new DataValidation();
$examValidation->setType(DataValidation::TYPE_LIST);
$examValidation->setFormula1('"normal,abnormal"');
$examValidation->setAllowBlank(true);
$examValidation->setShowDropDown(true);

// Behavior dropdown (ค่าต้องตรงกับฟอร์ม: none, has)
$behaviorValidation = new DataValidation();
$behaviorValidation->setType(DataValidation::TYPE_LIST);
$behaviorValidation->setFormula1('"none,has"');
$behaviorValidation->setAllowBlank(true);
$behaviorValidation->setShowDropDown(true);
$sheet2->setDataValidation('O2:O' . $lastRow, $behaviorValidation);

// Physical exam columns (every other column starting from AA=27)
$examStatusColumns = [27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49, 51, 53, 55, 57, 59, 61];
// AA=27: สภาพทั่วไป, AC=29: ผิวหนัง, AE=31: ศีรษะ, etc.
foreach ($examStatusColumns as $colIdx) {
    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
    $sheet2->setDataValidation($colLetter . '2:' . $colLetter . $lastRow, $examValidation);
}

// Neurological columns (BG=59, BI=61)
$neuroCols = [59, 61];
foreach ($neuroCols as $colIdx) {
    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
    $sheet2->setDataValidation($colLetter . '2:' . $colLetter . $lastRow, $examValidation);
}

// Physical measures dropdowns
// Column K=11: น้ำหนักตามอายุ
$weightAgeValidation = new DataValidation();
$weightAgeValidation->setType(DataValidation::TYPE_LIST);
$weightAgeValidation->setFormula1('"น้อยกว่าเกณฑ์,ค่อนข้างน้อย,ตามเกณฑ์,ค่อนข้างมาก,มากกว่าเกณฑ์"');
$weightAgeValidation->setAllowBlank(true);
$weightAgeValidation->setShowDropDown(true);
$sheet2->setDataValidation('I2:I' . $lastRow, $weightAgeValidation);

// Column J=10: ส่วนสูงตามอายุ
$heightAgeValidation = new DataValidation();
$heightAgeValidation->setType(DataValidation::TYPE_LIST);
$heightAgeValidation->setFormula1('"เตี้ย,ค่อนข้างเตี้ย,ตามเกณฑ์,ค่อนข้างสูง,สูง"');
$heightAgeValidation->setAllowBlank(true);
$heightAgeValidation->setShowDropDown(true);
$sheet2->setDataValidation('J2:J' . $lastRow, $heightAgeValidation);

// Column K=11: น้ำหนักตามส่วนสูง
$weightHeightValidation = new DataValidation();
$weightHeightValidation->setType(DataValidation::TYPE_LIST);
$weightHeightValidation->setFormula1('"ผอม,ค่อนข้างผอม,สมส่วน,ท้วม,เริ่มอ้วน,อ้วน"');
$weightHeightValidation->setAllowBlank(true);
$weightHeightValidation->setShowDropDown(true);
$sheet2->setDataValidation('K2:K' . $lastRow, $weightHeightValidation);

// Column L=12: รอบศีรษะเปอร์เซ็นไทล์
$headCircValidation = new DataValidation();
$headCircValidation->setType(DataValidation::TYPE_LIST);
$headCircValidation->setFormula1('"น้อยกว่า 3,3-15,15-50,50-85,85-97,มากกว่า 97,ความเสี่ยง"');
$headCircValidation->setAllowBlank(true);
$headCircValidation->setShowDropDown(true);
$sheet2->setDataValidation('L2:L' . $lastRow, $headCircValidation);

// Freeze top row
$sheet2->freezePane('A2');

// Set print area (รวมคอลัมน์แสดงผล)
$sheet2->getPageSetup()->setPrintArea($headerRange . ':' . Coordinate::stringFromColumnIndex(count($headers)) . $lastRow);

// ─── Output ───
$filename = 'template_import_checklist_สุขภาพ.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;