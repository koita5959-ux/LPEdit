<?php

/**
 * Contact Form Handling
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * フォーム項目のフィールド名（name属性）を生成
 */
function lp_editor_get_form_field_name($field, $index = 0)
{
    $label = sanitize_title((string)($field['label'] ?? ''));
    return ($label !== '') ? $label : 'field_' . intval($index);
}

/**
 * フォーム項目ラベル（未入力時は型ベースで補完）
 */
function lp_editor_get_form_field_label($field, $index = 0)
{
    $label = sanitize_text_field((string)($field['label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    $type = sanitize_key((string)($field['type'] ?? ''));
    $type_labels = array(
        'text' => 'テキスト',
        'textarea' => 'テキストエリア',
        'email' => 'メール',
        'tel' => '電話',
        'number' => '数値',
        'url' => 'URL',
        'select' => 'セレクト',
        'radio' => 'ラジオ',
        'checkbox' => 'チェック',
    );
    return $type_labels[$type] ?? '項目';
}

/**
 * フォーム通知用の署名ブロック（LP作成者情報ベース）
 */
function lp_editor_get_form_mail_signature_block($company_name, $lp_url, $recipient_email)
{
    $name = sanitize_text_field((string) $company_name);
    if ($name === '') {
        $name = get_bloginfo('name');
    }

    $url = esc_url_raw((string) $lp_url);
    if ($url === '') {
        $url = home_url('/');
    }

    $mail = sanitize_email((string) $recipient_email);

    $signature  = "――――――――――\n";
    $signature .= $name . "\n";
    $signature .= $url . "\n";
    if ($mail !== '') {
        $signature .= $mail . "\n";
    }
    $signature .= "――――――――――\n";

    return $signature;
}

/**
 * お問い合わせフォーム送信のレート制限キー
 */
function lp_editor_contact_rate_limit_key($page_id)
{
    $ip = function_exists('lp_editor_get_client_ip')
        ? lp_editor_get_client_ip()
        : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return 'lp_contact_' . md5($ip . '|' . intval($page_id));
}

/**
 * お問い合わせフォームのPOST処理
 */
function lp_editor_handle_contact_form()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (! isset($_POST['lp_contact_submit'])) return;
    if (! isset($_POST['lp_contact_nonce']) || ! wp_verify_nonce($_POST['lp_contact_nonce'], 'lp_contact_form')) return;

    $page_id = isset($_POST['lp_page_id']) ? intval($_POST['lp_page_id']) : 0;
    if (! $page_id) return;

    $post = get_post($page_id);
    if (! $post || $post->post_type !== 'lp' || $post->post_status !== 'publish') {
        return;
    }

    $cooldown_seconds = intval(defined('LP_EDITOR_RATE_LIMIT_CONTACT_SECONDS') ? LP_EDITOR_RATE_LIMIT_CONTACT_SECONDS : 30);
    $rate_key = lp_editor_contact_rate_limit_key($page_id);
    if (function_exists('lp_editor_rate_limit_remaining_seconds')) {
        $remaining = lp_editor_rate_limit_remaining_seconds($rate_key, $cooldown_seconds);
        if ($remaining > 0) {
            set_transient('lp_contact_error_' . $page_id, '連続送信はできません。' . $remaining . '秒後に再度お試しください。', 60);
            return;
        }
    }

    $recipient_email = sanitize_email((string) get_post_meta($page_id, 'owner_email', true));
    $email_subject   = get_field('form_email_subject', $page_id) ?: '【お問い合わせ】ホームページより';
    $success_message = get_field('form_success_message', $page_id) ?: "お問い合わせありがとうございます。\n内容を確認次第、ご連絡いたします。";
    $company_name    = get_field('company_name', $page_id) ?: get_bloginfo('name');
    $lp_url          = get_permalink($page_id) ?: '';
    $lp_slug         = $post->post_name ?? '';

    if (empty($recipient_email) || ! is_email($recipient_email)) {
        set_transient('lp_contact_error_' . $page_id, '送信先が設定されていません。', 60);
        return;
    }

    $form_fields  = get_field('form_fields', $page_id) ?: array();
    $errors       = array();
    $fields_body  = '';
    $reply_to     = '';

    foreach ($form_fields as $index => $field) {
        $field_label = lp_editor_get_form_field_label($field, $index);
        $name  = lp_editor_get_form_field_name($field, $index);
        $val   = $_POST[$name] ?? '';
        $is_empty = is_array($val)
            ? count(array_filter(array_map('trim', array_map('strval', $val)))) === 0
            : trim((string) $val) === '';
        if (! empty($field['required']) && $is_empty) {
            $errors[] = ($field_label ?: '必須項目') . 'は入力必須です。';
        }
        $display_val = is_array($val) ? implode(', ', array_map('sanitize_text_field', $val)) : sanitize_textarea_field($val);
        $fields_body .= "【{$field_label}】\n{$display_val}\n\n";
        if (($field['type'] ?? '') === 'email' && ! $is_empty) {
            $candidate = sanitize_email(is_array($val) ? (string) reset($val) : (string) $val);
            if (is_email($candidate)) {
                $reply_to = $candidate;
            }
        }
    }

    if (! empty($errors)) {
        set_transient('lp_contact_error_' . $page_id, implode("\n", $errors), 60);
        return;
    }

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    if (! empty($reply_to)) $headers[] = 'Reply-To: ' . $reply_to;

    $owner_body  = "このメールはフォーム送信により自動送信されています。\n\n";
    $owner_body .= "【{$company_name}】お問い合わせがありました。\n\n";
    $owner_body .= $fields_body;
    if (! empty($lp_url)) {
        $owner_body .= "【対象LP】\n";
        $owner_body .= "　ID: " . $lp_slug . "\n";
        $owner_body .= "　URL: " . $lp_url . "\n\n";
    }
    $owner_body .= lp_editor_get_form_mail_signature_block($company_name, $lp_url, $recipient_email);

    if (wp_mail($recipient_email, $email_subject, $owner_body, $headers)) {
        if (function_exists('lp_editor_rate_limit_mark')) {
            lp_editor_rate_limit_mark($rate_key, $cooldown_seconds);
        }
        set_transient('lp_contact_success_' . $page_id, $success_message, 60);
        // 自動返信
        if (! empty($reply_to)) {
            $auto_subject = "【{$company_name}】お問い合わせありがとうございます";
            $auto_body    = "このメールは自動送信です。\n";
            $auto_body   .= "LP Editor が送信を代行しています。\n\n";
            $auto_body   .= "お問い合わせを受け付けました。\n";
            $auto_body   .= "内容を確認のうえ、担当者よりご連絡いたします。\n\n";
            $auto_body   .= "――――――――――\n";
            $auto_body   .= "受付内容\n";
            $auto_body   .= "――――――――――\n";
            $auto_body   .= $fields_body;
            $auto_body   .= lp_editor_get_form_mail_signature_block($company_name, $lp_url, $recipient_email);
            wp_mail($reply_to, $auto_subject, $auto_body, array('Content-Type: text/plain; charset=UTF-8'));
        }
    } else {
        set_transient('lp_contact_error_' . $page_id, 'メール送信に失敗しました。', 60);
    }

    wp_redirect(get_permalink($page_id) . '#contact');
    exit;
}
add_action('template_redirect', 'lp_editor_handle_contact_form');
