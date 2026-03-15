<?php

/**
 * Service Site Header
 *
 * @package LP_Editor_Theme
 */

$theme_uri = get_template_directory_uri();
$select_url = home_url('/select/');
$guide_url = home_url('/guide/');
$terms_url = home_url('/terms/');
$template_url = home_url('/template/');
$option_url = home_url('/option/');

// サイト設定
$site_name = get_bloginfo('name');
$site_description = get_bloginfo('description');

// ヘッダー表示文言（未指定時はサイトタイトルの右側を優先）
$header_brand = '';
if (isset($args['brand']) && $args['brand'] !== '') {
    $header_brand = (string) $args['brand'];
} else {
    $parts = preg_split('/[|｜]/u', (string) $site_name);
    if (is_array($parts) && count($parts) > 1) {
        $header_brand = trim((string) end($parts));
    } else {
        $header_brand = $site_name;
    }
}

// ページタイトル（ブラウザ）
$base_title = isset($args['title']) ? trim((string)$args['title']) : '';
if ($base_title === '') {
    $page_title = $site_name;
} else {
    if (strpos($site_name, $base_title) !== false) {
        $page_title = $site_name;
    } elseif (strpos($base_title, $site_name) !== false) {
        $page_title = $base_title;
    } else {
        $page_title = $base_title . ' | ' . $site_name;
    }
}

// ディスクリプション
$page_description = isset($args['description']) && $args['description'] !== ''
    ? (string)$args['description']
    : (string)$site_description;

// OGP
$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$current_url = home_url($request_uri);
$og_image = isset($args['og_image']) && $args['og_image'] !== ''
    ? (string)$args['og_image']
    : ($theme_uri . '/assets/images/ogp-default.jpg');
$current_page = isset($args['current']) ? $args['current'] : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?></title>
    <?php if ($page_description !== '') : ?>
        <meta name="description" content="<?php echo esc_attr($page_description); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
    <meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
    <?php if ($page_description !== '') : ?>
        <meta property="og:description" content="<?php echo esc_attr($page_description); ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?php echo esc_url($current_url); ?>">
    <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($page_title); ?>">
    <?php if ($page_description !== '') : ?>
        <meta name="twitter:description" content="<?php echo esc_attr($page_description); ?>">
    <?php endif; ?>
    <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url(lp_editor_asset_url('assets/css/main-style.css')); ?>">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <header class="header">
        <div class="header-brand">
            <img src="<?php echo esc_url($theme_uri . '/assets/images/logo.png'); ?>" alt="MEDIA HOUSE ロゴ" class="header-logo">
            <span class="header-title">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($header_brand); ?></a>
            </span>
        </div>
        <nav class="header-nav" id="headerNav">
            <a href="<?php echo esc_url($guide_url); ?>" class="<?php echo ($current_page === 'guide') ? 'is-current' : ''; ?>">使い方ガイド</a>
            <a href="<?php echo esc_url($template_url); ?>" class="<?php echo ($current_page === 'template') ? 'is-current' : ''; ?>" target="_blank" rel="noopener noreferrer">テンプレート</a>
            <a href="<?php echo esc_url($option_url); ?>" class="<?php echo ($current_page === 'option') ? 'is-current' : ''; ?>">オプション</a>
            <a href="<?php echo esc_url($terms_url); ?>" class="<?php echo ($current_page === 'terms') ? 'is-current' : ''; ?>">利用規約</a>
            <a href="<?php echo esc_url($select_url); ?>" class="nav-cta <?php echo ($current_page === 'select') ? 'is-current' : ''; ?>">作成 / 修正</a>
        </nav>
        <button class="hamburger" id="hamburger" type="button" aria-label="メニューを開く" aria-expanded="false" aria-controls="headerNav">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>
