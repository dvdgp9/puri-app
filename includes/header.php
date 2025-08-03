<!DOCTYPE html>
<html lang="<?php echo isset($lang) ? $lang : 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? "Puri - " . $pageTitle : "Puri"; ?></title>
    <link rel="icon" type="image/png" href="public/assets/images/favicon.png">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($extraStyles)): ?>
        <?php echo $extraStyles; ?>
    <?php endif; ?>
    <?php if (isset($pageTitle) && $pageTitle === 'Instalaciones'): ?>
        <script defer src="public/assets/js/instalaciones-search.js"></script>
    <?php endif; ?>
</head>
<body> 