<?php

/**
 * Template Name: Option Page
 *
 * オプションページ（内容未定）
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('service', array(
    'title' => 'オプション',
    'description' => 'LP Editorのオプションサービスについてご案内します。',
    'current' => 'option'
));
?>

<!-- ===== メインコンテンツ ===== -->
<main class="page-content">
    <section class="guide-section">
        <h1 class="page-title">オプション</h1>
        <h3>テンプレートの原稿をいただければ制作を承ります</h3>
        <p class="guide-section__lead">原稿をいただき、各セクションの文字と構成を相談し画像の生成のお手伝いも致します。（別途製作費必要）</p>
    </section>
</main>

<?php get_footer('service'); ?>
