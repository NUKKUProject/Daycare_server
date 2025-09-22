<?php
require_once(__DIR__ . '/../../../config/database.php');

function getTotalStudents() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM children");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting total students: " . $e->getMessage());
        return 0;
    }
}

function getTotalStaff() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM teacher");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting total staff: " . $e->getMessage());
        return 0;
    }
}

function getAttendanceRate() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                (COUNT(CASE WHEN status = 'present' THEN 1 END) * 100.0 / COUNT(*)) as rate
            FROM attendance 
            WHERE DATE(check_date) = CURRENT_DATE
        ");
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error getting attendance rate: " . $e->getMessage());
        return 0;
    }
}

function getTotalActivities() {
    // ถ้ายังไม่มีตาราง activities
    return 0;
}

function getMonthlyAttendance() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(check_date, '%M') as month,
                (COUNT(CASE WHEN status = 'present' THEN 1 END) * 100.0 / COUNT(*)) as rate
            FROM attendance 
            WHERE check_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(check_date, '%Y-%m')
            ORDER BY check_date
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error getting monthly attendance: " . $e->getMessage());
        return [];
    }
}

function getStudentsByGroup() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                child_group,
                COUNT(*) as count
            FROM children 
            GROUP BY child_group
            ORDER BY child_group
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error getting students by group: " . $e->getMessage());
        return [];
    }
}

function getStaffByPosition() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                position,
                COUNT(*) as count
            FROM teachers 
            WHERE status = 'active'
            GROUP BY position
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error getting staff by position: " . $e->getMessage());
        return [];
    }
}

function getAttendanceStats() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(check_date, '%Y-%m') as month,
                COUNT(*) as total_records,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                ROUND((COUNT(CASE WHEN status = 'present' THEN 1 END) * 100.0 / COUNT(*)), 1) as attendance_rate
            FROM attendance 
            WHERE check_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(check_date, '%Y-%m')
            ORDER BY month DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting attendance stats: " . $e->getMessage());
        return [];
    }
}
?> 