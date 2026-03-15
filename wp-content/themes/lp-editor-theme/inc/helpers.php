<?php

/**
 * Helper Functions
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 新しいページIDを生成する（例: 123ab）
 */
function lp_editor_generate_page_id()
{
    $seq = get_option('lp_sequence_number', 123);
    $letters = 'abcdefghjklmnpqrstuvwxyz';
    $random_letters = $letters[wp_rand(0, strlen($letters) - 1)] . $letters[wp_rand(0, strlen($letters) - 1)];
    $page_id = sprintf('%03d%s', $seq, $random_letters);
    update_option('lp_sequence_number', $seq + 1);
    return $page_id;
}

/**
 * メールアドレスからLPを検索
 */
function lp_editor_find_lp_by_email($email)
{
    $args = array(
        'post_type'   => 'lp',
        'post_status' => 'publish',
        'meta_query'  => array(
            array(
                'key'     => 'owner_email',
                'value'   => sanitize_email($email),
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    );
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * ページIDからLPを検索
 */
function lp_editor_find_lp_by_page_id($page_id)
{
    $page_id = strtolower(sanitize_text_field($page_id));
    if ($page_id === 'template') {
        $template = get_page_by_path('template', OBJECT, 'page');
        return ($template && $template->post_status === 'publish') ? $template : null;
    }
    $args = array(
        'post_type'   => 'lp',
        'name'        => $page_id,
        'post_status' => 'publish',
        'posts_per_page' => 1,
    );
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * エディタページのURLを取得
 */
function lp_editor_get_editor_url()
{
    // まずは運用スラッグ "editor" の固定ページを優先
    $preferred = get_page_by_path('editor', OBJECT, 'page');
    if ($preferred && $preferred->post_status === 'publish') {
        return get_permalink($preferred->ID);
    }

    // Template Name: LP Editor（編集画面）を使用しているページを検索
    $args = array(
        'post_type'  => 'page',
        'meta_query' => array(
            array(
                'key'   => '_wp_page_template',
                'value' => 'page-editor.php',
            ),
        ),
        'posts_per_page' => 1,
    );
    $pages = get_posts($args);
    if (! empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    // フォールバック: 旧スラッグ固定は避け、既定の /editor/ を優先
    return home_url('/editor/');
}

/**
 * メール署名ブロックを生成
 */
function lp_editor_get_mail_signature_block()
{
    $site_name = get_bloginfo('name');
    $site_url = home_url('/');
    $contact_email = defined('LP_EDITOR_MAIL_FROM') ? LP_EDITOR_MAIL_FROM : get_option('admin_email');

    $signature  = "――――――――――\n";
    $signature .= $site_name . "\n";
    $signature .= $site_url . "\n";
    $signature .= $contact_email . "\n";
    $signature .= "――――――――――\n";

    return $signature;
}

/**
 * 編集トークン関連
 */
function lp_editor_generate_edit_token($post_id)
{
    $token = wp_generate_password(32, false, false);
    update_post_meta($post_id, 'edit_token', $token);
    update_post_meta($post_id, 'token_expires', time() + (10 * 60));
    update_post_meta($post_id, 'token_used', false);
    return $token;
}

function lp_editor_verify_token($post_id, $token)
{
    $stored = get_post_meta($post_id, 'edit_token', true);
    $expires = get_post_meta($post_id, 'token_expires', true);
    if ($stored !== $token) return array('valid' => false, 'message' => '無効なURLです');
    if (get_post_meta($post_id, 'token_used', true)) return array('valid' => false, 'message' => 'このURLは既に使用されました');
    if (time() > $expires) return array('valid' => false, 'message' => 'URLの有効期限が切れました');
    return array('valid' => true);
}

function lp_editor_consume_token($post_id)
{
    update_post_meta($post_id, 'token_used', true);
}

/**
 * 色・デザイン関連
 */
function lp_editor_get_contrast_color($hex_color)
{
    $hex = ltrim((string)$hex_color, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '#1F2937';
    }

    $to_luminance = function ($hex_val) {
        $rgb = str_split($hex_val, 2);
        $res = array_map(function ($c) {
            $v = hexdec($c) / 255;
            return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
        }, $rgb);
        return 0.2126 * $res[0] + 0.7152 * $res[1] + 0.0722 * $res[2];
    };

    $bg_l = $to_luminance($hex);
    return ((1.05) / ($bg_l + 0.05) > ($bg_l + 0.05) / (0.05)) ? '#FFFFFF' : '#1F2937';
}

function lp_editor_adjust_brightness($hex, $steps)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * テンプレートが必要とする変数を一括準備する
 */
function lp_editor_prepare_template_variables($data)
{
    $vars = array();

    // 基本色とコントラスト色
    $vars['primary_color']   = $data['color_primary']   ?? '#2563EB';
    $vars['secondary_color'] = $data['color_secondary'] ?? '#34D399';

    $vars['text_primary_adjusted'] = lp_editor_get_contrast_color($vars['primary_color']) === '#FFFFFF' ? $vars['primary_color'] : lp_editor_adjust_brightness($vars['primary_color'], -40);
    $vars['text_on_primary']       = lp_editor_get_contrast_color($vars['primary_color']);
    $vars['text_on_secondary']     = lp_editor_get_contrast_color($vars['secondary_color']);

    // ヘッダー・基本情報
    $vars['company_name']           = $data['company_name'] ?? '';
    $vars['header_icon']            = $data['header_icon'] ?? 'bolt';
    $vars['header_icon_type']       = $data['header_icon_type'] ?? 'material';
    $vars['header_icon_image_url']  = lp_editor_normalize_image_url($data['header_icon_image'] ?? '');
    $vars['phone']                  = $data['phone'] ?? '';
    $vars['phone_display']          = $vars['phone']; // 必要に応じてフォーマット
    $vars['address']                = $data['address'] ?? '';
    $vars['business_hours']         = $data['business_hours'] ?? '';
    $vars['business_hours_full']    = $data['business_hours_full'] ?? '';
    $vars['copyright_year']         = $data['copyright_year'] ?? date('Y');
    $vars['footer_tagline']         = $data['footer']['tagline'] ?? '';

    // 営業時間HTML（フッター用）
    $hours_full = $vars['business_hours_full'];
    if (strpos($hours_full, '/') !== false) {
        $parts = explode('/', $hours_full);
        $vars['business_hours_footer_html'] = '<span class="footer-hours-chunk">' . esc_html(trim($parts[0])) . '</span>' .
            '<span class="mx-1">/</span>' .
            '<span class="footer-hours-chunk">' . esc_html(trim($parts[1])) . '</span>';
    } else {
        $vars['business_hours_footer_html'] = esc_html($hours_full);
    }

    // SNSリンク
    $sns_links = array();
    $sns_types = array('facebook' => 'facebook', 'twitter' => 'twitter', 'instagram' => 'camera_alt', 'youtube' => 'play_circle', 'tiktok' => 'music_note');
    foreach ($sns_types as $key => $icon) {
        $url = $data['sns_' . $key] ?? '';
        if (! empty($url)) {
            $sns_links[] = array('type' => $key, 'url' => $url, 'icon' => $icon);
        }
    }
    $vars['sns_links'] = $sns_links;

    // 共通テキスト
    $vars['cta_long']       = $data['common_settings']['cta_long'] ?? '';
    $vars['cta_short']      = $data['common_settings']['cta_short'] ?? '';
    $vars['contact_phone_guidance'] = $data['common_settings']['phone_guidance'] ?? '';

    // ヒーロー
    $vars['hero']           = $data['hero'] ?? array();
    $vars['hero_badge']     = $vars['hero']['badge'] ?? '';
    $vars['hero_headline']  = $vars['hero']['headline_html'] ?? '';
    $vars['hero_subtext']   = $vars['hero']['subtext_html'] ?? '';
    $vars['hero_image_url'] = $vars['hero']['image'] ?? '';

    // セクション
    $vars['problems_title'] = $data['problems_title'] ?? '';
    $vars['problems']       = $data['problems'] ?? array();
    $vars['solutions']      = $data['solutions'] ?? array();
    $vars['reasons_title']  = $data['reasons_title_html'] ?? '';
    $vars['reasons']        = $data['reasons'] ?? array();
    $vars['flow_title']     = $data['flow_title'] ?? '';
    $vars['steps']          = $data['steps'] ?? array();
    $vars['services_title'] = $data['services_title'] ?? '';
    $vars['services']       = $data['services'] ?? array();

    // お問い合わせ
    $vars['contact_title']    = $data['contact']['title'] ?? '';
    $vars['contact_subtitle'] = $data['contact']['subtitle_html'] ?? '';
    $vars['form_fields']      = $data['form_fields'] ?? array();
    $vars['form_submit_text'] = $data['form_settings']['submit_text'] ?? '送信する';

    // ボトムバー
    $vars['bottom_bar_email_label'] = $data['bottom_bar']['email_label'] ?? '';
    $vars['bottom_bar_email_text']  = $data['bottom_bar']['email_text']  ?? '';
    $vars['bottom_bar_phone_label'] = $data['bottom_bar']['phone_label'] ?? '';
    $vars['bottom_bar_phone_text']  = $data['bottom_bar']['phone_text']  ?? '';

    // ID（公開用）
    $vars['page_id'] = $data['page_id'] ?? '';

    return $vars;
}

/**
 * 本番環境ではminified版のアセットパスを返す
 */
function lp_editor_asset_url($relative_path)
{
    $theme_uri = get_template_directory_uri();
    if (! lp_editor_is_dev_environment() && preg_match('/\.(css|js)$/', $relative_path)) {
        $relative_path = preg_replace('/\.(css|js)$/', '.min.$1', $relative_path);
    }
    return $theme_uri . '/' . $relative_path;
}

/**
 * 環境判定・画像URL解決
 */
function lp_editor_is_dev_environment()
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = strtolower(preg_replace('/:\d+$/', '', $host));
    $wp_env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : '';

    // 本番での誤判定を避けるため、WP_DEBUGは判定に使わない
    return in_array($host, array('localhost', '127.0.0.1', '::1'), true)
        || str_ends_with($host, '.local')
        || $wp_env === 'local';
}

function lp_editor_normalize_image_url($value, $fallback = '')
{
    $src = is_array($value) ? ($value['url'] ?? '') : (is_numeric($value) ? wp_get_attachment_image_url($value, 'full') : (string) $value);
    if (empty($src)) $src = $fallback;
    $src = trim($src);
    if (empty($src)) return '';
    if (preg_match('#^https?://#i', $src) || str_starts_with($src, '//') || str_starts_with($src, '/')) return $src;
    if (! str_contains($src, '/')) return trailingslashit(get_template_directory_uri()) . 'assets/images/' . $src;
    if (str_starts_with($src, 'images/')) return trailingslashit(get_template_directory_uri()) . 'assets/' . $src;
    if (str_starts_with($src, 'assets/')) return trailingslashit(get_template_directory_uri()) . $src;
    if (str_starts_with($src, 'uploads/')) return content_url($src);
    if (str_starts_with($src, 'wp-content/uploads/')) return home_url('/' . $src);
    return $src;
}

/**
 * ACFデータを取得してエディタ・公開用に変換（共通関数）
 * $context: 'editor' | 'public'
 */
function lp_editor_get_acf_data($post_id, $context = 'editor')
{
    $data = array();
    $is_public = ($context === 'public');
    $resolve = function ($field, $fallback = '') {
        return lp_editor_normalize_image_url($field, $fallback);
    };

    $data['company_name'] = get_field('company_name', $post_id) ?: '';
    $data['header_icon'] = get_field('header_icon', $post_id) ?: 'bolt';
    $data['header_icon_type'] = get_post_meta($post_id, 'header_icon_type', true) ?: 'material';
    $data['header_icon_image'] = get_post_meta($post_id, 'header_icon_image', true) ?: '';
    $data['phone'] = get_field('phone', $post_id) ?: '';
    $data['address'] = get_field('address', $post_id) ?: '';
    $data['business_hours'] = get_field('business_hours', $post_id) ?: '';
    $data['business_hours_full'] = get_field('business_hours_full', $post_id) ?: '';
    $data['copyright_year'] = get_field('copyright_year', $post_id) ?: date('Y');
    $data['color_primary'] = get_field('primary_color', $post_id) ?: '#2563EB';
    $data['color_secondary'] = get_field('secondary_color', $post_id) ?: '#34D399';

    $data['common_settings'] = array(
        'cta_long'       => get_post_meta($post_id, 'common_cta_long', true) ?: '',
        'cta_short'      => get_post_meta($post_id, 'common_cta_short', true) ?: '',
        'phone_guidance' => get_post_meta($post_id, 'common_phone_guidance', true) ?: '',
    );

    $hero_image = get_field('hero_image', $post_id);
    $data['hero'] = array(
        'badge'         => get_field('hero_badge', $post_id) ?: '',
        'headline_html' => get_field('hero_title', $post_id) ?: '',
        'subtext_html'  => get_field('hero_subtitle', $post_id) ?: '',
        'cta_text'      => get_field('hero_cta_text', $post_id) ?: '',
        'image'         => $resolve($hero_image, get_field('hero_image_url', $post_id) ?: ''),
        'image_id'      => is_array($hero_image) ? ($hero_image['ID'] ?? 0) : (is_numeric($hero_image) ? intval($hero_image) : 0),
        'text_align'    => get_field('hero_text_align', $post_id) ?: 'bottom',
    );

    // 公開表示時のみフォールバックを許可（編集時は過去値復活を防ぐ）
    if ($is_public) {
        if (empty($data['hero']['cta_text'])) {
            $data['hero']['cta_text'] = '無料見積もりを依頼する';
        }
        if (empty($data['common_settings']['cta_long'])) {
            $data['common_settings']['cta_long'] = $data['hero']['cta_text'];
        }
        if (empty($data['common_settings']['cta_short'])) {
            $data['common_settings']['cta_short'] = get_field('bottom_bar_email_text', $post_id) ?: 'お問い合わせ';
        }
        if (empty($data['common_settings']['phone_guidance'])) {
            $data['common_settings']['phone_guidance'] = get_field('contact_phone_guidance', $post_id) ?: 'お電話でのご相談';
        }
    }

    // Sections
    $data['problems_title'] = get_field('problems_title', $post_id) ?: '';
    $data['problems'] = array();
    foreach ((get_field('problems', $post_id) ?: array()) as $p) {
        $data['problems'][] = array(
            'title'       => $p['title'] ?? '',
            'description' => $p['description'] ?? '',
            'image'       => $resolve($p['image'] ?? '', $p['image_url'] ?? ''),
            'image_id'    => is_array($p['image'] ?? null) ? ($p['image']['ID'] ?? 0) : (is_numeric($p['image'] ?? null) ? intval($p['image']) : 0),
        );
    }

    $solutions = get_post_meta($post_id, 'solutions', true);
    if (! is_array($solutions) || empty($solutions)) {
        $s_img = get_field('solution_image', $post_id);
        $solutions = array(array(
            'label'            => get_field('solution_label', $post_id) ?: '',
            'message_html'     => get_field('solution_title', $post_id) ?: '',
            'image'            => $resolve($s_img, get_field('solution_image_url', $post_id) ?: ''),
            'image_id'         => is_array($s_img) ? ($s_img['ID'] ?? 0) : (is_numeric($s_img) ? intval($s_img) : 0),
            'image_caption'    => get_field('solution_image_caption', $post_id) ?: '',
            'description_html' => get_field('solution_description', $post_id) ?: '',
        ));
    }
    $data['solutions'] = array();
    foreach (array_slice($solutions, 0, 2) as $s) {
        $data['solutions'][] = array(
            'label'            => $s['label'] ?? '',
            'message_html'     => $s['message_html'] ?? '',
            'image'            => $resolve($s['image'] ?? '', $s['image_url'] ?? ''),
            'image_id'         => intval($s['image_id'] ?? 0),
            'image_caption'    => $s['image_caption'] ?? '',
            'description_html' => $s['description_html'] ?? '',
        );
    }

    $data['reasons_title_html'] = get_field('reasons_title', $post_id) ?: '';
    $data['reasons_cta'] = get_field('reasons_cta_text', $post_id) ?: $data['common_settings']['cta_long'];
    $data['reasons'] = array();
    foreach ((get_field('reasons', $post_id) ?: array()) as $r) {
        $data['reasons'][] = array(
            'number'      => $r['number'] ?? '',
            'title'       => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
            'image'       => $resolve($r['image'] ?? '', $r['image_url'] ?? ''),
            'image_id'    => is_array($r['image'] ?? null) ? ($r['image']['ID'] ?? 0) : (is_numeric($r['image'] ?? null) ? intval($r['image']) : 0),
        );
    }

    $data['flow_title'] = get_field('flow_title', $post_id) ?: '';
    $data['steps'] = get_field('steps', $post_id) ?: array();

    $data['services_title'] = get_field('services_title', $post_id) ?: (get_field('cases_title', $post_id) ?: '');
    $data['services'] = array();
    foreach ((get_field('services', $post_id) ?: (get_field('cases', $post_id) ?: array())) as $c) {
        $data['services'][] = array(
            'caption'  => $c['caption'] ?? '',
            'layout'   => $c['layout'] ?? 'half',
            'image'    => $resolve($c['image'] ?? '', $c['image_url'] ?? ''),
            'image_id' => is_array($c['image'] ?? null) ? ($c['image']['ID'] ?? 0) : (is_numeric($c['image'] ?? null) ? intval($c['image']) : 0),
        );
    }

    $data['contact'] = array(
        'title'          => get_field('contact_title', $post_id) ?: '',
        'subtitle_html'  => get_field('contact_subtitle', $post_id) ?: '',
        'phone_guidance' => $data['common_settings']['phone_guidance'],
    );
    $data['form_fields']     = get_field('form_fields', $post_id) ?: array();
    $data['form_settings']   = array(
        'recipient_email' => get_field('form_recipient_email', $post_id) ?: '',
        'email_subject'   => get_field('form_email_subject', $post_id) ?: '',
        'success_message' => get_field('form_success_message', $post_id) ?: '',
        'submit_text'      => get_field('form_submit_text', $post_id) ?: '',
    );
    $data['footer']['tagline'] = get_field('footer_tagline', $post_id) ?: '';
    $data['sns_facebook']      = get_field('sns_facebook', $post_id) ?: '';
    $data['sns_twitter']       = get_field('sns_twitter', $post_id) ?: '';
    $data['sns_instagram']     = get_field('sns_instagram', $post_id) ?: '';
    $data['sns_youtube']       = get_field('sns_youtube', $post_id) ?: '';
    $data['sns_tiktok']        = get_field('sns_tiktok', $post_id) ?: '';
    $data['bottom_bar']        = array(
        'email_label' => get_field('bottom_bar_email_label', $post_id) ?: '',
        'email_text'  => $data['common_settings']['cta_short'],
        'phone_label' => get_field('bottom_bar_phone_label', $post_id) ?: '',
        'phone_text'  => get_field('bottom_bar_phone_text', $post_id) ?: '',
    );
    // 旧文言を新文言へ吸収
    if (($data['bottom_bar']['phone_text'] ?? '') === '電話する') {
        $data['bottom_bar']['phone_text'] = '電話相談';
    }
    // 公開表示向けの最低限デフォルト
    if ($is_public) {
        if (empty($data['reasons_title_html'])) $data['reasons_title_html'] = '選ばれる理由';
        if (empty($data['flow_title'])) $data['flow_title'] = 'ご依頼の流れ';
        if (empty($data['services_title'])) $data['services_title'] = 'サービス例';
        if (empty($data['contact']['title'])) $data['contact']['title'] = 'お問い合わせ';
        if (empty($data['form_settings']['email_subject'])) $data['form_settings']['email_subject'] = '【お問い合わせ】ホームページより';
        if (empty($data['form_settings']['success_message'])) $data['form_settings']['success_message'] = "お問い合わせありがとうございます。\n内容を確認次第、ご連絡いたします。";
        if (empty($data['form_settings']['submit_text'])) $data['form_settings']['submit_text'] = '送信する';
        if (empty($data['bottom_bar']['email_label'])) $data['bottom_bar']['email_label'] = '24時間受付';
        if (empty($data['bottom_bar']['phone_label'])) $data['bottom_bar']['phone_label'] = 'お急ぎの方';
        if (empty($data['bottom_bar']['phone_text'])) $data['bottom_bar']['phone_text'] = '電話相談';
    }

    return $data;
}

/**
 * 投稿IDからLP公開用データを構築（後方互換用）
 */
function lp_editor_build_public_data($post_id)
{
    return lp_editor_get_acf_data($post_id, 'public');
}
