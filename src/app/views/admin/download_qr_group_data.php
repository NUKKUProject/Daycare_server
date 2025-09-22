<?php

require_once('../../../config/database.php');

function cleanFileName($string) {
    return preg_replace('/[^ก-๙a-zA-Z0-9_\-\.]/u', '_', $string);
}



// ตรวจสอบ header สำหรับ zip download
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$group = $input['group'] ?? 'all';
$classroom = $input['classroom'] ?? 'all';

$pdo = getDatabaseConnection();


$sql = "SELECT * FROM children WHERE 1=1 AND qr_code IS NOT NULL";
$params = [];

if ($group !== 'all') {
    $sql .= " AND child_group = :group";
    $params['group'] = $group;
}
if ($classroom !== 'all') {
    $sql .= " AND classroom = :classroom";
    $params['classroom'] = $classroom;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$children) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล']);
    exit;
}

$basePath = __DIR__ . '/tmp_qr_export';
@mkdir($basePath);

// สร้างโฟลเดอร์สำหรับแต่ละ classroom และคัดลอกไฟล์
foreach ($children as $child) {
    $classroomFolder = $basePath . '/' . $child['classroom'];
    if (!file_exists($classroomFolder)) {
        mkdir($classroomFolder, 0777, true);
    }

    $qrPath = __DIR__ . '/' . $child['qr_code']; // path เดิม
    
    if (file_exists($qrPath)) {
        $ext = pathinfo($qrPath, PATHINFO_EXTENSION); // ดึงนามสกุลไฟล์ เช่น png
        $nameFile = cleanFileName($child['prefix_th'].'_'.$child['firstname_th'].'_'.$child['lastname_th']) . '.' . $ext;
        $destPath = $classroomFolder . '/' . $nameFile;

        copy($qrPath, $destPath);
    }
}

// สร้าง ZIP
$zipFile = 'qr_export_' . time() . '.zip';
$zipPath = __DIR__ . '/' . $zipFile;


$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($basePath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
}

// ลบโฟลเดอร์ชั่วคราว (ไม่จำเป็นต้องเก็บไว้)
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    foreach (scandir($dirPath) as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dirPath . '/' . $item;
        is_dir($path) ? deleteDir($path) : unlink($path);
    }
    rmdir($dirPath);
}

deleteDir($basePath);

$filename = basename($zipFile); // ป้องกัน path traversal

// ส่ง URL ของ ZIP กลับ
$zipUrl = __DIR__ .'/'. $zipFile;

echo json_encode([
    'status' => 'success',
    'download_url' => 'download_qr_zip.php?file=' . urlencode($filename)
]);
