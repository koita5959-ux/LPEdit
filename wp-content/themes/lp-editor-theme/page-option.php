<?php
/**
 * Template Name: Option Page
 *
 * オプションページ（Stripe決済機能付き）
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('service', array(
    'title' => 'オプション',
    'description' => 'LP Editorのオプションサービス。プロの目線でLP制作を代行します。',
    'current' => 'option'
));
?>

<!-- ===== メインコンテンツ ===== -->
<main class="page-content">
    <section class="guide-section">
        <h1 class="page-title">オプション</h1>
        <h3>テンプレートの原稿をいただければ制作を承ります</h3>
        <p class="guide-section__lead">原稿をいただき、各セクションの文字と構成を相談し画像の生成のお手伝いも致します。（別途製作費必要）</p>
    </section>

    <section class="option-container">

        <!-- 左ブロック: サービス説明 -->
        <div class="option-info">
            <h2 class="option-info__title">制作のワークフロー</h2>
            <p class="option-info__desc">テンプレートを基本にページ内容をプロの目線で作成し、<br>有効なサイト制作をご提案します</p>
            <p class="option-info__price">費用固定の<br>便利なサービス <span class="option-info__price-amount">10,000</span><span class="option-info__price-unit">円</span><span class="option-info__price-tax">（税込み）</span></p>

            <div class="option-workflow">
                <div class="option-workflow__step">
                    <div class="option-workflow__step-box">
                        テンプレートのセッション（内容）を<br>基に制作指示をいただきます
                    </div>
                </div>
                <div class="option-workflow__arrow">&#9660;</div>
                <div class="option-workflow__step">
                    <div class="option-workflow__step-box">
                        セッションブロックの企画<br>メール打ち合わせで内容を確認
                    </div>
                    <p class="option-workflow__note">必要に応じて、生成画像をご提案します</p>
                </div>
                <div class="option-workflow__arrow">&#9660;</div>
                <div class="option-workflow__step">
                    <div class="option-workflow__step-box">
                        HTMLページ確認<br>メールや非公開データで確認をお願いします
                    </div>
                    <p class="option-workflow__note">修正確認は、4回までで修正をお受けします</p>
                </div>
                <div class="option-workflow__arrow">&#9660;</div>
                <div class="option-workflow__step">
                    <div class="option-workflow__step-box">
                        確認完了で公開<br>IDをご連絡するので以降の修正は<br>クライアント様で行ってください
                    </div>
                </div>
            </div>

            <p class="option-info__notice">規格制作になりますが、ご質問やご意見は、お気軽にお問い合わせください</p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="option-info__contact-link">お問い合わせ</a>
        </div>

        <!-- 右ブロック: 決済部 -->
        <div class="option-payment">

            <!-- ステップ1: メアド入力 -->
            <div id="step-1" class="payment-step">
                <h3 class="payment-step__title">オプションを決済</h3>
                <p class="payment-step__price">10,000<span class="payment-step__price-unit">円</span><span class="payment-step__price-tax">（税込み）</span></p>
                <label for="input-email" class="payment-step__label">メールアドレス</label>
                <input type="email" id="input-email" class="payment-step__input" placeholder="example@mail.com" required>
                <p id="email-error" class="payment-step__error"></p>
                <button id="btn-to-payment" class="payment-step__btn">決済に進む</button>
            </div>

            <!-- ステップ2: 決済フォーム -->
            <div id="step-2" class="payment-step" style="display:none;">
                <h3 class="payment-step__title">お支払い</h3>
                <p class="payment-step__email-display">
                    <span id="display-email"></span>
                    <a href="#" id="btn-change-email" class="payment-step__change-link">変更</a>
                </p>
                <div id="payment-element"></div>
                <button id="btn-pay" class="payment-step__btn payment-step__btn--pay">10,000円を支払う</button>
                <p id="payment-error" class="payment-step__error"></p>
            </div>

            <!-- ステップ3: 完了 -->
            <div id="step-3" class="payment-step" style="display:none;">
                <div class="payment-step__complete">
                    <span class="payment-step__complete-icon">&#10003;</span>
                    <h3 class="payment-step__title">ご発注ありがとうございます</h3>
                    <p>ご登録のメールアドレスに確認メールをお送りしました。</p>
                    <p>担当者より2営業日以内にご連絡いたします。</p>
                </div>
            </div>

        </div>

    </section>
</main>

<!-- Stripe JS（このページでのみ読み込み） -->
<script src="https://js.stripe.com/v3/"></script>
<script src="<?php echo esc_url(lp_editor_asset_url('assets/js/option-payment.js')); ?>"></script>

<?php get_footer('service'); ?>
