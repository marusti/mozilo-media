<?php declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';


if (is_installed()) {

    layout_start('Installation');

    ?>

    <div class="auth-card">

        <h1>
            <?= e(lang('install_already_done_title')) ?>
        </h1>

        <p>
            <?= e(lang('install_already_done_text')) ?>
        </p>

        <p>
            <?= e(lang('install_already_done_login')) ?>
        </p>

        <a class="button" href="login.php">
            <?= e(lang('install_go_to_login')) ?>
        </a>

    </div>

    <?php

    layout_end();

    exit;
}


$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();


    /*
     * Falls jemand install.lock gelöscht hat,
     * aber Benutzer vorhanden sind:
     * Installation nicht erneut erlauben.
     */
    if (count(users()) > 0) {

        $errors[] = lang('install_already_done_text');

    }


    $username = trim(
        (string)($_POST['username'] ?? '')
    );


    $password = (string)(
        $_POST['password'] ?? ''
    );


    $password2 = (string)(
        $_POST['password2'] ?? ''
    );


    if (!preg_match(
        '/^[a-zA-Z0-9_.-]{3,30}$/',
        $username
    )) {

        $errors[] = lang('invalid_username');

    }


    if (strlen($password) < 8) {

        $errors[] = lang('password_too_short');

    }


    if ($password !== $password2) {

        $errors[] = lang('password_mismatch');

    }


    if (!$errors) {


        $users = [];


        $users[] = [

            'id' => bin2hex(
                random_bytes(16)
            ),

            'username' => $username,

            'password' => password_hash(
                $password,
                PASSWORD_DEFAULT
            ),

            'role' => 'admin',

            'created_at' => date('c'),
        ];


        try {


            save_users($users);
save_categories([]);

save_files([]);


            /*
             * Schutzdateien erstellen
             */

            file_put_contents(
                DATA_DIR . '/install.lock',
                date('c'),
                LOCK_EX
            );


            file_put_contents(
                DATA_DIR . '/.htaccess',
                "Require all denied\n",
                LOCK_EX
            );


            file_put_contents(
                UPLOAD_DIR . '/.htaccess',
                "Require all denied\n",
                LOCK_EX
            );


            file_put_contents(
                DATA_DIR . '/index.html',
                '',
                LOCK_EX
            );


            file_put_contents(
                UPLOAD_DIR . '/index.html',
                '',
                LOCK_EX
            );



            flash(
                lang('install_success')
            );


            header(
                'Location: login.php'
            );

            exit;


        } catch (Throwable $e) {


            error_log(
                'Installation fehlgeschlagen: ' .
                $e->getMessage()
            );


            $errors[] =
                lang('install_failed');

        }

    }
}



layout_start(
    'Installation',
    false
);

?>

<div class="auth-card">

<h1>
    <?= e(lang('install_title')) ?>
</h1>


<p>
    <?= e(lang('install_create_admin')) ?>
</p>


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



    <button
        class="button"
        type="submit"
    >
        <?= e(lang('install_finish')) ?>
    </button>


</form>

</div>


<?php

layout_end();