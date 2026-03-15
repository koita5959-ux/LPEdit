/* ================================================
   LP自動作成機能 - JavaScript
   ================================================ */

(function () {
  'use strict';

  // --- ハンバーガーメニュー開閉 ---
  var hamburger = document.getElementById('hamburger');
  var nav = document.getElementById('headerNav');

  if (hamburger && nav) {
    hamburger.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      hamburger.classList.toggle('is-active');
      hamburger.setAttribute('aria-expanded', String(isOpen));
      hamburger.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
    });

    // ナビリンクをクリックしたらメニューを閉じる
    var navLinks = nav.querySelectorAll('a');
    for (var i = 0; i < navLinks.length; i++) {
      navLinks[i].addEventListener('click', function () {
        nav.classList.remove('is-open');
        hamburger.classList.remove('is-active');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', 'メニューを開く');
      });
    }

    // 画面リサイズ時にメニューをリセット
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) {
        nav.classList.remove('is-open');
        hamburger.classList.remove('is-active');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // --- FAQアコーディオン スムーズアニメーション ---
  var faqItems = document.querySelectorAll('.faq-item');

  for (var j = 0; j < faqItems.length; j++) {
    (function (details) {
      var summary = details.querySelector('.faq-question');
      var answer = details.querySelector('.faq-answer');
      if (!summary || !answer) return;

      summary.addEventListener('click', function (e) {
        e.preventDefault();

        if (details.open) {
          // 閉じる開始
          details.classList.remove('is-active');
          
          answer.style.maxHeight = answer.scrollHeight + 'px';
          answer.style.paddingTop = '16px';
          answer.style.paddingBottom = '24px';
          
          requestAnimationFrame(function () {
            answer.style.maxHeight = '0';
            answer.style.paddingTop = '0';
            answer.style.paddingBottom = '0';
            answer.style.opacity = '0';
          });
          
          answer.addEventListener('transitionend', function handler() {
            details.open = false;
            answer.style.maxHeight = '';
            answer.style.paddingTop = '';
            answer.style.paddingBottom = '';
            answer.style.opacity = '';
            answer.removeEventListener('transitionend', handler);
          }, { once: true });
        } else {
          // 開く開始
          details.open = true;
          details.classList.add('is-active');
          
          answer.style.maxHeight = '0';
          answer.style.paddingTop = '0';
          answer.style.paddingBottom = '0';
          answer.style.opacity = '0';
          
          requestAnimationFrame(function () {
            var height = answer.scrollHeight;
            answer.style.maxHeight = height + 'px';
            answer.style.paddingTop = '16px';
            answer.style.paddingBottom = '24px';
            answer.style.opacity = '1';
          });
          
          answer.addEventListener('transitionend', function handler() {
            answer.style.maxHeight = '';
            answer.style.paddingTop = '';
            answer.style.paddingBottom = '';
            answer.style.opacity = '';
            answer.removeEventListener('transitionend', handler);
          }, { once: true });
        }
      });
    })(faqItems[j]);
  }

  // --- メールアドレス スパム対策 ---
  var emailElements = document.querySelectorAll('.email-protect');
  for (var k = 0; k < emailElements.length; k++) {
    (function (el) {
      var u = el.getAttribute('data-u');
      var d = el.getAttribute('data-d');
      if (u && d) {
        var addr = u + '@' + d;
        var link = document.createElement('a');
        link.href = 'mai' + 'lto:' + addr;
        link.textContent = addr;
        link.className = 'email-protect';
        el.parentNode.replaceChild(link, el);
      }
    })(emailElements[k]);
  }
})();
