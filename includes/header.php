<?php
/**
 * Global Header Component
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Allow pages to define custom page title and meta description
$page_title = isset($page_title) ? $page_title . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE;
$page_description = isset($page_description) ? $page_description : 'Official portal of Muthoot Institute of Technology and Science - MITS Connect. Connect, mentor, and explore opportunities.';
$current_page = $current_page ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="<?= e($page_description) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">

    <title><?= e($page_title) ?></title>

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Modern Styling -->
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css?v=<?= APP_VERSION ?>">
    <?php if (($current_page ?? '') === 'admin'): ?>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/admin.css?v=<?= APP_VERSION ?>">
    <?php endif; ?>

    <!-- Google Identity Services (GIS) Library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-surface">

<?php include_once __DIR__ . '/navbar.php'; ?>

<main class="flex-grow-1" id="main-content">
    <div class="container py-3">
        <?= display_flash(); ?>
    </div>
