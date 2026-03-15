/**
 * LP Editor - WordPress版フロントエンドロジック
 */

// グローバルデータ
let editorData = {};
let wpConfig = {};
let lastSavedData = null; // 保存時のデータを記録
let hasUnsavedChanges = false; // 未保存の変更があるか

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    // WordPress設定読み込み
    wpConfig = JSON.parse(document.getElementById('wp-config').textContent);

    // ページ表示ログを送信（トークンエラー時も送信する）
    sendAccessLog('ページ表示');

    // トークンエラー時は編集機能を初期化しない
    if (wpConfig.tokenError) {
        return;
    }

    // デフォルトデータ読み込み
    const defaultDataEl = document.getElementById('default-data');
    if (!defaultDataEl) return;
    const raw = defaultDataEl.textContent;
    editorData = JSON.parse(raw);

    // 編集トークンをセッションに保持（URLクリーン後の更新・削除用）
    if (wpConfig.lpPageSlug) {
        const sessionKey = 'lp_editor_edit_token_' + wpConfig.lpPageSlug;
        if (wpConfig.editToken) {
            sessionStorage.setItem(sessionKey, wpConfig.editToken);
        } else {
            const savedToken = sessionStorage.getItem(sessionKey);
            if (savedToken) {
                wpConfig.editToken = savedToken;
            }
        }
    }

    // リッチエディタを初期化
    initRichEditors();

    // フォームにデータ反映
    populateForm(editorData);

    // リッチエディタのツールバー色を初期化（populateForm後に実行）
    setTimeout(() => updateColorHints(), 0);

    // バリデーション初期化
    initValidation();

    // 初回プレビュー（初回はトースト非表示）
    updatePreview(false);

    // 編集モードの場合、UIを更新
    if (wpConfig.isEditMode && wpConfig.lpPageId) {
        const headerTitle = document.getElementById('header-title');
        if (headerTitle) {
            headerTitle.textContent = '- ' + wpConfig.pageTitle + ' (編集中)';
        }
        const viewBtn = document.getElementById('view-page-btn');
        if (viewBtn && wpConfig.previewUrl) {
            viewBtn.href = wpConfig.previewUrl;
            viewBtn.style.display = 'inline-flex';
        }
        // 保存ボタンのテキストを変更
        const saveBtn = document.querySelector('button[onclick="saveData()"]');
        if (saveBtn) {
            saveBtn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> 更新';
        }
        // 新規作成後のリダイレクト時はURLだけクリーンにする（新規タブは保存クリック時に開く）
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('created') === '1') {
            const cleanUrl = wpConfig.siteUrl + '?edit=' + wpConfig.lpPageSlug + '&created=1';
            history.replaceState(null, '', cleanUrl);
            showToast('LPページを作成しました！', 'success');
        }
    }

    // 「公開ページを見る」ボタンのクリック通知
    const viewPageBtn = document.getElementById('view-page-btn');
    if (viewPageBtn) {
        viewPageBtn.addEventListener('click', () => {
            sendAccessLog('公開ページを見る');
        });
    }

    // アコーディオン
    document.querySelectorAll('.section-header').forEach(header => {
        header.addEventListener('click', () => {
            header.parentElement.classList.toggle('open');
        });
    });

    // 初期データを記録（未保存検出用）
    lastSavedData = JSON.stringify(collectFormData());

    // フォーム変更を監視
    document.querySelectorAll('input, textarea, select').forEach(el => {
        el.addEventListener('change', markAsChanged);
        el.addEventListener('input', markAsChanged);
    });

    // ページ離脱時の確認
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '変更が保存されていません。ページを離れますか？';
            return e.returnValue;
        }
    });
});

// ===== フォーム → データ収集 =====
// forSave=true: 保存用（入力が空なら空）、false: プレビュー用（入力が空ならデフォルト値）
function collectFormData(forSave = false) {
    const d = JSON.parse(JSON.stringify(editorData));

    // 基本情報
    d.company_name = forSave ? (val('company_name') || '') : (val('company_name') || d.company_name);
    d.address = forSave ? (val('address') || '') : (val('address') || d.address);
    // 電話番号: 保存時は入力値のみ、プレビュー時はデフォルト値も使用
    d.phone = forSave ? (val('phone') || '') : (val('phone') || d.phone);
    d.company_email = val('company_email') || d.company_email || '';
    d.website_url = val('website_url') || d.website_url || '';
    d.header_icon = val('header_icon') || d.header_icon;
    d.header_icon_type = val('header_icon_type') || d.header_icon_type || 'material';
    d.header_icon_image = val('header_icon_image') || '';
    d.business_hours = val('business_hours') || d.business_hours;
    d.business_hours_full = val('business_hours_full') || d.business_hours_full;
    d.copyright_year = val('copyright_year') || d.copyright_year;

    // 配色
    d.color_primary = val('color_primary') || d.color_primary;
    d.color_secondary = val('color_secondary') || d.color_secondary;
    d.common_settings = {
        cta_long: val('common_cta_long') || d.common_settings?.cta_long || d.hero?.cta_text || '無料見積もりを依頼する',
        cta_short: val('common_cta_short') || d.common_settings?.cta_short || d.bottom_bar?.email_text || 'お問い合わせ',
        phone_guidance: val('common_phone_guidance') || d.common_settings?.phone_guidance || d.contact?.phone_guidance || 'お電話でのご相談',
    };

    // ヒーロー
    d.hero = {
        badge: val('hero_badge') || '',
        headline_html: val('hero_headline_html') || '',
        subtext_html: val('hero_subtext_html') || '',
        cta_text: d.common_settings.cta_long,
        image: val('hero_image') || '',
        image_id: parseInt(val('hero_image_id')) || 0,
        text_align: val('hero_text_align') || 'bottom',
    };

    // 悩みセクション
    d.problems_title = val('problems_title') || d.problems_title;
    d.problems = collectListItems('problems');

    // 解決
    d.solutions = [1, 2].map((idx) => ({
        label: val(`solution_${idx}_label`) || '',
        message_html: val(`solution_${idx}_message_html`) || '',
        image: val(`solution_${idx}_image`) || '',
        image_id: parseInt(val(`solution_${idx}_image_id`)) || 0,
        image_caption: val(`solution_${idx}_image_caption`) || '',
        description_html: val(`solution_${idx}_description_html`) || '',
    }));

    // 選ばれる理由
    d.reasons_title_html = val('reasons_title_html') || d.reasons_title_html;
    d.reasons_cta = d.common_settings.cta_long;
    d.reasons = collectListItems('reasons');

    // ご依頼の流れ
    d.flow_title = val('flow_title') || d.flow_title;
    d.steps = collectListItems('steps');

    // 施工事例
    d.services_title = val('services_title') || d.services_title;
    d.services = collectListItems('services');

    // フォーム項目
    d.form_fields = normalizeConsultSelectOptions(collectFormFields());

    // コンタクト
    d.contact = {
        title: val('contact_title') || 'お問い合わせ',
        subtitle_html: val('contact_subtitle_html') || '',
        phone_guidance: d.common_settings.phone_guidance,
    };

    // フッター / SNS（項目が非表示の場合は既存値を保持）
    d.footer = d.footer || {};
    d.footer.tagline = valOr('footer_tagline', d.footer.tagline || '');
    d.sns_facebook = valOr('sns_facebook', d.sns_facebook || '');
    d.sns_twitter = valOr('sns_twitter', d.sns_twitter || '');
    d.sns_instagram = valOr('sns_instagram', d.sns_instagram || '');
    d.sns_youtube = valOr('sns_youtube', d.sns_youtube || '');
    d.sns_tiktok = valOr('sns_tiktok', d.sns_tiktok || '');

    // ボトムバー（項目が非表示の場合は既存値を保持）
    d.bottom_bar = d.bottom_bar || {};
    d.bottom_bar.email_label = valOr('bottom_bar_email_label', d.bottom_bar.email_label || '');
    d.bottom_bar.email_text = valOr('bottom_bar_email_text', d.bottom_bar.email_text || d.common_settings.cta_short);
    d.bottom_bar.phone_label = valOr('bottom_bar_phone_label', d.bottom_bar.phone_label || '');
    const defaultPhoneCta = (d.bottom_bar.phone_text === '電話する') ? '電話相談' : (d.bottom_bar.phone_text || '電話相談');
    d.bottom_bar.phone_text = valOr('bottom_bar_phone_text', defaultPhoneCta);

    // フォーム送信設定
    d.form_settings = {
        recipient_email: val('form_recipient_email') || '',
        email_subject: val('form_email_subject') || '【お問い合わせ】ホームページより',
        success_message: val('form_success_message') || '',
        submit_text: val('form_submit_text') || '送信する',
    };

    if (!d.form_fields.some((f) => f.type === 'checkbox' && f.required && (f.options || '').includes('お問い合わせ内容を確認しました'))) {
        d.form_fields.push({
            label: '',
            type: 'checkbox',
            placeholder: '',
            required: true,
            options: 'お問い合わせ内容を確認しました',
        });
    }
    return d;
}

function normalizeConsultSelectOptions(fields) {
    const list = Array.isArray(fields) ? fields : [];
    const consultOptions = [
        '保守契約などの相談',
        '修繕・改修の補修工事',
        'ブレーカーや配電盤',
        '照明・配線スイッチ',
        'エアコンや空調',
        'その他の相談',
    ].join('\n');

    let found = false;
    list.forEach((field) => {
        if (field && field.type === 'select') {
            field.label = 'ご相談内容';
            field.options = consultOptions;
            if (typeof field.required === 'undefined') {
                field.required = true;
            }
            found = true;
        }
    });

    if (!found) {
        list.push({
            label: 'ご相談内容',
            type: 'select',
            placeholder: '',
            required: true,
            options: consultOptions,
        });
    }

    return list;
}

function collectListItems(listId) {
    const container = document.getElementById(`${listId}-list`);
    if (!container) return [];

    const items = [];
    container.querySelectorAll('.list-item').forEach(item => {
        const obj = {};
        item.querySelectorAll('[data-field]').forEach(input => {
            const field = input.dataset.field;
            if (input.type === 'checkbox') {
                obj[field] = input.checked;
            } else {
                obj[field] = input.value;
            }
        });
        items.push(obj);
    });
    return items;
}

function collectFormFields() {
    const container = document.getElementById('form_fields-list');
    if (!container) return [];

    const items = [];
    container.querySelectorAll('.list-item').forEach(item => {
        const obj = {};
        item.querySelectorAll('[data-field]').forEach(input => {
            const field = input.dataset.field;
            if (input.type === 'checkbox') {
                obj[field] = input.checked;
            } else {
                obj[field] = input.value;
            }
        });
        items.push(obj);
    });
    return items;
}

// ===== 未保存変更の追跡 =====
function markAsChanged() {
    hasUnsavedChanges = true;
}

function markAsSaved() {
    lastSavedData = JSON.stringify(collectFormData(true));
    hasUnsavedChanges = false;
}

// ===== バリデーション・文字数制限 =====
function initValidation() {
    // 文字数カウント対象のフィールドを初期化
    document.querySelectorAll('[data-maxlength]').forEach(input => {
        const maxLen = parseInt(input.dataset.maxlength);
        const countSpan = document.querySelector(`.char-count[data-for="${input.id}"]`);

        if (countSpan) {
            updateCharCount(input, countSpan, maxLen);
            input.addEventListener('input', () => updateCharCount(input, countSpan, maxLen));
        }
    });

    // 電話番号バリデーション
    document.querySelectorAll('[data-validate="phone"]').forEach(input => {
        input.addEventListener('blur', () => validatePhone(input));
        input.addEventListener('input', () => clearValidationError(input));
    });

    // メールアドレスバリデーション
    document.querySelectorAll('[data-validate="email"]').forEach(input => {
        input.addEventListener('blur', () => validateEmail(input));
        input.addEventListener('input', () => clearValidationError(input));
    });
}

function updateCharCount(input, countSpan, maxLen) {
    const len = input.value.length;
    countSpan.textContent = `(${len}/${maxLen})`;

    // 残り文字数に応じてスタイル変更
    countSpan.classList.remove('warning', 'danger');
    if (len >= maxLen) {
        countSpan.classList.add('danger');
    } else if (len >= maxLen * 0.8) {
        countSpan.classList.add('warning');
    }
}

function validatePhone(input) {
    const value = input.value.trim();
    if (!value) {
        clearValidationError(input);
        return true;
    }

    // 電話番号の形式チェック（数字、ハイフン、括弧、スペースのみ）
    const phoneRegex = /^[\d\-\(\)\s]+$/;
    if (!phoneRegex.test(value)) {
        showValidationError(input, '電話番号は数字とハイフンのみ使用できます');
        return false;
    }

    // 数字だけ抽出して桁数チェック
    const digits = value.replace(/\D/g, '');
    if (digits.length < 10 || digits.length > 11) {
        showValidationError(input, '電話番号は10〜11桁で入力してください');
        return false;
    }

    clearValidationError(input);
    return true;
}

function validateEmail(input) {
    const value = input.value.trim();
    if (!value) {
        // 必須チェックは別で行うので、空の場合はエラーなし
        if (input.hasAttribute('required')) {
            showValidationError(input, 'メールアドレスは必須項目です');
            return false;
        }
        clearValidationError(input);
        return true;
    }

    // メールアドレス形式チェック
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        showValidationError(input, '正しいメールアドレスを入力してください');
        return false;
    }

    clearValidationError(input);
    return true;
}

function showValidationError(input, message) {
    input.classList.add('invalid');

    // 既存のエラーメッセージを削除
    const existingError = input.parentElement.querySelector('.validation-error');
    if (existingError) existingError.remove();

    // 新しいエラーメッセージを追加
    const errorSpan = document.createElement('span');
    errorSpan.className = 'validation-error';
    errorSpan.textContent = message;
    input.parentElement.appendChild(errorSpan);
}

function clearValidationError(input) {
    input.classList.remove('invalid');
    const existingError = input.parentElement.querySelector('.validation-error');
    if (existingError) existingError.remove();
}

function validateAll() {
    let isValid = true;

    // 会社名（必須）
    const companyNameInput = document.getElementById('company_name');
    if (companyNameInput) {
        const companyNameVal = companyNameInput.value.trim();
        if (!companyNameVal) {
            showValidationError(companyNameInput, '会社名は必須です');
            isValid = false;
        } else {
            clearValidationError(companyNameInput);
        }
    }

    // 住所（必須）
    const addressInput = document.getElementById('address');
    if (addressInput) {
        const addressVal = addressInput.value.trim();
        if (!addressVal) {
            showValidationError(addressInput, '住所は必須です');
            isValid = false;
        } else {
            clearValidationError(addressInput);
        }
    }

    // 電話番号（必須）
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        const phoneVal = phoneInput.value.trim();
        if (!phoneVal) {
            showValidationError(phoneInput, '電話番号は必須です');
            isValid = false;
        } else if (!validatePhone(phoneInput)) {
            isValid = false;
        }
    }

    // メールアドレス（必須）
    const emailInput = document.getElementById('company_email');
    if (emailInput) {
        const emailVal = emailInput.value.trim();
        if (!emailVal) {
            showValidationError(emailInput, 'メールアドレスは必須です');
            isValid = false;
        } else if (!validateEmail(emailInput)) {
            isValid = false;
        }
    }

    return isValid;
}

// ===== プレビュー更新 =====
async function updatePreview(showSuccessToast = true) {
    if (showSuccessToast) sendAccessLog('プレビュー更新');
    const data = collectFormData();
    try {
        const res = await fetch(wpConfig.restUrl + 'preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: JSON.stringify(data),
        });
        const raw = await res.text();
        let html = raw;
        try {
            const parsed = JSON.parse(raw);
            if (parsed && typeof parsed.html === 'string') {
                html = parsed.html;
            }
        } catch (_) {
            // 文字列HTMLレスポンスの場合はそのまま使う
        }
        const iframe = document.getElementById('preview-iframe');
        iframe.srcdoc = html;
        if (showSuccessToast) {
            showToast('プレビュー更新完了', 'success');
        }
    } catch (e) {
        showToast('エラー: ' + e.message, 'error');
    }
}

// ===== 新規LPページ作成・保存 / 既存ページ更新 =====
async function saveData() {
    // バリデーション実行
    if (!validateAll()) {
        showToast('入力内容を確認してください', 'error');
        return;
    }

    const data = collectFormData(true);  // 保存用（プレースホルダー値は除外）
    showLoading(true);

    // 編集モードか新規作成モードかを判定
    const isEditMode = wpConfig.isEditMode && wpConfig.lpPageId;
    sendAccessLog(isEditMode ? '更新' : '保存して公開（新規）');
    const endpoint = isEditMode
        ? wpConfig.restUrl + 'update/' + wpConfig.lpPageId
        : wpConfig.restUrl + 'create';

    if (isEditMode && wpConfig.editToken) {
        data.edit_token = wpConfig.editToken;
    }

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: JSON.stringify(data),
        });
        const result = await res.json();

        if (result.success) {
            markAsSaved(); // 保存成功時に未保存フラグをリセット

            if (isEditMode) {
                showToast('更新しました！', 'success');

                // ヘッダータイトルを更新
                const headerTitle = document.getElementById('header-title');
                if (headerTitle) {
                    headerTitle.textContent = '- ' + result.title + ' (編集中)';
                }

                // 公開ページを見るボタンを更新
                const viewBtn = document.getElementById('view-page-btn');
                if (viewBtn && result.permalink) {
                    viewBtn.href = result.permalink;
                    viewBtn.style.display = 'inline-flex';
                }
                        } else {
                showToast('LPページを作成しました！', 'success');
                sendAccessLog('保存して公開（新規）- 完了', result.page_id);

                // ポップアップブロックを避けるため、保存クリックの文脈で先にタブを開く
                let previewTab = null;
                try {
                    previewTab = window.open('', '_blank');
                } catch (e) {
                    previewTab = null;
                }

                if (previewTab && result.permalink) {
                    previewTab.location.href = result.permalink;
                }

                // 新規作成後は編集モードURLへ遷移（トークン付き）
                const newUrl = wpConfig.siteUrl + '?edit=' + result.page_id + '&token=' + result.edit_token + '&created=1';
                setTimeout(() => {
                    window.location.href = newUrl;
                }, 300);
            }
        } else {
            showToast((isEditMode ? '更新' : '作成') + 'エラー: ' + (result.message || '不明なエラー'), 'error');
        }
    } catch (e) {
        showToast((isEditMode ? '更新' : '作成') + 'エラー: ' + e.message, 'error');
    } finally {
        showLoading(false);
    }
}

// ===== 画像アップロード（認証不要のカスタムエンドポイント使用）=====
async function uploadImage(input, urlFieldId, idFieldId) {
    const file = input.files[0];
    if (!file) return;
    sendAccessLog('画像アップロード');

    const formData = new FormData();
    formData.append('file', file);

    showLoading(true);

    try {
        const res = await fetch(wpConfig.restUrl + 'upload', {
            method: 'POST',
            headers: {
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: formData,
        });
        const result = await res.json();

        if (result.id) {
            // URLとIDをフィールドにセット
            const urlField = document.getElementById(urlFieldId);
            const idField = document.getElementById(idFieldId);
            if (urlField) urlField.value = result.source_url;
            if (idField) idField.value = result.id;

            // プレビュー画像を表示
            const previewImg = document.getElementById(urlFieldId + '_preview');
            if (previewImg) {
                previewImg.src = result.source_url;
                previewImg.style.display = 'block';
            }

            showToast('画像をアップロードしました', 'success');
        } else {
            showToast('アップロードエラー: ' + (result.message || '不明なエラー'), 'error');
        }
    } catch (e) {
        showToast('アップロードエラー: ' + e.message, 'error');
    } finally {
        showLoading(false);
    }
}

// 動的リスト用の画像アップロード
async function uploadListImage(input, urlFieldId, idFieldId) {
    const file = input.files[0];
    if (!file) return;
    sendAccessLog('画像アップロード');

    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch(wpConfig.restUrl + 'upload', {
            method: 'POST',
            headers: {
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: formData,
        });
        const result = await res.json();

        if (result.id) {
            const urlField = document.getElementById(urlFieldId);
            const idField = document.getElementById(idFieldId);
            if (urlField) urlField.value = result.source_url;
            if (idField) idField.value = result.id;

            const previewImg = input.closest('.image-upload')?.querySelector('.image-preview');
            if (previewImg) {
                previewImg.src = result.source_url;
                previewImg.style.display = 'block';
            }

            showToast('画像をアップロードしました', 'success');
        } else {
            showToast('アップロードエラー: ' + (result.message || '不明なエラー'), 'error');
        }
    } catch (e) {
        showToast('アップロードエラー: ' + e.message, 'error');
    }
}

// ===== 動的リスト管理 =====
let listCounter = 0;
const LIST_HEADER_LABELS = {
    problems: 'ポイント',
    reasons: '理由',
    steps: 'ステップ',
    services: 'サービス',
    form_fields: '項目',
};

function addProblem() {
    addListItem('problems', problemTemplate(getListCount('problems') + 1));
}

function addReason() {
    addListItem('reasons', reasonTemplate(getListCount('reasons') + 1));
}

function addStep() {
    addListItem('steps', stepTemplate(getListCount('steps') + 1));
}

function addService() {
    addListItem('services', serviceTemplate(getListCount('services') + 1));
}

function addFormField() {
    addListItem('form_fields', formFieldTemplate(getListCount('form_fields') + 1));
}

function getListCount(listId) {
    const container = document.getElementById(`${listId}-list`);
    return container ? container.querySelectorAll('.list-item').length : 0;
}

function addListItem(listId, template) {
    const container = document.getElementById(`${listId}-list`);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'list-item';
    div.innerHTML = template;
    container.appendChild(div);
    renumberListHeaders(listId);
}

function removeListItem(btn) {
    const item = btn.closest('.list-item');
    if (!item) return;
    const container = item.parentElement;
    item.remove();
    if (!container || !container.id || !container.id.endsWith('-list')) return;
    const listId = container.id.replace(/-list$/, '');
    renumberListHeaders(listId);
}

function renumberListHeaders(listId) {
    const container = document.getElementById(`${listId}-list`);
    if (!container) return;
    const baseLabel = LIST_HEADER_LABELS[listId];
    if (!baseLabel) return;

    const items = container.querySelectorAll('.list-item');
    items.forEach((item, index) => {
        const headerLabel = item.querySelector('.list-item-header > span');
        if (headerLabel) {
            headerLabel.textContent = `${baseLabel} ${index + 1}`;
        }
    });
}

// ===== フォームにデータ反映 =====
function populateForm(data) {
    if (data && Array.isArray(data.form_fields)) {
        data.form_fields = normalizeConsultSelectOptions(data.form_fields);
    }

    // 基本情報
    const companyInput = document.getElementById('company_name');
    const addressInput = document.getElementById('address');
    if (wpConfig.isEditMode) {
        setVal('company_name', data.company_name || '');
        setVal('address', data.address || '');
        if (companyInput) companyInput.placeholder = '';
        if (addressInput) addressInput.placeholder = '';
    } else {
        setVal('company_name', '');
        setVal('address', '');
        if (companyInput) companyInput.placeholder = data.company_name || '';
        if (addressInput) addressInput.placeholder = data.address || '';
    }
    // 電話番号: プレースホルダー値はセットしない（編集モードで実際の値がある場合のみセット）
    if (data.phone && data.phone !== '0120-XXX-XXX') {
        setVal('phone', data.phone);
    }
    setVal('company_email', data.company_email || '');
    setVal('website_url', data.website_url || '');
    setVal('header_icon', data.header_icon);
    setVal('header_icon_type', data.header_icon_type || 'material');
    setVal('header_icon_image', data.header_icon_image || '');
    setVal('business_hours', data.business_hours);
    setVal('business_hours_full', data.business_hours_full);
    setVal('copyright_year', data.copyright_year);

    // アイコンプレビューを更新
    const iconPreview = document.getElementById('header_icon_preview');
    if (iconPreview) {
        if ((data.header_icon_type || 'material') === 'image' && data.header_icon_image) {
            iconPreview.innerHTML = `<img src="${esc(data.header_icon_image)}" alt="アイコン">`;
        } else {
            iconPreview.innerHTML = `<span class="material-icons">${esc(data.header_icon || 'bolt')}</span>`;
        }
    }
    // 配色
    setVal('color_primary', data.color_primary);
    setVal('color_primary_text', data.color_primary);
    setVal('color_secondary', data.color_secondary);
    setVal('color_secondary_text', data.color_secondary);

    // カラー連動ヒントの色を更新
    updateColorHints();

    // 共通設定
    const common = data.common_settings || {};
    setVal('common_cta_long', common.cta_long || data.hero?.cta_text || '無料見積もりを依頼する');
    setVal('common_cta_short', common.cta_short || data.bottom_bar?.email_text || 'お問い合わせ');
    // 電話案内テキストはUI非表示のため、既存値を保持する

    // ヒーロー
    if (data.hero) {
        setVal('hero_badge', data.hero.badge);
        setRichEditorValue('hero_headline_html', data.hero.headline_html);
        setRichEditorValue('hero_subtext_html', data.hero.subtext_html);
        setVal('hero_image', data.hero.image);
        setVal('hero_image_id', data.hero.image_id);
        // テキスト配置
        const textAlign = data.hero.text_align || 'bottom';
        setVal('hero_text_align', textAlign);
        document.querySelectorAll('#hero_align_picker .align-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.value === textAlign);
        });
        if (data.hero.image) {
            const preview = document.getElementById('hero_image_preview');
            if (preview) {
                preview.src = data.hero.image;
                preview.style.display = 'block';
            }
        }
    }

    // 悩み
    setVal('problems_title', data.problems_title);
    if (data.problems) renderList('problems', data.problems, problemTemplate);

    // 解決
    const solutions = data.solutions || (data.solution ? [data.solution] : []);
    [1, 2].forEach((idx) => {
        const item = solutions[idx - 1] || {};
        setVal(`solution_${idx}_label`, item.label || '');
        setRichEditorValue(`solution_${idx}_message_html`, item.message_html || '');
        setVal(`solution_${idx}_image`, item.image || '');
        setVal(`solution_${idx}_image_id`, item.image_id || '');
        setVal(`solution_${idx}_image_caption`, item.image_caption || '');
        setRichEditorValue(`solution_${idx}_description_html`, item.description_html || '');
        const preview = document.getElementById(`solution_${idx}_image_preview`);
        if (preview) {
            if (item.image) {
                preview.src = item.image;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
    });

    // 理由
    setRichEditorValue('reasons_title_html', data.reasons_title_html);
    if (data.reasons) renderList('reasons', data.reasons, reasonTemplate);

    // 流れ
    setVal('flow_title', data.flow_title);
    if (data.steps) renderList('steps', data.steps, stepTemplate);

    // サービス例
    setVal('services_title', data.services_title || data.cases_title);
    if (data.services || data.cases) renderList('services', data.services || data.cases, serviceTemplate);

    // フォーム項目
    if (data.form_fields) renderList('form_fields', data.form_fields, formFieldTemplate);

    // フォーム送信設定
    if (data.form_settings) {
        setVal('form_recipient_email', data.form_settings.recipient_email);
        setVal('form_email_subject', data.form_settings.email_subject);
        setVal('form_success_message', data.form_settings.success_message);
        setVal('form_submit_text', data.form_settings.submit_text);
    }

    // コンタクト
    if (data.contact) {
        setVal('contact_title', data.contact.title);
        setRichEditorValue('contact_subtitle_html', data.contact.subtitle_html);
    }

    // フッター
    if (data.footer) {
        setVal('footer_tagline', data.footer.tagline);
    }
    setVal('sns_facebook', data.sns_facebook);
    setVal('sns_twitter', data.sns_twitter);
    setVal('sns_instagram', data.sns_instagram);
    setVal('sns_youtube', data.sns_youtube);
    setVal('sns_tiktok', data.sns_tiktok);

    // ボトムバー
    if (data.bottom_bar) {
        setVal('bottom_bar_email_label', data.bottom_bar.email_label);
        setVal('bottom_bar_phone_label', data.bottom_bar.phone_label);
        setVal('bottom_bar_phone_text', data.bottom_bar.phone_text === '電話する' ? '電話相談' : data.bottom_bar.phone_text);
    }
}

function renderList(listId, items, templateFn) {
    const container = document.getElementById(`${listId}-list`);
    if (!container) return;
    container.innerHTML = '';
    items.forEach((item, i) => {
        const div = document.createElement('div');
        div.className = 'list-item';
        div.innerHTML = templateFn(i + 1, item);
        container.appendChild(div);
    });
    renumberListHeaders(listId);
}

// ===== リストテンプレート =====
function problemTemplate(index, data = {}) {
    const uniqueId = `problem_${index}_${Date.now()}`;
    return `
        <div class="list-item-header">
            <span>ポイント ${index}</span>
            <button class="btn-remove" onclick="removeListItem(this)" title="削除">✕</button>
        </div>
        <div class="form-group">
            <label>タイトル</label>
            <input type="text" data-field="title" value="${esc(data.title || '')}">
        </div>
        <div class="form-group">
            <label>説明文</label>
            <textarea data-field="description">${esc(data.description || '')}</textarea>
        </div>
        <div class="form-group">
            <label>画像</label>
            <input type="hidden" data-field="image_id" id="${uniqueId}_id" value="${data.image_id || ''}">
            <input type="hidden" data-field="image" id="${uniqueId}_url" value="${esc(data.image || '')}">
            <div class="image-upload-row">
                <div class="image-upload" style="margin-top:6px">
                    <input type="file" accept="image/*" onchange="uploadListImage(this, '${uniqueId}_url', '${uniqueId}_id')">
                    <div class="placeholder">
                        <span class="material-icons icon">cloud_upload</span>
                        画像を選択
                    </div>
                    <img class="image-preview" style="display:${data.image ? 'block' : 'none'}" src="${esc(data.image || '')}" alt="プレビュー">
                </div>
                <p class="image-recommend">推奨:<br>1600x900（16:9）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
            </div>
        </div>`;
}

function reasonTemplate(index, data = {}) {
    const uniqueId = `reason_${index}_${Date.now()}`;
    return `
        <div class="list-item-header">
            <span>理由 ${index}</span>
            <button class="btn-remove" onclick="removeListItem(this)" title="この理由を削除">
                <span class="material-icons" style="font-size:16px">close</span>
                <span class="btn-remove-label">削除</span>
            </button>
        </div>
        <div class="form-group">
            <label>番号</label>
            <input type="text" data-field="number" value="${esc(data.number || String(index).padStart(2, '0'))}">
        </div>
        <div class="form-group">
            <label>タイトル</label>
            <input type="text" data-field="title" value="${esc(data.title || '')}">
        </div>
        <div class="form-group">
            <label>説明文</label>
            <textarea data-field="description">${esc(data.description || '')}</textarea>
        </div>
        <div class="form-group">
            <label>画像</label>
            <input type="hidden" data-field="image_id" id="${uniqueId}_id" value="${data.image_id || ''}">
            <input type="hidden" data-field="image" id="${uniqueId}_url" value="${esc(data.image || '')}">
            <div class="image-upload-row">
                <div class="image-upload" style="margin-top:6px">
                    <input type="file" accept="image/*" onchange="uploadListImage(this, '${uniqueId}_url', '${uniqueId}_id')">
                    <div class="placeholder">
                        <span class="material-icons icon">cloud_upload</span>
                        画像を選択
                    </div>
                    <img class="image-preview" style="display:${data.image ? 'block' : 'none'}" src="${esc(data.image || '')}" alt="プレビュー">
                </div>
                <p class="image-recommend">推奨:<br>1600x900（16:9）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
            </div>
        </div>`;
}

function stepTemplate(index, data = {}) {
    return `
        <div class="list-item-header">
            <span>ステップ ${index}</span>
            <button class="btn-remove" onclick="removeListItem(this)" title="削除">✕</button>
        </div>
        <div class="form-group">
            <label>タイトル</label>
            <input type="text" data-field="title" value="${esc(data.title || '')}">
        </div>
        <div class="form-group">
            <label>説明文</label>
            <textarea data-field="description">${esc(data.description || '')}</textarea>
        </div>`;
}

function serviceTemplate(index, data = {}) {
    const uniqueId = `service_${index}_${Date.now()}`;
    return `
        <div class="list-item-header">
            <span>サービス ${index}</span>
            <button class="btn-remove" onclick="removeListItem(this)" title="削除">✕</button>
        </div>
        <div class="form-group">
            <label>キャプション</label>
            <input type="text" data-field="caption" value="${esc(data.caption || '')}">
        </div>
        <div class="form-group">
            <label>画像</label>
            <input type="hidden" data-field="image_id" id="${uniqueId}_id" value="${data.image_id || ''}">
            <input type="hidden" data-field="image" id="${uniqueId}_url" value="${esc(data.image || '')}">
            <div class="image-upload-row">
                <div class="image-upload" style="margin-top:6px">
                    <input type="file" accept="image/*" onchange="uploadListImage(this, '${uniqueId}_url', '${uniqueId}_id')">
                    <div class="placeholder">
                        <span class="material-icons icon">cloud_upload</span>
                        画像を選択
                    </div>
                    <img class="image-preview" style="display:${data.image ? 'block' : 'none'}" src="${esc(data.image || '')}" alt="プレビュー">
                </div>
                <p class="image-recommend">推奨:<br>全幅 1600x800（2:1） / 半幅 1200x1200（1:1）<br>JPEG・PNG・GIF・WebP<br>最大5MB</p>
            </div>
        </div>
        <div class="form-group">
            <label>レイアウト</label>
            <select data-field="layout">
                <option value="half" ${data.layout === 'half' ? 'selected' : ''}>半分幅</option>
                <option value="full" ${data.layout === 'full' ? 'selected' : ''}>全幅</option>
            </select>
        </div>`;
}

function formFieldTemplate(index, data = {}) {
    const typeOptions = [
        { value: 'text', label: 'テキスト' },
        { value: 'email', label: 'メール' },
        { value: 'tel', label: '電話番号' },
        { value: 'textarea', label: 'テキストエリア' },
        { value: 'select', label: 'セレクト' },
        { value: 'radio', label: 'ラジオボタン' },
        { value: 'checkbox', label: 'チェックボックス' }
    ];
    const currentType = data.type || 'text';
    const needsOptions = ['select', 'radio', 'checkbox'].includes(currentType);

    return `
        <div class="list-item-header">
            <span>項目 ${index}</span>
            <button class="btn-remove" onclick="removeListItem(this)" title="削除">✕</button>
        </div>
        <div class="form-group">
            <label>項目名</label>
            <input type="text" data-field="label" value="${esc(data.label || '')}">
        </div>
        <div class="form-group">
            <label>入力タイプ</label>
            <select data-field="type" onchange="toggleOptionsField(this)">
                ${typeOptions.map(opt =>
                    `<option value="${opt.value}" ${currentType === opt.value ? 'selected' : ''}>${opt.label}</option>`
                ).join('')}
            </select>
        </div>
        <div class="form-group options-field" style="display:${needsOptions ? 'block' : 'none'}">
            <label>選択肢（1行に1つ）</label>
            <textarea data-field="options" rows="3" placeholder="選択肢1&#10;選択肢2&#10;選択肢3">${esc(data.options || '')}</textarea>
        </div>
        <div class="form-group">
            <label>プレースホルダー</label>
            <input type="text" data-field="placeholder" value="${esc(data.placeholder || '')}">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" data-field="required" ${data.required ? 'checked' : ''}>
                必須項目
            </label>
        </div>`;
}

// ===== ユーティリティ =====
function val(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function valOr(id, fallback = '') {
    const el = document.getElementById(id);
    return el ? el.value : fallback;
}

function setVal(id, value) {
    const el = document.getElementById(id);
    if (el && value !== undefined && value !== null) el.value = value;
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function syncColor(sourceId, targetId) {
    const source = document.getElementById(sourceId);
    const target = document.getElementById(targetId);
    if (source && target) target.value = source.value;

    // カラー連動を更新
    updateColorHints();
}

function updateColorHints() {
    const primaryColor = document.getElementById('color_primary')?.value || '#2563EB';
    const secondaryColor = document.getElementById('color_secondary')?.value || '#34D399';

    // ツールバーの「A」アイコン色を更新
    document.querySelectorAll('.rich-editor-btn-primary .material-icons').forEach(el => {
        el.style.color = primaryColor;
    });
    document.querySelectorAll('.rich-editor-btn-secondary .material-icons').forEach(el => {
        el.style.color = secondaryColor;
    });

    // リッチエディタ内の.text-primary, .text-secondaryの色を更新
    document.querySelectorAll('.rich-editor-content .text-primary').forEach(el => {
        el.style.color = primaryColor;
    });
    document.querySelectorAll('.rich-editor-content .text-secondary').forEach(el => {
        el.style.color = secondaryColor;
    });

    // .color-hint は連動しない（固定色のまま）
}

function toggleOptionsField(selectEl) {
    const listItem = selectEl.closest('.list-item');
    const optionsField = listItem.querySelector('.options-field');
    const needsOptions = ['select', 'radio', 'checkbox'].includes(selectEl.value);
    optionsField.style.display = needsOptions ? 'block' : 'none';
}

function resetToDefault() {
    if (!confirm('入力内容をクリアしますか？')) {
        return;
    }
    sendAccessLog('初期化（復元）');
    location.reload();
}

function showToast(message, type = 'success') {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.className = `toast ${type}`;
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => toast.classList.remove('show'), 2500);
}

function showLoading(show) {
    let overlay = document.getElementById('loading-overlay');
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else if (overlay) {
        overlay.style.display = 'none';
    }
}

// ===== アイコンピッカー =====
const COMMON_ICONS = [
    'bolt', 'home', 'business', 'storefront', 'apartment', 'restaurant', 'local_hospital', 'medical_services',
    'construction', 'handyman', 'plumbing', 'electrical_services', 'ac_unit', 'roofing', 'carpenter',
    'car_repair', 'directions_car', 'local_shipping', 'flight', 'train',
    'spa', 'fitness_center', 'sports_gymnastics', 'self_improvement', 'psychology',
    'school', 'menu_book', 'science', 'biotech', 'computer',
    'shopping_cart', 'store', 'payments', 'account_balance', 'savings',
    'favorite', 'star', 'thumb_up', 'verified', 'workspace_premium',
    'phone', 'email', 'chat', 'support_agent', 'headset_mic',
    'schedule', 'event', 'alarm', 'timer', 'update',
    'location_on', 'map', 'explore', 'navigation', 'near_me',
    'person', 'group', 'family_restroom', 'diversity_3', 'handshake',
    'eco', 'park', 'water_drop', 'wb_sunny', 'nature',
    'build', 'settings', 'engineering', 'precision_manufacturing', 'factory',
    'security', 'lock', 'shield', 'gpp_good', 'verified_user',
    'lightbulb', 'tips_and_updates', 'emoji_objects', 'rocket_launch', 'auto_awesome'
];

let currentIconField = null;

function openIconPicker(fieldId) {
    currentIconField = fieldId;
    const currentIcon = document.getElementById(fieldId).value || 'bolt';
    const currentType = document.getElementById(fieldId + '_type')?.value || 'material';
    const currentImage = document.getElementById(fieldId + '_image')?.value || '';

    const modal = document.createElement('div');
    modal.className = 'icon-picker-modal';
    modal.id = 'icon-picker-modal';
    modal.innerHTML = `
        <div class="icon-picker-content">
            <div class="icon-picker-header">
                <h3>アイコンを選択</h3>
                <button class="icon-picker-close" onclick="closeIconPicker()">&times;</button>
            </div>
            <div class="icon-picker-tabs">
                <button type="button" class="icon-picker-tab ${currentType !== 'image' ? 'active' : ''}" onclick="switchIconTab('material')">Material Icons</button>
                <button type="button" class="icon-picker-tab ${currentType === 'image' ? 'active' : ''}" onclick="switchIconTab('upload')">画像アップロード</button>
            </div>
            <div class="icon-picker-body">
                <div id="icon-tab-material" style="display:${currentType === 'image' ? 'none' : 'block'}">
                    <div class="icon-grid">
                        ${COMMON_ICONS.map(icon => `
                            <div class="icon-grid-item ${icon === currentIcon && currentType !== 'image' ? 'selected' : ''}"
                                 onclick="selectIcon('${icon}')" title="${icon}">
                                <span class="material-icons">${icon}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div id="icon-tab-upload" style="display:${currentType === 'image' ? 'block' : 'none'}">
                    <label class="icon-upload-area">
                        <input type="file" accept="image/*" onchange="uploadIconImage(this)">
                        <span class="material-icons">cloud_upload</span>
                        <p>画像を選択してアップロード</p>
                    </label>
                    ${currentImage ? `<div class="icon-upload-current"><img src="${esc(currentImage)}" alt="現在のアイコン"></div>` : ''}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeIconPicker();
    });
}

function switchIconTab(tab) {
    const materialTab = document.getElementById('icon-tab-material');
    const uploadTab = document.getElementById('icon-tab-upload');
    const tabs = document.querySelectorAll('.icon-picker-tab');

    if (!materialTab || !uploadTab) return;

    materialTab.style.display = tab === 'material' ? 'block' : 'none';
    uploadTab.style.display = tab === 'upload' ? 'block' : 'none';

    tabs.forEach((t, i) => {
        t.classList.toggle('active', (tab === 'material' && i === 0) || (tab === 'upload' && i === 1));
    });
}

function closeIconPicker() {
    const modal = document.getElementById('icon-picker-modal');
    if (modal) modal.remove();
}

function selectIcon(iconName) {
    if (!currentIconField) return;

    document.getElementById(currentIconField).value = iconName;
    document.getElementById(currentIconField + '_type').value = 'material';
    document.getElementById(currentIconField + '_image').value = '';

    const preview = document.getElementById(currentIconField + '_preview');
    if (preview) {
        preview.innerHTML = `<span class="material-icons">${iconName}</span>`;
    }

    closeIconPicker();
    updatePreview();
}

async function uploadIconImage(input) {
    if (!currentIconField) return;

    const file = input.files?.[0];
    if (!file) return;
    sendAccessLog('画像アップロード');

    const formData = new FormData();
    formData.append('file', file);

    showLoading(true);
    try {
        const res = await fetch(wpConfig.restUrl + 'upload', {
            method: 'POST',
            headers: {
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: formData,
        });
        const result = await res.json();

        if (result.id && result.source_url) {
            document.getElementById(currentIconField + '_type').value = 'image';
            document.getElementById(currentIconField + '_image').value = result.source_url;

            const preview = document.getElementById(currentIconField + '_preview');
            if (preview) {
                preview.innerHTML = `<img src="${result.source_url}" alt="アイコン">`;
            }

            closeIconPicker();
            updatePreview();
            showToast('アイコン画像をアップロードしました', 'success');
        } else {
            showToast('アップロードエラー: ' + (result.message || '不明なエラー'), 'error');
        }
    } catch (e) {
        showToast('アップロードエラー: ' + e.message, 'error');
    } finally {
        showLoading(false);
    }
}
// ===== リッチテキストエディタ =====
function createRichEditor(id, placeholder = '') {
    const wrapper = document.createElement('div');
    wrapper.className = 'rich-editor-wrapper';
    wrapper.innerHTML = `
        <div class="rich-editor-toolbar">
            <button type="button" class="rich-editor-btn" onclick="richExec('bold')" title="太字">
                <span class="material-icons">format_bold</span>
            </button>
            <button type="button" class="rich-editor-btn" onclick="richExec('italic')" title="斜体">
                <span class="material-icons">format_italic</span>
            </button>
            <div class="rich-editor-separator"></div>
            <button type="button" class="rich-editor-btn rich-editor-btn-primary" onclick="richInsertClass('text-primary')" title="プライマリカラー">
                <span class="material-icons">format_color_text</span>
            </button>
            <button type="button" class="rich-editor-btn rich-editor-btn-secondary" onclick="richInsertClass('text-secondary')" title="セカンダリカラー">
                <span class="material-icons">format_color_text</span>
            </button>
            <div class="rich-editor-separator"></div>
            <button type="button" class="rich-editor-btn" onclick="richInsertBr()" title="改行">
                <span class="material-icons">keyboard_return</span>
            </button>
        </div>
        <div class="rich-editor-content" contenteditable="true" data-placeholder="${placeholder}" id="${id}_editor"></div>
        <input type="hidden" id="${id}">
    `;
    return wrapper;
}

let activeRichEditor = null;

function richExec(command) {
    document.execCommand(command, false, null);
}

function richInsertClass(className) {
    const selection = window.getSelection();
    if (!selection.rangeCount) return;

    const range = selection.getRangeAt(0);
    if (range.collapsed) return;

    const span = document.createElement('span');
    span.className = className;

    // 現在のカラー設定を適用
    if (className === 'text-primary') {
        const primaryColor = document.getElementById('color_primary')?.value || '#2563EB';
        span.style.color = primaryColor;
    } else if (className === 'text-secondary') {
        const secondaryColor = document.getElementById('color_secondary')?.value || '#34D399';
        span.style.color = secondaryColor;
    }

    try {
        range.surroundContents(span);
    } catch (e) {
        // 複数要素をまたぐ場合
        const text = range.extractContents();
        span.appendChild(text);
        range.insertNode(span);
    }

    syncRichEditorToHidden();
}

function richInsertBr() {
    document.execCommand('insertHTML', false, '<br>');
    syncRichEditorToHidden();
}

function syncRichEditorToHidden() {
    document.querySelectorAll('.rich-editor-content').forEach(editor => {
        const hiddenId = editor.id.replace('_editor', '');
        const hidden = document.getElementById(hiddenId);
        if (hidden) {
            hidden.value = editor.innerHTML;
        }
    });
}

function initRichEditors() {
    // HTMLフィールドをリッチエディタに変換
    const htmlFields = [
        { id: 'hero_headline_html', placeholder: 'メインコピーを入力...' },
        { id: 'hero_subtext_html', placeholder: 'サブコピーを入力...' },
        { id: 'solution_1_message_html', placeholder: 'メッセージを入力...' },
        { id: 'solution_1_description_html', placeholder: '説明文を入力...' },
        { id: 'solution_2_message_html', placeholder: 'メッセージを入力...' },
        { id: 'solution_2_description_html', placeholder: '説明文を入力...' },
        { id: 'reasons_title_html', placeholder: '見出しを入力...' },
        { id: 'contact_subtitle_html', placeholder: '説明文を入力...' },
    ];

    htmlFields.forEach(field => {
        const textarea = document.getElementById(field.id);
        if (textarea && textarea.tagName === 'TEXTAREA') {
            const wrapper = createRichEditor(field.id, field.placeholder);
            textarea.parentNode.replaceChild(wrapper, textarea);

            // エディタにフォーカス/ブラー時の同期
            const editor = document.getElementById(field.id + '_editor');
            editor.addEventListener('input', syncRichEditorToHidden);
            editor.addEventListener('blur', syncRichEditorToHidden);
        }
    });
}

function setRichEditorValue(id, value) {
    const editor = document.getElementById(id + '_editor');
    const hidden = document.getElementById(id);
    if (editor) {
        editor.innerHTML = value || '';
    }
    if (hidden) {
        hidden.value = value || '';
    }
}

function getRichEditorValue(id) {
    const hidden = document.getElementById(id);
    return hidden ? hidden.value : '';
}

// ===== ヒーロー配置選択 =====
function setHeroAlign(value) {
    document.getElementById('hero_text_align').value = value;

    // ボタンのアクティブ状態を更新
    document.querySelectorAll('#hero_align_picker .align-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === value);
    });

    updatePreview();
}

// ===== LP削除モーダル =====
function openDeleteModal() {
    const modal = document.getElementById('delete-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function deleteLp() {
    if (!wpConfig.lpPageSlug) {
        showToast('削除対象が見つかりません', 'error');
        return;
    }
    sendAccessLog('LP削除');

    showLoading(true);

    try {
        const payload = wpConfig.editToken ? { edit_token: wpConfig.editToken } : {};
        const res = await fetch(wpConfig.restUrl + 'delete/' + wpConfig.lpPageSlug, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LP-Nonce': wpConfig.nonce,
                ...(wpConfig.editToken ? { 'X-LP-Edit-Token': wpConfig.editToken } : {}),
            },
            body: JSON.stringify(payload),
        });

        const result = await res.json();

        if (result.success) {
            // 未保存フラグをクリア（beforeunload警告を出さない）
            hasUnsavedChanges = false;
            showToast('削除が完了しました', 'success');
            // 1秒後にホームへリダイレクト
            setTimeout(() => {
                window.location.href = wpConfig.siteUrl;
            }, 1000);
        } else {
            showToast(result.message || '削除に失敗しました', 'error');
            closeDeleteModal();
        }
    } catch (e) {
        showToast('通信エラー: ' + e.message, 'error');
        closeDeleteModal();
    } finally {
        showLoading(false);
    }
}

// ===== アクセスログ送信 =====
function sendAccessLog(actionName, lpId) {
    try {
        fetch(wpConfig.restUrl + 'access-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LP-Nonce': wpConfig.nonce,
            },
            body: JSON.stringify({
                action: actionName,
                page_name: 'Editor画面',
                lp_id: lpId || wpConfig.lpPageSlug || '',
                screen_size: screen.width + 'x' + screen.height,
                window_size: window.innerWidth + 'x' + window.innerHeight,
            }),
        }).catch(() => {});
    } catch (_) {}
}







