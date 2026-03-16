<?php
/**
 * LP Editor Theme functions and definitions
 */
if (!defined('ABSPATH')) {
    exit;
}
define('LP_EDITOR_MAIL_FROM', 'lpeditor@media-house.jp');
define('LP_EDITOR_MAIL_FROM_NAME', 'LP Editor');


if (! defined('LP_EDITOR_ENABLE_LOG')) define('LP_EDITOR_ENABLE_LOG', true);
if (! defined('LP_EDITOR_RATE_LIMIT_EDIT_URL_SECONDS')) define('LP_EDITOR_RATE_LIMIT_EDIT_URL_SECONDS', 60);
if (! defined('LP_EDITOR_RATE_LIMIT_CREATE_SECONDS')) define('LP_EDITOR_RATE_LIMIT_CREATE_SECONDS', 30);
if (! defined('LP_EDITOR_RATE_LIMIT_UPLOAD_SECONDS')) define('LP_EDITOR_RATE_LIMIT_UPLOAD_SECONDS', 3);
if (! defined('LP_EDITOR_RATE_LIMIT_CONTACT_SECONDS')) define('LP_EDITOR_RATE_LIMIT_CONTACT_SECONDS', 30);
if (! defined('LP_EDITOR_UPLOAD_MAX_SIZE_BYTES')) define('LP_EDITOR_UPLOAD_MAX_SIZE_BYTES', 5 * 1024 * 1024);
if (! defined('LP_EDITOR_UPLOAD_ALLOWED_MIMES')) {
    define('LP_EDITOR_UPLOAD_ALLOWED_MIMES', array('image/jpeg', 'image/png', 'image/gif', 'image/webp'));
}

// 必要なファイルを読み込む
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/template-logic.php';
require_once get_template_directory() . '/inc/api.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/access-log.php';

// Stripe設定（Git管理外）
if (file_exists(get_template_directory() . '/stripe-config.php')) {
    require_once get_template_directory() . '/stripe-config.php';
}

/**
 * 初期テンプレートの管理画面アクセス時にのみ自動作成する。
 * フロントアクセス時の副作用を避けるため、管理画面の確認操作のみに限定する。
 */
add_action('after_setup_theme', function () {
    if (is_admin() && function_exists('lp_editor_create_template_lp')) {
        lp_editor_create_template_lp();
    }
}, 20);
