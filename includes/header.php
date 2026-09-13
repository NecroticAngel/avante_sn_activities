<?php
$pageTitle = $pageTitle ?? 'Avante Travel';
$activeNav = $activeNav ?? '';
$base = $base ?? '';
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? 'avante-search-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>assets/css/page.css">
    <?php foreach ($extraCss as $href): ?>
        <?php $cssHref = preg_match('#^https?://#i', (string) $href) ? $href : $base . $href; ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <a class="avante-skip-link" href="#main-content">Skip to main content</a>
    <div class="avante-page">
        <header class="avante-page-header">
            <a class="avante-brand" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php" aria-label="Avante Travel home">Avante Travel</a>
            <nav class="avante-nav" aria-label="Primary">
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php"<?php echo $activeNav === 'search' ? ' class="is-active"' : ''; ?>>Accommodation</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>map.php"<?php echo $activeNav === 'map' ? ' class="is-active"' : ''; ?>>Property map</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>holiday.php"<?php echo $activeNav === 'holiday' ? ' class="is-active"' : ''; ?>>Holiday builder</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>activities.php"<?php echo $activeNav === 'activities' ? ' class="is-active"' : ''; ?>>Activities</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>manage.php"<?php echo $activeNav === 'manage' ? ' class="is-active"' : ''; ?>>Manage booking</a>
                    <!-- Temporary convenience link; remove before production launch. -->
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>admin/index.php">Admin</a>
            </nav>
        </header>
        <div class="avante-page-heading">
            <span class="avante-page-kicker">Plan your escape</span>
            <h1><?php echo htmlspecialchars($headerTitle ?? $pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <main id="main-content" class="avante-main-content">
