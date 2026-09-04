<?php

require_once __DIR__ . '/includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

verify_csrf();

$id = (string)($_POST['id'] ?? '');

$fs = files();

foreach ($fs as $i => $f) {

    if (($f['id'] ?? '') === $id) {

        $p = UPLOAD_DIR . '/' . basename(
            (string)$f['stored_name']
        );

        if (is_file($p)) {
            unlink($p);
        }

        unset($fs[$i]);

        save_files(array_values($fs));

        flash(lang('admin_file_deleted'));

        break;
    }
}

header('Location: admin.php');
exit;