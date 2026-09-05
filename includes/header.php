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
    <div class="avante-page">
        <header class="avante-page-header">
            <a class="avante-logo-link" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php">
                <img src="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>assets/img/avantetravel.png" alt="Avante Travel">
            </a>
            <div class="avante-header-copy">
                <h1><?php echo htmlspecialchars($headerTitle ?? $pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <nav class="avante-nav" aria-label="Primary">
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php"<?php echo $activeNav === 'search' ? ' class="is-active"' : ''; ?>>Accommodation</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>holiday.php"<?php echo $activeNav === 'holiday' ? ' class="is-active"' : ''; ?>>Holiday builder</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>activities.php"<?php echo $activeNav === 'activities' ? ' class="is-active"' : ''; ?>>Activities</a>
                    <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>manage.php"<?php echo $activeNav === 'manage' ? ' class="is-active"' : ''; ?>>Manage booking</a>
                </nav>
            </div>
        </header>
