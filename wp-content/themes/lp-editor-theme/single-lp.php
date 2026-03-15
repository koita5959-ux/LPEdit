<?php
/**
 * Single LP Template
 *
 * カスタム投稿タイプ「lp」の個別表示テンプレート
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// WordPressループ内で投稿IDを取得
while (have_posts()) {
    the_post();

    $post_id = get_the_ID();
    $data = lp_editor_build_public_data($post_id);
    echo lp_editor_generate_public_html($data, $post_id);
}
