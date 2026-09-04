<?php

require_once __DIR__ . '/includes/auth.php';

$all = array_values(
    array_filter(
        files(),
        fn($f) => ($f['category'] ?? '') === 'Layouts'
    )
);

usort(
    $all,
    fn($a, $b) => strcmp(
        $b['uploaded_at'] ?? '',
        $a['uploaded_at'] ?? ''
    )
);

$q = trim((string)($_GET['q'] ?? ''));
$subcategory = trim((string)($_GET['subcategory'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$cmsVersion = trim((string)($_GET['cms_version'] ?? ''));

$subcategories = getSubcategories('Layouts');

$filtered = $all;

if (
    $q !== '' ||
    $subcategory !== '' ||
    $tag !== '' ||
    $cmsVersion !== ''
) {

    $filtered = [];

    foreach ($all as $f) {

        $haystack = strtolower(
            ($f['original_name'] ?? '') . ' ' .
            ($f['display_name'] ?? '') . ' ' .
            ($f['description'] ?? '') . ' ' .
            ($f['username'] ?? '') . ' ' .
            implode(' ', (array)($f['tags'] ?? []))
        );

        if (
            $q !== '' &&
            strpos(
                $haystack,
                strtolower($q)
            ) === false
        ) {
            continue;
        }

        if (
            $subcategory !== '' &&
            ($f['subcategory'] ?? '') !== $subcategory
        ) {
            continue;
        }

        if (
            $tag !== '' &&
            !in_array(
                $tag,
                (array)($f['tags'] ?? []),
                true
            )
        ) {
            continue;
        }

        if (
            $cmsVersion !== '' &&
            !in_array(
                $cmsVersion,
                (array)($f['cms_versions'] ?? []),
                true
            )
        ) {
            continue;
        }

        $filtered[] = $f;
    }
}

$preview = isset($_GET['preview']);

layout_start(
    'Layouts für moziloCMS',
    true,
    $preview,
    [
        'description'=>'Kostenlose Layouts und Templates für moziloCMS. Entdecke verschiedene Designs für deine Website.',
        'keywords'=>'moziloCMS, Layouts, Templates, Designs, Themes'
    ]
);

?>

<section class="hero">

    <h1>Layouts für moziloCMS</h1>

    <p>
        Hier findest du verschiedene
        <strong>Layouts (Templates) für moziloCMS</strong>,
        mit denen du das Aussehen deiner Website individuell gestalten kannst.
    </p>

    <p>
        Neben dem bereits mitgelieferten Standard-Layout stehen dir hier
        weitere Layouts zur Verfügung, die von Mitgliedern der
        <strong>moziloCMS-Community</strong> erstellt wurden und kostenlos
        heruntergeladen werden können.
    </p>

    <p>
        Wenn du Fragen zu einem bestimmten Layout hast, kannst du dich direkt
        an den jeweiligen Entwickler wenden. Unterstützung und Austausch
        findest du außerdem in der
        <strong>moziloCMS-Community und unserem Forum</strong>.
    </p>

    <p>
        Eine ausführliche
        <a href="https://www.mozilo.de/Anleitung/Template.html">
            Anleitung zur Installation von Layouts
        </a>
        findest du in unserer Dokumentation.
    </p>

    

</section>


<div class="card search">
<p><?= e(lang('public_files_description')) ?></p>

    <form method="get">

    <label class="visually-hidden" for="search-input">
        <?= e(lang('search_placeholder')) ?>
    </label>

    <input name="q" id="search-input" placeholder="<?= e(lang('search_placeholder')) ?>" value="<?= e($q) ?>">

    <?php if ($subcategories): ?>

        <label class="visually-hidden" for="subcategory">
            <?= e(lang('all_subcategories')) ?>
        </label>

        <select name="subcategory" id="subcategory">

            <option value="">
                <?= e(lang('all_subcategories')) ?>
            </option>

            <?php foreach ($subcategories as $sub): ?>

                <option
                    value="<?= e($sub) ?>"
                    <?= $subcategory === $sub ? 'selected' : '' ?>
                >
                    <?= e($sub) ?>
                </option>

            <?php endforeach; ?>

        </select>

    <?php endif; ?>


    <label class="visually-hidden" for="cms-version">
        <?= e(lang('all_cms_versions')) ?>
    </label>

    <select name="cms_version" id="cms-version">

        <option value="">
            <?= e(lang('all_cms_versions')) ?>
        </option>

        <?php foreach (CMS_VERSIONS as $version): ?>

            <option
                value="<?= e($version) ?>"
                <?= $cmsVersion === $version ? 'selected' : '' ?>
            >
                <?= e($version) ?>
            </option>

        <?php endforeach; ?>

    </select>

</form>

</div>


<div class="card" id="search-results">

    <?php if (!$all): ?>

        <p><?= e(lang('no_files')) ?></p>

    <?php elseif (!$filtered): ?>

        <p><?= e(lang('no_files_found')) ?></p>

    <?php else: ?>

        <div class="file-list">

            <?php foreach ($filtered as $f): ?>

                <div class="file-row">

                    <div>

                        <strong>
                            <?= e(
                                trim(
                                    (string)($f['display_name'] ?? '')
                                ) !== ''
                                    ? $f['display_name']
                                    : $f['original_name']
                            ) ?>
                        </strong>


                        <?php if (
                            trim(
                                (string)($f['description'] ?? '')
                            ) !== ''
                        ): ?>

                            <div class="file-description">
                                <?= nl2br(
                                    e($f['description'])
                                ) ?>
                            </div>

                        <?php endif; ?>


                        <small>

                            <?= e($f['username']) ?>

                            <?php if (
                                !empty($f['subcategory'])
                            ): ?>

                                · <?= e($f['subcategory']) ?>

                            <?php endif; ?>


                            <?php if (
                                !empty($f['cms_versions'])
                            ): ?>

                                · <?= e(
                                    lang('cms_versions')
                                ) ?>

                                <?= e(
                                    implode(
                                        ', ',
                                        (array)$f['cms_versions']
                                    )
                                ) ?>

                            <?php endif; ?>


                            · <?= e(
                                format_bytes(
                                    (int)$f['size']
                                )
                            ) ?>


                            · <?= e(
                                (string)(
                                    $f['downloads'] ?? 0
                                )
                            ) ?>

                            <?= e(lang('downloads')) ?>

                            · <?= e(
                                date(
                                    'd.m.Y',
                                    strtotime(
                                        $f['uploaded_at']
                                    )
                                )
                            ) ?>

                        </small>


                        <?php if (!empty($f['tags'])): ?>

                            <div class="tags">

                                <?php foreach (
                                    (array)$f['tags'] as $t
                                ): ?>

                                    <a
                                        href="?tag=<?= urlencode($t) ?>"
                                    >
                                        #<?= e($t) ?>
                                    </a>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <a
                        class="button"
                        href="download.php?id=<?= e($f['id']) ?>"
                    >
                        <?= e(lang('download')) ?>
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>


<?php layout_end(); ?>