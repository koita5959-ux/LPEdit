<?php

/**
 * Template Name: Guide Page
 *
 * 使い方ガイドページ
 * 元HTML: lpedit_htmlページ/guide.html
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('service', array(
    'title' => '使い方ガイド',
    'description' => 'LP作成から公開・更新までの手順を解説。画像準備や各入力項目の使い方をステップ形式で確認できます。',
    'current' => 'guide'
));

$theme_uri = get_template_directory_uri();
?>

<!-- ===== メインコンテンツ ===== -->
<main class="page-content">

    <!-- リード -->
    <section class="guide-intro">
        <h1 class="page-title">使い方ガイド</h1>
        <p class="guide-intro__lead">このページでは、LP作成から公開までの流れと、各入力項目について説明します。<br>はじめての方でも迷わず進められるよう、ステップごとにまとめました。</p>
    </section>

    <!-- ===== 基本情報のご準備 ===== -->
    <section class="guide-section">
        <h2 class="guide-section__title">はじめに ─ 基本情報のご準備</h2>
        <p class="guide-section__lead">LP作成には、会社名、所在地、電話番号、メールアドレスなどの基本情報の入力が必要です。入力された情報は、LPの各セクションへ自動的に連動されます。あらかじめ情報を整理しておくと、スムーズに作成を進められます。</p>
    </section>

    <!-- ===== LP作成の流れ ===== -->
    <section class="guide-section">
        <h2 class="guide-section__title">LP作成の流れ</h2>

        <!-- STEP1: 画像左（3）テキスト右（7） -->
        <div class="guide-row">
            <div class="guide-row__img">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/gu01.png'); ?>" alt="STEP1 作成・修正画面" class="guide-row__screenshot">
            </div>
            <div class="guide-row__text">
                <h3 class="guide-row__heading">STEP 1：作成・修正ボタンから編集画面へ</h3>
                <p>「作成 / 修正」ボタンから編集画面に進みます。</p>
                <p>すでに登録済みのLPは、メールアドレスへのメールでアクセスURLが発行されます。お問い合わせに使用するメールアドレスが一意のキーとなり、更新時のセキュリティにも対応しています。</p>
                <p>アカウント登録は不要です。公開する LP に掲載するメールアドレスが、そのままキーになります。</p>
                <p>事前にテンプレートのサンプルページをご覧いただき、登録する内容や差し替え画像の準備をしておくとスムーズに進められます。</p>
            </div>
        </div>

        <!-- STEP2: テキスト左（7）画像右（3） -->
        <div class="guide-row guide-row--reverse">
            <div class="guide-row__img">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/gu02.png'); ?>" alt="STEP2 編集画面" class="guide-row__screenshot">
            </div>
            <div class="guide-row__text">
                <h3 class="guide-row__heading">STEP 2：各項目の編集</h3>
                <p>LP Edit画面では、右列に表示される編集項目をそれぞれ変更が可能です。</p>
                <p>画像は事前にご準備いただけると作業がスムーズです。サンプルページのテンプレートをご確認のうえ、流用も含めて差し替える画像をご用意ください。</p>
                <p>公開後もLP Edit画面を表示中であれば、そのまま続けて変更・更新が可能です。LP Edit画面を離れた場合は、公開メールアドレスへアクセスURLが届き、そのURLアクセスで更新作業に戻れます。</p>
            </div>
        </div>

        <!-- STEP3: 画像左（3）テキスト右（7） -->
        <div class="guide-row">
            <div class="guide-row__img">
                <img src="<?php echo esc_url($theme_uri . '/assets/images/gu03.png'); ?>" alt="STEP3 プレビューと公開" class="guide-row__screenshot">
            </div>
            <div class="guide-row__text">
                <h3 class="guide-row__heading">STEP 3：確認プレビューと公開</h3>
                <p>登録がそのまま公開となります。必ず「プレビュー」機能で内容をご確認のうえ、「保存して公開」へお進みください。</p>
            </div>
        </div>

    </section>

    <!-- ===== 事前にご準備いただくもの ===== -->
    <section class="guide-section">
        <h2 class="guide-section__title">事前にご準備いただくもの</h2>
        <p class="guide-section__lead">以下をあらかじめご用意いただくと、スムーズに登録・公開まで進められます。</p>
        <ul class="guide-notes">
            <li>テンプレートに差し替えるための画像（事前にトリミング済みのもの）</li>
            <li>会社名・住所・電話番号・メールアドレス</li>
            <li>会社紹介を利用する場合は、ホームページアドレス</li>
            <li>タイトルとサブコピー</li>
        </ul>
    </section>

    <!-- ===== 各セクションの入力項目 ===== -->
    <section class="guide-section">
        <h2 class="guide-section__title">各セクションの入力項目</h2>
        <p class="guide-section__lead">テンプレートは複数のセクションで構成されています。上から順に入力していけば、1枚のLPが完成します。セクションタイトルの変更も可能なので、サンプルの構成にとらわれず自由にカスタマイズできます。</p>
        <ul class="guide-notes">
            <li>コーナータイトルの変更や、セクション項目の追加・削除が自由にできます</li>
            <li>使用する画像は、事前に適切なサイズにトリミングしてからアップロードしてください</li>
            <li>配色は2色まで指定できます</li>
            <li>お問い合わせフォームの項目は、コンテンツの内容に合わせて確認・調整が必要です</li>
        </ul>
    </section>

</main>

<?php get_footer('service'); ?>
