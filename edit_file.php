<?php

require_once __DIR__ . '/includes/auth.php';

$u = require_login();

$id = trim((string)($_GET['id'] ?? ''));

$allFiles = files();
$file = null;

foreach ($allFiles as $f) {

    if (
        ($f['id'] ?? '') === $id &&
        ($f['user_id'] ?? '') === $u['id']
    ) {
        $file = $f;
        break;
    }
}

if (!$file) {
    http_response_code(404);
    exit(lang('file_not_found'));
}

$error = null;
$categories = categories();

$availableVersions = CMS_VERSIONS;

$currentCmsVersions = array_values(
    array_intersect(
        $availableVersions,
        (array)($file['cms_versions'] ?? [])
    )
);

$selectedCategory = trim(
    (string)($file['category'] ?? '')
);

$selectedSubcategory = trim(
    (string)($file['subcategory'] ?? '')
);


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $displayName = trim(
        (string)($_POST['display_name'] ?? '')
    );

    $description = trim(
        (string)($_POST['description'] ?? '')
    );

    $category = trim(
        (string)($_POST['category'] ?? '')
    );

    $subcategory = trim(
        (string)($_POST['subcategory'] ?? '')
    );
    
$version = trim(
    (string)($_POST['version'] ?? '')
);

$demoUrl = trim(
    (string)($_POST['demo_url'] ?? '')
);

    $tagsInput = (string)($_POST['tags'] ?? '');

    $cmsVersions = $_POST['cms_versions'] ?? [];

    if (!is_array($cmsVersions)) {
        $cmsVersions = [];
    }

    $cmsVersions = array_values(
        array_intersect(
            $availableVersions,
            array_map('strval', $cmsVersions)
        )
    );

    $tags = tags_from_input($tagsInput);

    $categoryExists = categoryExists($category);

    $validSubcategory = (
        $subcategory === '' ||
        subcategoryExists($category, $subcategory)
    );


    /*
    |--------------------------------------------------------------------------
    | Validierung
    |--------------------------------------------------------------------------
    */

    if (mb_strlen($displayName) > 100) {

        $error = lang('display_name_too_long');

    } elseif (mb_strlen($description) > 1000) {

        $error = lang('description_too_long');

    } elseif (mb_strlen($version) > 50) {

    $error = 'Version darf maximal 50 Zeichen lang sein.';

} elseif (
    $demoUrl !== '' &&
    (
        !filter_var($demoUrl, FILTER_VALIDATE_URL) ||
        !in_array(
            strtolower(
                (string)(
                    parse_url(
                        $demoUrl,
                        PHP_URL_SCHEME
                    ) ?? ''
                )
            ),
            ['http', 'https'],
            true
        )
    )
) {

    $error = 'Ungültige Demo-URL.';

} elseif (!$categoryExists) {

        $error = lang('invalid_category');

    } elseif (!$validSubcategory) {

        $error = lang('invalid_subcategory');

    } elseif (!$cmsVersions) {

        $error = lang('cms_version_required');

    }


    /*
    |--------------------------------------------------------------------------
    | Neue ZIP-Datei prüfen
    |--------------------------------------------------------------------------
    */

    $replaceZip = (
        isset($_FILES['zip']) &&
        ($_FILES['zip']['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_NO_FILE
    );

    $newStored = null;
    $oldStored = null;

    if (!$error && $replaceZip) {

        if (
            $_FILES['zip']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = lang('upload_failed');

        } elseif (
            $_FILES['zip']['size'] > MAX_UPLOAD_BYTES
        ) {

            $error = lang('max_upload_size');

        } else {

            $orig = basename(
                (string)$_FILES['zip']['name']
            );

            $tmp = $_FILES['zip']['tmp_name'];

            $mime = (new finfo(FILEINFO_MIME_TYPE))
                ->file($tmp);

            $extension = strtolower(
                pathinfo($orig, PATHINFO_EXTENSION)
            );

            if (
                $extension !== 'zip' ||
                !in_array(
                    $mime,
                    [
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream'
                    ],
                    true
                )
            ) {

                $error = lang('zip_only');

            } else {

                /*
                 * Neue Datei bekommt zunächst einen eigenen Namen.
                 * Dadurch bleibt die alte ZIP erhalten, solange
                 * der Update-Vorgang noch nicht erfolgreich beendet ist.
                 */

                $newStored =
                    $id . '-' .
                    bin2hex(random_bytes(8)) .
                    '.zip';

                $newPath =
                    UPLOAD_DIR . '/' . $newStored;

                if (!move_uploaded_file($tmp, $newPath)) {

                    $error = lang('file_save_error');

                    $newStored = null;
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Daten speichern
    |--------------------------------------------------------------------------
    */

    if (!$error) {

        foreach ($allFiles as &$f) {

            if (
                ($f['id'] ?? '') === $id &&
                ($f['user_id'] ?? '') === $u['id']
            ) {

                /*
                 * Alte Datei merken, damit wir sie nach
                 * erfolgreichem Speichern löschen können.
                 */

                if ($newStored !== null) {

                    $oldStored =
                        $f['stored_name'] ?? null;

                    $f['stored_name'] =
                        $newStored;

                    $f['original_name'] =
                        $orig;

                    $f['size'] =
                        (int)$_FILES['zip']['size'];
                }

                $f['display_name'] =
                    $displayName;

                $f['description'] =
                    $description;
                    
                    $f['version'] =
    $version;

$f['demo_url'] =
    $demoUrl;

                $f['category'] =
                    $category;

                $f['subcategory'] =
                    $subcategory;

                $f['tags'] =
                    $tags;

                $f['cms_versions'] =
                    $cmsVersions;

                $f['updated_at'] =
                    date('c');

                break;
            }
        }

        unset($f);


        /*
         * Daten speichern
         */

        save_files($allFiles);


        /*
         * Alte ZIP erst nach erfolgreichem Speichern löschen.
         */

        if (
            $newStored !== null &&
            $oldStored
        ) {

            $oldPath =
                UPLOAD_DIR . '/' .
                basename($oldStored);

            if (
                is_file($oldPath) &&
                $oldStored !== $newStored
            ) {
                unlink($oldPath);
            }
        }


        flash(lang('file_updated'));

        header(
            'Location: dashboard.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Formularwerte nach Fehler wiederherstellen
    |--------------------------------------------------------------------------
    */

    $selectedCategory =
        $category;

    $selectedSubcategory =
        $subcategory;

    $currentCmsVersions =
        $cmsVersions;
}


/*
|--------------------------------------------------------------------------
| Aktuelle Tags
|--------------------------------------------------------------------------
*/

$currentTags =
    (array)($file['tags'] ?? []);

$currentTagsInput =
    implode(', ', $currentTags);


/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

layout_start(
    lang('edit_file')
);

?>

<div class="auth-card">

    <h1><?= e(lang('edit_file')) ?></h1>

    <p>
        <?= e($file['original_name'] ?? '') ?>
    </p>


    <?php if ($error): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <form
        method="post"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf_token()) ?>"
        >


        <!--
        ================================================================
        ZIP-Datei
        ================================================================
        -->

        <label>

            <?= e(lang('zip_file')) ?>

            <small>
                <?= e(lang('upload_new_file_hint')) ?>
            </small>

            <input
                type="file"
                name="zip"
                accept=".zip,application/zip"
            >

        </label>


        <!--
        ================================================================
        Display Name
        ================================================================
        -->

        <label>

            <?= e(lang('display_name')) ?>

            <small>
                <?= e(lang('display_name_hint')) ?>
            </small>

            <input
                type="text"
                name="display_name"
                maxlength="100"
                value="<?= e(
                    (string)(
                        $_POST['display_name']
                        ?? $file['display_name']
                        ?? ''
                    )
                ) ?>"
            >

        </label>


        <!--
        ================================================================
        Beschreibung
        ================================================================
        -->

        <label>

            <?= e(lang('description')) ?>

            <small>
                <?= e(lang('description_hint')) ?>
            </small>

            <textarea
                name="description"
                maxlength="1000"
                rows="7"
            ><?= e(
                (string)(
                    $_POST['description']
                    ?? $file['description']
                    ?? ''
                )
            ) ?></textarea>

        </label>
        
        <label>

    <?= e(lang('version')) ?>

    <small>
        <?= e(lang('version_hint')) ?>
    </small>

    <input
        type="text"
        name="version"
        maxlength="50"
        placeholder="z.B. 1.0.0"
        value="<?= e(
            (string)(
                $_POST['version']
                ?? $file['version']
                ?? ''
            )
        ) ?>"
    >

</label>

<label>

    <?= e(lang('demo_url')) ?>

    <small>
        <?= e(lang('demo_url_hint')) ?>
    </small>

    <input
        type="url"
        name="demo_url"
        maxlength="255"
        placeholder="https://example.com/demo"
        value="<?= e(
            (string)(
                $_POST['demo_url']
                ?? $file['demo_url']
                ?? ''
            )
        ) ?>"
    >

</label>


        <!--
        ================================================================
        Kategorie
        ================================================================
        -->

        <label>

            <?= e(lang('category')) ?>

            <select
                name="category"
                id="category"
                required
            >

                <option value="">
                    <?= e(lang('please_choose')) ?>
                </option>

                <?php foreach ($categories as $categoryItem): ?>

                    <option
                        value="<?= e($categoryItem['name']) ?>"
                        <?= $selectedCategory === $categoryItem['name']
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= e($categoryItem['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </label>


        <!--
        ================================================================
        Unterkategorie
        ================================================================
        -->

        <label
            id="subcategory-wrapper"
            <?= $selectedCategory === ''
                ? 'style="display:none;"'
                : ''
            ?>
        >

            <?= e(lang('subcategory')) ?>

            <select
                name="subcategory"
                id="subcategory"
                required
            >

                <option value="">
                    <?= e(lang('no_subcategory')) ?>
                </option>

                <?php foreach ($categories as $categoryItem): ?>

                    <?php if (
                        $categoryItem['name']
                        !== $selectedCategory
                    ): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php foreach (
                        $categoryItem['subcategories']
                        as $sub
                    ): ?>

                        <option
                            value="<?= e($sub) ?>"
                            <?= $selectedSubcategory === $sub
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= e($sub) ?>
                        </option>

                    <?php endforeach; ?>

                <?php endforeach; ?>

            </select>

        </label>


        <!--
        ================================================================
        CMS-Versionen
        ================================================================
        -->

        <label>

            <?= e(lang('cms_versions')) ?>

        </label>

        <div class="checkbox-group">

            <?php foreach (
                $availableVersions as $version
            ): ?>

                <label class="checkbox-label">

                    <input
                        type="checkbox"
                        name="cms_versions[]"
                        value="<?= e($version) ?>"
                        <?= in_array(
                            $version,
                            $currentCmsVersions,
                            true
                        )
                            ? 'checked'
                            : ''
                        ?>
                    >

                    <span>
                        <?= e($version) ?>
                    </span>

                </label>

            <?php endforeach; ?>

        </div>


        <!--
        ================================================================
        Tags
        ================================================================
        -->

        <label>

            <?= e(lang('tags')) ?>

            <small>
                <?= e(lang('tags_hint')) ?>
            </small>

            <input
                type="text"
                name="tags"
                maxlength="300"
                placeholder="<?= e(
                    lang('tags_placeholder')
                ) ?>"
                value="<?= e(
                    (string)(
                        $_POST['tags']
                        ?? $currentTagsInput
                    )
                ) ?>"
            >

        </label>


        <!--
        ================================================================
        Buttons
        ================================================================
        -->

        <div class="actions">

            <button
                class="button"
                type="submit"
            >
                <?= e(lang('save')) ?>
            </button>

            <a
                class="button secondary"
                href="dashboard.php"
            >
                <?= e(lang('cancel')) ?>
            </a>

        </div>

    </form>

</div>


<script>

const categories = <?= json_encode(
    $categories,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


const categorySelect =
    document.getElementById('category');


const subcategorySelect =
    document.getElementById('subcategory');


const subcategoryWrapper =
    document.getElementById('subcategory-wrapper');


const initialSubcategory =
    <?= json_encode(
        $selectedSubcategory,
        JSON_UNESCAPED_UNICODE
    ) ?>;


function updateSubcategories(
    selectedSubcategory = ''
) {

    const selected =
        categorySelect.value;

    const category =
        categories.find(
            item => item.name === selected
        );

    const subcategories =
        category?.subcategories ?? [];


    subcategorySelect.innerHTML = '';


    const emptyOption =
        document.createElement('option');

    emptyOption.value = '';

    emptyOption.textContent =
        '<?= e(lang('no_subcategory')) ?>';

    subcategorySelect.appendChild(
        emptyOption
    );


    subcategories.forEach(
        function (subcategory) {

            const option =
                document.createElement('option');

            option.value =
                subcategory;

            option.textContent =
                subcategory;

            if (
                subcategory ===
                selectedSubcategory
            ) {
                option.selected = true;
            }

            subcategorySelect.appendChild(
                option
            );
        }
    );


    subcategoryWrapper.style.display =
        selected !== '' &&
        subcategories.length > 0
            ? 'grid'
            : 'none';
}


categorySelect.addEventListener(
    'change',
    function () {

        updateSubcategories('');

    }
);


updateSubcategories(
    initialSubcategory
);

</script>


<?php layout_end(); ?>