<?php

require_once __DIR__ . '/includes/auth.php';

$u = require_login();

$mine = array_values(
    array_filter(
        files(),
        fn($f) => ($f['user_id'] ?? '') === $u['id']
    )
);

usort(
    $mine,
    fn($a, $b) => strcmp(
        $b['uploaded_at'] ?? '',
        $a['uploaded_at'] ?? ''
    )
);

layout_start(lang('dashboard_title'));

?>

<section class="hero">

<h1>
    <?= e(lang('dashboard_greeting')) ?>
    <?= e($u['username']) ?>!
</h1>

<p><?= e(lang('dashboard_description')) ?></p>

<a class="button" href="upload.php">
    <?= e(lang('upload_zip')) ?>
</a>

<?php if (($u['role'] ?? 'user') === 'admin'): ?>

    <a class="button secondary" href="admin.php">
        <?= e(lang('admin_area_title')) ?>
    </a>

<?php endif; ?>

</section>

<div class="card">

<h2><?= e(lang('my_files')) ?></h2>

<?php if (!$mine): ?>

    <p><?= e(lang('no_uploads')) ?></p>

<?php else: ?>

    <?php foreach ($mine as $f): ?>

        <div class="file-row files-row">

            <div class="file-row-desc">

                <strong>
                    <?= e(
                        trim((string)($f['display_name'] ?? '')) !== ''
                            ? $f['display_name']
                            : $f['original_name']
                    ) ?>
                </strong>

                <?php if (trim((string)($f['description'] ?? '')) !== ''): ?>

                    <div class="file-description">
                        <?= nl2br(e($f['description'])) ?>
                    </div>

                <?php endif; ?>

                <small>
    <?= e($f['category'] ?? lang('other_category')) ?>

    <?php if (!empty($f['cms_versions'])): ?>
        · <?= e(lang('cms_versions')) ?>
        <?= e(implode(', ', (array)$f['cms_versions'])) ?>
    <?php endif; ?>

    · <?= e(format_bytes((int)$f['size'])) ?>
    · <?= e((string)($f['downloads'] ?? 0)) ?>
    <?= e(lang('downloads')) ?>
    · <?= e(date('d.m.Y', strtotime($f['uploaded_at']))) ?>
</small>

                <?php if (!empty($f['tags'])): ?>

                    <div class="tags">

                        <?php foreach ((array)$f['tags'] as $tag): ?>

                            <a href="index.php?tag=<?= urlencode($tag) ?>">
                                #<?= e($tag) ?>
                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>


            <div class="actions">

                <a
                    class="button secondary"
                    href="edit_file.php?id=<?= e($f['id']) ?>"
                >
                    <?= e(lang('edit_file')) ?>
                </a>

                <form method="post" action="delete.php" class="confirm-form" data-confirm="<?= e(lang('delete_file_confirm')) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e($f['id']) ?>">
                    <button class="button danger" type="submit"><?= e(lang('delete')) ?> </button>
                </form>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>
<?php layout_end(); ?>
