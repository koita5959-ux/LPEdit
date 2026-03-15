<?php

/**
 * REST API and Data Saving Logic
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API エンドポイントの登録
 */
function lp_editor_register_rest_routes()
{
    register_rest_route('lp-editor/v1', '/preview', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_preview',
        'permission_callback' => '__return_true',  // 本番nonce問題の暫定対応
    ));
    register_rest_route('lp-editor/v1', '/create', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_create',
        'permission_callback' => '__return_true',  // 本番nonce問題の暫定対応
    ));
    register_rest_route('lp-editor/v1', '/upload', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_upload',
        'permission_callback' => '__return_true',  // 本番nonce問題の暫定対応
    ));
    register_rest_route('lp-editor/v1', '/update/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_update',
        'permission_callback' => 'lp_editor_rest_permission_update',
    ));
    register_rest_route('lp-editor/v1', '/request-edit-url', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_request_edit_url',
        'permission_callback' => '__return_true',  // 本番nonce問題の暫定対応
    ));
    register_rest_route('lp-editor/v1', '/delete/(?P<id>[a-zA-Z0-9\-]+)', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_delete',
        'permission_callback' => 'lp_editor_rest_permission_delete',
    ));
    register_rest_route('lp-editor/v1', '/access-log', array(
        'methods'             => 'POST',
        'callback'            => 'lp_editor_rest_access_log',
        'permission_callback' => '__return_true',  // アクセスログ記録のみのため認証不要
    ));
}
add_action('rest_api_init', 'lp_editor_register_rest_routes');

function lp_editor_log_event($level, $event, $context = array())
{
    if (! defined('LP_EDITOR_ENABLE_LOG') || ! LP_EDITOR_ENABLE_LOG) {
        return;
    }
    $ip = function_exists('lp_editor_get_client_ip') ? lp_editor_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $payload = array(
        'level'   => $level,
        'event'   => $event,
        'ip'      => $ip,
        'context' => $context,
    );
    error_log('[LP_EDITOR] ' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function lp_editor_api_success($data = array(), $status = 200)
{
    return new WP_REST_Response(array_merge(array('success' => true), $data), $status);
}

function lp_editor_api_error($message, $status = 400, $code = 'lp_editor_error', $context = array())
{
    lp_editor_log_event('warning', $code, array_merge(array('status' => $status, 'message' => $message), $context));
    return new WP_REST_Response(array('success' => false, 'message' => $message, 'code' => $code), $status);
}

function lp_editor_rest_permission_public($request)
{
    if (lp_editor_is_dev_environment()) {
        return true;
    }
    $nonce = sanitize_text_field((string) $request->get_header('X-LP-Nonce'));
    if ($nonce === '') {
        $nonce = sanitize_text_field((string) $request->get_header('X-WP-Nonce'));
    }
    if ($nonce === '') {
        $nonce = sanitize_text_field((string) $request->get_param('_lp_nonce'));
    }

    if ($nonce === '' || ! wp_verify_nonce($nonce, 'lp_editor_public_api')) {
        return new WP_Error('lp_editor_forbidden', '不正なリクエストです', array('status' => 403));
    }

    return true;
}

/**
 * 変更系APIの権限チェック（更新・削除）
 * 本番は編集トークン必須。ローカル開発のみスキップ許可。
 */
function lp_editor_rest_validate_edit_token($post_id, $request)
{
    if (lp_editor_is_dev_environment()) {
        return true;
    }

    $token = sanitize_text_field((string) $request->get_param('edit_token'));
    if ($token === '') {
        $token = sanitize_text_field((string) $request->get_header('X-LP-Edit-Token'));
    }
    if ($token === '') {
        return new WP_Error('lp_editor_forbidden', '編集トークンが必要です', array('status' => 403));
    }

    $stored_token = (string) get_post_meta($post_id, 'edit_token', true);
    $expires      = intval(get_post_meta($post_id, 'token_expires', true));

    if ($stored_token === '' || ! hash_equals($stored_token, $token)) {
        return new WP_Error('lp_editor_forbidden', '無効な編集トークンです', array('status' => 403));
    }
    if ($expires > 0 && time() > $expires) {
        return new WP_Error('lp_editor_forbidden', '編集トークンの有効期限が切れています', array('status' => 403));
    }

    return true;
}

function lp_editor_rest_permission_update($request)
{
    $post_id = intval($request->get_param('id'));
    $post = get_post($post_id);

    if (! $post || ! in_array($post->post_type, array('lp', 'page'), true)) {
        return new WP_Error('lp_editor_not_found', '指定されたLPが見つかりません', array('status' => 404));
    }
    if ($post->post_type === 'page' && $post->post_name !== 'template') {
        return new WP_Error('lp_editor_forbidden', 'このページは編集できません', array('status' => 403));
    }

    return lp_editor_rest_validate_edit_token($post_id, $request);
}

function lp_editor_rest_permission_delete($request)
{
    $page_id = strtolower(sanitize_text_field($request->get_param('id')));
    if ($page_id === 'template') {
        return new WP_Error('lp_editor_forbidden', 'テンプレートは削除できません', array('status' => 403));
    }

    $lp = lp_editor_find_lp_by_page_id($page_id);
    if (! $lp || $lp->post_type !== 'lp') {
        return new WP_Error('lp_editor_not_found', 'LPが見つかりません', array('status' => 404));
    }

    return lp_editor_rest_validate_edit_token($lp->ID, $request);
}

function lp_editor_get_client_ip()
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded) {
        $parts = explode(',', $forwarded);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';
}

function lp_editor_rate_limit_key_for_edit_url($email)
{
    $ip = lp_editor_get_client_ip();
    return 'lp_edit_url_' . md5(strtolower(trim($email)) . '|' . $ip);
}

function lp_editor_rate_limit_key_for_create($email)
{
    $ip = lp_editor_get_client_ip();
    return 'lp_create_' . md5(strtolower(trim($email)) . '|' . $ip);
}

function lp_editor_rate_limit_key_for_upload()
{
    $ip = lp_editor_get_client_ip();
    return 'lp_upload_' . md5($ip);
}

function lp_editor_rate_limit_remaining_seconds($key, $cooldown_seconds)
{
    $last_requested_at = intval(get_transient($key));
    if ($last_requested_at <= 0) return 0;
    return max(1, $cooldown_seconds - (time() - $last_requested_at));
}

function lp_editor_rate_limit_mark($key, $cooldown_seconds)
{
    set_transient($key, time(), $cooldown_seconds);
}

/**
 * REST API ハンドラー: プレビュー
 */
function lp_editor_rest_preview($request)
{
    return lp_editor_api_success(array(
        'html' => lp_editor_generate_preview_html($request->get_json_params()),
    ));
}

/**
 * REST API ハンドラー: 新規作成
 */
function lp_editor_send_create_notification_email($to_email, $permalink, $company_name = '')
{
    if (empty($to_email) || ! is_email($to_email)) {
        return false;
    }

    $site_name = get_bloginfo('name');
    $label_name = $company_name !== '' ? $company_name : $site_name;

    $subject = '【LP Editor】LP作成が完了しました';
    $body = "このメールはLP作成完了により自動送信されています。\n\n";
    $body .= "LPの作成が完了しました。\n";
    $body .= "対象: " . $label_name . "\n\n";
    $body .= "【公開URL】\n";
    $body .= $permalink . "\n\n";
    $body .= lp_editor_get_mail_signature_block();

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . LP_EDITOR_MAIL_FROM_NAME . ' <' . LP_EDITOR_MAIL_FROM . '>',
    );

    return wp_mail($to_email, $subject, $body, $headers);
}

function lp_editor_rest_create($request)
{
    $data  = $request->get_json_params();
    $email = isset($data['company_email']) ? sanitize_email($data['company_email']) : '';

    if (empty($email)) {
        return lp_editor_api_error('メールアドレスは必須です', 400, 'create_email_required');
    }

    $create_cooldown_seconds = intval(LP_EDITOR_RATE_LIMIT_CREATE_SECONDS);
    $create_rate_key = lp_editor_rate_limit_key_for_create($email);
    $create_remaining = lp_editor_rate_limit_remaining_seconds($create_rate_key, $create_cooldown_seconds);
    if ($create_remaining > 0) {
        return lp_editor_api_error(
            '短時間に連続作成はできません。' . $create_remaining . '秒後に再度お試しください',
            429,
            'create_rate_limited',
            array('remaining' => $create_remaining)
        );
    }

    if (lp_editor_find_lp_by_email($email)) {
        return lp_editor_api_error('このメールアドレスは既に登録されています', 400, 'create_email_already_exists');
    }

    $page_id = lp_editor_generate_page_id();
    $title   = ! empty($data['company_name']) ? $data['company_name'] . ' LP' : 'LP ' . date('Y-m-d H:i:s');

    $post_id = wp_insert_post(array(
        'post_title'  => $title,
        'post_name'   => $page_id,
        'post_type'   => 'lp',
        'post_status' => 'publish',
    ));

    if (is_wp_error($post_id)) {
        return lp_editor_api_error($post_id->get_error_message(), 500, 'create_insert_failed');
    }

    lp_editor_save_acf_data($post_id, $data);
    update_post_meta($post_id, 'owner_email', $email);
    lp_editor_rate_limit_mark($create_rate_key, $create_cooldown_seconds);

    $permalink = home_url('/' . $page_id . '/');
    $company_name = sanitize_text_field((string)($data['company_name'] ?? ''));
    $mail_sent = lp_editor_send_create_notification_email($email, $permalink, $company_name);
    if (! $mail_sent) {
        lp_editor_log_event('warning', 'create_notice_mail_failed', array(
            'post_id' => $post_id,
            'email'   => $email,
        ));
    }

    return lp_editor_api_success(array(
        'post_id'    => $post_id,
        'page_id'    => $page_id,
        'permalink'  => $permalink,
        'edit_token' => lp_editor_generate_edit_token($post_id),
    ));
}

/**
 * REST API ハンドラー: アップロード
 */
function lp_editor_rest_upload($request)
{
    $upload_cooldown_seconds = intval(LP_EDITOR_RATE_LIMIT_UPLOAD_SECONDS);
    $upload_rate_key = lp_editor_rate_limit_key_for_upload();
    $upload_remaining = lp_editor_rate_limit_remaining_seconds($upload_rate_key, $upload_cooldown_seconds);
    if ($upload_remaining > 0) {
        return lp_editor_api_error(
            'アップロード間隔が短すぎます。' . $upload_remaining . '秒後に再試行してください',
            429,
            'upload_rate_limited',
            array('remaining' => $upload_remaining)
        );
    }

    $files = $request->get_file_params();
    if (empty($files['file'])) return lp_editor_api_error('ファイルがありません', 400, 'upload_file_missing');
    if (! empty($files['file']['error'])) return lp_editor_api_error('アップロードに失敗しました', 400, 'upload_file_error');

    $max_size = intval(LP_EDITOR_UPLOAD_MAX_SIZE_BYTES);
    $file_size = intval($files['file']['size'] ?? 0);
    if ($file_size <= 0 || $file_size > $max_size) {
        return lp_editor_api_error('画像サイズは5MB以下にしてください', 400, 'upload_file_too_large');
    }

    $tmp_name = $files['file']['tmp_name'] ?? '';
    $name = $files['file']['name'] ?? '';
    $type_check = wp_check_filetype_and_ext($tmp_name, $name);
    $allowed_mimes = defined('LP_EDITOR_UPLOAD_ALLOWED_MIMES') ? LP_EDITOR_UPLOAD_ALLOWED_MIMES : array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $detected_mime = $type_check['type'] ?? '';
    if (! in_array($detected_mime, $allowed_mimes, true)) {
        return lp_editor_api_error('JPEG/PNG/GIF/WebP画像のみアップロードできます', 400, 'upload_mime_not_allowed', array('detected_mime' => $detected_mime));
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $upload = wp_handle_upload($files['file'], array('test_form' => false));
    if (isset($upload['error'])) return lp_editor_api_error($upload['error'], 500, 'upload_handle_failed');

    $attach_id = wp_insert_attachment(array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name(pathinfo($files['file']['name'], PATHINFO_FILENAME)),
        'post_status'    => 'inherit',
    ), $upload['file']);

    if (is_wp_error($attach_id)) return lp_editor_api_error('メディア登録に失敗しました', 500, 'upload_attach_failed');

    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
    lp_editor_rate_limit_mark($upload_rate_key, $upload_cooldown_seconds);

    return lp_editor_api_success(array('id' => $attach_id, 'source_url' => $upload['url']));
}

/**
 * REST API ハンドラー: 更新
 */
function lp_editor_rest_update($request)
{
    $post_id = intval($request->get_param('id'));
    $data    = $request->get_json_params();

    $post = get_post($post_id);
    if (! $post || ! in_array($post->post_type, array('lp', 'page'), true)) {
        return lp_editor_api_error('指定されたLPが見つかりません', 404, 'update_target_not_found');
    }
    if ($post->post_type === 'page' && $post->post_name !== 'template') {
        return lp_editor_api_error('このページは編集できません', 403, 'update_target_forbidden');
    }

    lp_editor_save_acf_data($post_id, $data);

    if (! empty($data['company_name'])) {
        wp_update_post(array('ID' => $post_id, 'post_title' => $data['company_name'] . ' LP'));
    }

    return lp_editor_api_success(array('message' => '更新しました', 'permalink' => get_permalink($post_id)));
}

/**
 * REST API ハンドラー: 編集URLリクエスト
 */
function lp_editor_rest_request_edit_url($request)
{
    $data  = $request->get_json_params();
    $email = sanitize_email($data['email'] ?? '');

    if (empty($email)) return lp_editor_api_error('メールアドレスを入力してください', 400, 'request_email_required');

    $rate_key = lp_editor_rate_limit_key_for_edit_url($email);
    $cooldown_seconds = intval(LP_EDITOR_RATE_LIMIT_EDIT_URL_SECONDS);
    $remaining = lp_editor_rate_limit_remaining_seconds($rate_key, $cooldown_seconds);
    if ($remaining > 0) {
        return lp_editor_api_error(
            '短時間に繰り返し送信されています。' . $remaining . '秒後に再度お試しください',
            429,
            'request_rate_limited',
            array('remaining' => $remaining)
        );
    }

    $lp = lp_editor_find_lp_by_email($email);
    if (! $lp) return lp_editor_api_error('該当するLPが見つかりませんでした', 404, 'request_target_not_found');

    $token      = lp_editor_generate_edit_token($lp->ID);
    $editor_url = lp_editor_get_editor_url();
    $edit_query = (strpos($editor_url, '?') === false ? '?' : '&') . 'edit=' . $lp->post_name . '&token=' . $token;
    $full_edit_url = $editor_url . $edit_query;

    if (lp_editor_is_dev_environment()) {
        lp_editor_rate_limit_mark($rate_key, $cooldown_seconds);
        return lp_editor_api_success(array('message' => '【開発】URLを発行しました', 'debug_url' => $full_edit_url, 'is_dev' => true));
    }

    $subject = '【LP Editor】編集URLのご案内';
    $editor_url = lp_editor_get_editor_url();
    $contact_email = LP_EDITOR_MAIL_FROM;

    $body = "このメールは、LP作成・修正ページにて「既存LPを修正」を選択された方にお送りしています。\n\n";
    $body .= "LP Editorをご利用いただきありがとうございます。\n\n";
    $body .= "以下のURLより、ページ内容の編集・更新を行ってください。\n";
    $body .= "（※このURLは発行から10分間のみ有効です）\n\n";
    $body .= "▼ 更新用URLはこちら\n";
    $body .= $full_edit_url . "\n\n";
    $body .= "【ご注意】\n";
    $body .= "・セキュリティのため、このURLは一度アクセスすると無効になります。\n";
    $body .= "・再度更新作業を行う場合は、お手数ですが再度メールアドレスを入力して新しいURLを発行してください。\n";
    $body .= "・このURLはページ編集のための重要なキーです。第三者には共有しないでください。\n";
    $body .= "・心当たりがない場合は、本メールを破棄してください。\n\n";
    $body .= lp_editor_get_mail_signature_block();

    $headers = array('Content-Type: text/plain; charset=UTF-8', 'From: LP Editor <' . $contact_email . '>');

    if (wp_mail($email, $subject, $body, $headers)) {
        lp_editor_rate_limit_mark($rate_key, $cooldown_seconds);
        return lp_editor_api_success(array('message' => '編集URLをメールで送信しました'));
    }
    return lp_editor_api_error('メール送信に失敗しました', 500, 'request_mail_send_failed');
}

/**
 * REST API ハンドラー: 削除
 */
function lp_editor_rest_delete($request)
{
    $page_id = strtolower(sanitize_text_field($request->get_param('id')));
    if ($page_id === 'template') return lp_editor_api_error('テンプレートは削除できません', 403, 'delete_template_forbidden');

    $lp = lp_editor_find_lp_by_page_id($page_id);
    if (! $lp || $lp->post_type !== 'lp') return lp_editor_api_error('LPが見つかりません', 404, 'delete_target_not_found');

    if (wp_delete_post($lp->ID, true)) return lp_editor_api_success(array('message' => '削除しました'));
    return lp_editor_api_error('削除に失敗しました', 500, 'delete_failed');
}

/**
 * ACFフィールドの保存処理（一括）
 */
function lp_editor_save_acf_data($post_id, $data)
{
    // 共通項目の抽出
    $cta_long       = $data['common_settings']['cta_long'] ?? ($data['hero']['cta_text'] ?? '');
    $cta_short      = $data['common_settings']['cta_short'] ?? ($data['bottom_bar']['email_text'] ?? '');
    $phone_guidance = $data['common_settings']['phone_guidance'] ?? ($data['contact']['phone_guidance'] ?? '');

    // 基本情報
    update_field('company_name', $data['company_name'] ?? '', $post_id);
    update_field('header_icon', $data['header_icon'] ?? 'bolt', $post_id);
    update_post_meta($post_id, 'header_icon_type', $data['header_icon_type'] ?? 'material');
    update_post_meta($post_id, 'header_icon_image', $data['header_icon_image'] ?? '');
    update_field('phone', $data['phone'] ?? '', $post_id);
    update_field('address', $data['address'] ?? '', $post_id);
    update_field('business_hours', $data['business_hours'] ?? '', $post_id);
    update_field('business_hours_full', $data['business_hours_full'] ?? '', $post_id);
    update_field('copyright_year', $data['copyright_year'] ?? date('Y'), $post_id);

    // カラー
    update_field('primary_color', $data['color_primary'] ?? '#2563EB', $post_id);
    update_field('secondary_color', $data['color_secondary'] ?? '#34D399', $post_id);
    update_post_meta($post_id, 'common_cta_long', $cta_long);
    update_post_meta($post_id, 'common_cta_short', $cta_short);
    update_post_meta($post_id, 'common_phone_guidance', $phone_guidance);

    // ヒーロー
    if (isset($data['hero'])) {
        update_field('hero_badge', $data['hero']['badge'] ?? '', $post_id);
        update_field('hero_title', $data['hero']['headline_html'] ?? '', $post_id);
        update_field('hero_subtitle', $data['hero']['subtext_html'] ?? '', $post_id);
        update_field('hero_cta_text', $data['hero']['cta_text'] ?? '', $post_id);
        update_field('hero_text_align', $data['hero']['text_align'] ?? 'bottom', $post_id);
        update_field('hero_image', ! empty($data['hero']['image_id']) ? $data['hero']['image_id'] : '', $post_id);
        update_field('hero_image_url', $data['hero']['image'] ?? '', $post_id);
    }

    // お悩み
    if (isset($data['problems'])) {
        $problems = array();
        foreach ($data['problems'] as $p) {
            $problems[] = array(
                'title'       => $p['title'] ?? '',
                'description' => $p['description'] ?? '',
                'image'       => ! empty($p['image_id']) ? $p['image_id'] : '',
                'image_url'   => $p['image'] ?? '',
            );
        }
        update_field('problems', $problems, $post_id);
    }

    // 解決
    if (isset($data['solutions'])) {
        $solutions = array();
        foreach ($data['solutions'] as $s) {
            $solutions[] = array(
                'label'            => $s['label'] ?? '',
                'message_html'     => $s['message_html'] ?? '',
                'image'            => ! empty($s['image_id']) ? $s['image_id'] : '',
                'image_url'        => $s['image'] ?? '',
                'image_caption'    => $s['image_caption'] ?? '',
                'description_html' => $s['description_html'] ?? '',
            );
        }
        update_post_meta($post_id, 'solutions', $solutions);
    }

    // 理由
    update_field('reasons_title', $data['reasons_title_html'] ?? '', $post_id);
    if (isset($data['reasons'])) {
        $reasons = array();
        foreach ($data['reasons'] as $r) {
            $reasons[] = array(
                'number'      => $r['number'] ?? '',
                'title'       => $r['title'] ?? '',
                'description' => $r['description'] ?? '',
                'image'       => ! empty($r['image_id']) ? $r['image_id'] : '',
                'image_url'   => $r['image'] ?? '',
            );
        }
        update_field('reasons', $reasons, $post_id);
    }

    // 流れ
    update_field('flow_title', $data['flow_title'] ?? '', $post_id);
    if (isset($data['steps'])) {
        update_field('steps', $data['steps'], $post_id);
    }

    // サービス
    update_field('services_title', $data['services_title'] ?? '', $post_id);
    if (isset($data['services'])) {
        $services = array();
        foreach ($data['services'] as $s) {
            $services[] = array(
                'caption'   => $s['caption'] ?? '',
                'layout'    => $s['layout'] ?? 'half',
                'image'     => ! empty($s['image_id']) ? $s['image_id'] : '',
                'image_url' => $s['image'] ?? '',
            );
        }
        update_field('services', $services, $post_id);
    }

    // お問い合わせ
    if (isset($data['contact'])) {
        update_field('contact_title', $data['contact']['title'] ?? '', $post_id);
        update_field('contact_subtitle', $data['contact']['subtitle_html'] ?? '', $post_id);
    }

    // フォーム項目（確認用チェックボックスの自動追加含む）
    if (isset($data['form_fields'])) {
        $fields = $data['form_fields'];
        $has_confirm = false;
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'checkbox') $has_confirm = true;
        }
        if (! $has_confirm) {
            // editor.js / default_data と同一文言に統一
            $fields[] = array(
                'label' => '',
                'type' => 'checkbox',
                'placeholder' => '',
                'required' => true,
                'options' => 'お問い合わせ内容を確認しました',
            );
        }
        update_field('form_fields', $fields, $post_id);
    }

    // フォーム設定
    if (isset($data['form_settings'])) {
        update_field('form_recipient_email', $data['form_settings']['recipient_email'] ?? '', $post_id);
        update_field('form_email_subject', $data['form_settings']['email_subject'] ?? '', $post_id);
        update_field('form_success_message', $data['form_settings']['success_message'] ?? '', $post_id);
        update_field('form_submit_text', $data['form_settings']['submit_text'] ?? '', $post_id);
    }

    // SNS・フッター
    update_field('sns_facebook', $data['sns_facebook'] ?? '', $post_id);
    update_field('sns_twitter', $data['sns_twitter'] ?? '', $post_id);
    update_field('sns_instagram', $data['sns_instagram'] ?? '', $post_id);
    update_field('sns_youtube', $data['sns_youtube'] ?? '', $post_id);
    update_field('sns_tiktok', $data['sns_tiktok'] ?? '', $post_id);
    update_field('footer_tagline', $data['footer']['tagline'] ?? '', $post_id);

    // ボトムバー
    if (isset($data['bottom_bar'])) {
        update_field('bottom_bar_email_label', $data['bottom_bar']['email_label'] ?? '', $post_id);
        update_field('bottom_bar_phone_label', $data['bottom_bar']['phone_label'] ?? '', $post_id);
        update_field('bottom_bar_phone_text', $data['bottom_bar']['phone_text'] ?? '', $post_id);
    }
}

/**
 * HTML生成（プレビュー・公開共通）
 */
function lp_editor_generate_preview_html($data)
{
    $vars = lp_editor_prepare_template_variables($data);
    extract($vars);

    ob_start();
    include get_template_directory() . '/templates/preview.php';
    return ob_get_clean();
}

function lp_editor_generate_public_html($data, $page_id)
{
    $vars = lp_editor_prepare_template_variables($data);
    $vars['page_id'] = $page_id;
    extract($vars);

    ob_start();
    include get_template_directory() . '/templates/public.php';
    return ob_get_clean();
}

/**
 * REST API ハンドラー: アクセスログ（全ページのページ表示・ボタンアクション通知）
 */
function lp_editor_rest_access_log($request)
{
    $data = $request->get_json_params();

    $action_name = sanitize_text_field($data['action'] ?? '');
    $lp_id       = sanitize_text_field($data['lp_id'] ?? '');
    $page_name   = sanitize_text_field($data['page_name'] ?? '');
    $screen_size = sanitize_text_field($data['screen_size'] ?? '');
    $window_size = sanitize_text_field($data['window_size'] ?? '');
    $email       = sanitize_email($data['email'] ?? '');

    if ($action_name === '') {
        return lp_editor_api_error('アクション名は必須です', 400, 'access_log_action_required');
    }

    if (function_exists('lp_editor_send_action_log_mail')) {
        lp_editor_send_action_log_mail($action_name, $lp_id, array(
            'page_name'   => $page_name,
            'screen_size' => $screen_size,
            'window_size' => $window_size,
            'email'       => $email,
        ));
    }

    return lp_editor_api_success(array('message' => 'logged'));
}
