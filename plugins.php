<?php

require_once __DIR__ . '/includes/auth.php';

$preview = isset($_GET['preview']);

$all = array_values(
    array_filter(
        files(),
        fn($f) => ($f['category'] ?? '') === 'Plugins'
    )
);

usort(
    $all,
    function ($a, $b) {

        $dateA = !empty($a['updated_at'])
            ? $a['updated_at']
            : ($a['uploaded_at'] ?? '');

        $dateB = !empty($b['updated_at'])
            ? $b['updated_at']
            : ($b['uploaded_at'] ?? '');

        return strcmp($dateB, $dateA);
    }
);

$q = trim((string)($_GET['q'] ?? ''));
$subcategory = trim((string)($_GET['subcategory'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$cmsVersion = trim((string)($_GET['cms_version'] ?? ''));

$pluginSubcategories = getSubcategories('Plugins');

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
            strpos($haystack, strtolower($q)) === false
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

layout_start(
    'Plugins für moziloCMS',
    true,
    $preview,
    [
        'description'=>'Meine Pluginübersicht',
        'keywords'=>'Plugins, Erweiterungen'
    ]
);

?>

<section class="hero">
    <h1>Plugins für moziloCMS</h1>
    <p>Hier findest du <strong>Plugins für moziloCMS 2.0 und 3.x</strong>, mit denen du den Funktionsumfang deiner Website erweitern kannst.</p>
    <p>Die Plugins werden von Mitgliedern der <strong>moziloCMS-Community</strong> entwickelt und anderen Nutzern zur Verfügung gestellt.</p>
    <p>Wenn du Fragen zu einem bestimmten Plugin hast, kannst du dich direkt an den jeweiligen Entwickler wenden. Unterstützung und Austausch findest du außerdem in der <strong>moziloCMS-Community und unserem Forum</strong>.</p>
    <p>Eine ausführliche <a href="https://www.mozilo.de/Anleitung/Plugins.html">Anleitung zur Installation von Plugins</a> findest du in unserer Dokumentation.</p>
    <h2>Komplettpaket der moziloCMS-2.0-Plugins bis 28.01.2019 (ZIP)</h2>
    <p>Dieses Komplettpaket enthält <strong>64 Plugins für moziloCMS 2.0</strong>, die bis zum 28. Januar 2019 veröffentlicht wurden.</p>
    <p>Nach dem Download kannst du die ZIP-Datei auf deinem PC entpacken und einzelne Plugins für die Installation auswählen. Alternativ kannst du die enthaltenen Plugins auch gemeinsam installieren, ohne die ZIP-Datei vorher zu entpacken.</p>
    <div class="info-box">
        <p><strong>Hinweis:</strong> Bei diesem Download handelt es sich um ein historisches Plugin-Paket. Es enthält ausschließlich die bis zum 28. Januar 2019 veröffentlichten Plugins für moziloCMS 2.0.</p>
    	<a class="button" href="uploads/-mozilo2-plugins_2019-01-28.zip">Komplettpaket herunterladen (2,9 MB)</a>
    </div>
</section>

<div class="card search">
 <p><?= e(lang('public_files_description')) ?></p>

    <form method="get">

    <label class="visually-hidden" for="search-input">
        <?= e(lang('search_placeholder')) ?>
    </label>

    <input name="q" id="search-input" placeholder="<?= e(lang('search_placeholder')) ?>" value="<?= e($q) ?>">

    <?php if ($pluginSubcategories): ?>

        <label class="visually-hidden" for="subcategory">
            <?= e(lang('all_subcategories')) ?>
        </label>

        <select name="subcategory" id="subcategory">

            <option value=""><?= e(lang('all_subcategories')) ?></option>

            <?php foreach ($pluginSubcategories as $sub): ?>
                <option value="<?= e($sub) ?>"<?= $subcategory === $sub ? 'selected' : '' ?>><?= e($sub) ?></option>
            <?php endforeach; ?>

        </select>

    <?php endif; ?>

    <label class="visually-hidden" for="cms-version">
        <?= e(lang('all_cms_versions')) ?>
    </label>

    <select name="cms_version" id="cms-version">

        <option value=""><?= e(lang('all_cms_versions')) ?></option>

        <?php foreach (CMS_VERSIONS as $version): ?>
            <option value="<?= e($version) ?>"<?= $cmsVersion === $version ? 'selected' : '' ?>><?= e($version) ?></option>
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
                                trim((string)($f['display_name'] ?? '')) !== ''
                                    ? $f['display_name']
                                    : $f['original_name']
                            ) ?>
                        </strong>
                        
                        <?php if (trim((string)($f['version'] ?? '')) !== ''): ?>

    <span class="file-version">
        v<?= e($f['version']) ?>
    </span>

<?php endif; ?>

                        <?php if (trim((string)($f['description'] ?? '')) !== ''): ?>

                            <div class="file-description">
                                <?= nl2br(e($f['description'])) ?>
                            </div>

                        <?php endif; ?>


                        <small>

    <?= e($f['username']) ?>

    <?php if (!empty($f['subcategory'])): ?>

        · <?= e($f['subcategory']) ?>

    <?php endif; ?>

    <?php if (!empty($f['cms_versions'])): ?>

        · <?= e(lang('cms_versions')) ?>
        <?= e(implode(', ', (array)$f['cms_versions'])) ?>

    <?php endif; ?>

    · <?= e(format_bytes((int)$f['size'])) ?>

    · <?= e((string)($f['downloads'] ?? 0)) ?>
    <?= e(lang('downloads')) ?>

    <?php
    $displayDate = !empty($f['updated_at'])
        ? $f['updated_at']
        : ($f['uploaded_at'] ?? null);
    ?>

    <?php if ($displayDate): ?>

        · <?= e(lang('last_updated')) ?>
        <?= e(date('d.m.Y', strtotime($displayDate))) ?>

    <?php endif; ?>

</small>
                        <?php if (!empty($f['tags'])): ?>

                            <div class="tags">

                                <?php foreach ((array)$f['tags'] as $t): ?>

                                    <a href="?tag=<?= urlencode($t) ?>">
                                        #<?= e($t) ?>
                                    </a>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>
                    <div class="file-actions">

    <?php if (!empty($f['demo_url'])): ?>

        <a
            class="button secondary"
            href="<?= e($f['demo_url']) ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?= e(lang('demo')) ?>
        </a>

    <?php endif; ?>

    <a class="button" href="download.php?id=<?= e($f['id']) ?>">
        <?= e(
            trim((string)($f['display_name'] ?? '')) !== ''
                ? $f['display_name']
                : $f['original_name']
        ) ?>
        <?= e(lang('download')) ?>
        (<?= e(format_bytes((int)$f['size'])) ?>)
    </a>

</div>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
<?php layout_end(); ?>