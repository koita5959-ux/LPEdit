<?php

/**
 * Service Site Footer
 *
 * @package LP_Editor_Theme
 */

$theme_uri = get_template_directory_uri();
$select_url = home_url('/select/');
$guide_url = home_url('/guide/');
$terms_url = home_url('/terms/');
?>
<footer class="footer">
    <div class="footer-inner">
        <nav class="footer-nav">
            <a href="<?php echo esc_url($guide_url); ?>">使い方ガイド</a>
            <a href="<?php echo esc_url($terms_url); ?>">利用規約</a>
            <a href="<?php echo esc_url($select_url); ?>">作成・修正</a>
        </nav>
        <div class="footer-info">
            <p class="footer-company">株式会社メディアハウス</p>
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
    try {
        fetch('<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LP-Nonce': '<?php echo esc_js(wp_create_nonce('lp_editor_public_api')); ?>'
            },
            body: JSON.stringify({
                action: 'ページ表示',
                page_name: '<?php echo esc_js($access_log_page_name); ?>',
                screen_size: screen.width + 'x' + screen.height,
                window_size: window.innerWidth + 'x' + window.innerHeight
            })
        }).catch(function() {});
    } catch (e) {}
});
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>

</html>
