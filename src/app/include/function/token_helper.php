<?php
require_once __DIR__ . '/../../../config/database.php';

define('TOKEN_EXPIRY_DAYS', 1095); // 3 years

function generateToken(): string {
    return 'QR-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function revokeActiveTokens(PDO $pdo, string $children_id, string $reason): void {
    $stmt = $pdo->prepare("
        UPDATE student_qr_tokens 
        SET is_active = FALSE, revoked_at = NOW(), revoked_reason = :reason
        WHERE children_id = :children_id AND is_active = TRUE
    ");
    $stmt->execute(['children_id' => $children_id, 'reason' => $reason]);
}

function createToken(PDO $pdo, string $children_id, string $qr_type = 'student_card', ?int $expiryDays = null): array {
    $stmt = $pdo->prepare("SELECT id FROM children WHERE id = :id");
    $stmt->execute(['id' => $children_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }

    $token = generateToken();
    $expiryDays = $expiryDays ?? TOKEN_EXPIRY_DAYS;
    $stmt = $pdo->prepare("
        INSERT INTO student_qr_tokens (children_id, token, qr_type, expires_at) 
        VALUES (:children_id, :token, :qr_type, NOW() + :expires_at::interval)
        RETURNING id
    ");
    $stmt->execute([
        'children_id' => $children_id,
        'token' => $token,
        'qr_type' => $qr_type,
        'expires_at' => "{$expiryDays} days"
    ]);
    $row = $stmt->fetch();

    return ['id' => $row['id'], 'token' => $token];
}

function getActiveToken(PDO $pdo, string $children_id): ?array {
    $stmt = $pdo->prepare("
        SELECT id, token, qr_type, created_at, expires_at
        FROM student_qr_tokens 
        WHERE children_id = :children_id AND is_active = TRUE
          AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute(['children_id' => $children_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getTokenHistory(PDO $pdo, string $children_id): array {
    $stmt = $pdo->prepare("
        SELECT id, token, qr_type, is_active, created_at, expires_at, revoked_at, revoked_reason
        FROM student_qr_tokens 
        WHERE children_id = :children_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['children_id' => $children_id]);
    return $stmt->fetchAll();
}

function getClassrooms(): array {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT DISTINCT classroom FROM children WHERE classroom IS NOT NULL AND classroom != '' AND status = 'กำลังศึกษา' ORDER BY classroom");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getStudentsWithTokenStatus(PDO $pdo, ?string $classroom = null, ?string $status_filter = null, ?string $search = null, ?string $group = null, ?int $academicYear = null, ?int $page = null, int $perPage = 50, ?int &$totalCount = null): array {
    // Build WHERE conditions
    $conditions = ["c.status = 'กำลังศึกษา'"];
    $params = [];

    if ($classroom && $classroom !== 'all') {
        $conditions[] = "c.classroom = :classroom";
        $params['classroom'] = $classroom;
    }

    if ($group && $group !== 'all') {
        $conditions[] = "c.child_group = :child_group";
        $params['child_group'] = $group;
    }

    if ($academicYear) {
        $conditions[] = "c.academic_year = :academic_year";
        $params['academic_year'] = $academicYear;
    }

    // Token-level filters require the LATERAL join, so we handle them in the main query
    $tokenFilter = '';
    if ($status_filter === 'has_qr') {
        $tokenFilter = " AND t.id IS NOT NULL AND (t.expires_at IS NULL OR t.expires_at > NOW())";
    } elseif ($status_filter === 'no_qr') {
        $tokenFilter = " AND (t.id IS NULL OR (t.expires_at IS NOT NULL AND t.expires_at <= NOW()))";
    }

    if ($search) {
        $conditions[] = "(c.firstname_th ILIKE :search OR c.lastname_th ILIKE :search2 OR c.nickname ILIKE :search3 OR c.studentid ILIKE :search4)";
        $params['search'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
        $params['search3'] = "%{$search}%";
        $params['search4'] = "%{$search}%";
    }

    $where = implode(' AND ', $conditions);

    // Count total (only when pagination is requested)
    $totalCount = 0;
    if ($page !== null) {
        $countSql = "SELECT COUNT(*) FROM children c WHERE $where";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalCount = (int)$stmt->fetchColumn();
        if ($totalCount === 0) {
            return [];
        }
    }

    // Main query
    $sql = "
        SELECT 
            c.id, c.studentid, c.prefix_th, c.firstname_th, c.lastname_th,
            c.nickname, c.classroom, c.child_group, c.profile_image,
            t.id as token_id, t.token, t.created_at as token_created_at,
            t.expires_at as token_expires_at
        FROM children c
        LEFT JOIN LATERAL (
            SELECT id, token, created_at, expires_at FROM student_qr_tokens 
            WHERE children_id = c.id AND is_active = TRUE
            ORDER BY created_at DESC LIMIT 1
        ) t ON true
        WHERE $where $tokenFilter
        ORDER BY c.child_group, c.classroom, c.firstname_th
    ";

    if ($page !== null) {
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $now = new DateTime();
    foreach ($rows as &$row) {
        $row['has_active_token'] = $row['token_id'] !== null;
        $row['active_token'] = $row['token'] ?? null;
        if ($row['token_expires_at']) {
            $expiresAt = new DateTime($row['token_expires_at']);
            $row['is_expired'] = $expiresAt <= $now;
        } else {
            $row['is_expired'] = false;
        }
    }
    return $rows;
}

function getTokenStats(PDO $pdo): array {
    $total = $pdo->query("SELECT COUNT(*) FROM children WHERE status = 'กำลังศึกษา'")->fetchColumn();
    $withToken = $pdo->query("
        SELECT COUNT(DISTINCT c.id) FROM children c 
        INNER JOIN student_qr_tokens t ON t.children_id = c.id AND t.is_active = TRUE
          AND (t.expires_at IS NULL OR t.expires_at > NOW())
        WHERE c.status = 'กำลังศึกษา'
    ")->fetchColumn();
    return [
        'total' => (int)$total,
        'with_qr' => (int)$withToken,
        'without_qr' => (int)$total - (int)$withToken
    ];
}

function getGroupTabCounts(PDO $pdo, ?int $academicYear = null): array {
    $sql = "SELECT c.child_group, COUNT(*) as cnt FROM children c WHERE c.status = 'กำลังศึกษา'";
    $params = [];
    if ($academicYear) {
        $sql .= " AND c.academic_year = :academic_year";
        $params['academic_year'] = $academicYear;
    }
    $sql .= " GROUP BY c.child_group";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['child_group']] = (int)$row['cnt'];
    }
    return $result;
}

function getStatsCounts(PDO $pdo, ?string $group = null, ?int $academicYear = null): array {
    $extraCond = '';
    $params = [];
    if ($group && $group !== 'all') {
        $extraCond .= " AND c.child_group = :grp";
        $params['grp'] = $group;
    }
    if ($academicYear) {
        $extraCond .= " AND c.academic_year = :academic_year";
        $params['academic_year'] = $academicYear;
    }

    // Total
    $total = $pdo->prepare("SELECT COUNT(*) FROM children c WHERE c.status = 'กำลังศึกษา'$extraCond");
    $total->execute($params);
    $totalCount = (int)$total->fetchColumn();

    // Active (non-expired) tokens
    $active = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id) FROM children c
        INNER JOIN student_qr_tokens t ON t.children_id = c.id AND t.is_active = TRUE
          AND (t.expires_at IS NULL OR t.expires_at > NOW())
        WHERE c.status = 'กำลังศึกษา'$extraCond
    ");
    $active->execute($params);
    $activeCount = (int)$active->fetchColumn();

    // Expired tokens
    $expired = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id) FROM children c
        INNER JOIN student_qr_tokens t ON t.children_id = c.id AND t.is_active = TRUE
          AND t.expires_at IS NOT NULL AND t.expires_at <= NOW()
        WHERE c.status = 'กำลังศึกษา'$extraCond
    ");
    $expired->execute($params);
    $expiredCount = (int)$expired->fetchColumn();

    // No token at all
    $noToken = $totalCount - $activeCount - $expiredCount;

    return [
        'total'       => $totalCount,
        'active'      => $activeCount,
        'expired'     => $expiredCount,
        'no_token'    => $noToken,
        'with_qr'     => $activeCount + $expiredCount,
    ];
}
