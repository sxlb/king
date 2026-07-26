/**
 * KingJoe Pjax - 全站无刷新加载引擎
 * 兼容 fetch API，无 jQuery 依赖
 */
(function () {
    'use strict';

    var d = document;
    var w = window;
    var KEY_PJAX = 'kingjoe-pjax-state';

    // Pjax 配置
    var config = {
        container: '#app',           // 内容容器
        timeout: 8000,               // 请求超时 ms
        cacheMax: 30,                // 最大缓存页数
        scrollRestore: true,         // 恢复滚动位置
    };

    var isTransitioning = false;
    var currentUrl = location.href;
    var cache = {};
    var cacheKeys = [];
    var scrollPositions = {};

    // ---------- 工具函数 ----------
    function sameOrigin(url) {
        try {
            var u = new URL(url, location.origin);
            return u.origin === location.origin;
        } catch (e) {
            return false;
        }
    }

    function isInternalLink(a) {
        if (!a.href || a.hostname !== location.hostname) return false;
        if (a.target === '_blank') return false;
        if (a.getAttribute('download') !== null) return false;
        if (a.getAttribute('data-no-pjax') !== null) return false;
        if (a.hash && a.pathname + a.search === location.pathname + location.search) return false;
        // 跳过评论提交、登出等操作链接
        if (a.href.indexOf('/logout') > -1) return false;
        if (a.href.indexOf('/action/') > -1) return false;
        if (a.href.indexOf('/feed') > -1) return false;
        if (a.closest('[data-no-pjax]')) return false;
        // 跳过管理链接
        if (a.href.indexOf('/admin/') > -1) return false;
        return true;
    }

    function parseHTML(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        return doc;
    }

    function getPageTitle(doc) {
        var title = doc.querySelector('title');
        return title ? title.textContent : '';
    }

    function getPageContent(doc) {
        return doc.querySelector(config.container);
    }

    function getPageScripts(doc, container) {
        // 收集新页面 body 中的 script（不含 src 的外部脚本，外部脚本由 addScriptsFromHead 处理）
        if (!container) return [];
        var scripts = container.querySelectorAll('script');
        var result = [];
        for (var i = 0; i < scripts.length; i++) {
            var s = scripts[i];
            if (s.src) continue; // 外部脚本通过 addScriptsFromHead 处理
            if (s.textContent.trim()) {
                result.push(s.textContent);
            }
        }
        return result;
    }

    function addScriptsFromHead(doc) {
        var scripts = doc.querySelectorAll('head script[src]');
        var existing = {};
        d.querySelectorAll('head script[src]').forEach(function (s) {
            if (s.src) existing[s.src] = true;
        });
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src;
            if (src && !existing[src]) {
                var el = d.createElement('script');
                el.src = src;
                el.async = true;
                d.head.appendChild(el);
                existing[src] = true;
            }
        }
    }

    function updateHead(doc) {
        // 更新 title
        var newTitle = doc.querySelector('title');
        if (newTitle) {
            d.title = newTitle.textContent;
        }

        // 更新 meta description
        var metas = doc.querySelectorAll('meta');
        var currentMetas = d.querySelectorAll('meta');
        metas.forEach(function (m) {
            var name = m.name || m.getAttribute('property');
            if (!name) return;
            // 更新现有 meta
            var found = false;
            currentMetas.forEach(function (cm) {
                if ((cm.name || cm.getAttribute('property')) === name) {
                    cm.setAttribute('content', m.getAttribute('content'));
                    found = true;
                }
            });
            if (!found) {
                var clone = m.cloneNode(true);
                d.head.appendChild(clone);
            }
        });

        // 更新 canonical
        var newCanonical = doc.querySelector('link[rel="canonical"]');
        var oldCanonical = d.querySelector('link[rel="canonical"]');
        if (newCanonical && oldCanonical) {
            oldCanonical.href = newCanonical.href;
        }
    }

    function addToCache(url, content) {
        cache[url] = content;
        cacheKeys.push(url);
        if (cacheKeys.length > config.cacheMax) {
            var old = cacheKeys.shift();
            delete cache[old];
        }
    }

    function saveScrollPos() {
        try {
            sessionStorage.setItem(KEY_PJAX + '-scroll', JSON.stringify({
                url: currentUrl,
                x: w.scrollX || w.pageXOffset,
                y: w.scrollY || w.pageYOffset
            }));
        } catch (e) {}
    }

    function restoreScrollPos() {
        try {
            var raw = sessionStorage.getItem(KEY_PJAX + '-scroll');
            if (raw) {
                var pos = JSON.parse(raw);
                if (pos && pos.y !== undefined) {
                    w.scrollTo(pos.x || 0, pos.y);
                }
                sessionStorage.removeItem(KEY_PJAX + '-scroll');
            }
        } catch (e) {
            w.scrollTo(0, 0);
        }
    }

    // ---------- 加载器 ----------
    function loadingStart() {
        if (d.getElementById('joe-pjax-loader')) return;
        var bar = d.createElement('div');
        bar.id = 'joe-pjax-loader';
        bar.className = 'joe-pjax-loader';
        bar.innerHTML = '<div class="joe-pjax-loader__bar"></div>';
        d.body.appendChild(bar);
        // 触发动画
        requestAnimationFrame(function () {
            var b = bar.querySelector('.joe-pjax-loader__bar');
            if (b) b.classList.add('is-animate');
        });
    }

    function loadingEnd() {
        var bar = d.getElementById('joe-pjax-loader');
        if (bar) {
            var b = bar.querySelector('.joe-pjax-loader__bar');
            if (b) b.classList.add('is-done');
            setTimeout(function () {
                if (bar.parentNode) bar.parentNode.removeChild(bar);
            }, 300);
        }
    }

    // ---------- 内容切换 ----------
    function swapContent(newContent) {
        var container = d.querySelector(config.container);
        if (!container || !newContent) return false;

        // 淡出
        container.style.opacity = '0';
        container.style.transform = 'translateY(8px)';

        setTimeout(function () {
            // 替换内容
            container.innerHTML = newContent.innerHTML;
            // 替换 class
            container.className = newContent.className;

            // 淡入
            requestAnimationFrame(function () {
                container.style.opacity = '';
                container.style.transform = '';
            });

            // 触发 Pjax 完成事件
            var evt = new CustomEvent('pjax:complete', {
                bubbles: true,
                detail: { url: currentUrl }
            });
            d.dispatchEvent(evt);

            // 滚动到顶部或恢复位置
            if (config.scrollRestore) {
                restoreScrollPos();
            } else {
                w.scrollTo(0, 0);
            }

            // 执行内联脚本
            setTimeout(function () {
                var inlineScripts = newContent.querySelectorAll('script:not([src])');
                inlineScripts.forEach(function (s) {
                    if (s.textContent.trim()) {
                        try {
                            var fn = new Function(s.textContent);
                            fn();
                        } catch (e) {
                            console.warn('Pjax inline script error:', e);
                        }
                    }
                });
            }, 50);
        }, 200);

        return true;
    }

    // ---------- 导航 ----------
    function navigate(url, pushState) {
        if (isTransitioning) return;
        if (!sameOrigin(url)) {
            location.href = url;
            return;
        }

        // 检查缓存
        if (cache[url]) {
            loadFromCache(url, pushState);
            return;
        }

        isTransitioning = true;
        saveScrollPos();
        loadingStart();

        var controller = new AbortController();
        var timeout = setTimeout(function () {
            controller.abort();
            location.href = url; // 超时回退到整页刷新
        }, config.timeout);

        fetch(url, {
            signal: controller.signal,
            headers: { 'X-PJAX': 'true', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            clearTimeout(timeout);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function (html) {
            var doc = parseHTML(html);
            var content = getPageContent(doc);
            if (!content) throw new Error('No container found');

            // 更新缓存
            addToCache(url, content);

            // 更新页面
            updateHead(doc);
            addScriptsFromHead(doc);

            if (pushState !== false) {
                w.history.pushState({ url: url, pjax: true }, getPageTitle(doc), url);
            }
            currentUrl = url;
            swapContent(content);
        })
        .catch(function (err) {
            console.warn('Pjax error:', err.message);
            location.href = url; // 失败回退
        })
        .finally(function () {
            loadingEnd();
            isTransitioning = false;
        });
    }

    function loadFromCache(url, pushState) {
        if (isTransitioning) return;
        isTransitioning = true;
        saveScrollPos();
        loadingStart();

        var content = cache[url];
        if (!content) {
            loadingEnd();
            isTransitioning = false;
            return;
        }

        var doc = parseHTML('<html><head></head><body>' + content.outerHTML + '</body></html>');
        var title = getPageTitle(doc);

        setTimeout(function () {
            // 更新 head 元数据
            updateHead(doc);
            addScriptsFromHead(doc);

            if (pushState !== false) {
                w.history.pushState({ url: url, pjax: true }, title || d.title, url);
            }
            currentUrl = url;
            swapContent(content);
            loadingEnd();
            isTransitioning = false;
        }, 100);
    }

    // ---------- 事件绑定 ----------
    function bindLinks() {
        // 全局点击委托
        d.addEventListener('click', function (e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey) return;
            if (e.defaultPrevented) return;

            var a = e.target.closest('a');
            if (!a || !isInternalLink(a)) return;

            e.preventDefault();
            navigate(a.href, true);
        });
    }

    // ---------- popstate ----------
    w.addEventListener('popstate', function (e) {
        if (e.state && e.state.pjax && e.state.url) {
            saveScrollPos();
            navigate(e.state.url, false);
        }
    });

    // ---------- Pjax 按钮（上一页/下一页）----------
    function bindPagination() {
        d.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-pjax]');
            if (!btn) return;
            var url = btn.getAttribute('data-pjax');
            if (!url) return;
            e.preventDefault();
            navigate(url, true);
        });
    }

    // ---------- 初始化 ----------
    function init() {
        bindLinks();
        bindPagination();

        // 标记当前状态，防止初始 popstate
        w.history.replaceState({ url: location.href, pjax: true }, d.title, location.href);

        // 暴露 Pjax API
        w.KingJoePjax = {
            navigate: navigate,
            reload: function () {
                saveScrollPos();
                navigate(location.href, false);
            },
            cache: cache,
            config: config
        };
    }

    // Pjax 配置开关检查
    if (typeof KINGJOE_PJAX !== 'undefined' && KINGJOE_PJAX === true) {
        if (d.readyState === 'loading') {
            d.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    }

})();
