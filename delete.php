<?php

require_once __DIR__ . '/includes/auth.php';

$u = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

verify_csrf();

$id = (string)($_POST['id'] ?? '');

$a = files();

foreach ($a as $i => $f) {

    if (
        ($f['id'] ?? '') === $id &&
        (
            ($f['user_id'] ?? '') === $u['id'] ||
            ($u['role'] ?? 'user') === 'admin'
        )
    ) {

        $p = UPLOAD_DIR . '/' . basename(
            $f['stored_name']
        );

        if (is_file($p)) {
            unlink($p);
        }

        unset($a[$i]);

        save_files(array_values($a));

        flash(lang('file_deleted'));

        break;
    }
}


/*
 * Prüfen, von welcher Seite der Löschvorgang kam.
 */
$referer = $_SERVER['HTTP_REFERER'] ?? '';

if (
    ($u['role'] ?? 'user') === 'admin' &&
    strpos($referer, '/admin.php') !== false
) {
    header('Location: admin.php');
    exit;
}


/*
 * Standard: zurück zum Dashboard.
 */
header('Location: dashboard.php');
exit;