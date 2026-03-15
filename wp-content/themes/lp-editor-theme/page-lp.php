<?php

/**
 * Template Name: テンプレート
 * Template Post Type: page
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$data = lp_editor_build_public_data($post_id);
echo lp_editor_generate_public_html($data, $post_id);
