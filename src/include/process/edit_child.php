<?php
/**
 * Process edit child form submission.
 * Handles updating child information and profile image.
 */

require_once __DIR__ . '/../../include/function/child_functions.php';
require_once __DIR__ . '/../../include/auth/auth.php';

// Only allow admin or teacher to update child data
checkUserRole(['admin', 'teacher']);

// Helper function to respond with JSON
function jsonResponse($status, $message = '', $data = [])
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

// Validate required fields
$required = ['student_id', 'prefix_th', 'firstname_th', 'lastname_th'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        jsonResponse('error', "Missing required field: $field");
    }
}

$student_id = $_POST['student_id'];

// Prepare data array for update
$data = [
    'prefix_th'      => $_POST['prefix_th'],
    'firstname_th'   => $_POST['firstname_th'],
    'lastname_th'    => $_POST['lastname_th'],
    // Optional fields – use null if not set
    'nickname'       => $_POST['nickname'] ?? null,
    'prefix_en'      => $_POST['prefix_en'] ?? null,
    'firstname_en'   => $_POST['firstname_en'] ?? null,
    'lastname_en'    => $_POST['lastname_en'] ?? null,
    'birthday'       => $_POST['birthday'] ?? null,
    'sex'            => $_POST['sex'] ?? null,
    'academic_year'  => $_POST['academic_year'] ?? null,
    'child_group'    => $_POST['child_group'] ?? null,
    'classroom'      => $_POST['classroom'] ?? null,
    'address'        => $_POST['address'] ?? null,
    'district'       => $_POST['district'] ?? null,
    'amphoe'         => $_POST['amphoe'] ?? null,
    'province'       => $_POST['province'] ?? null,
    'zipcode'        => $_POST['zipcode'] ?? null,
    // Add other fields as needed …
];

// Handle profile image if a new one is uploaded
if (!empty($_FILES['profile_image']['tmp_name'])) {
    $imageData = file_get_contents($_FILES['profile_image']['tmp_name']);
    $base64   = 'data:' . $_FILES['profile_image']['type'] . ';base64,' . base64_encode($imageData);
    $data['profile_image'] = $base64;
} elseif (!empty($_POST['profile_image_data'])) {
    // If the form sent a base64 version (client side compression)
    $data['profile_image'] = $_POST['profile_image_data'];
}

// Perform update via child function
$updated = updateChild($student_id, $data);

if ($updated) {
    jsonResponse('success', 'บันทึกข้อมูลเรียบร้อย');
} else {
    jsonResponse('error', 'ไม่สามารถบันทึกข้อมูลได้');
}

?>