<?php
require_once(__DIR__ . '/../../../config/database.php');
require_once 'classroom_functions.php';

header('Content-Type: application/json');

if (isset($_GET['child_group'])) {
    $classrooms = getClassroomsByGroup($_GET['child_group']);
} else {
    $classrooms = getAllClassrooms();
}

echo json_encode($classrooms); 