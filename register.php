<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
        $errors[] = lang('invalid_username');
    }

    if (strlen($password) < 8) {
        $errors[] = lang('password_too_short');
    }

    if ($password !== $password2) {
        $errors[] = lang('password_mismatch');
    }

    $users = users();

    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            $errors[] = lang('username_taken');
            break;
        }
    }

    $role = 'user';

    if (!$errors) {

        $newUser = [
            'id' => bin2hex(random_bytes(16)),
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => date('c'),
        ];

        $users[] = $newUser;

        try {

            save_users($users);

            // Nur bei erfolgreichem Speichern weiterleiten
            if ($role === 'admin') {
                flash(lang('admin_registration_success'));
            } else {
                flash(lang('registration_success'));
            }

            header('Location: login.php');
            exit;

        } catch (\Throwable $e) {

            // Technischen Fehler nur ins Server-Log schreiben
            error_log(
                'Registrierung konnte nicht gespeichert werden: ' .
                $e->getMessage()
            );

            // Benutzerfreundliche Meldung
            $errors[] = lang('registration_save_error');
        }
    }
}

layout_start(lang('register_title'));
?>

<div class="auth-card">

    <h1><?= e(lang('register_title')) ?></h1>

    <?php foreach ($errors as $error): ?>
        <div class="error">
            <?= e($error) ?>
        </div>
    <?php endforeach; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf_token()) ?>"
        >

        <label>
            <?= e(lang('username')) ?>

            <input
                type="text"
                name="username"
                maxlength="30"
                required
                autocomplete="username"
                value="<?= e($_POST['username'] ?? '') ?>"
            >
        </label>

        <label>
            <?= e(lang('password')) ?>

            <input
                type="password"
                name="password"
                minlength="8"
                required
                autocomplete="new-password"
            >
        </label>

        <label>
            <?= e(lang('password_repeat')) ?>

            <input
                type="password"
                name="password2"
                minlength="8"
                required
                autocomplete="new-password"
            >
        </label>

        <button class="button" type="submit">
            <?= e(lang('create_account')) ?>
        </button>

    </form>

</div>

<?php layout_end(); ?>