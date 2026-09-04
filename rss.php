<?php

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$all = files();

foreach ($all as &$file) {
    $uploadedTimestamp = !empty($file['uploaded_at'])
        ? strtotime($file['uploaded_at'])
        : 0;

    $updatedTimestamp = !empty($file['updated_at'])
        ? strtotime($file['updated_at'])
        : 0;

    $file['_feed_timestamp'] = max(
        $uploadedTimestamp,
        $updatedTimestamp
    );
}

unset($file);

usort(
    $all,
    fn($a, $b) =>
        ($b['_feed_timestamp'] ?? 0) <=> ($a['_feed_timestamp'] ?? 0)
);

$all = array_slice($all, 0, 5);


function rssEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_XML1 | ENT_COMPAT,
        'UTF-8'
    );
}


function fileTitle(array $file): string
{
    $displayName = trim((string)($file['display_name'] ?? ''));

    return $displayName !== ''
        ? $displayName
        : (string)($file['original_name'] ?? 'Download');
}


function fileDescription(array $file): string
{
    $description = trim((string)($file['description'] ?? ''));
    $category = trim((string)($file['category'] ?? ''));
    $username = trim((string)($file['username'] ?? ''));

    $parts = [];

    if ($description !== '') {
        $parts[] = $description;
    }

    if ($category !== '') {
        $parts[] = 'Kategorie: ' . $category;
    }

    if ($username !== '') {
        $parts[] = 'Entwickler: ' . $username;
    }

    return implode(' · ', $parts);
}


function rssDate(array $file): string
{
    $timestamp = $file['_feed_timestamp'] ?? 0;

    if ($timestamp <= 0) {
        $timestamp = time();
    }

    return date(DATE_RSS, $timestamp);
}


function fileUrl(array $file): string
{
    $category = strtolower(
        trim((string)($file['category'] ?? ''))
    );

    if ($category === 'plugins' || $category === 'plugin') {
        return 'https://www.mozilo.de/media/plugins.php';
    }

    if ($category === 'layouts' || $category === 'layout') {
        return 'https://www.mozilo.de/media/layouts.php';
    }

    return 'https://www.mozilo.de/media';
}


function changeType(array $file): string
{
    $uploadedTimestamp = !empty($file['uploaded_at'])
        ? strtotime($file['uploaded_at'])
        : 0;

    $updatedTimestamp = !empty($file['updated_at'])
        ? strtotime($file['updated_at'])
        : 0;

    return (
        $updatedTimestamp > 0 &&
        $updatedTimestamp > $uploadedTimestamp
    )
        ? 'Aktualisiert'
        : 'Neu';
}


$siteUrl = 'https://www.mozilo.de/media';

echo '<?xml version="1.0" encoding="UTF-8"?>';

?> <rss version="2.0"> <channel>

<title><?= rssEscape('moziloCMS Downloads') ?></title>
<link><?= rssEscape($siteUrl) ?></link>
<description><?= rssEscape('Neue und aktualisierte Plugins, Layouts und Templates für moziloCMS.') ?></description>
<language>de-de</language>
<ttl>60</ttl>
<?php foreach ($all as $file):
    $title = fileTitle($file);
    $description = fileDescription($file);
    $url = fileUrl($file);
    $type = changeType($file);

if (isset($file['id'])) {
    $guid = $siteUrl . 'rss.php#file-' . (int)$file['id'];
} else {
    $guid = $url;
}

?> <item>

<title><?= rssEscape($type . ': ' . $title) ?></title>
<link><?= rssEscape($url) ?></link>
<guid isPermaLink="false"><?= rssEscape($guid) ?></guid>
<description><?= rssEscape($description) ?></description>
<pubDate><?= rssDate($file) ?></pubDate>
<?php if (!empty($file['username'])): ?>
<author><?= rssEscape((string)$file['username']) ?></author>
<?php endif; ?>
<?php if (!empty($file['category'])): ?>
<category><?= rssEscape((string)$file['category']) ?></category>
<?php endif; ?>
</item>
<?php endforeach; ?>
</channel>
</rss>