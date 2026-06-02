/**
 * 子比主题美化增强 - 前台交互脚本
 */
(function () {
  'use strict';

  var cfg = window.ZibeEnhConfig || {};

  /* ─── 工具函数 ──────────────────────────────────────────── */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }
  function onReady(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  /* ─── 1. 顶部进度条 ─────────────────────────────────────── */
  if (cfg.loadingBar === '1') {
    var bar = document.createElement('div');
    bar.id = 'zibe-loading-bar';
    document.head.appendChild(bar); // 先挂到 head 内防布局抖动

    var progress = 0;
    var barTimer;

    function startBar() {
      progress = 0;
      bar.style.width = '0%';
      bar.style.opacity = '1';
      barTimer = setInterval(function () {
        progress += Math.random() * 8;
        if (progress > 90) progress = 90;
        bar.style.width = progress + '%';
      }, 200);
    }

    function finishBar() {
      clearInterval(barTimer);
      bar.classList.add('done');
      setTimeout(function () {
        bar.classList.remove('done');
        bar.style.opacity = '0';
      }, 600);
    }

    startBar();
    window.addEventListener('load', finishBar);
    // 防止页面已经 loaded 时仍在转
    if (document.readyState === 'complete') finishBar();

    onReady(function () {
      // 拦截链接跳转，触发进度条
      document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || a.target === '_blank') return;
        startBar();
      });
    });
  }

  /* ─── 2. 页面滚动：导航栏阴影 & 返回顶部 ───────────────── */
  onReady(function () {
    // 导航阴影
    var htmlEl = document.documentElement;
    function onScroll() {
      if (window.scrollY > 50) {
        htmlEl.classList.add('zibe-header-scroll');
      } else {
        htmlEl.classList.remove('zibe-header-scroll');
      }

      // 返回顶部
      if (backTop) {
        if (window.scrollY > 400) {
          backTop.classList.add('visible');
        } else {
          backTop.classList.remove('visible');
        }
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });

    // 返回顶部按钮
    var backTop = document.getElementById('zibe-back-top');
    if (!backTop) {
      // 如果 PHP 没输出，动态创建
      backTop = document.createElement('button');
      backTop.id = 'zibe-back-top';
      backTop.setAttribute('aria-label', '返回顶部');
      backTop.innerHTML = '↑';
      document.body.appendChild(backTop);
    }
    backTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  /* ─── 3. 鼠标光效（CSS 变量驱动）──────────────────────── */
  if (cfg.mouseEffect === '1') {
    onReady(function () {
      document.documentElement.classList.add('zibe-mouse-effect');
      var cards = $$('.zib-post-wrap, .zib-post-item, .site-card');
      cards.forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
          var rect = card.getBoundingClientRect();
          var x = ((e.clientX - rect.left) / rect.width  * 100).toFixed(1) + '%';
          var y = ((e.clientY - rect.top)  / rect.height * 100).toFixed(1) + '%';
          card.style.setProperty('--mx', x);
          card.style.setProperty('--my', y);
        });
      });
    });
  }

  /* ─── 4. 卡片悬停上浮 ──────────────────────────────────── */
  if (cfg.animations === '1') {
    onReady(function () {
      document.documentElement.classList.add('zibe-card-lift');
    });
  }

  /* ─── 5. 侧边栏毛玻璃 ──────────────────────────────────── */
  // 通过 PHP 在 body class 中控制，此处备用
  onReady(function () {
    var bodyClass = document.body.className;
    if (bodyClass.indexOf('zibe-sidebar-glass') === -1) {
      // 若 PHP 没加类则检查选项（降级处理）
    }
  });

  /* ─── 6. 点击波纹 ───────────────────────────────────────── */
  if (cfg.ripple === '1') {
    onReady(function () {
      var rippleTargets = $$('.zib-btn, .btn-primary, button[type="submit"], input[type="submit"], .site-btn, .zib-tags a, .post-tag');
      rippleTargets.forEach(function (el) {
        el.classList.add('zibe-ripple-host');
        el.addEventListener('click', function (e) {
          var rect = el.getBoundingClientRect();
          var size = Math.max(rect.width, rect.height) * 1.5;
          var wave = document.createElement('span');
          wave.className = 'zibe-ripple-wave';
          wave.style.cssText = [
            'width:'  + size + 'px',
            'height:' + size + 'px',
            'left:'   + (e.clientX - rect.left  - size / 2) + 'px',
            'top:'    + (e.clientY - rect.top   - size / 2) + 'px',
          ].join(';');
          el.appendChild(wave);
          wave.addEventListener('animationend', function () {
            wave.remove();
          });
        });
      });
    });
  }

  /* ─── 7. 图片懒加载辅助（IntersectionObserver）─────────── */
  onReady(function () {
    if (!('IntersectionObserver' in window)) return;
    var imgs = $$('img[data-src], img[loading="lazy"]');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
            delete img.dataset.src;
          }
          io.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });
    imgs.forEach(function (img) { io.observe(img); });
  });

  /* ─── 8. 平滑锚点跳转 ───────────────────────────────────── */
  onReady(function () {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#"]');
      if (!a) return;
      var target = document.getElementById(a.getAttribute('href').slice(1));
      if (!target) return;
      e.preventDefault();
      var offset = 80; // 导航栏高度
      var top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top: top, behavior: 'smooth' });
    });
  });

})();
