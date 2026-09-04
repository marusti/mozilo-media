<?php

require_once __DIR__ . '/includes/auth.php';

$user = require_login();

if (($user['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    exit(lang('access_denied'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['action'] ?? '');
    $targetId = (string)($_POST['user_id'] ?? '');

    $users = users();
    $changed = false;

    foreach ($users as &$target) {
        if (($target['id'] ?? '') !== $targetId) {
            continue;
        }

        if ($target['id'] === $user['id'] && $action === 'remove_admin') {
            flash(lang('cannot_remove_own_admin'));
            break;
        }

        if ($action === 'make_admin') {
            $target['role'] = 'admin';
            $changed = true;
            flash(lang('user_made_admin'));
        } elseif ($action === 'remove_admin') {
            $target['role'] = 'user';
            $changed = true;
            flash(lang('admin_rights_removed'));
        }

        break;
    }

    unset($target);

    if ($changed) {
        save_users($users);
    }

    header('Location: admin.php');
    exit;
}

function update_user_activity():void
{
    $id=$_SESSION['user_id']??null;

    if(!$id)return;

    $users=users();

    foreach($users as &$u){

        if(($u['id']??'')===$id){

            $u['last_activity']=date('c');
            break;

        }
    }

    unset($u);

    save_users($users);
}

$allUsers = users();

usort(
    $allUsers,
    fn($a, $b) => strcmp(
        strtolower($a['username'] ?? ''),
        strtolower($b['username'] ?? '')
    )
);

layout_start(lang('admin_page_title'));

?>

<div class="hero">
    <h1><?= e(lang('admin_area_title')) ?></h1>
    <p><?= e(lang('admin_area_description')) ?></p>
    <a class="button" href="admin_categories.php">
    <?= e(lang('category_management')) ?>
</a>
</div>

<div class="card">

<h2><?= e(lang('users')) ?></h2>

<?php if (!$allUsers): ?>

    <p><?= e(lang('no_users')) ?></p>

<?php else: ?>

    <div class="file-list">

        <?php foreach ($allUsers as $target): ?>

            <div class="file-row user-row">

                <div>
                    <strong><?= e($target['username']) ?></strong>

                    <small>
    <?= e(lang('role')) ?>:
    <?php if (($target['role'] ?? 'user') === 'admin'): ?>

    <?= e(lang('admin_role')) ?>

<?php else: ?>

    <?= e(lang('user_role')) ?>

<?php endif; ?>

    ·
    
    <?= e(lang('last_login')) ?>:

    <?php if (!empty($target['last_login'])): ?>

        <?= e(date(
            'd.m.Y H:i',
            strtotime($target['last_login'])
        )) ?>

    <?php else: ?>

        <?= e(lang('never'))

        ?>

    <?php endif; ?>
    
<?php

$online = false;

if (!empty($target['last_activity'])) {

    $online =
        strtotime($target['last_activity']) > time() - 300;

}

?>

<?php if ($online): ?>

<span class="badge">
🟢 Online
</span>

<?php else: ?>

<span class="badge">
⚪ Offline
</span>

<?php endif; ?>

</small>
                </div>

                <div class="actions">

                    <?php if (($target['role'] ?? 'user') === 'admin'): ?>

                        <?php if ($target['id'] !== $user['id']): ?>

                            <form method="post" class="confirm-form" data-confirm="<?= e(lang('remove_admin_confirm')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="user_id" value="<?= e($target['id']) ?>">
    <input type="hidden" name="action" value="remove_admin">

    <button class="button danger" type="submit">
        <?= e(lang('remove_admin')) ?>
    </button>
</form>

<?php else: ?>

<span class="badge">
    <?= e(lang('you')) ?>
</span>

<?php endif; ?>

<?php else: ?>

<form method="post" action="delete.php" class="confirm-form" data-confirm="<?= e(lang('delete_file_confirm')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($file['id']) ?>">

    <button class="button danger" type="submit">
        <?= e(lang('admin_delete_file')) ?>
    </button>
</form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</div>

<div class="card">

<h2><?= e(lang('admin_files')) ?></h2>

<?php

$allFiles = files();

usort(
    $allFiles,
    fn($a, $b) => strcmp(
        $b['uploaded_at'] ?? '',
        $a['uploaded_at'] ?? ''
    )
);

?>

<?php if (!$allFiles): ?>

    <p><?= e(lang('admin_no_files')) ?></p>

<?php else: ?>

    <div class="file-list">

        <?php foreach ($allFiles as $file): ?>

            <div class="file-row files-row">

                <div>

                    <strong>
                        <?= e($file['original_name'] ?? '') ?>
                    </strong>

                    <small>
                        <?= e(lang('admin_uploaded_by')) ?>
                        <?= e($file['username'] ?? '') ?>

                        · <?= e(lang('admin_category')) ?>
                        <?= e($file['category'] ?? lang('other_category')) ?>

                        · <?= e(lang('admin_size')) ?>
                        <?= e(format_bytes((int)($file['size'] ?? 0))) ?>

                        · <?= e(lang('admin_downloads')) ?>
                        <?= e((string)($file['downloads'] ?? 0)) ?>

                        · <?= e(lang('admin_uploaded_at')) ?>
                        <?= e(date('d.m.Y H:i', strtotime($file['uploaded_at'] ?? ''))) ?>
                    </small>

                    <?php if (!empty($file['tags'])): ?>

                        <div class="tags">

                            <?php foreach ((array)$file['tags'] as $tag): ?>

                                <a href="index.php?tag=<?= urlencode($tag) ?>">
                                    #<?= e($tag) ?>
                                </a>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="actions">

                    <form method="post" action="delete.php" class="confirm-form" data-confirm="<?= e(lang('delete_file_confirm')) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= e($file['id']) ?>">

                        <button class="button danger" type="submit">
                            <?= e(lang('admin_delete_file')) ?>
                        </button>
                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</div>

<?php layout_end(); ?>
