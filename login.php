<?php

require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$isLocked = false;

$timeoutMessage = null;

if (isset($_GET['timeout'])) {
    $timeoutMessage = lang('login_timeout');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');

    if (login_is_locked($u) || login_is_ip_locked()) {
        $error = lang('login_locked');
        $isLocked = true;
    } else {
        foreach (users() as $x) {
            if (
                strcasecmp($x['username'], $u) === 0 &&
                password_verify($p, $x['password'])
            ) {
                clear_login_failures($u);
clear_login_ip_failures();


/*
 * Letzten Login speichern
 */
$users = users();

foreach ($users as &$user) {

    if (($user['id'] ?? '') === $x['id']) {

        $user['last_login'] = date('c');
        $user['last_activity'] = date('c');

        break;
    }
}

unset($user);

save_users($users);


session_regenerate_id(true);
$_SESSION['user_id'] = $x['id'];
$_SESSION['last_activity'] = time();

header('Location: dashboard.php');
exit;
            }
        }

        record_login_failure($u);
        record_login_ip_failure();

        if (login_is_locked($u) || login_is_ip_locked()) {
            $error = lang('login_locked');
            $isLocked = true;
        } else {
            $error = lang('login_error');
        }
    }
}

layout_start(lang('login_title'));
?>

<div class="auth-card">
    <h1><?= e(lang('login_title')) ?></h1>

    <?php if ($timeoutMessage): ?>
    <div class="notice" role="status">
        <?= e($timeoutMessage) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error" id="login-error" role="alert" tabindex="-1">
        <?= e($error) ?>
    </div>
<?php endif; ?>

    <form id="login-form" method="post" <?= $error ? 'aria-describedby="login-error"' : '' ?>>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label for="username"><?= e(lang('username')) ?></label>
    <input id="username" type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required>

    <label for="password"><?= e(lang('password')) ?></label>
    <input id="password" type="password" name="password" autocomplete="current-password" required>

    <button class="button" type="submit" <?= $isLocked ? 'disabled' : '' ?>>
        <?= e(lang('login_button')) ?>
    </button>
</form>

    <p class="text-center">
    <?= sprintf(
        e(lang('register_new')),
        '<a href="register.php">' .
        e(lang('register_new_link')) .
        '</a>'
    ) ?>
</p>
</div>

<?php layout_end(); ?>