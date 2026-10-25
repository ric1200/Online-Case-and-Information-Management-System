<?php
require_once __DIR__ . '/../../_private/bootstrap.php';
$pdo = app_pdo();
if (!function_exists('insertAudit')) {
    function insertAudit(PDO $pdo, string $action, ?string $table = null, $object_id = null, $changed_data = null, ?string $sql_text = null, ?int $user_id = null): void
    {
        try {
            $data = is_array($changed_data) || is_object($changed_data) ? json_encode($changed_data, JSON_UNESCAPED_UNICODE) : $changed_data;
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id,action,table_name,object_id,changed_data,sql_text,ip_address) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$user_id, $action, $table, $object_id, $data, $sql_text, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Throwable $e) { error_log('Audit event could not be stored.'); }
    }
}
