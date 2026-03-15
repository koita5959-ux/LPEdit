<?php

/**
 * Template Name: Select Page
 *
 * LP 新規作成・既存編集の入口ページ（オリジナルデザイン復旧版）
 * 元HTML: lpedit_htmlページ/select.html
 *
 * @package LP_Editor_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$editor_url = function_exists('lp_editor_get_editor_url')
    ? lp_editor_get_editor_url()
    : home_url('/editor/');
get_header('service', array(
    'title' => 'LPの作成・編集',
    'description' => 'メールアドレスだけで新規LP作成・既存LP編集に進めます。登録不要で運用できるLP作成更新サービスです。',
    'current' => ''
));
?>
<main>
    <section class="select-page-section">
        <div class="container">
            <div class="panel">

                <p class="panel-title">LPの作成・編集</p>

                <div class="actions">
                    <!-- 新規作成 -->
                    <a href="<?php echo esc_url($editor_url); ?>"
                        class="action-button primary"
                        id="newButton">
                        新しくLPを作成する
                    </a>

                    <!-- 更新 -->
                    <button class="action-button secondary" id="editToggle">
                        公開中のLPを編集する
                    </button>
                </div>

                <!-- アコーディオン -->
                <div class="accordion" id="editAccordion">
                    <p class="notice">
                        入力したメールアドレス宛に、<br>
                        編集に必要なURLをお送りします。
                    </p>

                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" placeholder="example@example.com">

                    <div class="wp-message wp-message--success" id="selectSuccess"></div>
                    <div class="wp-message wp-message--error" id="selectError"></div>

                    <button class="send-button" id="sendButton" disabled>
                        メール送信
                    </button>
                </div>

            </div>
        </div>
    </section>
</main>

    <script>
        const toggleBtn = document.getElementById('editToggle');
        const accordion = document.getElementById('editAccordion');
        const emailInput = document.getElementById('email');
        const sendButton = document.getElementById('sendButton');
        const newButton = document.getElementById('newButton');
        const successMsg = document.getElementById('selectSuccess');
        const errorMsg = document.getElementById('selectError');
        const cooldownStorageKey = 'lp_editor_select_cooldown_map';
        let cooldownTimer = null;

        function isEmailValid() {
            const email = emailInput.value.trim();
            return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
        }

        function normalizeEmail(email) {
            return (email || '').trim().toLowerCase();
        }

        function getCooldownMap() {
            try {
                const raw = localStorage.getItem(cooldownStorageKey);
                const parsed = raw ? JSON.parse(raw) : {};
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (_) {
                return {};
            }
        }

        function setCooldownMap(map) {
            localStorage.setItem(cooldownStorageKey, JSON.stringify(map));
        }

        function cleanupCooldownMap() {
            const map = getCooldownMap();
            const now = Date.now();
            const next = {};
            Object.keys(map).forEach((key) => {
                const until = parseInt(map[key], 10);
                if (!Number.isNaN(until) && until > now) {
                    next[key] = until;
                }
            });
            setCooldownMap(next);
            return next;
        }

        function getCooldownUntilForEmail(email) {
            const key = normalizeEmail(email);
            if (!key) return 0;
            const map = cleanupCooldownMap();
            const until = parseInt(map[key] || '0', 10);
            return Number.isNaN(until) ? 0 : until;
        }

        function setCooldownUntilForEmail(email, until) {
            const key = normalizeEmail(email);
            if (!key) return;
            const map = cleanupCooldownMap();
            map[key] = until;
            setCooldownMap(map);
        }

        function hasAnyCooldown() {
            const map = cleanupCooldownMap();
            return Object.keys(map).length > 0;
        }

        function ensureCooldownTimer() {
            if (cooldownTimer) return;
            cooldownTimer = setInterval(() => {
                refreshSendButtonState();
                if (!hasAnyCooldown()) {
                    clearInterval(cooldownTimer);
                    cooldownTimer = null;
                }
            }, 250);
        }

        function refreshSendButtonState() {
            const now = Date.now();
            const currentEmail = normalizeEmail(emailInput.value);
            const cooldownUntil = getCooldownUntilForEmail(currentEmail);
            const inCooldown = cooldownUntil > now;
            const valid = isEmailValid();

            if (inCooldown) {
                const sec = Math.max(1, Math.ceil((cooldownUntil - now) / 1000));
                sendButton.disabled = true;
                sendButton.classList.remove('enabled');
                sendButton.textContent = `再送まで ${sec}秒`;
                return;
            }

            sendButton.textContent = 'メール送信';
            if (valid) {
                sendButton.classList.add('enabled');
                sendButton.disabled = false;
            } else {
                sendButton.classList.remove('enabled');
                sendButton.disabled = true;
            }
        }

        function startCooldown(seconds, email) {
            const until = Date.now() + (seconds * 1000);
            setCooldownUntilForEmail(email, until);
            refreshSendButtonState();
            ensureCooldownTimer();
        }

        function messageByStatus(status, data) {
            const apiMessage = (data && typeof data.message === 'string') ? data.message : '';
            if (apiMessage) return apiMessage;

            if (status === 400) return '入力内容を確認してください。';
            if (status === 403) return '認証エラーが発生しました。ページを再読み込みしてください。';
            if (status === 404) return '該当するLPが見つかりませんでした。';
            if (status === 429) return '短時間に繰り返し送信されています。しばらく待ってから再度お試しください。';
            if (status >= 500) return 'サーバーエラーが発生しました。時間をおいて再度お試しください。';
            return '送信に失敗しました。もう一度お試しください。';
        }

        // アクセスログ送信（select画面用）
        function sendSelectAccessLog(actionName, extra) {
            try {
                fetch('<?php echo esc_url(rest_url('lp-editor/v1/access-log')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-LP-Nonce': '<?php echo wp_create_nonce('lp_editor_public_api'); ?>'
                    },
                    body: JSON.stringify(Object.assign({
                        action: actionName,
                        page_name: '作成・編集選択',
                        lp_id: '',
                        screen_size: screen.width + 'x' + screen.height,
                        window_size: window.innerWidth + 'x' + window.innerHeight
                    }, extra || {}))
                }).catch(function() {});
            } catch (e) {}
        }

        // アコーディオン開閉
        toggleBtn.addEventListener('click', () => {
            accordion.classList.toggle('open');
            // 「開く」方向の場合のみ通知
            if (accordion.classList.contains('open')) {
                sendSelectAccessLog('公開中のLPを編集する');
            }
        });

        // リロード後もクールダウンを維持
        cleanupCooldownMap();
        if (hasAnyCooldown()) {
            ensureCooldownTimer();
        }

        // メール入力監視
        emailInput.addEventListener('input', () => {
            const email = emailInput.value.trim();
            // 新規作成ボタンをグレーダウン
            if (email.length > 0) {
                newButton.classList.add('disabled');
            } else {
                newButton.classList.remove('disabled');
            }

            // 送信ボタン制御
            refreshSendButtonState();
        });

        // 初期状態の送信ボタン反映
        refreshSendButtonState();

        // WP REST API 送信
        sendButton.addEventListener('click', () => {
            const email = emailInput.value.trim();
            if (!email || sendButton.disabled) return;

            sendButton.disabled = true;
            sendButton.textContent = '送信中…';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';

            fetch('<?php echo esc_url(rest_url('lp-editor/v1/request-edit-url')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-LP-Nonce': '<?php echo wp_create_nonce('lp_editor_public_api'); ?>'
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(async (res) => {
                    let data = {};
                    try {
                        data = await res.json();
                    } catch (_) {
                        data = {};
                    }
                    return {
                        status: res.status,
                        data: data
                    };
                })
                .then(({
                    status,
                    data
                }) => {
                    if (data.success) {
                        successMsg.textContent = data.message || '編集URLをメールに送信しました。';
                        successMsg.style.display = 'block';
                        // アクセスURL発行の通知
                        sendSelectAccessLog('アクセスURL発行', { email: email });
                    } else {
                        errorMsg.textContent = messageByStatus(status, data);
                        errorMsg.style.display = 'block';
                        if (status === 429) {
                            const m = (data.message || '').match(/(\d+)\s*秒/);
                            const wait = m ? parseInt(m[1], 10) : 60;
                            startCooldown(wait, email);
                        }
                    }
                })
                .catch(() => {
                    errorMsg.textContent = '通信エラーが発生しました。もう一度お試しください。';
                    errorMsg.style.display = 'block';
                })
                .finally(() => {
                    refreshSendButtonState();
                });
        });
    </script>
<?php get_footer('service'); ?>
