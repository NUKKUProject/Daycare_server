<?php
include_once '../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDatabaseConnection(); // << สำคัญ: รับ PDO จากฟังก์ชัน

    $sql = "
      WITH base AS (
  SELECT
    c.classroom,
    c.child_group,
    CASE
      WHEN lower(coalesce(c.prefix_th, c.prefix_en, '')) IN ('เด็กชาย','ด.ช.','mr','master','ชาย') THEN 'male'
      WHEN lower(coalesce(c.prefix_th, c.prefix_en, '')) IN ('เด็กหญิง','ด.ญ.','miss','ms','mrs','หญิง') THEN 'female'
      ELSE 'unknown'
    END AS gender
  FROM children c
  WHERE c.status = 'กำลังศึกษา'
)
SELECT
  classroom,
  child_group,
  COUNT(*) FILTER (WHERE gender = 'female') AS total_children_female,
  COUNT(*) FILTER (WHERE gender = 'male')   AS total_children_male,
  COUNT(*) FILTER (WHERE gender = 'unknown') AS total_children_unknown,
  COUNT(*) AS total_children
FROM base
GROUP BY classroom, child_group
ORDER BY classroom, child_group;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}