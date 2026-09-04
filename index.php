<?php

require_once __DIR__ . '/includes/auth.php';

$preview = isset($_GET['preview']);

$all = files();

$latestUploads = getLatestUploads($all, 5);
$mostDownloaded = getMostDownloaded($all, 5);
$recentlyUpdated = getRecentlyUpdated($all, 5);

usort(
    $all,
    fn($a, $b) => strcmp(
        $b['uploaded_at'] ?? '',
        $a['uploaded_at'] ?? ''
    )
);

$q = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$cmsVersion = trim((string)($_GET['cms_version'] ?? ''));

$filtered = $all;

if ($q !== '' || $category !== '' || $tag !== '' || $cmsVersion !== '') {

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
            $category !== '' &&
            ($f['category'] ?? '') !== $category
        ) {
            continue;
        }

        if (
            $tag !== '' &&
            !in_array($tag, (array)($f['tags'] ?? []), true)
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
    lang('public_files_title'),
    true,
    $preview,
    [
        'description'=>'Entdecke Plugins, Layouts und Templates für moziloCMS. Lade Erweiterungen der Community herunter oder stelle eigene Projekte bereit.',
        'keywords'=>'moziloCMS, Plugins, Layouts, Templates, Erweiterungen, Downloads'
    ]
);

?>

<section class="hero">
    <h1><?= e(lang('public_files_title')) ?></h1>
    <p>Auf diesen Seiten findest du <strong>Layouts (Templates)</strong> und <strong>Plugins</strong> für moziloCMS. Die Erweiterungen wurden von Mitgliedern der moziloCMS-Community entwickelt und stehen anderen Nutzern zur Verfügung.</p>
    <p>Wenn du Fragen zu einem bestimmten Layout oder Plugin hast, kannst du dich direkt an den jeweiligen Entwickler wenden. Unterstützung und Austausch findest du außerdem in der <strong>moziloCMS-Community und unserem Forum</strong>.</p>
    <h2>Plugins</h2>
    <p>Für <strong>moziloCMS 2.0 und 3.x</strong> stehen verschiedene <a href="plugins.php">Plugins</a> zur Verfügung. Die Plugins können einzeln heruntergeladen und anschließend in moziloCMS installiert werden.</p>
    <p>Für ältere <strong>moziloCMS-2.0-Plugins</strong> gibt es zusätzlich ein historisches Komplettpaket mit 64 Plugins. Die Komplett-Datei enthält alle Plugins, die bis zum 28. Januar 2019 veröffentlicht wurden.</p>
    <p>Nach dem Download kannst du die ZIP-Datei auf deinem PC entpacken und einzelne Plugins für die Installation auswählen. Alternativ können die enthaltenen Plugins auch gemeinsam installiert werden, ohne die ZIP-Datei vorher zu entpacken.</p>
    <p>Eine ausführliche <a href="https://www.mozilo.de/Anleitung/Plugins.html">Anleitung zur Installation von Plugins</a> findest du in unserer Dokumentation.</p>
    <h2>Layouts</h2>
    <p>Für moziloCMS stehen verschiedene <a href="layouts.php">Layouts</a> zur Verfügung, mit denen du das Aussehen deiner Website individuell gestalten kannst. Die einzelnen Layouts können separat heruntergeladen und anschließend in moziloCMS installiert werden.</p>
    <p>Eine <a href="https://www.mozilo.de/Anleitung/Template.html">Anleitung zur Installation von Layouts</a> findest du ebenfalls in unserer Dokumentation.</p>
    <h2>Eigene Plugins und Layouts vorstellen</h2>
    <p>Du hast ein eigenes <strong>Plugin oder Layout für moziloCMS</strong> entwickelt und möchtest es anderen Nutzern zur Verfügung stellen? Dann <a href="login.php">logge dich ein</a> und stelle deine Arbeit hier vor.</p>
    <p>Du hast noch keinen Account? <a href="register.php">Jetzt registrieren</a> und Teil der moziloCMS-Community werden.</p>
    <h2>Hilfe und weitere Informationen</h2>
    <p>Weitere Informationen rund um moziloCMS findest du auf der <a href="https://www.mozilo.de">Projekt-Homepage</a>. Bei Fragen, Problemen oder zum Austausch mit anderen Nutzern steht dir außerdem <a href="https://www.mozilo.de/forum/">unser Forum</a> zur Verfügung.</p>
</section>

<div class="modules-grid">
    <section class="module">
        <h2><?= e(lang('downloads_new')) ?></h2>

        <?php foreach ($latestUploads as $file): ?>

            <article class="file-item">
                <div>
                    <h3><?= e(
                                trim((string)($file['display_name'] ?? '')) !== ''
                                    ? $file['display_name']
                                    : $file['original_name']
                            ) ?></h3>
                    <div class="file-meta">
                        <?= e($file['category'] ?? '') ?>
                        ·
                        <?= e($file['username'] ?? '') ?>
                    </div>
                </div>
                <div class="file-downloads">
                    <?= (int)($file['downloads'] ?? 0) ?> Downloads
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="module">
        <h2><?= e(lang('downloads_updated')) ?></h2>
        <?php foreach ($recentlyUpdated as $file): ?>
            <article class="file-item">
                <div>
                    <h3><?= e(
                                trim((string)($file['display_name'] ?? '')) !== ''
                                    ? $file['display_name']
                                    : $file['original_name']
                            ) ?></h3>
                    <div class="file-meta">
                        <?= e($file['category'] ?? '') ?>
                        ·
                        <?= e($file['username'] ?? '') ?>
                    </div>
                </div>
                <div class="file-downloads">
                    <?= e(date('d.m.Y', strtotime($file['updated_at']))) ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="module">
        <h2><?= e(lang('downloads_most')) ?></h2>
        <?php foreach ($mostDownloaded as $file): ?>
            <article class="file-item">
                <div>
                    <h3><?= e(
                                trim((string)($file['display_name'] ?? '')) !== ''
                                    ? $file['display_name']
                                    : $file['original_name']
                            ) ?></h3>
                    <div class="file-meta">
                        <?= e($file['category'] ?? '') ?>
                        ·
                        <?= e($file['username'] ?? '') ?>
                    </div>
                </div>
                <div class="file-downloads">
                    <?= (int)($file['downloads'] ?? 0) ?> Downloads
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<?php layout_end(); ?>