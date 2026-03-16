/**
 * オプションページ決済フロント処理
 * Stripe Payment Element 方式
 */
(function () {
  'use strict';

  var stripe, elements, emailAddress;

  var step1 = document.getElementById('step-1');
  var step2 = document.getElementById('step-2');
  var step3 = document.getElementById('step-3');
  var inputEmail = document.getElementById('input-email');
  var emailError = document.getElementById('email-error');
  var btnToPayment = document.getElementById('btn-to-payment');
  var btnPay = document.getElementById('btn-pay');
  var paymentError = document.getElementById('payment-error');
  var displayEmail = document.getElementById('display-email');
  var btnChangeEmail = document.getElementById('btn-change-email');

  // ページ読み込み時: ?payment=complete があればステップ3を表示
  // （3Dセキュア等のリダイレクト戻り対応）
  if (new URLSearchParams(window.location.search).get('payment') === 'complete') {
    showStep(3);
  }

  function showStep(n) {
    step1.style.display = n === 1 ? 'block' : 'none';
    step2.style.display = n === 2 ? 'block' : 'none';
    step3.style.display = n === 3 ? 'block' : 'none';
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // ステップ1 → 2
  btnToPayment.addEventListener('click', async function () {
    var email = inputEmail.value.trim();
    emailError.textContent = '';

    if (!email) {
      emailError.textContent = 'メールアドレスを入力してください';
      return;
    }
    if (!isValidEmail(email)) {
      emailError.textContent = '有効なメールアドレスを入力してください';
      return;
    }

    btnToPayment.disabled = true;
    btnToPayment.textContent = '処理中...';

    try {
      var res = await fetch('/wp-json/lp-editor/v1/create-payment-intent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
      });
      var data = await res.json();

      if (!data.success) {
        emailError.textContent = data.message || '決済の初期化に失敗しました';
        btnToPayment.disabled = false;
        btnToPayment.textContent = '決済に進む';
        return;
      }

      emailAddress = email;
      displayEmail.textContent = email;

      stripe = Stripe(data.pk);
      elements = stripe.elements({ clientSecret: data.clientSecret, locale: 'ja' });
      var paymentElement = elements.create('payment');
      paymentElement.mount('#payment-element');

      showStep(2);
    } catch (err) {
      emailError.textContent = '通信エラーが発生しました';
    }

    btnToPayment.disabled = false;
    btnToPayment.textContent = '決済に進む';
  });

  // ステップ2 → 3（支払い）
  btnPay.addEventListener('click', async function () {
    btnPay.disabled = true;
    btnPay.textContent = '処理中...';
    paymentError.textContent = '';

    var result = await stripe.confirmPayment({
      elements: elements,
      confirmParams: {
        return_url: window.location.origin + '/option/?payment=complete',
        receipt_email: emailAddress
      },
      redirect: 'if_required'
    });

    if (result.error) {
      paymentError.textContent = result.error.message || '決済に失敗しました';
      btnPay.disabled = false;
      btnPay.textContent = '10,000円を支払う';
    } else {
      showStep(3);
    }
  });

  // メアド変更リンク
  btnChangeEmail.addEventListener('click', function (e) {
    e.preventDefault();
    showStep(1);
  });
})();
