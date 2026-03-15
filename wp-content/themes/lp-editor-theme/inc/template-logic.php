<?php

/**
 * Template LP Logic
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * テンプレートLPを取得
 */
function lp_editor_get_template_lp()
{
    $args = array(
        'post_type'   => 'page',
        'name'        => 'template',
        'post_status' => 'publish',
        'posts_per_page' => 1,
    );
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * テンプレートLPが存在しない場合に作成
 */
function lp_editor_create_template_lp()
{
    if (lp_editor_get_template_lp()) return;

    $post_id = wp_insert_post(array(
        'post_title'  => 'LPテンプレート（編集禁止）',
        'post_name'   => 'template',
        'post_type'   => 'page',
        'post_status' => 'publish',
    ));

    if (! is_wp_error($post_id)) {
        // templateページは公開LPテンプレートを使用する
        update_post_meta($post_id, '_wp_page_template', 'page-lp.php');
        lp_editor_save_acf_data($post_id, lp_editor_get_default_data());
    }
}

/**
 * デフォルトのテンプレートデータ (3dc3db6 をベースとした完成状態)
 */
function lp_editor_get_default_data()
{
    $theme_uri = get_template_directory_uri();

    return array(
        'company_name' => 'スマイル電気工事',
        'header_icon' => 'bolt',
        'header_icon_type' => 'material',
        'header_icon_image' => '',
        'phone' => '0120-XXX-XXX',
        'company_email' => '',
        'website_url' => '',
        'address' => '東京都渋谷区○○1-2-3',
        'business_hours' => '9:00〜18:00',
        'business_hours_full' => '平日 9:00〜18:00 / 土曜 9:00〜15:00 / 日祝休み',
        'copyright_year' => date('Y'),
        'color_primary' => '#2563EB',
        'color_secondary' => '#34D399',
        'common_settings' => array(
            'cta_long' => '無料見積もりを依頼する',
            'cta_short' => 'お問い合わせ',
            'phone_guidance' => 'お電話でのご相談',
        ),
        'hero' => array(
            'badge' => '地域密着20年の実績',
            'headline_html' => '電気のトラブル<br><span class="text-secondary">最短30分</span>で駆けつけます',
            'subtext_html' => 'コンセント修理から大規模工事まで<br>お気軽にご相談ください',
            'cta_text' => '無料見積もりを依頼する',
            'image' => $theme_uri . '/assets/images/hero-main.png',
            'image_id' => 0,
            'text_align' => 'bottom',
        ),
        'problems_title' => '',
        'problems' => array(
            array('title' => 'ブレーカーが頻繁に落ちる', 'description' => '電気の使いすぎ？それとも配線の問題？原因がわからず困っている', 'image' => $theme_uri . '/assets/images/problem-old-panel.png', 'image_id' => 0),
            array('title' => 'コンセントが焦げ臭い', 'description' => '火災が心配…すぐに見てもらいたいけど、どこに頼めばいいかわからない', 'image' => $theme_uri . '/assets/images/problem-dark-hallway.png', 'image_id' => 0),
            array('title' => '照明がチカチカする', 'description' => '電球を替えても直らない。配線の問題かもしれない', 'image' => $theme_uri . '/assets/images/detail-tester.png', 'image_id' => 0),
        ),
        'solutions' => array(
            array(
                'label' => '安全',
                'message_html' => 'そのお悩み<br><span class="text-secondary">スマイル電気工事</span>が解決します！',
                'image' => $theme_uri . '/assets/images/solution-team.png',
                'image_id' => 0,
                'image_caption' => '有資格者が丁寧に対応',
                'description_html' => '第一種電気工事士の資格を持つベテランスタッフが、お客様のお悩みに迅速・丁寧に対応いたします。',
            ),
            array(
                'label' => '便利',
                'message_html' => '<span class="text-secondary">省電力</span>と<span class="text-secondary">コスト</span><br>古い機器の見直しノウハウ',
                'image' => $theme_uri . '/assets/images/detail-hv-equipment.png',
                'image_id' => 0,
                'image_caption' => '設備・施工に対応',
                'description_html' => '安全面も考慮した機器の改修は長期的なコスト面でも有効です。',
            ),
        ),
        'reasons_title_html' => '<span class="text-secondary">スマイル電気工事</span>が<br>選ばれる理由',
        'reasons_cta' => '今すぐ相談する',
        'reasons' => array(
            array('number' => '01', 'title' => '安心の保証制度', 'description' => '施工後1年間の無料保証付き。万が一の際も迅速に対応します。', 'image' => $theme_uri . '/assets/images/reason03-handshake.png', 'image_id' => 0),
            array('number' => '02', 'title' => '地域最安値に挑戦', 'description' => '他社様のお見積もりをお持ちください。可能な限り対応いたします。', 'image' => $theme_uri . '/assets/images/reason01-qualified.png', 'image_id' => 0),
        ),
        'flow_title' => 'ご依頼の流れ',
        'steps' => array(
            array('title' => 'まずはお問い合わせ', 'description' => 'お電話またはメールにてお気軽にご連絡ください'),
            array('title' => '現地調査・打ち合わせ', 'description' => 'ご依頼内容の確認と現地調査を兼ねて、お打ち合わせに伺います'),
            array('title' => 'ご契約', 'description' => '月契約・年間契約のほか、スポット対応まで、お客様のニーズに合わせた契約形態をご用意しています'),
            array('title' => '保守メンテ開始', 'description' => '契約内容に基づく定期対応に加え、随時のメンテナンスやお困りごとにも対応いたします。消耗品の在庫管理や機器の更新計画などもご相談ください'),
        ),
        'services_title' => 'サービス例',
        'services' => array(
            array('caption' => '分電盤交換', 'image' => $theme_uri . '/assets/images/case01-panel.png', 'image_id' => 0, 'layout' => 'full'),
            array('caption' => 'チーム施工', 'image' => $theme_uri . '/assets/images/case02-team.png', 'image_id' => 0, 'layout' => 'half'),
            array('caption' => 'お打ち合わせ', 'image' => $theme_uri . '/assets/images/case03-meeting.png', 'image_id' => 0, 'layout' => 'half'),
            array('caption' => '高圧設備', 'image' => $theme_uri . '/assets/images/detail-hv-equipment.png', 'image_id' => 0, 'layout' => 'half'),
            array('caption' => 'エアコン工事', 'image' => $theme_uri . '/assets/images/case05-aircon.png', 'image_id' => 0, 'layout' => 'half'),
        ),
        'contact' => array(
            'title' => 'お問い合わせ',
            'subtitle_html' => '24時間受付中！お気軽にご相談ください',
            'phone_guidance' => 'お急ぎの方はお電話で',
        ),
        'form_fields' => array(
            array('label' => 'お名前', 'type' => 'text', 'placeholder' => '例：山田 太郎', 'required' => true, 'options' => ''),
            array('label' => 'メールアドレス', 'type' => 'email', 'placeholder' => '例：info@example.com', 'required' => true, 'options' => ''),
            array('label' => '電話番号', 'type' => 'tel', 'placeholder' => '例：090-1234-5678', 'required' => true, 'options' => ''),
            array('label' => 'ご住所', 'type' => 'text', 'placeholder' => '例：東京都渋谷区○○1-2-3', 'required' => false, 'options' => ''),
            array('label' => 'ご相談内容', 'type' => 'select', 'placeholder' => '', 'required' => true, 'options' => "保守契約などの相談\n修繕・改修の補修工事\nブレーカーや配電盤\n照明・配線スイッチ\nエアコンや空調\nその他の相談"),
            array('label' => '詳細・ご要望', 'type' => 'textarea', 'placeholder' => '症状や状況を詳しくお書きください', 'required' => false, 'options' => ''),
            array('label' => '', 'type' => 'checkbox', 'placeholder' => '', 'required' => true, 'options' => "お問い合わせ内容を確認しました"),
        ),
        'form_settings' => array(
            'recipient_email' => '',
            'email_subject' => '【お問い合わせ】ホームページより',
            'success_message' => "お問い合わせありがとうございます。\n内容を確認次第、ご連絡いたします。",
            'submit_text' => '送信する',
        ),
    );
}
