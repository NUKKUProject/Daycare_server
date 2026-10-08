<?php
require_once __DIR__ . '/../auth/auth.php';
checkUserRole(['admin']);   // ไฟล์นี้ส่งออกข้อมูลส่วนบุคคลของเด็ก อนุญาตเฉพาะผู้ดูแลระบบ
require_once(__DIR__ . '/../../../config/database.php');

error_reporting(0);
ini_set('display_errors', 0);

// ชื่อหัวคอลัมน์ภาษาไทยของคอลัมน์ที่รู้จัก คอลัมน์อื่นที่เพิ่มในตารางภายหลังจะใช้ชื่อคอลัมน์เดิม
const CHILD_COLUMN_LABELS = [
    'studentid' => 'รหัสนักเรียน',
    'academic_year' => 'ปีการศึกษา',
    'child_group' => 'กลุ่ม',
    'classroom' => 'ห้องเรียน',
    'status' => 'สถานะการศึกษา',
    'date_success' => 'วันที่จบ/ออก',
    'prefix_th' => 'คำนำหน้า (ไทย)',
    'firstname_th' => 'ชื่อ (ไทย)',
    'lastname_th' => 'นามสกุล (ไทย)',
    'nickname' => 'ชื่อเล่น',
    'prefix_en' => 'คำนำหน้า (อังกฤษ)',
    'firstname_en' => 'ชื่อ (อังกฤษ)',
    'lastname_en' => 'นามสกุล (อังกฤษ)',
    'sex' => 'เพศ',
    'birthday' => 'วันเกิด',
    'age_student' => 'อายุ (ปี)',
    'id_card' => 'เลขบัตรประชาชน',
    'race' => 'เชื้อชาติ',
    'nationality' => 'สัญชาติ',
    'religion' => 'ศาสนา',
    'height' => 'ส่วนสูง (ซม.)',
    'weight' => 'น้ำหนัก (กก.)',
    'blood_type' => 'กรุ๊ปเลือด',
    'congenital_disease' => 'โรคประจำตัว',
    'allergic_food' => 'อาหารที่แพ้',
    'allergic_medicine' => 'ยาที่แพ้',
    'address' => 'ที่อยู่',
    'district' => 'ตำบล/แขวง',
    'amphoe' => 'อำเภอ/เขต',
    'province' => 'จังหวัด',
    'zipcode' => 'รหัสไปรษณีย์',
    'father_first_name' => 'ชื่อบิดา',
    'father_last_name' => 'นามสกุลบิดา',
    'father_phone' => 'เบอร์โทรบิดา',
    'father_phone_backup' => 'เบอร์โทรสำรองบิดา',
    'mother_first_name' => 'ชื่อมารดา',
    'mother_last_name' => 'นามสกุลมารดา',
    'mother_phone' => 'เบอร์โทรมารดา',
    'mother_phone_backup' => 'เบอร์โทรสำรองมารดา',
    'relative_first_name' => 'ชื่อผู้ปกครอง/ผู้ดูแล',
    'relative_last_name' => 'นามสกุลผู้ปกครอง/ผู้ดูแล',
    'relative_phone' => 'เบอร์โทรผู้ปกครอง/ผู้ดูแล',
    'emergency_contact' => 'ผู้ติดต่อฉุกเฉิน',
    'emergency_phone' => 'เบอร์โทรฉุกเฉิน',
    'emergency_relation' => 'ความสัมพันธ์ผู้ติดต่อฉุกเฉิน',
];

// คอลัมน์ที่ไม่ส่งออก (รหัสภายใน, QR, ไฟล์รูป, เวลาสร้าง/แก้ไข, อายุที่คำนวณ, ข้อมูลบัตรประชาชนเพิ่มเติม)
const CHILD_COLUMNS_EXCLUDED = [
    'id', 'qr_code', 'created_at', 'updated_at', 'academic_year_id',
    'profile_image', 'father_image', 'mother_image', 'relative_image',
    'age_years', 'age_months', 'age_days',
    'has_drug_allergy_history', 'has_food_allergy_history',   // ใช้ข้อมูลรายการแพ้จริงแทน
    'success_type',
    'place_birth', 'issue_at', 'issue_date', 'expiry_date',
];

// คอลัมน์ตัวเลขที่ต้องคงเลข 0 นำหน้าเมื่อเปิดด้วย Excel
function child_is_text_number_column(string $col): bool
{
    return in_array($col, ['studentid', 'id_card', 'zipcode'], true) || strpos($col, 'phone') !== false;
}

function child_csv_cell(string $col, $value): string
{
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? 'ใช่' : 'ไม่ใช่';
    }
    $v = (string) $value;
    if ($v !== '' && child_is_text_number_column($col) && preg_match('/^\d+$/', $v)) {
        return '="' . $v . '"';   // Excel จะไม่ตัดเลข 0 นำหน้า
    }
    // กัน CSV injection: ค่าที่ขึ้นต้นด้วยอักขระสูตร
    if ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false && !preg_match('/^-?\d+(\.\d+)?$/', $v)) {
        return "'" . $v;
    }
    return $v;
}

try {
    $pdo = getDatabaseConnection();

    // ----- ตัวกรอง -----
    $groupMap = ['medium' => 'เด็กกลาง', 'big' => 'เด็กโต', 'prep' => 'เตรียมอนุบาล'];
    $group = trim($_POST['child_group'] ?? 'all');
    $group = $groupMap[$group] ?? $group;
    $classroom = trim($_POST['classroom'] ?? '');
    $year = trim($_POST['academic_year'] ?? '');
    $eduStatus = trim($_POST['edu_status'] ?? 'all');

    $where = [];
    $params = [];
    if ($year !== '' && ctype_digit($year)) {
        $where[] = 'academic_year = :year';
        $params['year'] = (int) $year;
    }
    if ($group !== '' && $group !== 'all') {
        $where[] = 'child_group = :grp';
        $params['grp'] = $group;
    }
    if ($classroom !== '' && $classroom !== 'all') {
        $where[] = 'classroom = :room';
        $params['room'] = $classroom;
    }

    // ----- คอลัมน์ทั้งหมดของตาราง children (ตามที่มีอยู่จริงในฐานข้อมูล) -----
    $colStmt = $pdo->query("
        SELECT column_name FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'children'
        ORDER BY ordinal_position
    ");
    $allColumns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_values(array_filter(
        $allColumns,
        fn($c) => !in_array($c, CHILD_COLUMNS_EXCLUDED, true)
    ));

    // สถานะการศึกษาจริง: เด็กที่จบ/ออกจะมี status = 'สำเร็จการศึกษา' เสมอ ประเภทจริง
    // (สำเร็จการศึกษา / ย้ายไปโรงเรียนอื่น / ลาออก) อยู่ใน success_type จึงรวมสองคอลัมน์เป็นค่าเดียว
    $statusExpr = in_array('success_type', $allColumns, true)
        ? "CASE WHEN status = 'สำเร็จการศึกษา' AND COALESCE(success_type, '') <> '' THEN success_type ELSE status END"
        : 'status';
    if ($eduStatus !== '' && $eduStatus !== 'all') {
        $where[] = "($statusExpr) = :edu";
        $params['edu'] = $eduStatus;
    }

    // เรียงคอลัมน์ที่รู้จักตามลำดับใน CHILD_COLUMN_LABELS ก่อน ที่เหลือต่อท้าย
    $known = array_keys(CHILD_COLUMN_LABELS);
    usort($columns, function ($a, $b) use ($known) {
        $ia = array_search($a, $known, true);
        $ib = array_search($b, $known, true);
        $ia = $ia === false ? PHP_INT_MAX : $ia;
        $ib = $ib === false ? PHP_INT_MAX : $ib;
        return $ia <=> $ib;
    });

    // ยา/อาหารที่แพ้: ใช้รายการจากตารางบันทึกการแพ้ (ที่แท็บประวัติประจำตัวใช้งานจริง)
    // ถ้าไม่มีให้ใช้ค่าข้อความเดิมในตารางเด็ก
    $hasTable = fn(string $t) => (bool) $pdo->query("SELECT to_regclass('public.$t') IS NOT NULL")->fetchColumn();
    $allergyExpr = [];
    if ($hasTable('drug_allergies')) {
        $allergyExpr['allergic_medicine'] = "COALESCE((SELECT string_agg(NULLIF(BTRIM(da.drug_name), ''), ', ' ORDER BY da.created_at) FROM public.drug_allergies da WHERE da.student_id = c.studentid), NULLIF(BTRIM(c.allergic_medicine), ''))";
    }
    if ($hasTable('food_allergies')) {
        $allergyExpr['allergic_food'] = "COALESCE((SELECT string_agg(NULLIF(BTRIM(fa.food_name), ''), ', ' ORDER BY fa.created_at) FROM public.food_allergies fa WHERE fa.student_id = c.studentid), NULLIF(BTRIM(c.allergic_food), ''))";
    }

    $select = implode(', ', array_map(function ($c) use ($statusExpr, $allergyExpr) {
        if ($c === 'status') {
            return "($statusExpr) AS status";
        }
        if (isset($allergyExpr[$c])) {
            return $allergyExpr[$c] . ' AS ' . $c;
        }
        return '"' . str_replace('"', '""', $c) . '"';
    }, $columns));
    $sql = "SELECT $select FROM public.children c"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY child_group, classroom, studentid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ----- ชื่อไฟล์ -----
    $parts = ['children'];
    if ($year !== '') { $parts[] = 'year' . $year; }
    if ($group !== '' && $group !== 'all') { $parts[] = array_search($group, $groupMap, true) ?: 'group'; }
    if ($classroom !== '' && $classroom !== 'all') { $parts[] = 'room'; }
    if ($eduStatus !== '' && $eduStatus !== 'all') { $parts[] = 'status'; }
    $filename = implode('_', $parts) . '_' . date('Y-m-d_His') . '.csv';

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));   // BOM ให้ Excel อ่านภาษาไทยถูกต้อง

    fputcsv($output, array_map(fn($c) => CHILD_COLUMN_LABELS[$c] ?? $c, $columns), ',', '"', '\\');

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $line = [];
        foreach ($columns as $c) {
            $line[] = child_csv_cell($c, $row[$c] ?? null);
        }
        fputcsv($output, $line, ',', '"', '\\');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    error_log('export_children: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo 'เกิดข้อผิดพลาดในการ export ข้อมูล';
}
