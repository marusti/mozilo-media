<?php

require_once __DIR__ . '/includes/auth.php';

$u = require_login();

$error = null;
$categories = categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $displayName = trim((string)($_POST['display_name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $version = trim((string)($_POST['version'] ?? ''));
    $demoUrl = trim((string)($_POST['demo_url'] ?? ''));
    $cat = trim((string)($_POST['category'] ?? ''));
    $subcategory = trim((string)($_POST['subcategory'] ?? ''));

    $tags = tags_from_input(
        (string)($_POST['tags'] ?? '')
    );

    $cmsVersions = $_POST['cms_versions'] ?? [];

    if (!is_array($cmsVersions)) {
        $cmsVersions = [];
    }

    $cmsVersions = array_values(
        array_intersect(
            CMS_VERSIONS,
            array_map('strval', $cmsVersions)
        )
    );

    $categoryExists = categoryExists($cat);

    $validSubcategory = (
        $subcategory === '' ||
        subcategoryExists($cat, $subcategory)
    );

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
                    (string)(parse_url(
                        $demoUrl,
                        PHP_URL_SCHEME
                    ) ?? '')
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

    } elseif (
        !isset($_FILES['zip']) ||
        $_FILES['zip']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = lang('upload_failed');

    } elseif (
        $_FILES['zip']['size'] > MAX_UPLOAD_BYTES
    ) {

        $error = sprintf(
            lang('max_upload_size'),
            max_upload_size_text()
        );

    } else {

        $orig = basename(
            (string)$_FILES['zip']['name']
        );

        $tmp = $_FILES['zip']['tmp_name'];

        $mime = (new finfo(FILEINFO_MIME_TYPE))
            ->file($tmp);

        if (
            strtolower(
                pathinfo($orig, PATHINFO_EXTENSION)
            ) !== 'zip' ||
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

            $id = bin2hex(
                random_bytes(16)
            );

            $stored = $id . '.zip';

            if (
                move_uploaded_file(
                    $tmp,
                    UPLOAD_DIR . '/' . $stored
                )
            ) {

                $fs = files();

                $fs[] = [
                    'id' => $id,
                    'user_id' => $u['id'],
                    'username' => $u['username'],

                    'original_name' => $orig,
                    'display_name' => $displayName,
                    'description' => $description,
                    'version' => $version,
                    'demo_url' => $demoUrl,

                    'stored_name' => $stored,
                    'size' => (int)$_FILES['zip']['size'],

                    'category' => $cat,
                    'subcategory' => $subcategory,

                    'tags' => $tags,
                    'cms_versions' => $cmsVersions,

                    'downloads' => 0,
                    'last_download' => null,

                    'uploaded_at' => date('c'),
                    'updated_at' => null
                ];

                save_files($fs);

                flash(
                    lang('upload_success')
                );

                header(
                    'Location: dashboard.php'
                );

                exit;

            } else {

                $error = lang('file_save_error');
            }
        }
    }
}

$selectedCmsVersions = $cmsVersions ?? [];

$selectedCategory = trim(
    (string)($_POST['category'] ?? '')
);

$selectedSubcategory = trim(
    (string)($_POST['subcategory'] ?? '')
);

layout_start(
    lang('upload_title')
);

?>

<div class="auth-card">

<h1><?= e(lang('upload_title')) ?></h1>

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


    <label>

        <?= e(lang('zip_file')) ?>

        <input
            type="file"
            name="zip"
            accept=".zip,application/zip"
            required
        >

    </label>


    <label>

        <?= e(lang('display_name')) ?>

        <small>
            <?= e(lang('display_name_hint')) ?>
        </small>

        <input
            type="text"
            name="display_name"
            maxlength="100"
            value="<?= e($_POST['display_name'] ?? '') ?>"
        >

    </label>


    <label>

        <?= e(lang('description')) ?>

        <small>
            <?= e(lang('description_hint')) ?>
        </small>

        <textarea
            name="description"
            maxlength="1000"
            rows="4"
        ><?= e($_POST['description'] ?? '') ?></textarea>

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
        value="<?= e($_POST['version'] ?? '') ?>"
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
        value="<?= e($_POST['demo_url'] ?? '') ?>"
    >

</label>


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

            <?php foreach ($categories as $category): ?>

                <option
                    value="<?= e($category['name']) ?>"
                    <?= $selectedCategory === $category['name']
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= e($category['name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </label>


    <label
        id="subcategory-wrapper"
        <?= $selectedCategory === ''
            ? 'style="display:none;"'
            : ''
        ?>
    >

        <?= e(lang('subcategory')) ?>

        <select            name="subcategory"            id="subcategory" required>

            <option value="">
                <?= e(lang('no_subcategory')) ?>
            </option>

            <?php foreach ($categories as $category): ?>

                <?php if ($category['name'] !== $selectedCategory): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <?php foreach ($category['subcategories'] as $sub): ?>

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


    <fieldset>

    <legend>
        <?= e(lang('cms_versions')) ?>
    </legend>

    <div class="checkbox-group">

        <?php foreach (CMS_VERSIONS as $version): ?>

            <label class="checkbox-label">

                <input
                    type="checkbox"
                    name="cms_versions[]"
                    value="<?= e($version) ?>"
                    <?= in_array(
                        $version,
                        $selectedCmsVersions,
                        true
                    ) ? 'checked' : '' ?>
                >

                <?= e($version) ?>

            </label>

        <?php endforeach; ?>

    </div>

</fieldset>


    <label>

        <?= e(lang('tags')) ?>

        <small>
            <?= e(lang('tags_hint')) ?>
        </small>

        <input
            name="tags"
            maxlength="300"
            placeholder="<?= e(lang('tags_placeholder')) ?>"
            value="<?= e($_POST['tags'] ?? '') ?>"
        >

    </label>


    <div class="actions">

    <button class="button" type="submit">
        <?= e(lang('upload_button')) ?>
    </button>

    <a class="button secondary" href="dashboard.php">
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

function updateSubcategories() {

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

    subcategories.forEach(function (subcategory) {

        const option =
            document.createElement('option');

        option.value = subcategory;
        option.textContent = subcategory;

        subcategorySelect.appendChild(
            option
        );
    });

    subcategoryWrapper.style.display =
        selected !== '' && subcategories.length > 0
            ? 'grid'
            : 'none';
}

categorySelect.addEventListener(
    'change',
    function () {

        subcategorySelect.value = '';

        updateSubcategories();
    }
);

updateSubcategories();
</script>

<?php layout_end(); ?>