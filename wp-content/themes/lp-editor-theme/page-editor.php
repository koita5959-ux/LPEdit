<?php

/**
 * Template Name: LP Editor（編集画面）
 * Template Post Type: page
 *
 * リアルタイムプレビュー付きのLP編集画面
 * ログイン不要で誰でもアクセス可能
 * 保存時に新規LPページを作成、または既存ページを更新
 *
 * @package LP_Editor_Theme
 */

// 編集モード判定とトークン認証
$edit_page_id = 0;       // WordPress内部ID
$edit_page_slug = '';    // ページID（123abなど）
$edit_token = '';
$is_edit_mode = false;
$token_error = '';
$is_template_edit = false;

$editor_css_url = lp_editor_asset_url('assets/css/editor.css');
$editor_js_url  = lp_editor_asset_url('assets/js/editor.js');
$editor_css_path = get_template_directory() . '/assets/css/editor.css';
$editor_js_path = get_template_directory() . '/assets/js/editor.js';
$editor_css_ver = file_exists($editor_css_path) ? filemtime($editor_css_path) : null;
$editor_js_ver = file_exists($editor_js_path) ? filemtime($editor_js_path) : null;

// ?edit=ページID&token=トークン パラメータがある場合は編集モード
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_page_slug = sanitize_text_field($_GET['edit']);
    $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    $edit_token = $token;
    $is_created = isset($_GET['created']) && $_GET['created'] === '1';

    // ページIDでLPを検索
    $edit_lp = lp_editor_find_lp_by_page_id($edit_page_slug);

    if ($edit_lp) {
        // 開発モード かつ created=1 の場合はトークン認証をスキップ
        $is_dev = lp_editor_is_dev_environment();

        if ($is_dev && $is_created) {
            // 開発モード：トークン検証スキップで直接編集可能
            $is_edit_mode = true;
            $edit_page_id = $edit_lp->ID;
            $is_template_edit = (strtolower($edit_page_slug) === 'template');
            $editor_data = lp_editor_get_acf_data($edit_page_id, 'editor');
        } else {
            // 通常モード：トークン検証
            $token_result = lp_editor_verify_token($edit_lp->ID, $token);

            if ($token_result['valid']) {
                // トークンを消費（使用済みにする）
                lp_editor_consume_token($edit_lp->ID);

                $is_edit_mode = true;
                $edit_page_id = $edit_lp->ID;
                $is_template_edit = (strtolower($edit_page_slug) === 'template');
                $editor_data = lp_editor_get_acf_data($edit_page_id, 'editor');
                // owner_emailをcompany_emailとして設定（編集画面表示用）
                $owner_email = get_post_meta($edit_page_id, 'owner_email', true);
                if (!empty($owner_email)) {
                    $editor_data['company_email'] = $owner_email;
                }
            } else {
                // トークンエラー
                $token_error = $token_result['message'];
            }
        }
    } else {
        $token_error = 'ページが見つかりません';
    }
}

// 新規作成モード（ホーム表示）
if (!$is_edit_mode && empty($token_error)) {
    // テンプレートLP（固定ページ: template）からデータを取得
    $template_lp = lp_editor_get_template_lp();

    if ($template_lp) {
        $editor_data = lp_editor_get_acf_data($template_lp->ID, 'editor');
        $editor_data = wp_parse_args($editor_data, lp_editor_get_default_data());
        // 新規作成時はメールアドレスをクリア（必須入力させる）
        $editor_data['company_email'] = '';
    } else {
        $editor_data = lp_editor_get_default_data();
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LP Editor - <?php echo $is_edit_mode ? esc_html(get_the_title($edit_page_id)) : '新規作成'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url(add_query_arg('v', $editor_css_ver, $editor_css_url)); ?>">
</head>

<body>
    <?php if (!empty($token_error)): ?>
        <!-- トークンエラー表示 -->
        <div class="token-error-overlay">
            <div class="token-error-modal">
                <span class="material-icons error-icon">error_outline</span>
                <h2>アクセスエラー</h2>
                <p><?php echo esc_html($token_error); ?></p>
                <div class="token-error-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                        ホームに戻る
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- デフォルトデータ（非表示） -->
    <?php if (empty($token_error)): ?>
        <script type="application/json" id="default-data">
            <?php echo wp_json_encode($editor_data); ?>
        </script>
    <?php endif; ?>
    <script type="application/json" id="wp-config">
        <?php echo wp_json_encode(array(
            'restUrl' => rest_url('lp-editor/v1/'),
            'nonce' => wp_create_nonce('lp_editor_public_api'),
            'lpPageId' => $edit_page_id,
            'lpPageSlug' => $edit_page_slug,
            'isEditMode' => $is_edit_mode,
            'isTemplateEdit' => $is_template_edit,
            'previewUrl' => $is_edit_mode ? home_url('/' . $edit_page_slug . '/') : '',
            'pageTitle' => $is_edit_mode ? get_the_title($edit_page_id) : '',
            'uploadUrl' => rest_url('wp/v2/media'),
            'siteUrl' => get_permalink(),
            'editToken' => $edit_token,
            'tokenError' => $token_error,
        )); ?>
    </script>

    <!-- ヘッダー -->
    <header class="editor-header">
        <h1>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
                <span class="material-icons icon">edit_note</span>
                LP Editor
            </a>
            <span class="page-title" id="header-title"></span>
        </h1>
        <div class="header-actions">
            <div class="header-actions-main">
                <?php if (!$is_edit_mode): ?>
                    <button class="btn btn-outline" onclick="resetToDefault()" title="初期状態に戻す">
                        <span class="material-icons" style="font-size:16px">refresh</span>
                        初期化（復元）
                    </button>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="saveData()">
                    <span class="material-icons" style="font-size:16px">save</span>
                    保存して公開
                </button>
            </div>
            <a href="#" id="view-page-btn" class="btn btn-success" style="display:none" target="_blank">
                <span class="material-icons" style="font-size:16px">open_in_new</span>
                公開ページを見る
            </a>
        </div>
    </header>

    <!-- メインレイアウト -->
    <div class="editor-main">

        <!-- 左: プレビュー -->
        <div class="preview-pane">
            <div class="preview-label">📱 プレビュー（390px）</div>
            <div class="preview-frame">
                <iframe id="preview-iframe" title="プレビュー"></iframe>
            </div>
        </div>

        <!-- 右: 編集パネル -->
        <div class="edit-pane">

            <!-- 基本情報 -->
            <div class="section open">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">business</span> 基本情報</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>会社名 <span class="required">*</span> <span class="char-count" data-for="company_name"></span></label>
                        <input type="text" id="company_name" maxlength="50" data-maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label>住所 <span class="required">*</span> <span class="char-count" data-for="address"></span></label>
                        <input type="text" id="address" maxlength="100" data-maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label>電話番号 <span class="required">*</span></label>
                        <input type="tel" id="phone" maxlength="20" pattern="[\d\-\(\)\s]+" data-validate="phone" placeholder="0120-XXX-XXX" required>
                        <small class="form-hint">電話番号は数字とハイフンのみ使用できます</small>
                    </div>
                    <div class="form-group">
                        <label>メールアドレス <span class="required">*</span></label>
                        <input type="email" id="company_email" placeholder="info@example.com" data-validate="email" required>
                        <small class="form-hint">お問い合わせの受信先として使用されます</small>
                    </div>
                    <div class="form-group">
                        <label>公式HP</label>
                        <input type="url" id="website_url" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label>ヘッダーアイコン</label>
                        <div class="icon-picker-wrapper">
                            <input type="hidden" id="header_icon">
                            <input type="hidden" id="header_icon_type" value="material">
                            <input type="hidden" id="header_icon_image">
                            <div class="icon-preview" id="header_icon_preview">
                                <span class="material-icons">bolt</span>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openIconPicker('header_icon')">
                                アイコンを選択
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>営業時間（短）</label>
                        <input type="text" id="business_hours">
                    </div>
                    <div class="form-group">
                        <label>営業時間（詳細）</label>
                        <input type="text" id="business_hours_full">
                    </div>
                    <div class="form-group">
                        <label>コピーライト年</label>
                        <input type="text" id="copyright_year">
                    </div>
                </div>
            </div>

            <!-- 共通設定 -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">tune</span> 共通設定</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>Primary カラー</label>
                        <div class="color-row">
                            <input type="color" id="color_primary" oninput="syncColor('color_primary','color_primary_text')">
                            <input type="text" id="color_primary_text" oninput="syncColor('color_primary_text','color_primary')">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Secondary カラー</label>
                        <div class="color-row">
                            <input type="color" id="color_secondary" oninput="syncColor('color_secondary','color_secondary_text')">
                            <input type="text" id="color_secondary_text" oninput="syncColor('color_secondary_text','color_secondary')">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>メールCTA（長） <span class="char-count" data-for="common_cta_long"></span></label>
                        <input type="text" id="common_cta_long" maxlength="30" data-maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>メールCTA（短） <span class="char-count" data-for="common_cta_short"></span></label>
                        <input type="text" id="common_cta_short" maxlength="20" data-maxlength="20">
                    </div>
                    <div class="form-group">
                        <label>電話CTA <span class="char-count" data-for="bottom_bar_phone_text"></span></label>
                        <input type="text" id="bottom_bar_phone_text" maxlength="20" data-maxlength="20">
                    </div>
                </div>
            </div>

            <!-- ヒーロー（トップ） -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">image</span> ヒーロー（トップ）</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>画像</label>
                        <input type="hidden" id="hero_image_id">
                        <input type="hidden" id="hero_image">
                        <div class="image-upload-row">
                            <div class="image-upload" style="margin-top:6px">
                                <input type="file" accept="image/*" onchange="uploadImage(this, 'hero_image', 'hero_image_id')">
                                <div class="placeholder">
                                    <span class="material-icons icon">cloud_upload</span>
                                    画像を選択またはドロップ
                                </div>
                                <img class="image-preview" id="hero_image_preview" style="display:none" alt="プレビュー">
                            </div>
                            <p class="image-recommend">推奨:<br>1920x1080（16:9）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>バッジテキスト <span class="char-count" data-for="hero_badge"></span></label>
                        <input type="text" id="hero_badge" maxlength="30" data-maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>メインコピー <span class="color-hint">※選択した配色と連動</span></label>
                        <textarea id="hero_headline_html" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>サブコピー</label>
                        <textarea id="hero_subtext_html" rows="2"></textarea>
                    </div>
                    <!-- テキスト配置オプション（トル指示によりコメントアウト）
                    <div class="form-group">
                        <label>テキスト配置</label>
                        <div class="align-picker" id="hero_align_picker">
                            <button type="button" class="align-btn" data-value="top" onclick="setHeroAlign('top')" title="上揃え">
                                <span class="material-icons">vertical_align_top</span>
                            </button>
                            <button type="button" class="align-btn" data-value="center" onclick="setHeroAlign('center')" title="中央揃え">
                                <span class="material-icons">vertical_align_center</span>
                            </button>
                            <button type="button" class="align-btn active" data-value="bottom" onclick="setHeroAlign('bottom')" title="下揃え">
                                <span class="material-icons">vertical_align_bottom</span>
                            </button>
                        </div>
                        <input type="hidden" id="hero_text_align" value="bottom">
                    </div>
                    -->
                    <input type="hidden" id="hero_text_align" value="bottom">
                </div>
            </div>

            <!-- ポイント紹介セクション -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">help_outline</span> ポイント紹介</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>セクション見出し</label>
                        <input type="text" id="problems_title">
                    </div>
                    <div id="problems-list"></div>
                    <button class="btn-add" onclick="addProblem()">
                        <span class="material-icons" style="font-size:16px">add</span> ポイントを追加
                    </button>
                </div>
            </div>

            <!-- 解決セクション -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">check_circle</span> 解決</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <h4 style="margin:0 0 8px">カード1</h4>
                    <div class="form-group">
                        <label>ラベル</label>
                        <input type="text" id="solution_1_label">
                    </div>
                    <div class="form-group">
                        <label>メッセージ</label>
                        <textarea id="solution_1_message_html" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>画像</label>
                        <input type="hidden" id="solution_1_image_id">
                        <input type="hidden" id="solution_1_image">
                        <div class="image-upload-row">
                            <div class="image-upload" style="margin-top:6px">
                                <input type="file" accept="image/*" onchange="uploadImage(this, 'solution_1_image', 'solution_1_image_id')">
                                <div class="placeholder">
                                    <span class="material-icons icon">cloud_upload</span>
                                    画像を選択
                                </div>
                                <img class="image-preview" id="solution_1_image_preview" style="display:none" alt="プレビュー">
                            </div>
                            <p class="image-recommend">推奨:<br>1600x900（16:9）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>画像キャプション</label>
                        <input type="text" id="solution_1_image_caption">
                    </div>
                    <div class="form-group">
                        <label>説明文</label>
                        <textarea id="solution_1_description_html" rows="2"></textarea>
                    </div>
                    <h4 style="margin:16px 0 8px">カード2</h4>
                    <div class="form-group">
                        <label>ラベル</label>
                        <input type="text" id="solution_2_label">
                    </div>
                    <div class="form-group">
                        <label>メッセージ</label>
                        <textarea id="solution_2_message_html" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>画像</label>
                        <input type="hidden" id="solution_2_image_id">
                        <input type="hidden" id="solution_2_image">
                        <div class="image-upload-row">
                            <div class="image-upload" style="margin-top:6px">
                                <input type="file" accept="image/*" onchange="uploadImage(this, 'solution_2_image', 'solution_2_image_id')">
                                <div class="placeholder">
                                    <span class="material-icons icon">cloud_upload</span>
                                    画像を選択
                                </div>
                                <img class="image-preview" id="solution_2_image_preview" style="display:none" alt="プレビュー">
                            </div>
                            <p class="image-recommend">推奨:<br>1600x900（16:9）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>画像キャプション</label>
                        <input type="text" id="solution_2_image_caption">
                    </div>
                    <div class="form-group">
                        <label>説明文</label>
                        <textarea id="solution_2_description_html" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- 選ばれる理由 -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">star</span> 選ばれる理由</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>セクション見出し <span class="color-hint">※選択した配色と連動</span></label>
                        <textarea id="reasons_title_html" rows="2"></textarea>
                    </div>
                    <div id="reasons-list"></div>
                    <button class="btn-add" onclick="addReason()">
                        <span class="material-icons" style="font-size:16px">add</span> 理由を追加
                    </button>
                </div>
            </div>

            <!-- ご依頼の流れ -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">timeline</span> ご依頼の流れ</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>セクション見出し</label>
                        <input type="text" id="flow_title">
                    </div>
                    <div id="steps-list"></div>
                    <button class="btn-add" onclick="addStep()">
                        <span class="material-icons" style="font-size:16px">add</span> ステップを追加
                    </button>
                </div>
            </div>

            <!-- サービス例 -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">photo_library</span> サービス例</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label>セクション見出し</label>
                        <input type="text" id="services_title">
                    </div>
                    <div id="services-list"></div>
                    <button class="btn-add" onclick="addService()">
                        <span class="material-icons" style="font-size:16px">add</span> サービスを追加
                    </button>
                </div>
            </div>

            <!-- フォーム（お問い合わせ・項目・設定の統合） -->
            <div class="section">
                <div class="section-header">
                    <h3><span class="material-icons" style="font-size:18px">mail</span> フォーム</h3>
                    <span class="material-icons toggle">expand_more</span>
                </div>
                <div class="section-body">
                    <!-- お問い合わせエリア見出し -->
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons text-primary mr-1" style="font-size:16px">info</span> お問い合わせエリア見出し
                        </h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="form-group">
                                <label class="form-label" for="contact_title">お問い合わせ見出し</label>
                                <input type="text" id="contact_title" class="form-control" placeholder="例: お問い合わせ">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contact_subtitle_html">お問い合わせサブタイトル (HTML可)</label>
                                <textarea id="contact_subtitle_html" class="form-control rich-editor" rows="2" placeholder="例: 24時間受付中！お気軽にご相談ください"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- フォーム項目 -->
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons text-primary mr-1" style="font-size:16px">list</span> フォーム項目
                        </h4>
                        <div id="form_fields-list" class="space-y-4 mb-4"></div>
                        <button type="button" class="btn-add" onclick="addFormField()">
                            <span class="material-icons" style="font-size:16px">add</span> 項目を追加
                        </button>
                    </div>

                </div>
            </div>

            <?php if ($is_edit_mode && !$is_template_edit): ?>
                <!-- LP削除（編集モードのみ表示、TEMPLATEは除く） -->
                <div class="section danger-zone">
                    <div class="section-header">
                        <h3><span class="material-icons" style="font-size:18px;color:#EF4444">warning</span> 危険な操作</h3>
                        <span class="material-icons toggle">expand_more</span>
                    </div>
                    <div class="section-body">
                        <p class="danger-text">このLPを削除すると、公開URLも無効になります。この操作は取り消せません。</p>
                        <button type="button" class="btn btn-danger" onclick="openDeleteModal()">
                            <span class="material-icons" style="font-size:16px">delete</span>
                            このLPを削除する
                        </button>
                    </div>
                </div>
            <?php endif; ?>


        </div><!-- /edit-pane -->

        <!-- 固定フッター -->
        <div class="edit-footer">
            <button class="btn btn-primary" onclick="updatePreview()">
                <span class="material-icons" style="font-size:16px">refresh</span>
                プレビュー更新
            </button>
        </div>

    </div><!-- /editor-main -->

    <!-- LP削除確認モーダル -->
    <?php if ($is_edit_mode && !$is_template_edit): ?>
        <div class="modal-overlay" id="delete-modal" style="display:none">
            <div class="modal">
                <div class="modal-header">
                    <h3><span class="material-icons" style="color:#EF4444">warning</span> LPを削除</h3>
                    <button type="button" class="modal-close" onclick="closeDeleteModal()">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>このLPを削除しますか？</p>
                    <p class="text-danger"><strong>この操作は取り消せません。</strong><br>削除すると公開URLも無効になります。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">キャンセル</button>
                    <button type="button" class="btn btn-danger" onclick="deleteLp()">
                        <span class="material-icons" style="font-size:16px">delete</span>
                        削除する
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="<?php echo esc_url(add_query_arg('v', $editor_js_ver, $editor_js_url)); ?>"></script>
</body>

</html>

