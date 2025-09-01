<?php
/**
 * Common Meta Tags and CSS/JS Includes
 * Shared across all pages
 */

// Ensure config is loaded
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/config.php';
}

$path_prefix = $GLOBALS['path_prefix'];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo escape_output($page_title); ?></title>

<!-- Meta Information -->
<meta name="description" content="<?php echo isset($page_description) ? escape_output($page_description) : 'Orlando International Resorts - Premier hotel in Machakos, Kenya offering luxury accommodation and world-class service.'; ?>">
<meta name="keywords" content="<?php echo isset($page_keywords) ? escape_output($page_keywords) : 'Orlando International Resorts, Hotel, Machakos, Kenya, Accommodation, Restaurant, Conference, Events'; ?>">
<meta name="author" content="Orlando International Resorts">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="<?php echo $path_prefix; ?>images/favicon.svg">
<link rel="apple-touch-icon" href="<?php echo $path_prefix; ?>images/apple-touch-icon.png">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
<meta property="og:title" content="<?php echo escape_output($page_title); ?>">
<meta property="og:description" content="<?php echo isset($page_description) ? escape_output($page_description) : 'Orlando International Resorts - Premier hotel in Machakos, Kenya'; ?>">
<meta property="og:image" content="<?php echo $path_prefix; ?>images/og-image.jpg">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
<meta property="twitter:title" content="<?php echo escape_output($page_title); ?>">
<meta property="twitter:description" content="<?php echo isset($page_description) ? escape_output($page_description) : 'Orlando International Resorts - Premier hotel in Machakos, Kenya'; ?>">
<meta property="twitter:image" content="<?php echo $path_prefix; ?>images/og-image.jpg">

<!-- Common CSS -->
<link href="<?php echo $path_prefix; ?>css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
<link href="<?php echo $path_prefix; ?>css/font-awesome.css" rel="stylesheet">
<link href="<?php echo $path_prefix; ?>css/shared-styles.css" rel="stylesheet" type="text/css" media="all" />

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css?family=Oswald:300,400,700" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Federo" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">

<!-- Common JavaScript -->
<script type="text/javascript" src="<?php echo $path_prefix; ?>js/modernizr-2.6.2.min.js"></script>
<script type="application/x-javascript"> 
    addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
    function hideURLbar(){ window.scrollTo(0,1); } 
</script>
