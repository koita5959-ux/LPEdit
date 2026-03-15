<?php

/**
 * Theme Setup and Scripts
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * カスタム投稿タイプ「lp」の登録
 */
function lp_editor_register_lp_post_type()
{
    register_post_type('lp', array(
        'label'        => 'LP',
        'labels'       => array(
            'name'               => 'LP',
            'singular_name'      => 'LP',
            'add_new'            => '新規追加',
            'add_new_item'       => '新規LPを追加',
            'edit_item'          => 'LPを編集',
            'new_item'           => '新規LP',
            'view_item'          => 'LPを表示',
            'search_items'       => 'LPを検索',
            'not_found'          => 'LPが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にLPはありません',
            'all_items'          => 'すべてのLP',
        ),
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => array('slug' => '', 'with_front' => false),
        'supports'     => array('title', 'custom-fields'),
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-welcome-widgets-menus',
    ));
}
add_action('init', 'lp_editor_register_lp_post_type');

/**
 * lpカスタム投稿タイプのパーマリンク競合を解決
 */
function lp_editor_fix_lp_permalink_conflicts($rules)
{
    $new_rules = array();

    // LPカスタム投稿タイプ用のルール（5桁のページID: 数字3桁 + 英字2桁、小文字）
    $new_rules['([0-9]{3}[a-z]{2})/?$'] = 'index.php?lp=$matches[1]';
    $new_rules['([0-9]{3}[a-z]{2})/page/?([0-9]{1,})/?$'] = 'index.php?lp=$matches[1]&paged=$matches[2]';

    return array_merge($new_rules, $rules);
}
add_filter('rewrite_rules_array', 'lp_editor_fix_lp_permalink_conflicts');

/**
 * テーマのセットアップ
 */
function lp_editor_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
}
add_action('after_setup_theme', 'lp_editor_theme_setup');

/**
 * スタイルとスクリプトの読み込み
 */
function lp_editor_theme_scripts()
{
    wp_enqueue_style(
        'lp-editor-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );

    if (is_page_template('page-lp.php')) {
        wp_enqueue_script(
            'tailwindcss',
            'https://cdn.tailwindcss.com?plugins=forms,typography',
            array(),
            null,
            false
        );
        wp_enqueue_style(
            'google-fonts-noto-sans-jp',
            'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap',
            array(),
            null
        );
        wp_enqueue_style(
            'google-material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            array(),
            null
        );
        wp_enqueue_style(
            'lp-style',
            lp_editor_asset_url('assets/css/lp-style.css'),
            array(),
            wp_get_theme()->get('Version')
        );
    }
}
add_action('wp_enqueue_scripts', 'lp_editor_theme_scripts');

/**
 * Tailwind設定をインラインで出力
 */
function lp_editor_tailwind_config()
{
    if (! is_page_template('page-lp.php')) {
        return;
    }

    $primary_color   = get_field('primary_color') ?: '#2563EB';
    $secondary_color = get_field('secondary_color') ?: '#34D399';
?>
    <script>
        window.tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "<?php echo esc_js($primary_color); ?>",
                        secondary: "<?php echo esc_js($secondary_color); ?>",
                        "background-light": "#F9FAFB",
                        "background-dark": "#111827",
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#1F2937",
                        "text-light": "#374151",
                        "text-dark": "#F3F4F6",
                    },
                    fontFamily: {
                        display: ["'Noto Sans JP'", "sans-serif"],
                        body: ["'Noto Sans JP'", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>
<?php
}
add_action('wp_head', 'lp_editor_tailwind_config', 5);

/**
 * ACF JSON 保存・読み込み先
 */
add_filter('acf/settings/save_json', function () {
    return get_stylesheet_directory() . '/acf-json';
});
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});

/**
 * 管理画面の固定ページ一覧にID列を追加
 */
function lp_editor_add_page_id_column($columns)
{
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['page_id'] = 'ID';
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}
add_filter('manage_pages_columns', 'lp_editor_add_page_id_column');

function lp_editor_show_page_id_column($column, $post_id)
{
    if ($column === 'page_id') {
        echo $post_id;
    }
}
add_action('manage_pages_custom_column', 'lp_editor_show_page_id_column', 10, 2);

add_action('admin_head', function () {
    echo '<style>.column-page_id { width: 50px; }</style>';
});

/**
 * 管理画面でユーザーが自分のページのみ表示
 */
function lp_editor_restrict_pages_to_author($query)
{
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'page' && ! current_user_can('edit_others_pages')) {
        $query->set('author', get_current_user_id());
    }
}
add_action('pre_get_posts', 'lp_editor_restrict_pages_to_author');
