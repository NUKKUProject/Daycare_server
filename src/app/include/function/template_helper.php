<?php
require_once __DIR__ . '/../../../config/database.php';

function getTemplates(): array {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("
        SELECT id, name, description, header_color, layout_config, is_default, is_active, sort_order, created_at, updated_at
        FROM card_templates
        WHERE is_active = TRUE
        ORDER BY sort_order ASC, created_at DESC
    ");
    return $stmt->fetchAll();
}

function getTemplate(int $id): ?array {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT id, name, description, header_color, layout_config, is_default, is_active, sort_order, created_at, updated_at
        FROM card_templates
        WHERE id = :id
    ");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getDefaultTemplate(): ?array {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("
        SELECT id, name, description, header_color, layout_config, is_default, is_active, sort_order, created_at, updated_at
        FROM card_templates
        WHERE is_default = TRUE AND is_active = TRUE
        LIMIT 1
    ");
    $row = $stmt->fetch();
    if ($row) return $row;

    $stmt = $pdo->query("
        SELECT id, name, description, header_color, layout_config, is_default, is_active, sort_order, created_at, updated_at
        FROM card_templates
        WHERE is_active = TRUE
        ORDER BY sort_order ASC
        LIMIT 1
    ");
    return $stmt->fetch() ?: null;
}

function saveTemplate(?int $id, string $name, string $description, string $header_color, array $layout_config): int {
    $pdo = getDatabaseConnection();

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE card_templates 
            SET name = :name, description = :description, header_color = :header_color, 
                layout_config = :layout_config::json
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'header_color' => $header_color,
            'layout_config' => json_encode($layout_config)
        ]);
        return $id;
    }

    $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM card_templates")->fetchColumn();
    $stmt = $pdo->prepare("
        INSERT INTO card_templates (name, description, header_color, layout_config, sort_order)
        VALUES (:name, :description, :header_color, :layout_config::json, :sort_order)
        RETURNING id
    ");
    $stmt->execute([
        'name' => $name,
        'description' => $description,
        'header_color' => $header_color,
        'layout_config' => json_encode($layout_config),
        'sort_order' => $maxSort
    ]);
    return (int)$stmt->fetchColumn();
}

function setDefaultTemplate(int $id): void {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE card_templates SET is_default = FALSE WHERE is_default = TRUE");
        $stmt = $pdo->prepare("UPDATE card_templates SET is_default = TRUE WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getChildDataForPrint(PDO $pdo, int $children_id): ?array {
    $stmt = $pdo->prepare("
        SELECT c.id, c.studentid, c.prefix_th, c.firstname_th, c.lastname_th, 
               c.nickname, c.classroom, c.child_group, c.blood_type, 
               c.congenital_disease, c.profile_image, c.birthday,
               COALESCE(
                   (SELECT string_agg(NULLIF(BTRIM(fa.food_name), ''), ', ' ORDER BY fa.id)
                    FROM food_allergies fa WHERE fa.student_id = c.studentid),
                   NULLIF(BTRIM(c.allergic_food), '')
               ) AS allergic_food,
               COALESCE(
                   (SELECT string_agg(NULLIF(BTRIM(da.drug_name), ''), ', ' ORDER BY da.id)
                    FROM drug_allergies da WHERE da.student_id = c.studentid),
                   NULLIF(BTRIM(c.allergic_medicine), '')
               ) AS allergic_medicine,
               c.father_first_name, c.father_last_name,
               c.mother_first_name, c.mother_last_name,
               c.relative_first_name, c.relative_last_name,
               c.father_phone, c.father_phone_backup,
               c.mother_phone, c.mother_phone_backup,
               c.relative_phone, c.relative_phone_backup,
               c.father_image, c.mother_image, c.relative_image,
               c.emergency_contact, c.emergency_phone, c.emergency_relation,
               t.token
        FROM children c
        LEFT JOIN LATERAL (
            SELECT token FROM student_qr_tokens 
            WHERE children_id = c.id AND is_active = TRUE
              AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC LIMIT 1
        ) t ON true
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $children_id]);
    return $stmt->fetch() ?: null;
}

function logPrint(PDO $pdo, string $printed_by, ?int $template_id, array $children_ids, string $print_type, int $total_cards): void {
    $stmt = $pdo->prepare("
        INSERT INTO print_logs (printed_by, template_id, children_ids, print_type, total_cards)
        VALUES (:printed_by, :template_id, :children_ids::json, :print_type, :total_cards)
    ");
    $stmt->execute([
        'printed_by' => $printed_by,
        'template_id' => $template_id,
        'children_ids' => json_encode($children_ids),
        'print_type' => $print_type,
        'total_cards' => $total_cards
    ]);
}
