/**
 * KingJoe — Prism 代码块增强
 * - 代码块右上角显示语言标签
 * - 一键复制按钮
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function addCopyButton(pre) {
        if (pre.dataset.kjEnhanced) return;
        pre.dataset.kjEnhanced = '1';

        // 容器
        var wrapper = document.createElement('div');
        wrapper.className = 'kj-code__toolbar';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        // 语言标签
        var code = pre.querySelector('code');
        var lang = '';
        if (code) {
            var m = (code.className || '').match(/language-([\w-]+)/);
            if (m) lang = m[1];
        }
        if (lang) {
            var tag = document.createElement('span');
            tag.className = 'kj-code__lang';
            tag.textContent = lang;
            wrapper.appendChild(tag);
        }

        // 复制按钮
        var btn = document.createElement('button');
        btn.className = 'joe-code__copy';
        btn.type = 'button';
        btn.setAttribute('aria-label', '复制代码');
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M5 15V5a2 2 0 0 1 2-2h8" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg><span>复制</span>';
        btn.addEventListener('click', function () {
            var text = code ? code.textContent : pre.textContent;
            var done = function () {
                btn.classList.add('is-done');
                var span = btn.querySelector('span');
                if (span) span.textContent = '已复制';
                setTimeout(function () {
                    btn.classList.remove('is-done');
                    if (span) span.textContent = '复制';
                }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, fallbackCopy);
            } else {
                fallbackCopy();
            }
            function fallbackCopy() {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(ta);
            }
        });
        wrapper.appendChild(btn);
    }

    ready(function () {
        var scope = document.getElementById('joe-content') || document.body;
        var pres = scope.querySelectorAll('pre');
        pres.forEach(addCopyButton);

        // Prism autoloader 异步加载语言后会重新高亮，监听 DOM 变化补加按钮
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.tagName === 'PRE' && !n.dataset.kjEnhanced) addCopyButton(n);
                    var inner = n.querySelectorAll ? n.querySelectorAll('pre:not([data-kj-enhanced])') : [];
                    inner.forEach(addCopyButton);
                });
            });
        });
        observer.observe(scope, { childList: true, subtree: true });
    });
})();
