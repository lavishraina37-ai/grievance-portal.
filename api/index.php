<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$db = database();
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';

try {
    if ($resource === 'auth' && $method === 'POST') {
        $body = requestBody();
        requireFields($body, ['email', 'password']);
        $statement = $db->prepare('SELECT id, full_name, email, password_hash, role, school_name FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$body['email']]);
        $user = $statement->fetch();
        if (!$user || !password_verify($body['password'], $user['password_hash'])) {
            respond(['error' => 'Invalid email or password'], 401);
        }
        unset($user['password_hash']);
        respond(['user' => $user]);
    }

    if ($resource === 'complaints' && $method === 'GET') {
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if (!$userId) { respond(['error' => 'user_id is required'], 422); }
        $statement = $db->prepare('SELECT c.id, c.tracking_code, c.subject, cat.name AS category, c.description, c.status, c.owner_name, c.created_at, c.updated_at FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id WHERE c.user_id = ? ORDER BY c.created_at DESC');
        $statement->execute([$userId]);
        respond(['data' => $statement->fetchAll()]);
    }

    if ($resource === 'complaints' && $method === 'POST') {
        $body = requestBody();
        requireFields($body, ['user_id', 'category_id', 'subject', 'description']);
        $trackingCode = 'GRV-' . date('ym') . '-' . random_int(100, 999);
        $statement = $db->prepare('INSERT INTO complaints (tracking_code, user_id, category_id, subject, description) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([$trackingCode, $body['user_id'], $body['category_id'], trim($body['subject']), trim($body['description'])]);
        $complaintId = (int) $db->lastInsertId();
        $history = $db->prepare('INSERT INTO complaint_status_history (complaint_id, changed_by, status, note) VALUES (?, ?, ?, ?)');
        $history->execute([$complaintId, $body['user_id'], 'submitted', 'Grievance submitted successfully']);
        respond(['id' => $complaintId, 'tracking_code' => $trackingCode, 'status' => 'submitted'], 201);
    }

    if ($resource === 'complaints' && $method === 'PATCH') {
        $complaintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $body = requestBody();
        requireFields($body, ['status', 'changed_by']);
        if (!$complaintId) { respond(['error' => 'Complaint id is required'], 422); }
        $allowed = ['under_review', 'assigned', 'in_progress', 'resolved', 'closed', 'rejected'];
        if (!in_array($body['status'], $allowed, true)) { respond(['error' => 'Invalid status'], 422); }
        $update = $db->prepare('UPDATE complaints SET status = ?, owner_name = COALESCE(?, owner_name), resolution_notes = COALESCE(?, resolution_notes) WHERE id = ?');
        $update->execute([$body['status'], $body['owner_name'] ?? null, $body['resolution_notes'] ?? null, $complaintId]);
        $history = $db->prepare('INSERT INTO complaint_status_history (complaint_id, changed_by, status, note) VALUES (?, ?, ?, ?)');
        $history->execute([$complaintId, $body['changed_by'], $body['status'], $body['note'] ?? null]);
        respond(['message' => 'Complaint status updated']);
    }

    if ($resource === 'notifications' && $method === 'GET') {
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if (!$userId) { respond(['error' => 'user_id is required'], 422); }
        $statement = $db->prepare('SELECT id, complaint_id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
        $statement->execute([$userId]);
        respond(['data' => $statement->fetchAll()]);
    }

    if ($resource === 'reports' && $method === 'GET') {
        $summary = $db->query("SELECT COUNT(*) AS total, SUM(status IN ('submitted','under_review','assigned','in_progress')) AS open_cases, SUM(status IN ('resolved','closed')) AS resolved_cases FROM complaints")->fetch();
        $categories = $db->query('SELECT cat.name AS category, COUNT(c.id) AS total FROM complaint_categories cat LEFT JOIN complaints c ON c.category_id = cat.id GROUP BY cat.id, cat.name ORDER BY total DESC')->fetchAll();
        respond(['summary' => $summary, 'categories' => $categories]);
    }

    respond(['error' => 'Route not found'], 404);
} catch (Throwable $error) {
    respond(['error' => 'Server error', 'detail' => $error->getMessage()], 500);
}
