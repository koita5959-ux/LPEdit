<?php

/**
 * Template Name: Terms Page
 *
 * 利用規約ページ
 * 元HTML: lpedit_htmlページ/terms.html
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('service', array(
    'title' => '利用規約',
    'description' => 'LP作成更新サービスの利用規約です。ご利用前に禁止事項・免責事項・お問い合わせ先をご確認ください。',
    'current' => 'terms'
));
?>

<!-- ===== メインコンテンツ ===== -->
<main class="page-content">
    <section class="terms">
        <h1 class="page-title">利用規約</h1>

        <article class="terms-article">
            <h2>サービス内容</h2>
            <p>本サービスは、無料でランディングページ（LP）を作成・公開できるサービスです。</p>
        </article>

        <article class="terms-article">
            <h2>利用条件</h2>
            <p>メールアドレスをお持ちの方であれば、どなたでもご利用いただけます。</p>
        </article>

        <article class="terms-article">
            <h2>禁止事項</h2>
            <p>以下の行為は禁止します。</p>
            <ul>
                <li>公序良俗に反する内容の掲載</li>
                <li>第三者の権利を侵害する行為</li>
                <li>法令に違反する行為</li>
            </ul>
        </article>

        <article class="terms-article">
            <h2>サービスの停止・終了</h2>
            <p>運営の都合により、事前にご連絡のうえ、本サービスを停止または終了する場合があります。</p>
        </article>

        <article class="terms-article">
            <h2>免責事項</h2>
            <p>本サービスの利用により生じた損害について、当社は責任を負いかねます。</p>
        </article>

        <article class="terms-article">
            <h2>お問い合わせ</h2>
            <p>ご不明な点がございましたら、下記までお問い合わせください。</p>
            <p class="terms-contact">
                メール：<a href="mailto:lpeditor@media-house.jp" class="footer-email-link" aria-label="メールを送信"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/email.svg'); ?>" alt="メールアドレス（画像）" class="footer-email-img"></a>
            </p>
        </article>
    </section>
</main>

<?php get_footer('service'); ?>
