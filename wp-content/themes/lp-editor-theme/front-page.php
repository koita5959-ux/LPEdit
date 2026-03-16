<?php

/**
 * Front Page Template
 *
 * サービスサイトのトップページ
 * 元HTML: lpedit_htmlページ/index.html
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('service', array(
    'title' => '自作LP生成',
    'description' => '指定メールアドレスでテンプレートをカスタマイズして自作LPを作成。メール問い合わせ機能にも対応、アカウント登録不要で公開できます。',
    'current' => 'home'
));

$theme_uri = get_template_directory_uri();
$select_url = home_url('/select/');
?>

<!-- ===== メインコンテンツ ===== -->
<main>

    <!-- ========================================
         スマホ専用ヒーロー
         ======================================== -->
    <section class="hero-sp-wrap">
        <div class="hero-sp-layout">
            <div class="hero-sp-text-top">
                <p class="hero-sp-text-left"><span>テンプレート修正で</span><span>自社LPが</span><span class="u-highlight">かんたん生成公開</span></p>
                <p class="hero-sp-text-right"><span>オリジナル画像で</span><span class="u-highlight">思い通りのLPが</span><span>自分の手で公開</span></p>
            </div>
            <div class="hero-sp-visual">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/hero-sp.png'); ?>" alt="スマートフォンでのLP表示イメージ" class="hero-sp-mockup">
            </div>
            <div class="hero-sp-text-bottom">
                <p><span>登録不要！！</span> <span>更新や複数LPの作成も</span><span class="u-highlight">無料でできる</span></p>
                <p><span>SEO対策もできる高機能で</span><span class="u-highlight">スマホLPには最適仕様</span></p>
                <p><span>期間キャンペーンや</span><span>テストLPに自由に使え</span><span>公開・削除も</span><span class="u-highlight">自由にできます</span></p>
            </div>
        </div>
    </section>

    <!-- ========================================
         PC専用ヒーロー
         ======================================== -->
    <section class="hero-pc-wrap">
        <div class="hero-pc-layout">
            <div class="hero-pc-text">
                <p><span>テンプレート修正で</span><span>自社LPが</span><span class="u-highlight">かんたん生成公開</span></p>
                <p><span>オリジナル画像で</span><span class="u-highlight">思い通りのLPが</span><span>自分の手で公開できる</span></p>
                <p><span>登録不要！！</span> <span>更新や複数LPの作成も</span><span class="u-highlight">無料でできる</span></p>
                <p><span>SEO対策もできる高機能で</span><span class="u-highlight">スマホLPには最適仕様</span></p>
                <p><span>期間キャンペーンや</span><span>テストLPに自由に使え</span><span>公開・削除も</span><span class="u-highlight">自由にできます</span></p>
            </div>
            <div class="hero-pc-visual">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/hero-pc.png'); ?>" alt="PC版 LP作成画面のスクリーンショット" class="hero-pc-mockup-main">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/hero-sp.png'); ?>" alt="スマートフォンでのLP表示イメージ" class="hero-pc-mockup-sp">
            </div>
        </div>
    </section>

    <!-- CTA（白背景エリア） -->
    <section class="hero-cta">
        <div class="hero-cta-buttons">
            <a href="<?php echo esc_url(home_url('/option/')); ?>" class="cta-btn-3d cta-btn-option">
                <span class="cta-btn-3d__label">オプションサービスで</span>
                <span class="cta-btn-3d__main">制作もお任せ</span>
            </a>
            <a href="<?php echo esc_url($select_url); ?>" class="cta-btn-3d cta-btn-create">
                <span class="cta-btn-3d__label">ご自分での登録は無料で</span>
                <span class="cta-btn-3d__main">作成・修正画面へ</span>
            </a>
        </div>
        <p class="hero-scroll hero-scroll--wide">詳しい説明は以下をご覧ください</p>
    </section>

    <!-- ===== テンプレートプレビュー ===== -->
    <section class="template-preview">
        <h2 class="preview-heading">スマホ・PC両用<br class="sp-only">ページが作れる</h2>
        <div class="preview-image-wrap">
            <img src="<?php echo esc_url($theme_uri . '/assets/images/spp01.png'); ?>" alt="テンプレートLPのサンプル画面" class="preview-img">
        </div>
    </section>

    <!-- ===== 利用の流れ ===== -->
    <section class="steps">
        <h2 class="section-title">かんたん4ステップで公開</h2>
        <div class="steps-list">
            <div class="step">
                <div class="step-image">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kt4_01.png'); ?>" alt="STEP1: メールアドレスの入力画面" class="step-img">
                </div>
                <div class="step-body">
                    <span class="step-number">1</span>
                    <h3 class="step-heading">メールアドレスを入力</h3>
                    <p class="step-text">登録不要。メールアドレスだけで始められます。</p>
                </div>
            </div>
            <div class="step-connector" aria-hidden="true"></div>
            <div class="step">
                <div class="step-image">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kt4_02.png'); ?>" alt="STEP2: テンプレートの編集画面" class="step-img">
                </div>
                <div class="step-body">
                    <span class="step-number">2</span>
                    <h3 class="step-heading">テンプレートを編集</h3>
                    <p class="step-text">会社名、サービス内容など、項目を埋めるだけ。</p>
                </div>
            </div>
            <div class="step-connector" aria-hidden="true"></div>
            <div class="step">
                <div class="step-image">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kt4_03.png'); ?>" alt="STEP3: 公開ボタンを押す画面" class="step-img">
                </div>
                <div class="step-body">
                    <span class="step-number">3</span>
                    <h3 class="step-heading">公開ボタンを押す</h3>
                    <p class="step-text">すぐにURLが発行され、LPが公開されます。</p>
                </div>
            </div>
            <div class="step-connector" aria-hidden="true"></div>
            <div class="step">
                <div class="step-image">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kt4_04.png'); ?>" alt="STEP4: 再編集の方法を示す画面" class="step-img">
                </div>
                <div class="step-body">
                    <span class="step-number">4</span>
                    <h3 class="step-heading">公開後の修正・編集がしたいとき</h3>
                    <p class="step-text">メールアドレスを入力するだけ。編集用URLがメールで届きます。ページIDを覚えておく必要はありません。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== こんな方におすすめ ===== -->
    <section class="target">
        <h2 class="section-title">こんな方に選ばれています</h2>
        <p class="section-lead">PC表示・スマホ表示に対応したページでスポット的な業務をアピールできる</p>
        <div class="target-grid">
            <div class="target-card">
                <div class="target-thumb">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kn_01.png'); ?>" alt="キャンペーンLPのイメージ" class="target-thumb__img">
                </div>
                <div class="target-body">
                    <p class="target-heading">キャンペーン用LPが</p>
                    <p class="target-sub">すぐほしい</p>
                </div>
            </div>
            <div class="target-card">
                <div class="target-thumb">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kn_02.png'); ?>" alt="LPを試すイメージ" class="target-thumb__img">
                </div>
                <div class="target-body">
                    <p class="target-heading">まずはLPを</p>
                    <p class="target-sub">試してみたい</p>
                </div>
            </div>
            <div class="target-card">
                <div class="target-thumb">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kn_03.png'); ?>" alt="複数のLPを管理するイメージ" class="target-thumb__img">
                </div>
                <div class="target-body">
                    <p class="target-heading">複数のLPを</p>
                    <p class="target-sub">使い分けたい</p>
                </div>
            </div>
            <div class="target-card">
                <div class="target-thumb">
                    <img src="<?php echo esc_url($theme_uri . '/assets/images/kn_04.png'); ?>" alt="費用をかけずにLP作成するイメージ" class="target-thumb__img">
                </div>
                <div class="target-body">
                    <p class="target-heading">費用をかけずに</p>
                    <p class="target-sub">始めたい</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== よくある質問 ===== -->
    <section class="faq">
        <h2 class="section-title">よくある質問</h2>
        <div class="faq-list">
            <details class="faq-item">
                <summary class="faq-question">本当に無料ですか？</summary>
                <div class="faq-answer"><span class="faq-answer__mark">A</span>
                    <p>はい、作成・公開・更新すべて無料です。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">あとから費用を請求されませんか？</summary>
                <div class="faq-answer"><span class="faq-answer__mark">A</span>
                    <p>一切ありません。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">何ページでも作れますか？</summary>
                <div class="faq-answer"><span class="faq-answer__mark">A</span>
                    <p>はい、制限なく作成できます。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">編集はいつでもできますか？</summary>
                <div class="faq-answer"><span class="faq-answer__mark">A</span>
                    <p>メールアドレスを入力すれば編集URLが届きます。</p>
                </div>
            </details>
        </div>
    </section>

    <!-- ===== CTA（再掲） ===== -->
    <section class="cta-section">
        <p class="cta-section__copy">まずは触ってみてください</p>
        <div class="cta-section__buttons">
            <a href="<?php echo esc_url(home_url('/option/')); ?>" class="cta-bottom-btn cta-bottom-option">
                <span class="cta-bottom-btn__label">オプションサービスで</span>
                <span class="cta-bottom-btn__main">制作もお任せ</span>
            </a>
            <a href="<?php echo esc_url($select_url); ?>" class="cta-bottom-btn cta-bottom-create">
                <span class="cta-bottom-btn__label">ご自分での登録は無料で</span>
                <span class="cta-bottom-btn__main">作成・修正画面へ</span>
            </a>
        </div>
        <p class="cta-section__note">※編集はPCでの操作を推奨しています</p>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ開閉の通知
    document.querySelectorAll('.faq-item').forEach(function(item) {
        item.addEventListener('toggle', function() {
            if (this.open) {
                var question = this.querySelector('.faq-question');
                var text = question ? question.textContent.trim() : '';
                try {
                    fetch('<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-LP-Nonce': '<?php echo esc_js(wp_create_nonce('lp_editor_public_api')); ?>'
                        },
                        body: JSON.stringify({
                            action: 'FAQ開く - ' + text,
                            page_name: 'トップページ',
                            screen_size: screen.width + 'x' + screen.height,
                            window_size: window.innerWidth + 'x' + window.innerHeight
                        })
                    }).catch(function() {});
                } catch (e) {}
            }
        });
    });

    // CTAボタンの通知（3Dボタン + 下部CTA共通）
    function sendTopLog(actionName) {
        try {
            fetch('<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-LP-Nonce': '<?php echo esc_js(wp_create_nonce('lp_editor_public_api')); ?>'
                },
                body: JSON.stringify({
                    action: actionName,
                    page_name: 'トップページ',
                    screen_size: screen.width + 'x' + screen.height,
                    window_size: window.innerWidth + 'x' + window.innerHeight
                })
            }).catch(function() {});
        } catch (e) {}
    }

    var optionBtn = document.querySelector('.cta-btn-option');
    if (optionBtn) {
        optionBtn.addEventListener('click', function() { sendTopLog('オプション・制作お任せ'); });
    }

    var createBtn = document.querySelector('.cta-btn-create');
    if (createBtn) {
        createBtn.addEventListener('click', function() { sendTopLog('作成・修正画面へ'); });
    }

    var bottomOption = document.querySelector('.cta-bottom-option');
    if (bottomOption) {
        bottomOption.addEventListener('click', function() { sendTopLog('オプション・制作お任せ（下部）'); });
    }

    var bottomCreate = document.querySelector('.cta-bottom-create');
    if (bottomCreate) {
        bottomCreate.addEventListener('click', function() { sendTopLog('作成・修正画面へ（下部）'); });
    }
});
</script>

<?php get_footer('service'); ?>
