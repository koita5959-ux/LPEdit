<?php

/**
 * Service Site Footer
 *
 * @package LP_Editor_Theme
 */

$theme_uri = get_template_directory_uri();
$select_url = home_url('/select/');
$guide_url = home_url('/guide/');
$template_url = home_url('/template/');
$option_url = home_url('/option/');
$terms_url = home_url('/terms/');
?>
<footer class="footer">
    <div class="footer-inner">
        <nav class="footer-nav">
            <a href="<?php echo esc_url($guide_url); ?>">使い方ガイド</a>
            <a href="<?php echo esc_url($template_url); ?>" target="_blank" rel="noopener noreferrer">テンプレート</a>
            <a href="<?php echo esc_url($option_url); ?>">オプション</a>
            <a href="<?php echo esc_url($terms_url); ?>">利用規約</a>
            <a href="<?php echo esc_url($select_url); ?>">作成・修正</a>
        </nav>
        <div class="footer-info">
            <p class="footer-company"><a href="https://media-house.jp" target="_blank" rel="noopener noreferrer">株式会社メディアハウス</a></p>
            <p class="footer-address">〒502-0932 岐阜県岐阜市則武中2-16-1</p>
            <p class="footer-contact">
                <span class="footer-badge-wrap"><span class="footer-badge">Tel</span><a href="tel:0582955234">058-295-5234</a></span>
                <span class="footer-badge-wrap"><span class="footer-badge">HP</span><a href="https://media-house.jp" target="_blank" rel="noopener noreferrer">media-house.jp</a></span>
                <span class="footer-badge-wrap"><span class="footer-badge">Mail</span><a href="mailto:lpeditor@media-house.jp" class="footer-email-link" aria-label="メールを送信"><img src="<?php echo esc_url($theme_uri . '/assets/images/email.svg'); ?>" alt="メールアドレス（画像）" class="footer-email-img"></a></span>
            </p>
        </div>
        <p class="footer-copyright">&copy; <?php echo date('Y'); ?> 株式会社メディアハウス</p>
    </div>
</footer>

<script src="<?php echo esc_url(lp_editor_asset_url('assets/js/main.js')); ?>"></script>
<?php
$access_log_page_name = function_exists('lp_editor_get_current_page_name') ? lp_editor_get_current_page_name() : null;
if ($access_log_page_name !== null) :
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var restUrl = '<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>';
    var nonce = '<?php echo esc_js(wp_create_nonce('lp_editor_public_api')); ?>';
    var pageName = '<?php echo esc_js($access_log_page_name); ?>';

    function sendLog(actionName) {
        try {
            fetch(restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-LP-Nonce': nonce
                },
                body: JSON.stringify({
                    action: actionName,
                    page_name: pageName,
                    screen_size: screen.width + 'x' + screen.height,
                    window_size: window.innerWidth + 'x' + window.innerHeight
                })
            }).catch(function() {});
        } catch (e) {}
    }

    // ページ表示通知を送信
    if (pageName === 'トップページ') {
        sendLog('トップページ表示');
    } else if (pageName === '作成・編集選択') {
        sendLog('作成・修正選択ページ表示');
    } else if (pageName === 'エディター編集・登録ページ') {
        sendLog('エディター編集・登録ページ表示');
    }

    // フッター: Tel・HP・Mailリンク
    document.querySelectorAll('.footer-contact a').forEach(function(a) {
        a.addEventListener('click', function() {
            var href = this.getAttribute('href') || '';
            if (href.indexOf('tel:') === 0) {
                sendLog('フッター・Tel');
            } else if (href.indexOf('mailto:') === 0) {
                sendLog('フッター・Mail');
            } else if (href.indexOf('media-house.jp') !== -1) {
                sendLog('フッター・HP');
            }
        });
    });

    // フッター: ナビリンク
    document.querySelectorAll('.footer-nav a').forEach(function(a) {
        a.addEventListener('click', function() {
            sendLog('フッター・' + this.textContent.trim());
        });
    });

    // ヘッダー: ナビリンク
    document.querySelectorAll('.header-nav a').forEach(function(a) {
        a.addEventListener('click', function() {
            sendLog('ヘッダー・' + this.textContent.trim());
        });
    });

    // フッター: 株式会社メディアハウス（社名リンク）
    var companyLink = document.querySelector('.footer-company a');
    if (companyLink) {
        companyLink.addEventListener('click', function() {
            sendLog('フッター・メディアハウス');
        });
    }

    // ヘッダー: ブランドロゴ
    var brandLink = document.querySelector('.header-brand a');
    if (brandLink) {
        brandLink.addEventListener('click', function() {
            sendLog('ヘッダー・ロゴ');
        });
    }
});
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>

</html>
