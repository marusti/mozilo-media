<?php

require_once __DIR__ . '/includes/auth.php';

require_admin();

$categories = categories();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['action'] ?? '');
    $categoryName = trim((string)($_POST['category'] ?? ''));
    $subName = trim((string)($_POST['sub_name'] ?? ''));

    /*
     * Hauptkategorie hinzufügen
     */
    if ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));

        if ($name === '') {
            $error = lang('category_empty');
        } elseif (mb_strlen($name) > 50) {
            $error = lang('category_too_long');
        } elseif (category_exists($categories, $name)) {
            $error = lang('category_exists');
        } else {
            $categories[] = [
                'name' => $name,
                'subcategories' => []
            ];

            save_categories($categories);

            flash(lang('category_added'));
            header('Location: admin_categories.php');
            exit;
        }
    }

    /*
     * Hauptkategorie umbenennen
     */
    elseif ($action === 'rename') {
        $oldName = trim((string)($_POST['old_name'] ?? ''));
        $newName = trim((string)($_POST['new_name'] ?? ''));

        if (!category_exists($categories, $oldName)) {
            $error = lang('category_not_found');
        } elseif ($newName === '') {
            $error = lang('category_empty');
        } elseif (mb_strlen($newName) > 50) {
            $error = lang('category_too_long');
        } elseif ($oldName === $newName) {
            $error = lang('category_rename_same');
        } elseif (category_exists($categories, $newName)) {
            $error = lang('category_exists');
        } else {
            foreach ($categories as &$category) {
                if ($category['name'] === $oldName) {
                    $category['name'] = $newName;
                    break;
                }
            }
            unset($category);

            save_categories($categories);

            /*
             * Kategorie bei vorhandenen Dateien aktualisieren
             */
            $files = files();
            $filesChanged = false;

            foreach ($files as &$file) {
                if (($file['category'] ?? '') === $oldName) {
                    $file['category'] = $newName;
                    $filesChanged = true;
                }
            }
            unset($file);

            if ($filesChanged) {
                save_files($files);
            }

            flash(lang('category_renamed'));
            header('Location: admin_categories.php');
            exit;
        }
    }

    /*
     * Hauptkategorie löschen
     */
    elseif ($action === 'delete') {
        if (!category_exists($categories, $categoryName)) {
            $error = lang('category_not_found');
        } else {
            $used = false;

            foreach (files() as $file) {
                if (($file['category'] ?? '') === $categoryName) {
                    $used = true;
                    break;
                }
            }

            $category = find_category($categories, $categoryName);
            $hasSubcategories = !empty($category['subcategories']);

            if ($used) {
                $error = lang('category_in_use');
            } elseif ($hasSubcategories) {
                $error = 'Kategorie enthält noch Subkategorien.';
            } else {
                $categories = array_values(array_filter(
                    $categories,
                    fn($category) => $category['name'] !== $categoryName
                ));

                save_categories($categories);

                flash(lang('category_deleted'));
                header('Location: admin_categories.php');
                exit;
            }
        }
    }

    /*
     * Subkategorie hinzufügen
     */
    elseif ($action === 'add_sub') {
        $subName = trim((string)($_POST['sub_name'] ?? ''));

        if (!category_exists($categories, $categoryName)) {
            $error = lang('category_not_found');
        } elseif ($subName === '') {
            $error = 'Subkategorie darf nicht leer sein.';
        } elseif (mb_strlen($subName) > 50) {
            $error = 'Subkategorie darf maximal 50 Zeichen lang sein.';
        } elseif (subcategory_exists($categories, $categoryName, $subName)) {
            $error = 'Subkategorie existiert bereits.';
        } else {
            foreach ($categories as &$category) {
                if ($category['name'] === $categoryName) {
                    $category['subcategories'][] = $subName;
                    break;
                }
            }
            unset($category);

            save_categories($categories);

            flash('Subkategorie wurde hinzugefügt.');
            header('Location: admin_categories.php');
            exit;
        }
    }

    /*
     * Subkategorie umbenennen
     */
    elseif ($action === 'rename_sub') {
        $oldName = trim((string)($_POST['old_sub_name'] ?? ''));
        $newName = trim((string)($_POST['new_sub_name'] ?? ''));

        if (!category_exists($categories, $categoryName)) {
            $error = lang('category_not_found');
        } elseif ($newName === '') {
            $error = 'Subkategorie darf nicht leer sein.';
        } elseif (mb_strlen($newName) > 50) {
            $error = 'Subkategorie darf maximal 50 Zeichen lang sein.';
        } elseif (!subcategory_exists($categories, $categoryName, $oldName)) {
            $error = 'Subkategorie wurde nicht gefunden.';
        } elseif ($oldName === $newName) {
            $error = 'Der neue Name ist identisch.';
        } elseif (subcategory_exists($categories, $categoryName, $newName)) {
            $error = 'Subkategorie existiert bereits.';
        } else {
            foreach ($categories as &$category) {
                if ($category['name'] !== $categoryName) {
                    continue;
                }

                foreach ($category['subcategories'] as &$sub) {
                    if ($sub === $oldName) {
                        $sub = $newName;
                        break;
                    }
                }
                unset($sub);

                break;
            }
            unset($category);

            save_categories($categories);

            flash('Subkategorie wurde umbenannt.');
            header('Location: admin_categories.php');
            exit;
        }
    }

    /*
     * Subkategorie löschen
     */
    elseif ($action === 'delete_sub') {
        if (!category_exists($categories, $categoryName)) {
            $error = lang('category_not_found');
        } elseif (!subcategory_exists($categories, $categoryName, $subName)) {
            $error = 'Subkategorie wurde nicht gefunden.';
        } else {
            foreach ($categories as &$category) {
                if ($category['name'] === $categoryName) {
                    $category['subcategories'] = array_values(array_filter(
                        $category['subcategories'],
                        fn($sub) => $sub !== $subName
                    ));
                    break;
                }
            }
            unset($category);

            save_categories($categories);

            flash('Subkategorie wurde gelöscht.');
            header('Location: admin_categories.php');
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Hilfsfunktionen
|--------------------------------------------------------------------------
*/

function category_exists(array $categories, string $name): bool
{
    foreach ($categories as $category) {
        if (($category['name'] ?? '') === $name) {
            return true;
        }
    }

    return false;
}


function find_category(array $categories, string $name): ?array
{
    foreach ($categories as $category) {
        if (($category['name'] ?? '') === $name) {
            return $category;
        }
    }

    return null;
}


function subcategory_exists(
    array $categories,
    string $categoryName,
    string $subName
): bool {
    $category = find_category($categories, $categoryName);

    if (!$category) {
        return false;
    }

    return in_array(
        $subName,
        (array)($category['subcategories'] ?? []),
        true
    );
}


layout_start(lang('category_management'));

?>

<div class="hero">
    <h1><?= e(lang('category_management')) ?></h1>
    <p><?= e(lang('category_management_description')) ?></p>
</div>

<?php if ($error): ?>
    <div class="error"><?= e($error) ?></div>
<?php endif; ?>


<!-- Hauptkategorie hinzufügen -->

<div class="card">
    <h2><?= e(lang('add_category')) ?></h2>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">

        <label>
            <?= e(lang('category_name')) ?>
            <input type="text" name="name" maxlength="50" required>
        </label>

        <button class="button" type="submit">
            <?= e(lang('add_category')) ?>
        </button>
    </form>
</div>


<!-- Kategorien -->

<div class="card">
    <h2><?= e(lang('categories')) ?></h2>

    <?php if (!$categories): ?>

        <p><?= e(lang('no_categories')) ?></p>

    <?php else: ?>

        <div class="file-list">

            <?php foreach ($categories as $category): ?>

                <?php
                $categoryName = $category['name'] ?? '';
                $subcategories = (array)($category['subcategories'] ?? []);
                $categoryId = md5($categoryName);
                ?>

                <div class="category-admin">

                    <div class="file-row">

                        <div>
                            <h3><?= e($categoryName) ?></h3>

                            <div
                                id="rename-<?= e($categoryId) ?>"
                                class="rename-form"
                            >
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="rename">
                                    <input type="hidden" name="old_name" value="<?= e($categoryName) ?>">

                                    <input
                                        type="text"
                                        name="new_name"
                                        value="<?= e($categoryName) ?>"
                                        maxlength="50"
                                        required
                                    >

                                    <button class="button" type="submit">
                                        <?= e(lang('save')) ?>
                                    </button>

                                    <button
                                        class="button secondary"
                                        type="button"
                                        onclick="cancelRename('<?= e($categoryId) ?>')"
                                    >
                                        <?= e(lang('cancel')) ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="actions">

                            <button
                                class="button secondary"
                                type="button"
                                onclick="showRename('<?= e($categoryId) ?>')"
                            >
                                <?= e(lang('rename')) ?>
                            </button>

                            <form
                                method="post"
                                onsubmit="return confirm('<?= e(lang('delete_category_confirm')) ?>')"
                            >
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category" value="<?= e($categoryName) ?>">

                                <button class="button danger" type="submit">
                                    <?= e(lang('delete')) ?>
                                </button>
                            </form>

                        </div>
                    </div>

                    <!-- Subkategorien -->

<div class="subcategories">

    <h4>Subkategorien</h4>

    <?php if ($subcategories): ?>

        <div class="subcategories-list">

            <?php foreach ($subcategories as $sub): ?>

                <?php
                $subId = md5($categoryName . '|' . $sub);
                ?>

                <div class="file-row subcategory-row">

                    <div class="flex-1">
                        <span><?= e($sub) ?></span>

                        <div
                            id="rename-sub-<?= e($subId) ?>"
                            class="rename-form"
                        >
                            <form method="post">

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="rename_sub"
                                >

                                <input
                                    type="hidden"
                                    name="category"
                                    value="<?= e($categoryName) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="old_sub_name"
                                    value="<?= e($sub) ?>"
                                >

                                <input
                                    type="text"
                                    name="new_sub_name"
                                    value="<?= e($sub) ?>"
                                    maxlength="50"
                                    required
                                >

                                <button
                                    class="button"
                                    type="submit"
                                >
                                    <?= e(lang('save')) ?>
                                </button>

                                <button
                                    class="button secondary"
                                    type="button"
                                    onclick="cancelRename('sub-<?= e($subId) ?>')"
                                >
                                    <?= e(lang('cancel')) ?>
                                </button>

                            </form>
                        </div>
                    </div>

                    <div class="actions">

                        <button
                            class="button secondary"
                            type="button"
                            onclick="showRename('sub-<?= e($subId) ?>')"
                        >
                            <?= e(lang('rename')) ?>
                        </button>

                        <form
                            method="post"
                            onsubmit="return confirm('Subkategorie wirklich löschen?')"
                        >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="delete_sub"
                            >

                            <input
                                type="hidden"
                                name="category"
                                value="<?= e($categoryName) ?>"
                            >

                            <input
                                type="hidden"
                                name="sub_name"
                                value="<?= e($sub) ?>"
                            >

                            <button
                                class="button danger"
                                type="submit"
                            >
                                <?= e(lang('delete')) ?>
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="text-muted">
            Keine Subkategorien vorhanden.
        </p>

    <?php endif; ?>


    <!-- Subkategorie hinzufügen -->

    <form
        method="post"
        class="subcategory-add"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="add_sub"
        >

        <input
            type="hidden"
            name="category"
            value="<?= e($categoryName) ?>"
        >

        <input
            type="text"
            name="sub_name"
            maxlength="50"
            placeholder="Neue Subkategorie"
            required
        >

        <button
            class="button"
            type="submit"
        >
            Subkategorie hinzufügen
        </button>

    </form>

</div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>
</div>


<?php layout_end(); ?>