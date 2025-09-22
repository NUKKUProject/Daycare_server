<?php

// Define routes and the corresponding controllers/methods
$routes = [
    '/' => 'AuthController@loginPage',                     // หน้า Login
    '/login' => 'AuthController@processLogin',             // การประมวลผลการ Login
    '/logout' => 'AuthController@logout',                  // การออกจากระบบ

    '/dashboard/parent' => 'ChildController@viewParentDashboard', // Dashboard ผู้ปกครอง
    '/dashboard/admin' => 'ChildController@viewAdminDashboard',   // Dashboard ผู้ดูแลระบบ

    '/child/add' => 'ChildController@addChild',            // เพิ่มข้อมูลเด็ก
    '/child/edit' => 'ChildController@editChild',          // แก้ไขข้อมูลเด็ก
    '/child/delete' => 'ChildController@deleteChild',      // ลบข้อมูลเด็ก

    '/activity/add' => 'ActivityController@addActivity',   // เพิ่มกิจกรรมรายวัน
    '/activity/view' => 'ActivityController@viewActivity', // ดูกิจกรรมรายวัน

    '/vaccination/add' => 'VaccinationController@addRecord',  // เพิ่มประวัติการฉีดวัคซีน
    '/vaccination/view' => 'VaccinationController@viewRecords' // ดูประวัติการฉีดวัคซีน
];

// Function to route requests
function route($uri, $routes) {
    if (array_key_exists($uri, $routes)) {
        list($controller, $method) = explode('@', $routes[$uri]);
        require_once __DIR__ . "/../app/controllers/{$controller}.php";

        $controllerInstance = new $controller();
        return call_user_func([$controllerInstance, $method]);
    } else {
        http_response_code(404);
        echo "404 Not Found";
    }
}

// Get the current URI
$requestUri = strtok($_SERVER['REQUEST_URI'], '?'); // ตัด query string ออก

// Route the request
route($requestUri, $routes);
