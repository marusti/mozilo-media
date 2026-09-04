<?php

require_once __DIR__ . '/includes/auth.php';

$id = (string)($_GET['id'] ?? '');

$fs = files();

$f = null;

foreach ($fs as $x) {

    if (($x['id'] ?? '') === $id) {
        $f = $x;
        break;
    }
}

if (!$f) {

    http_response_code(404);

    layout_start(lang('file_not_found'));

    ?>

    <div class="card">

        <div class="error">
            <?= e(lang('file_not_found')) ?>
        </div>

        <div class="actions">

            <a
                class="button secondary"
                href="javascript:history.back()"
            >
                <?= e(lang('back')) ?>
            </a>

        </div>

    </div>

    <?php

    layout_end();
    exit;
}

$path = UPLOAD_DIR . '/' . basename(
    (string)$f['stored_name']
);

if (!is_file($path)) {

    http_response_code(404);

    layout_start(lang('file_not_found'));

    ?>

    <div class="card">

        <div class="error">
            <?= e(lang('file_not_found')) ?>
        </div>

        <div class="actions">

            <a
                class="button secondary"
                href="javascript:history.back()"
            >
                <?= e(lang('back')) ?>
            </a>

        </div>

    </div>

    <?php

    layout_end();
    exit;
}

foreach ($fs as &$x) {

    if (($x['id'] ?? '') === $id) {

        $x['downloads'] =
            (int)($x['downloads'] ?? 0) + 1;

        $x['last_download'] = date('c');

        break;
    }
}

unset($x);

save_files($fs);

$name = str_replace(
    ["\r", "\n", '"'],
    '',
    basename((string)$f['original_name'])
);

header('Content-Type: application/zip');

header(
    'Content-Length: ' . filesize($path)
);

header(
    'Content-Disposition: attachment; filename="' .
    $name .
    '"'
);

header(
    'X-Content-Type-Options: nosniff'
);

readfile($path);

exit;