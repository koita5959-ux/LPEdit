<?php

/**
 * Access Log - アクセス解析レポートメール送信
 *
 * 全ページのアクセスログ・アクション通知はJavaScript経由でREST APIを叩き、
 * 画面サイズ・表示サイズを含めてメール送信する。
 *
 * @package LP_Editor_Theme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * アクセス解析メールの送信先
 */
define('LP_EDITOR_ACCESS_LOG_TO', 'lpeditor@media-house.jp');

/**
 * ユーザーエージェントからデバイス種別を判定する
 */
function lp_editor_detect_device($ua)
{
    $ua = strtolower((string) $ua);

    if (preg_match('/ipad|android(?!.*mobile)/i', $ua)) {
        return 'タブレット';
    }
    if (preg_match('/iphone|ipod|android.*mobile|windows phone|blackberry|opera mini|opera mobi/i', $ua)) {
        return 'スマホ';
    }
    return 'PC';
}

/**
 * ユーザーエージェントからブラウザ名を判定する
 */
function lp_editor_detect_browser($ua)
{
    $ua = (string) $ua;

    if (preg_match('/Edg\//i', $ua))        return 'Edge';
    if (preg_match('/OPR\//i', $ua))         return 'Opera';
    if (preg_match('/Vivaldi\//i', $ua))     return 'Vivaldi';
    if (preg_match('/YaBrowser\//i', $ua))   return 'Yandex';
    if (preg_match('/SamsungBrowser\//i', $ua)) return 'Samsung';
    if (preg_match('/Chrome\//i', $ua))      return 'Chrome';
    if (preg_match('/Safari\//i', $ua) && ! preg_match('/Chrome/i', $ua)) return 'Safari';
    if (preg_match('/Firefox\//i', $ua))     return 'Firefox';
    if (preg_match('/MSIE|Trident/i', $ua))  return 'IE';
    return '不明';
}

/**
 * ユーザーエージェントからOS名を判定する
 */
function lp_editor_detect_os($ua)
{
    $ua = (string) $ua;

    if (preg_match('/Windows NT 10/i', $ua))  return 'Windows 10/11';
    if (preg_match('/Windows NT/i', $ua))      return 'Windows';
    if (preg_match('/Mac OS X/i', $ua))        return 'macOS';
    if (preg_match('/iPhone|iPad|iPod/i', $ua)) return 'iOS';
    if (preg_match('/Android/i', $ua))         return 'Android';
    if (preg_match('/Linux/i', $ua))           return 'Linux';
    if (preg_match('/CrOS/i', $ua))            return 'ChromeOS';
    return '不明';
}

/**
 * アクセス経路を判定する
 */
function lp_editor_detect_access_route($referer, $has_token)
{
    if ($has_token) {
        return 'Token付きURL（メールからの更新アクセス）';
    }

    if (empty($referer)) {
        return 'ダイレクト（ブックマーク or 直接入力）';
    }

    $site_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
    $ref_host  = strtolower((string) wp_parse_url($referer, PHP_URL_HOST));

    if ($site_host === $ref_host) {
        return '通常遷移（画面内からの操作）';
    }

    return '外部サイトからの流入（' . $referer . '）';
}

/**
 * 現在のテンプレートからページ名を判定する
 */
function lp_editor_get_current_page_name()
{
    if (is_front_page()) {
        return 'トップページ';
    }

    $template = get_page_template_slug();

    if ($template === 'page-guide.php' || is_page('guide')) {
        return '使い方ガイド';
    }
    if ($template === 'page-option.php' || is_page('option')) {
        return 'オプション';
    }
    if ($template === 'page-terms.php' || is_page('terms')) {
        return '利用規約';
    }
    if ($template === 'page-select.php' || is_page('select')) {
        return '作成・編集選択';
    }
    if ($template === 'page-editor.php' || is_page('editor')) {
        return 'Editor画面';
    }
    if (is_singular('lp')) {
        $post = get_queried_object();
        $slug = $post ? $post->post_name : '';
        return '生成LP（' . $slug . '）';
    }

    return null;
}

/**
 * アクセス解析メールの本文を生成する（ページ表示用）
 */
function lp_editor_build_access_mail_body($page_name, $action, $context = array())
{
    $ip        = function_exists('lp_editor_get_client_ip') ? lp_editor_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $referer   = $_SERVER['HTTP_REFERER'] ?? '';
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $has_token = isset($_GET['token']) && $_GET['token'] !== '';
    $datetime  = date('Y-m-d H:i:s');

    $device  = lp_editor_detect_device($ua);
    $browser = lp_editor_detect_browser($ua);
    $os      = lp_editor_detect_os($ua);
    $route   = lp_editor_detect_access_route($referer, $has_token);

    $screen_size  = $context['screen_size'] ?? '(未取得)';
    $window_size  = $context['window_size'] ?? '(未取得)';
    $lp_id        = $context['lp_id'] ?? '';
    $email        = $context['email'] ?? '';

    $request_url    = ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    $accept_lang    = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '(不明)';
    $is_https       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS' : 'HTTP';

    $body  = "ページアクセス通知\n";
    $body .= "━━━━━━━━━━━━━━━━━━━\n\n";
    $body .= "【アクセスURL】\n";
    $body .= "　" . $is_https . '://' . $request_url . "\n\n";
    $body .= "【アクセスページ】\n";
    $body .= "　" . $page_name . "\n\n";
    $body .= "【アクション】\n";
    $body .= "　" . $action . "\n\n";

    if ($lp_id !== '') {
        $body .= "【対象LP】\n";
        $body .= "　ID: " . $lp_id . "\n";

        // owner_email を取得して表示
        $owner_email_value = '';
        if (function_exists('lp_editor_find_lp_by_page_id')) {
            $lp_post = lp_editor_find_lp_by_page_id($lp_id);
            if ($lp_post) {
                $owner_email_value = (string) get_post_meta($lp_post->ID, 'owner_email', true);
            }
        }
        $body .= "　メールアドレス: " . ($owner_email_value !== '' ? $owner_email_value : '（未登録）') . "\n\n";
    }

    $body .= "【アクセス経路】\n";
    $body .= "　" . $route . "\n\n";
    $body .= "【日時】\n";
    $body .= "　" . $datetime . "\n\n";
    $body .= "【アクセス元IP】\n";
    $body .= "　" . $ip . "\n\n";
    $body .= "【リファラー】\n";
    $body .= "　" . ($referer !== '' ? $referer : '(direct)') . "\n\n";
    $body .= "【デバイス】\n";
    $body .= "　" . $device . " / " . $browser . " / " . $os . "\n\n";
    $body .= "【ユーザーエージェント】\n";
    $body .= "　" . ($ua !== '' ? $ua : '(不明)') . "\n\n";
    $body .= "【画面サイズ】\n";
    $body .= "　" . $screen_size . "\n\n";
    $body .= "【表示サイズ】\n";
    $body .= "　" . $window_size . "\n\n";
    $body .= "【言語設定】\n";
    $body .= "　" . $accept_lang . "\n\n";
    $body .= "【接続方式】\n";
    $body .= "　" . $is_https . "\n";

    if ($email !== '') {
        $body .= "\n【送信先メールアドレス】\n";
        $body .= "　" . $email . "\n";
    }

    return $body;
}

/**
 * アクセス解析メールの件名を生成する
 * 基準: 【LPedit】{タイムスタンプ} - {IP} - {アクション名}
 */
function lp_editor_build_access_mail_subject($page_name, $action_name = '')
{
    $ip       = function_exists('lp_editor_get_client_ip') ? lp_editor_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $datetime = date('Y-m-d H:i:s');
    $action   = ($action_name !== '') ? $action_name : $page_name;

    return '【LPedit】' . $datetime . ' - ' . $ip . ' - ' . $action;
}

/**
 * アクセス解析メールを送信する
 */
function lp_editor_send_access_log_mail($page_name, $action = 'ページ表示', $context = array())
{
    $subject = lp_editor_build_access_mail_subject($page_name, $action);
    $body    = lp_editor_build_access_mail_body($page_name, $action, $context);

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail(LP_EDITOR_ACCESS_LOG_TO, $subject, $body, $headers);
}

/**
 * REST API経由のアクセスログ・アクション通知メールを送信する（api.phpから呼び出される）
 */
function lp_editor_send_action_log_mail($action_name, $lp_id = '', $extra = array())
{
    $page_name = $extra['page_name'] ?? 'Editor画面';
    $context = array(
        'lp_id'       => $lp_id,
        'screen_size' => $extra['screen_size'] ?? '(未取得)',
        'window_size' => $extra['window_size'] ?? '(未取得)',
        'email'       => $extra['email'] ?? '',
    );

    lp_editor_send_access_log_mail($page_name, $action_name, $context);
}
