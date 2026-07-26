/**
 * KingJoe 主题交互脚本
 * https://github.com/sxlb/king
 *
 * 功能索引
 * ────────────────────────────────────────────
 * 01. 工具函数          throttle, debounce, toggleClass
 * 02. 主题系统          暗黑模式切换、系统主题跟随
 * 03. 点赞按钮          文章点赞/取消点赞
 * 04. 图片灯箱          点击放大、左右切换、ESC关闭
 * 05. 鼠标特效          爱心/文字/粒子
 * 06. 侧边栏Tab         热门/随机切换
 * 07. 页面加载进度条
 * 08. 评论内链跳转
 * 09. 阅读模式          正文阅读模式切换
 * 10. 复制追加版权
 * 11. 快捷键系统        键盘快捷操作
 * 12. 打赏按钮
 * 13. 智能导航隐藏
 * 14. 文章浮动目录点
 * 15. 评论@回复
 * 16. 图片骨架屏渐入
 * 17. 公告栏关闭
 * 18. 无限滚动          首页文章自动加载
 * 19. 运行时间动态
 * 20. 首页轮播图
 * 21. 全站飘落特效
 * 22. 全局音乐播放器
 * 23. 友链在线申请
 * 24. 鱼群跳跃特效
 * 25. SSL认证图标
 * 26. 动态星空背景
 * 27. 百度收录提交
 * 28. 复制版权弹窗
 * 29. 缩略图替换
 * 30. 文章导读卡片
 * 31. 移动端适配
 * 32. Pjax 回调处理
 * 33. 评论点赞
 * 34. 时光机加载
 * 35. 移动端悬浮栏
 * 36. 代码块复制
 * 37. 字体大小调节
 * ────────────────────────────────────────────
 */
var initKingJoe = (function () {
    'use strict';

    var KEY_THEME = 'kingjoe-theme';
    var d = document;
    var w = window;
    var body = d.body;

    // ▸ 工具函数
    function throttle(fn, delay) {
        var last = 0;
        return function () {
            var now = Date.now();
            if (now - last >= delay) {
                last = now;
                fn.apply(null, arguments);
            }
        };
    }

    // ▸ 暗黑模式
    var mqDark = w.matchMedia('(prefers-color-scheme: dark)');

    function currentMode() {
        return body.classList.contains('is-dark') ? 'dark' : 'light';
    }

    function applyTheme(mode) {
        if (mode === 'auto') {
            body.classList.toggle('is-dark', mqDark.matches);
        } else {
            body.classList.toggle('is-dark', mode === 'dark');
        }
    }

    function getSavedTheme() {
        try { return localStorage.getItem(KEY_THEME) || 'auto'; } catch (e) { return 'auto'; }
    }

    function toggleTheme() {
        var cur = getSavedTheme();
        var next = cur === 'dark' ? 'light' : (cur === 'light' ? 'auto' : 'dark');
        applyTheme(next);
        try { localStorage.setItem(KEY_THEME, next); } catch (e) {}
        syncToggleIcon();
    }

    // 监听系统主题变化
    mqDark.addEventListener('change', function () {
        if (getSavedTheme() === 'auto') {
            body.classList.toggle('is-dark', mqDark.matches);
            syncToggleIcon();
        }
    });

    // 同步按钮图标状态
    function syncToggleIcon() {
        var btns = d.querySelectorAll('.joe-theme__toggle');
        btns.forEach(function (btn) {
            var m = getSavedTheme();
            btn.setAttribute('data-mode', m);
        });
    }

    d.addEventListener('click', function (e) {
        var t = e.target.closest('.joe-theme__toggle');
        if (t) {
            toggleTheme();
            return;
        }
    });

    // ▸ 搜索弹层
    var search = d.getElementById('joe-search');
    if (search) {
        d.addEventListener('click', function (e) {
            if (e.target.closest('.joe-search__trigger')) {
                search.classList.add('is-open');
                var input = search.querySelector('.joe-search__input');
                if (input) setTimeout(function () { input.focus(); }, 50);
            } else if (e.target.closest('[data-close-search]')) {
                search.classList.remove('is-open');
            }
        });
        d.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') search.classList.remove('is-open');
            // Ctrl/Cmd + K 唤起搜索
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                search.classList.add('is-open');
                var input = search.querySelector('.joe-search__input');
                if (input) setTimeout(function () { input.focus(); }, 50);
            }
        });
    }

    // ▸ 移动端汉堡菜单
    var hamburger = d.getElementById('joe-hamburger');
    var nav = d.querySelector('.joe-nav');
    var drawer = d.getElementById('joe-drawer');
    if (hamburger && nav) {
        hamburger.addEventListener('click', function () {
            hamburger.classList.toggle('is-active');
            nav.classList.toggle('is-open');
        });
        // 点击导航链接后自动关闭
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) {
                hamburger.classList.remove('is-active');
                nav.classList.remove('is-open');
            }
        });
    }
    if (drawer) {
        d.addEventListener('click', function (e) {
            if (e.target.closest('#joe-hamburger') && drawer) {
                drawer.classList.add('is-open');
            } else if (e.target.closest('[data-close-drawer]')) {
                drawer.classList.remove('is-open');
            }
        });
    }

    // ▸ 返回顶部
    var backTop = d.getElementById('joe-backtop');
    var backtopCircle = null;
    var backtopText = null;
    if (backtop) {
        backtopCircle = backtop.querySelector('.joe-backtop__progress circle');
        backtopText = d.getElementById('joe-backtop-text');

        var onScroll = function () {
            var scrollH = document.documentElement.scrollHeight - window.innerHeight;
            var pct = scrollH > 0 ? Math.round((window.pageYOffset / scrollH) * 100) : 0;

            if (window.pageYOffset > 300) {
                backtop.classList.add('is-show');
            } else {
                backtop.classList.remove('is-show');
            }

            // 更新进度环
            if (backtopCircle) {
                var circumference = 2 * Math.PI * 20;
                var offset = circumference - (pct / 100) * circumference;
                backtopCircle.style.strokeDasharray = circumference;
                backtopCircle.style.strokeDashoffset = offset;
            }
            // 更新百分比文字
            if (backtopText) {
                backtopText.textContent = pct + '%';
            }
            updateTocActive();
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        backtop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ▸ TOC 折叠
    var tocToggle = d.querySelector('.joe-toc__toggle');
    if (tocToggle) {
        tocToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var toc = this.closest('.joe-toc');
            if (toc) toc.classList.toggle('is-collapsed');
        });
    }

    // 点击 TOC 头部也可展开
    var tocHead = d.querySelector('.joe-toc__head');
    if (tocHead) {
        tocHead.addEventListener('click', function (e) {
            if (e.target.closest('.joe-toc__toggle')) return; // 不重复处理按钮点击
            var toc = this.closest('.joe-toc');
            if (toc && toc.classList.contains('is-collapsed')) {
                toc.classList.remove('is-collapsed');
            }
        });
    }

    // ▸ TOC 滚动高亮
    var toc = d.getElementById('joe-toc');
    var content = d.getElementById('joe-content');
    var headings = [];
    var tocLinks = [];

    function initToc() {
        if (!toc || !content) return;
        tocLinks = Array.prototype.slice.call(toc.querySelectorAll('a[data-toc-id]'));
        headings = tocLinks.map(function (a) {
            var id = a.getAttribute('data-toc-id');
            return d.getElementById(id);
        }).filter(Boolean);
    }
    function updateTocActive() {
        if (!headings.length) return;
        var scrollY = window.pageYOffset;
        var offsetTop = 100;
        var current = -1;
        for (var i = 0; i < headings.length; i++) {
            if (headings[i].offsetTop - offsetTop <= scrollY) {
                current = i;
            }
        }
        tocLinks.forEach(function (a, i) {
            a.classList.toggle('is-active', i === current);
        });
        // 自动滚动 TOC 让 active 可见
        if (current >= 0) {
            var activeEl = tocLinks[current];
            var tocRect = toc.getBoundingClientRect();
            var activeRect = activeEl.getBoundingClientRect();
            if (activeRect.top < tocRect.top || activeRect.bottom > tocRect.bottom) {
                toc.scrollTop = activeEl.offsetTop - toc.clientHeight / 2;
            }
        }
    }
    initToc();

    // TOC 链接平滑滚动
    if (toc) {
        toc.addEventListener('click', function (e) {
            var a = e.target.closest('a[data-toc-id]');
            if (!a) return;
            var id = a.getAttribute('data-toc-id');
            var target = d.getElementById(id);
            if (target) {
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: top, behavior: 'smooth' });
                if (history.replaceState) {
                    history.replaceState(null, '', '#' + id);
                }
            }
        });
    }

    // ▸ 移动端 TOC 抽屉
    var tocDrawer = d.getElementById('joe-toc-drawer');
    var tocFab = d.getElementById('joe-toc-fab');
    if (tocDrawer && tocFab) {
        function openTocDrawer()  { tocDrawer.classList.add('is-open'); d.body.style.overflow = 'hidden'; }
        function closeTocDrawer() { tocDrawer.classList.remove('is-open'); d.body.style.overflow = ''; }

        tocFab.addEventListener('click', openTocDrawer);

        d.addEventListener('click', function (e) {
            if (e.target.closest('[data-close-toc-drawer]')) {
                closeTocDrawer();
            }
        });

        // 抽屉内点击链接后自动关闭并平滑滚动到目标
        var drawerBody = tocDrawer.querySelector('.joe-toc__drawer-body');
        if (drawerBody) {
            drawerBody.addEventListener('click', function (e) {
                var a = e.target.closest('a[data-toc-id]');
                if (!a) return;
                var id = a.getAttribute('data-toc-id');
                var target = d.getElementById(id);
                if (target) {
                    e.preventDefault();
                    closeTocDrawer();
                    setTimeout(function () {
                        var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
                        window.scrollTo({ top: top, behavior: 'smooth' });
                        if (history.replaceState) history.replaceState(null, '', '#' + id);
                    }, 280);
                }
            });
        }

        // ESC 关闭
        d.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && tocDrawer.classList.contains('is-open')) closeTocDrawer();
        });
    }

    // ▸ 图片懒加载（IntersectionObserver + 占位淡入）
    function loadImage(img) {
        var realSrc = img.getAttribute('data-src');
        if (!realSrc) return;
        var tmp = new Image();
        tmp.onload = function () {
            img.src = realSrc;
            img.classList.add('is-loaded');
            img.removeAttribute('data-lazy');
        };
        tmp.onerror = function () {
            // 加载失败也淡入，避免一直显示占位
            img.src = realSrc;
            img.classList.add('is-loaded');
            img.removeAttribute('data-lazy');
        };
        tmp.src = realSrc;
    }

    var lazyImgs = d.querySelectorAll('img[data-lazy="1"]');
    if (lazyImgs.length && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadImage(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '120px 0px', threshold: 0.01 });
        lazyImgs.forEach(function (img) { io.observe(img); });
    } else {
        // 回退：直接加载
        lazyImgs.forEach(loadImage);
    }

    // ▸ 评论表单：Ctrl/Cmd+Enter 提交
    var commentForm = d.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                commentForm.submit();
            }
        });

        // 提交按钮加载态
        commentForm.addEventListener('submit', function () {
            var btn = commentForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                var old = btn.textContent;
                btn.textContent = '提交中…';
                setTimeout(function () {
                    btn.disabled = false;
                    btn.textContent = old;
                }, 4000);
            }
        });
    }

    // ▸ 阅读进度条
    var progressFill = d.getElementById('joe-progressbar-fill');
    var floatProgress = d.getElementById('joe-float-progress');
    var floatFill = d.getElementById('joe-float-progress-fill');
    var floatText = d.getElementById('joe-float-progress-text');
    var articleEl = d.querySelector('.joe-article__content');
    var CIRC = 169.6;

    function updateProgress() {
        if (!articleEl) return;
        var rect = articleEl.getBoundingClientRect();
        var vh = w.innerHeight || d.documentElement.clientHeight;
        var total = articleEl.offsetHeight;
        var scrolled = vh - rect.top;
        var pct = Math.max(0, Math.min(1, scrolled / (total + vh * 0.2)));
        var pctText = Math.round(pct * 100) + '%';
        if (progressFill) progressFill.style.width = pctText;
        if (floatFill) floatFill.style.strokeDashoffset = CIRC * (1 - pct);
        if (floatText) floatText.textContent = pctText;
        if (floatProgress) {
            if (pct > 0.02) floatProgress.classList.add('is-show');
            else floatProgress.classList.remove('is-show');
        }
    }
    if (progressFill || floatProgress) {
        updateProgress();
        w.addEventListener('scroll', throttle(updateProgress, 16), { passive: true });
        if (floatProgress) {
            floatProgress.addEventListener('click', function () {
                w.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    // ▸ 分享按钮
    var shareBox = d.querySelector('.joe-share');
    if (shareBox) {
        var wechatBtn = shareBox.querySelector('[data-share="wechat"]');
        var qrcode = d.getElementById('joe-share-qrcode');
        var canvas = d.getElementById('joe-share-canvas');
        var shareUrl = encodeURIComponent(location.href);
        var shareTitle = encodeURIComponent(d.title);

        shareBox.querySelectorAll('.joe-share__btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var type = btn.getAttribute('data-share');
                if (type === 'weibo') {
                    w.open('https://service.weibo.com/share/share.php?url=' + shareUrl + '&title=' + shareTitle, '_blank', 'width=600,height=500');
                } else if (type === 'twitter') {
                    w.open('https://twitter.com/intent/tweet?url=' + shareUrl + '&text=' + shareTitle, '_blank', 'width=600,height=400');
                } else if (type === 'wechat') {
                    if (qrcode) qrcode.classList.toggle('is-show');
                } else if (type === 'copy') {
                    var text = location.href;
                    var done = function () {
                        btn.classList.add('is-done');
                        var old = btn.textContent;
                        btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>已复制';
                        setTimeout(function () {
                            btn.classList.remove('is-done');
                            btn.innerHTML = old;
                        }, 1800);
                    };
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text, done); });
                    } else {
                        fallbackCopy(text, done);
                    }
                }
            });
        });

        function fallbackCopy(text, cb) {
            var ta = d.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            d.body.appendChild(ta);
            ta.select();
            try { d.execCommand('copy'); cb && cb(); } catch (e) {}
            d.body.removeChild(ta);
        }

        // 绘制简易二维码占位（纯文本链接 + 方框装饰）
        if (canvas && canvas.getContext) {
            var ctx = canvas.getContext('2d');
            var size = 160;
            var cells = 21;
            var cell = size / cells;
            // 背景
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, size, size);
            // 伪二维码格子（基于 URL hash 生成稳定模式）
            var seed = 0;
            for (var i = 0; i < location.href.length; i++) seed = (seed * 31 + location.href.charCodeAt(i)) >>> 0;
            function rng() { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 0xffffffff; }
            ctx.fillStyle = '#111';
            for (var y = 0; y < cells; y++) {
                for (var x = 0; x < cells; x++) {
                    if (rng() > 0.5) {
                        ctx.fillRect(x * cell, y * cell, cell, cell);
                    }
                }
            }
            // 三个定位角
            function drawFinder(fx, fy) {
                ctx.fillStyle = '#fff';
                ctx.fillRect(fx * cell, fy * cell, 7 * cell, 7 * cell);
                ctx.fillStyle = '#111';
                ctx.fillRect(fx * cell, fy * cell, 7 * cell, cell);
                ctx.fillRect(fx * cell, (fy + 6) * cell, 7 * cell, cell);
                ctx.fillRect(fx * cell, fy * cell, cell, 7 * cell);
                ctx.fillRect((fx + 6) * cell, fy * cell, cell, 7 * cell);
                ctx.fillRect((fx + 2) * cell, (fy + 2) * cell, 3 * cell, 3 * cell);
            }
            drawFinder(0, 0);
            drawFinder(cells - 7, 0);
            drawFinder(0, cells - 7);
            // 中心 logo 方块
            var cx = cells / 2 - 2, cy = cells / 2 - 2;
            ctx.fillStyle = '#fff';
            ctx.fillRect(cx * cell, cy * cell, 4 * cell, 4 * cell);
            ctx.fillStyle = '#5b6cff';
            ctx.fillRect((cx + 0.5) * cell, (cy + 0.5) * cell, 3 * cell, 3 * cell);
        }

        // 点击外部关闭微信二维码
        d.addEventListener('click', function (e) {
            if (qrcode && !qrcode.contains(e.target) && e.target !== wechatBtn && !wechatBtn.contains(e.target)) {
                qrcode.classList.remove('is-show');
            }
        });
    }

    // ▸ 技能条动画（about 页）
    var skillBars = d.querySelectorAll('.joe-skill');
    if (skillBars.length && 'IntersectionObserver' in w) {
        var skillIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-animate');
                    skillIO.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        skillBars.forEach(function (el) { skillIO.observe(el); });
    }

    // ▸ 初始化
    syncToggleIcon();
    if (window.pageYOffset > 300 && backtop) backtop.classList.add('is-show');

    // 暴露给全局便于调试
    window.KingJoe = {
        toggleTheme: toggleTheme,
        currentMode: currentMode
    };

    // ===== 点赞按钮 =====
    var agreeBtn = document.getElementById('joe-agree-btn');
    if (agreeBtn) {
        var cid = agreeBtn.getAttribute('data-cid');
        var agreedKey = 'joe_agree_' + cid;
        // 检查是否已点赞（仅 UI 提示，真正判断在后端）
        try {
            var saved = parseInt(localStorage.getItem(agreedKey) || '0', 10);
            if (saved && Date.now() - saved < 86400000) {
                agreeBtn.classList.add('is-agreed');
                var el = agreeBtn.querySelector('.joe-agree__text');
                if (el) el.textContent = '已点赞';
            }
        } catch (e) {}

        agreeBtn.addEventListener('click', function () {
            if (agreeBtn.classList.contains('is-loading') || agreeBtn.classList.contains('is-agreed')) return;
            agreeBtn.classList.add('is-loading');

            var formData = new FormData();
            formData.append('action', 'joe_agree');
            formData.append('cid', cid);

            fetch(location.pathname, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(function (res) { return res.json(); }).then(function (data) {
                agreeBtn.classList.remove('is-loading');
                if (data.code === 1) {
                    agreeBtn.classList.add('is-agreed');
                    var ac = agreeBtn.querySelector('.joe-agree__count');
                    if (ac) ac.textContent = data.count;
                    var at = agreeBtn.querySelector('.joe-agree__text');
                    if (at) at.textContent = '已点赞';
                    try { localStorage.setItem(agreedKey, String(Date.now())); } catch (e) {}
                } else {
                    if (data.msg) showToast(data.msg);
                    agreeBtn.classList.add('is-agreed');
                    var el2 = agreeBtn.querySelector('.joe-agree__text');
                    if (el2) el2.textContent = '已点赞';
                }
            }).catch(function () {
                agreeBtn.classList.remove('is-loading');
                showToast('点赞失败，请稍后再试');
            });
        });
    }

    // ===== 图片灯箱 =====
    var lightboxImgs = document.querySelectorAll('.joe-content img[data-lightbox-item]');
    if (lightboxImgs.length > 0) {
        var imgs = Array.prototype.slice.call(lightboxImgs);
        var currentIndex = 0;

        // 创建灯箱 DOM
        var lightbox = document.createElement('div');
        lightbox.className = 'joe-lightbox';
        lightbox.innerHTML =
            '<button class="joe-lightbox__close" aria-label="关闭">' +
            '<svg viewBox="0 0 24 24" width="22" height="22"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
            '</button>' +
            '<button class="joe-lightbox__prev" aria-label="上一张">' +
            '<svg viewBox="0 0 24 24" width="22" height="22"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>' +
            '</button>' +
            '<img class="joe-lightbox__img" src="" alt="">' +
            '<button class="joe-lightbox__next" aria-label="下一张">' +
            '<svg viewBox="0 0 24 24" width="22" height="22"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>' +
            '</button>' +
            '<div class="joe-lightbox__caption"></div>' +
            '<div class="joe-lightbox__counter"></div>';
        document.body.appendChild(lightbox);

        var lbImg = lightbox.querySelector('.joe-lightbox__img');
        var lbCaption = lightbox.querySelector('.joe-lightbox__caption');
        var lbCounter = lightbox.querySelector('.joe-lightbox__counter');
        var lbClose = lightbox.querySelector('.joe-lightbox__close');
        var lbPrev = lightbox.querySelector('.joe-lightbox__prev');
        var lbNext = lightbox.querySelector('.joe-lightbox__next');

        function openLightbox(index) {
            currentIndex = index;
            var img = imgs[index];
            var src = img.getAttribute('data-src') || img.getAttribute('src');
            var caption = img.getAttribute('data-caption') || img.getAttribute('alt') || '';
            lbImg.src = src;
            lbImg.alt = caption;
            lbCaption.textContent = caption;
            if (imgs.length > 1) {
                lbCounter.textContent = (index + 1) + ' / ' + imgs.length;
                lbPrev.style.display = '';
                lbNext.style.display = '';
            } else {
                lbCounter.textContent = '';
                lbPrev.style.display = 'none';
                lbNext.style.display = 'none';
            }
            lightbox.classList.add('is-show');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('is-show');
            document.body.style.overflow = '';
        }

        function showPrev() {
            if (imgs.length <= 1) return;
            currentIndex = (currentIndex - 1 + imgs.length) % imgs.length;
            openLightbox(currentIndex);
        }

        function showNext() {
            if (imgs.length <= 1) return;
            currentIndex = (currentIndex + 1) % imgs.length;
            openLightbox(currentIndex);
        }

        imgs.forEach(function (img, idx) {
            img.addEventListener('click', function () { openLightbox(idx); });
        });

        lbClose.addEventListener('click', closeLightbox);
        lbPrev.addEventListener('click', function (e) { e.stopPropagation(); showPrev(); });
        lbNext.addEventListener('click', function (e) { e.stopPropagation(); showNext(); });
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });

        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('is-show')) return;
            if (e.key === 'Escape') closeLightbox();
            else if (e.key === 'ArrowLeft') showPrev();
            else if (e.key === 'ArrowRight') showNext();
        });
    }

    // ===== 鼠标特效 =====
    // 通过 data-cursor-effect 属性在模板中设置
    var cursorEffect = document.body.getAttribute('data-cursor-effect') || 'off';
    if (cursorEffect !== 'off') {
        if (cursorEffect === 'click') {
            // 点击爱心
            var hearts = ['❤', '💖', '💕', '💗', '💓', '💝'];
            document.addEventListener('click', function (e) {
                var el = document.createElement('span');
                el.className = 'joe-cursor-fx';
                el.textContent = hearts[Math.floor(Math.random() * hearts.length)];
                el.style.left = e.clientX + 'px';
                el.style.top = e.clientY + 'px';
                document.body.appendChild(el);
                setTimeout(function () { el.remove(); }, 800);
            });
        } else if (cursorEffect === 'text') {
            // 点击文字（富强民主文明...）
            var texts = ['富强', '民主', '文明', '和谐', '自由', '平等', '公正', '法治', '爱国', '敬业', '诚信', '友善'];
            var textIdx = 0;
            document.addEventListener('click', function (e) {
                var el = document.createElement('span');
                el.className = 'joe-cursor-fx';
                el.textContent = texts[textIdx % texts.length];
                el.style.left = e.clientX + 'px';
                el.style.top = e.clientY + 'px';
                el.style.color = 'var(--primary)';
                el.style.fontWeight = '600';
                el.style.fontSize = '14px';
                document.body.appendChild(el);
                textIdx++;
                setTimeout(function () { el.remove(); }, 800);
            });
        } else if (cursorEffect === 'particle') {
            // 粒子跟随
            var lastMove = 0;
            document.addEventListener('mousemove', function (e) {
                var now = Date.now();
                if (now - lastMove < 40) return;
                lastMove = now;
                var dot = document.createElement('span');
                dot.className = 'joe-cursor-dot';
                dot.style.left = e.clientX + 'px';
                dot.style.top = e.clientY + 'px';
                document.body.appendChild(dot);
                setTimeout(function () { dot.remove(); }, 1000);
            });
        }
    }

    function showToast(msg) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:20%;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.8);color:#fff;padding:10px 20px;border-radius:8px;z-index:99999;font-size:14px;transition:opacity 0.3s;';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(function () { toast.style.opacity = '0'; }, 1500);
        setTimeout(function () { toast.remove(); }, 2000);
    }

    // ===== 侧边栏 Tab 切换 =====
    var cardTabs = document.querySelectorAll('.joe-card__tab');
    if (cardTabs.length > 0) {
        cardTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var card = tab.closest('.joe-card');
                if (!card) return;
                var target = tab.getAttribute('data-tab');
                // 切换 tab 样式
                card.querySelectorAll('.joe-card__tab').forEach(function (t) {
                    t.classList.remove('is-active');
                });
                tab.classList.add('is-active');
                // 切换内容
                var hotList = card.querySelectorAll('.joe-hotlist');
                hotList.forEach(function (list) {
                    list.style.display = 'none';
                });
                var targetList = document.getElementById('joe-tab-' + target);
                if (targetList) targetList.style.display = '';
            });
        });
    }

    // ===== 页面加载进度条 =====
    var pageLoader = document.getElementById('joe-pageloader');
    if (pageLoader) {
        var progress = pageLoader.querySelector('.joe-pageloader__bar');
        var progressW = 10;
        var timer = setInterval(function () {
            progressW += Math.random() * 15;
            if (progressW > 90) progressW = 90;
            if (progress) progress.style.width = progressW + '%';
        }, 200);
        window.addEventListener('load', function () {
            clearInterval(timer);
            if (progress) progress.style.width = '100%';
            setTimeout(function () {
                pageLoader.style.opacity = '0';
                setTimeout(function () { pageLoader.style.display = 'none'; }, 300);
            }, 200);
        });
    }

    // 代码块复制按钮已由 prism-extras.js 统一处理，此处不再冗余初始化

    // TOC 滚动高亮已由上方 scroll 事件统一处理，此处不再冗余初始化

    // ===== 评论内链跳转平滑滚动 =====
    (function initCommentScroll() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href^="#comment-"]');
            if (!link) return;
            e.preventDefault();
            var id = link.getAttribute('href').replace('#', '');
            var target = document.getElementById(id);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    })();

    /* ===== Pjax 页面切换后重新初始化 ===== */
    d.addEventListener('pjax:complete', function () {
        // 重新获取 body 引用（DOM 已更新）
        body = d.body;

        // 重新初始化阅读进度条
        var progressBar = d.getElementById('joe-progressbar-fill');
        if (progressBar) {
            function bindProgress() {
                var bar = progressBar;
                d.addEventListener('scroll', throttle(function () {
                    var scrollTop = w.scrollY || w.pageYOffset;
                    var docHeight = d.documentElement.scrollHeight - w.innerHeight;
                    if (docHeight > 0) {
                        var pct = Math.min(100, (scrollTop / docHeight) * 100);
                        bar.style.width = pct + '%';
                    }
                }, 50));
            }
            bindProgress();
        }

        // 重新初始化代码块复制按钮
        d.querySelectorAll('.joe-content pre, .joe-article__content pre').forEach(function (pre) {
            if (pre.querySelector('.joe-code-copy')) return;
            var btn = d.createElement('button');
            btn.className = 'joe-code-copy';
            btn.textContent = '复制';
            btn.addEventListener('click', function () {
                var code = pre.querySelector('code') || pre;
                var text = code.textContent || '';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        btn.textContent = '已复制';
                        setTimeout(function () { btn.textContent = '复制'; }, 2000);
                    });
                } else {
                    var ta = d.createElement('textarea');
                    ta.value = text;
                    ta.style.cssText = 'position:fixed;left:-9999px';
                    d.body.appendChild(ta);
                    ta.select();
                    d.execCommand('copy');
                    d.body.removeChild(ta);
                    btn.textContent = '已复制';
                    setTimeout(function () { btn.textContent = '复制'; }, 2000);
                }
            });
            pre.style.position = pre.style.position || 'relative';
            pre.appendChild(btn);
        });

        // 重新初始化图片灯箱
        d.querySelectorAll('.joe-content img:not([data-lightbox-item])').forEach(function (img) {
            if (img.closest('.joe-lightbox')) return;
            img.setAttribute('data-lightbox-item', '1');
            img.setAttribute('data-src', img.src);
            img.setAttribute('data-caption', img.alt || '');
        });

        d.dispatchEvent(new CustomEvent('pjax:page-ready', { bubbles: true, detail: { isSingle: !!d.querySelector('.joe-article') } }));
    });

})();

    // ===== 阅读模式切换 =====
    (function initReaderMode() {
        var btn = document.getElementById('joe-reader-btn');
        if (!btn) return;
        var wrapper = document.getElementById('app');
        var sidebar = document.querySelector('.joe-sidebar');
        var KEY_READER = 'kingjoe-reader';

        function isReader() {
            return wrapper && wrapper.classList.contains('joe-reader-mode');
        }

        function toggleReader() {
            var active = !isReader();
            if (wrapper) wrapper.classList.toggle('joe-reader-mode', active);
            if (sidebar) sidebar.style.display = active ? 'none' : '';
            btn.classList.toggle('is-active', active);
            try { localStorage.setItem(KEY_READER, active ? '1' : '0'); } catch (e) {}
        }

        // 恢复状态
        try {
            if (localStorage.getItem(KEY_READER) === '1') {
                if (wrapper) wrapper.classList.add('joe-reader-mode');
                if (sidebar) sidebar.style.display = 'none';
                btn.classList.add('is-active');
            }
        } catch (e) {}

        btn.addEventListener('click', toggleReader);

        // 快捷键：双击内容区域切换
        var content = document.querySelector('.joe-content');
        if (content) {
            content.addEventListener('dblclick', function (e) {
                if (e.target.closest('pre') || e.target.closest('a') || e.target.closest('button')) return;
                toggleReader();
            });
        }
    })();

    // ===== 复制内容自动追加版权 =====
    (function initCopyRight() {
        var content = document.querySelector('.joe-content');
        if (!content) return;
        content.addEventListener('copy', function (e) {
            var selection = window.getSelection().toString();
            if (!selection || selection.length < 30) return;
            var siteTitle = document.title.replace(/\s*[-|].*$/, '').trim();
            var siteUrl = location.href;
            var cr = '\n\n\u2014\u2014\u2014\u2014\u2014\u2014\u2014\u2014\u2014\u2014\u2014\u2014\n' +
                '\u672c\u6587\u6765\u81ea\uff1a' + siteTitle + '\n' +
                '\u94fe\u63a5\uff1a' + siteUrl + '\n' +
                '\u8f6c\u8f7d\u8bf7\u6ce8\u660e\u51fa\u5904';
            var original = window.getSelection().getRangeAt(0).cloneContents();
            e.clipboardData.setData('text/plain', selection + cr);
            e.clipboardData.setData('text/html', '<div>' + Array.from(original.childNodes).map(function(n) { return n.nodeType === 3 ? n.textContent : n.outerHTML; }).join('') + '</div>');
            e.preventDefault();
        });
    })();

    // ===== 快捷键系统 =====
    (function initShortcuts() {
        // ESC 关闭弹窗/搜索
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var search = document.getElementById('joe-search');
                if (search && search.classList.contains('is-open')) {
                    search.classList.remove('is-open');
                    return;
                }
            }
            // Ctrl+Enter 提交评论
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                var textarea = document.querySelector('.joe-commentbox__form textarea');
                var submitBtn = document.querySelector('.joe-commentbox__form button[type="submit"]');
                if (textarea && submitBtn && document.activeElement === textarea) {
                    submitBtn.click();
                }
            }
        });
    })();

    // 评论 Markdown 工具栏已由 owo.js 统一处理，此处不再冗余初始化

    // ===== 打赏按钮 =====
    (function initDonate() {
        var toggle = document.getElementById('joe-donate-toggle');
        if (!toggle) return;
        var body = document.getElementById('joe-donate-body');
        toggle.addEventListener('click', function () {
            if (body) body.classList.toggle('is-show');
        });
    })();

    // ===== 智能导航栏隐藏 =====
    (function initSmartHeader() {
        var header = document.querySelector('.joe-header');
        if (!header) return;
        var lastScroll = 0;
        var ticking = false;
        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(function () {
                    var current = window.pageYOffset;
                    if (current > 80) {
                        header.classList.add('is-scrolled');
                    } else {
                        header.classList.remove('is-scrolled');
                    }
                    if (current > lastScroll && current > 200) {
                        header.classList.add('is-hidden');
                    } else {
                        header.classList.remove('is-hidden');
                    }
                    lastScroll = current;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    })();

    // ===== 文章目录点 =====
    (function initTocDots() {
        var content = document.getElementById('joe-content');
        if (!content) return;
        var headings = content.querySelectorAll('h2, h3');
        if (headings.length < 2) return;
        var dots = document.createElement('div');
        dots.className = 'joe-toc-dots';
        headings.forEach(function (h, i) {
            // 确保有 id
            if (!h.id) {
                h.id = 'heading-' + i;
            }
            var dot = document.createElement('span');
            dot.className = 'joe-toc-dots__dot';
            dot.setAttribute('data-title', h.textContent.substring(0, 20));
            dot.setAttribute('data-target', h.id);
            dot.addEventListener('click', function () {
                var el = document.getElementById(this.getAttribute('data-target'));
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            dots.appendChild(dot);
        });
        document.body.appendChild(dots);

        // IntersectionObserver 高亮当前
        var allDots = dots.querySelectorAll('.joe-toc-dots__dot');
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    allDots.forEach(function (d) { d.classList.remove('is-active'); });
                    var target = entry.target.id;
                    var dot = dots.querySelector('[data-target="' + target + '"]');
                    if (dot) dot.classList.add('is-active');
                }
            });
        }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
        headings.forEach(function (h) { observer.observe(h); });
    })();

    // ===== 评论 @ 回复 =====
    (function initCommentAt() {
        document.addEventListener('click', function (e) {
            var replyLink = e.target.closest('.comment-reply');
            if (!replyLink) return;
            var commentItem = replyLink.closest('.joe-comment, .comment-list li');
            if (!commentItem) return;
            var authorEl = commentItem.querySelector('.comment-author .fn, .joe-comment__author');
            var author = authorEl ? authorEl.textContent.trim() : '';
            if (!author) return;
            var textarea = document.querySelector('.joe-commentbox__form textarea, #comment');
            if (!textarea) return;
            // 检查是否已有 @
            if (textarea.value.indexOf('@' + author) === -1) {
                textarea.value = '@' + author + ' ' + textarea.value;
                textarea.focus();
                textarea.setSelectionRange(author.length + 2, author.length + 2);
            }
        });
    })();

    // ===== 图片懒加载骨架屏 =====
    (function initImageSkeleton() {
        var lazyImgs = document.querySelectorAll('.joe-lazy-wrap img');
        if (!lazyImgs.length) return;
        if ('IntersectionObserver' in window) {
            var imgObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    img.addEventListener('load', function () {
                        img.closest('.joe-lazy-wrap').classList.add('is-loaded');
                    });
                    img.addEventListener('error', function () {
                        img.closest('.joe-lazy-wrap').classList.add('is-loaded');
                    });
                    // 如果图片已缓存
                    if (img.complete) {
                        img.closest('.joe-lazy-wrap').classList.add('is-loaded');
                    }
                    imgObserver.unobserve(img);
                });
            });
            lazyImgs.forEach(function (img) {
                imgObserver.observe(img);
            });
        } else {
            // 不支持直接显示
            lazyImgs.forEach(function (img) {
                if (img.dataset.src) img.src = img.dataset.src;
                img.closest('.joe-lazy-wrap').classList.add('is-loaded');
            });
        }
    })();

    // ===== 顶部公告栏关闭 =====
    (function initNoticeBar() {
        var notice = document.getElementById('joe-notice');
        if (!notice) return;
        var closeBtn = document.getElementById('joe-notice-close');
        var noticeId = notice.getAttribute('data-notice-id');
        var KEY = 'kingjoe-notice-closed';
        // 检查是否已关闭
        try {
            var closed = localStorage.getItem(KEY);
            if (closed && closed === noticeId) {
                notice.style.display = 'none';
                return;
            }
        } catch (e) {}
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                notice.style.maxHeight = notice.offsetHeight + 'px';
                notice.offsetHeight; // reflow
                notice.style.maxHeight = '0';
                notice.style.opacity = '0';
                notice.style.padding = '0';
                setTimeout(function () { notice.style.display = 'none'; }, 300);
                try { localStorage.setItem(KEY, noticeId); } catch (e) {}
            });
        }
    })();

    // ===== 首页无限滚动 =====
    (function initInfiniteScroll() {
        // 检查后台是否启用
        if (document.body.getAttribute('data-infinite-scroll') !== '1') return;
        var container = document.querySelector('.joe-postlist');
        if (!container) return;
        var nextLink = document.querySelector('.page-navigator .next a, .joe-pagination .is-next a');
        if (!nextLink) return;
        var loading = false;
        var ended = false;
        // 创建加载指示器
        var spinner = document.createElement('div');
        spinner.className = 'joe-loadmore';
        spinner.innerHTML = '<div class="joe-loadmore__spinner"></div>';
        container.parentNode.insertBefore(spinner, container.nextSibling);

        function loadNext() {
            if (loading || ended) return;
            loading = true;
            spinner.classList.add('is-show');
            var spinnerEl = spinner.querySelector('.joe-loadmore__spinner');
            if (spinnerEl) spinnerEl.style.display = 'block';

            var url = nextLink.href;
            fetch(url, { credentials: 'same-origin' })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newPosts = doc.querySelector('.joe-postlist');
                    var newNext = doc.querySelector('.page-navigator .next a, .joe-pagination .is-next a');
                    if (newPosts) {
                        var items = newPosts.querySelectorAll('.joe-postlist__item');
                        items.forEach(function (item) { container.appendChild(item); });
                    }
                    if (newNext) {
                        nextLink.href = newNext.href;
                    } else {
                        ended = true;
                        spinner.innerHTML = '<div style="text-align:center;color:var(--text-secondary);padding:20px;font-size:14px">没有更多了</div>';
                    }
                    loading = false;
                    spinner.classList.remove('is-show');
                    if (spinnerEl) spinnerEl.style.display = 'none';
                    // 重新初始化懒加载
                    initLazyForNew();
                })
                .catch(function () {
                    loading = false;
                    spinner.classList.remove('is-show');
                    if (spinnerEl) spinnerEl.style.display = 'none';
                });
        }

        function initLazyForNew() {
            var newImgs = container.querySelectorAll('img[data-lazy="1"]:not([data-observed])');
            if (newImgs.length && 'IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            var realSrc = img.getAttribute('data-src');
                            if (realSrc) {
                                var tmp = new Image();
                                tmp.onload = function () {
                                    img.src = realSrc;
                                    img.classList.add('is-loaded');
                                    img.removeAttribute('data-lazy');
                                };
                                tmp.onerror = function () {
                                    img.src = realSrc;
                                    img.classList.add('is-loaded');
                                    img.removeAttribute('data-lazy');
                                };
                                tmp.src = realSrc;
                            }
                            io.unobserve(img);
                        }
                    });
                }, { rootMargin: '120px 0px', threshold: 0.01 });
                newImgs.forEach(function (img) {
                    img.setAttribute('data-observed', '1');
                    io.observe(img);
                });
            }
        }

        // IntersectionObserver 检测是否接近底部
        if ('IntersectionObserver' in window) {
            var sentinel = document.createElement('div');
            sentinel.style.cssText = 'height:1px';
            container.parentNode.insertBefore(sentinel, spinner);
            var io2 = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) loadNext();
            }, { rootMargin: '200px 0px' });
            io2.observe(sentinel);
        }
    })();

    // ===== 页脚运行时间动态显示 =====
    (function initRuntime() {
        var runtimeEl = document.getElementById('joe-runtime');
        if (!runtimeEl) return;
        var startStr = runtimeEl.getAttribute('data-start');
        if (!startStr) return;
        var start = new Date(startStr.replace(/-/g, '/'));
        if (isNaN(start.getTime())) return;

        function pad(n) { return n < 10 ? '0' + n : n; }

        function update() {
            var now = new Date();
            var diff = now - start;
            if (diff < 0) return;
            var days = Math.floor(diff / 86400000);
            var hours = Math.floor((diff % 86400000) / 3600000);
            var minutes = Math.floor((diff % 3600000) / 60000);
            var seconds = Math.floor((diff % 60000) / 1000);
            runtimeEl.textContent = days + ' 天 ' + pad(hours) + ' 小时 ' + pad(minutes) + ' 分 ' + pad(seconds) + ' 秒';
        }
        update();
        setInterval(update, 1000);
    })();

    // ===== 首页轮播图 =====
    (function initCarousel() {
        var carousel = document.getElementById('joe-carousel');
        if (!carousel) return;
        var track = document.getElementById('joe-carousel-track');
        var slides = carousel.querySelectorAll('.joe-carousel__slide');
        var dots = document.querySelectorAll('.joe-carousel__dot');
        var prevBtn = document.getElementById('joe-carousel-prev');
        var nextBtn = document.getElementById('joe-carousel-next');
        var current = 0;
        var total = slides.length;
        var timer = null;
        var interval = 4000;

        function goTo(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            slides.forEach(function (s) { s.classList.remove('is-active'); });
            dots.forEach(function (d) { d.classList.remove('is-active'); });
            slides[index].classList.add('is-active');
            if (dots[index]) dots[index].classList.add('is-active');
            current = index;
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { next(); resetTimer(); });
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var idx = parseInt(dot.getAttribute('data-index'));
                goTo(idx);
                resetTimer();
            });
        });

        // 触摸滑动
        var touchStartX = 0;
        carousel.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; });
        carousel.addEventListener('touchend', function (e) {
            var diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) { diff > 0 ? next() : prev(); resetTimer(); }
        });

        function resetTimer() { clearInterval(timer); timer = setInterval(next, interval); }
        timer = setInterval(next, interval);
    })();

    // ===== 全站飘落特效 =====
    (function initFalling() {
        var effect = document.body.getAttribute('data-falling-effect') || 'off';
        if (effect === 'off') return;

        var items = { snow: '❄', petal: '🌸', star: '⭐' };
        var speeds = { snow: [8, 16], petal: [6, 14], star: [5, 12] };
        var sizes = { snow: [10, 20], petal: [12, 22], star: [10, 18] };
        var chars = items[effect] || '❄';
        var speedRange = speeds[effect] || [8, 16];
        var sizeRange = sizes[effect] || [10, 20];

        function createParticle() {
            var el = document.createElement('span');
            el.className = 'joe-falling__item';
            el.textContent = chars;
            el.style.left = Math.random() * 100 + '%';
            el.style.fontSize = (Math.random() * (sizeRange[1] - sizeRange[0]) + sizeRange[0]) + 'px';
            el.style.animationDuration = (Math.random() * (speedRange[1] - speedRange[0]) + speedRange[0]) + 's';
            el.style.opacity = (Math.random() * 0.5 + 0.3);
            document.body.appendChild(el);
            setTimeout(function () { el.remove(); }, parseFloat(el.style.animationDuration) * 1000 + 500);
        }

        setInterval(function () { createParticle(); }, effect === 'snow' ? 200 : 400);
    })();

    // ===== 全局音乐播放器 =====
    (function initMusic() {
        var music = document.getElementById('joe-music');
        if (!music) return;
        var panel = document.getElementById('joe-music-panel');
        var toggle = document.getElementById('joe-music-toggle');
        var audio = document.getElementById('joe-music-audio');
        var playBtn = document.getElementById('joe-music-play');
        var prevBtn = document.getElementById('joe-music-prev');
        var nextBtn = document.getElementById('joe-music-next');
        var closeBtn = document.getElementById('joe-music-close');
        var titleEl = document.getElementById('joe-music-title');
        var artistEl = document.getElementById('joe-music-artist');
        var coverEl = document.getElementById('joe-music-cover');

        var server = music.getAttribute('data-server') || 'netease';
        var type = music.getAttribute('data-type') || 'playlist';
        var id = music.getAttribute('data-id');

        // 更新播放按钮图标的安全辅助函数
        function setPlayIcon(iconHtml) {
            var svg = playBtn && playBtn.querySelector('svg');
            if (svg) svg.innerHTML = iconHtml;
        }
        var autoplay = music.getAttribute('data-autoplay') === '1';
        var playlist = [];
        var currentIndex = 0;

        // 通过 Meting API 获取播放列表
        var apiUrl = 'https://api.mizore.cn/meting/api?server=' + server + '&type=' + type + '&id=' + id;

        fetch(apiUrl)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.length) {
                    playlist = data;
                    loadTrack(0);
                    setPlayIcon('<path d="M8 5v14l11-7Z" fill="currentColor"/>');
                }
            })
            .catch(function () {
                titleEl.textContent = '加载失败';
                artistEl.textContent = '请检查配置';
            });

        function loadTrack(i) {
            currentIndex = i;
            var track = playlist[i];
            if (!track) return;
            titleEl.textContent = track.title || track.name || '未知歌曲';
            artistEl.textContent = track.artist || track.author || '';
            coverEl.textContent = '🎵';
            audio.src = track.url;
            audio.load();
            if (autoplay) { audio.play(); toggle.classList.add('is-playing'); }
        }

        function togglePlay() {
            if (audio.paused) {
                audio.play();
                toggle.classList.add('is-playing');
                setPlayIcon('<path d="M4 4h5v16H4ZM15 4h5v16h-5Z" fill="currentColor"/>');
            } else {
                audio.pause();
                toggle.classList.remove('is-playing');
                setPlayIcon('<path d="M8 5v14l11-7Z" fill="currentColor"/>');
            }
        }

        toggle.addEventListener('click', function () {
            if (panel.classList.contains('is-show')) {
                panel.classList.remove('is-show');
            } else {
                panel.classList.add('is-show');
            }
        });

        playBtn.addEventListener('click', togglePlay);
        prevBtn.addEventListener('click', function () {
            var idx = currentIndex - 1;
            if (idx < 0) idx = playlist.length - 1;
            loadTrack(idx);
            audio.play();
            toggle.classList.add('is-playing');
        });
        nextBtn.addEventListener('click', function () {
            var idx = currentIndex + 1;
            if (idx >= playlist.length) idx = 0;
            loadTrack(idx);
            audio.play();
            toggle.classList.add('is-playing');
        });
        closeBtn.addEventListener('click', function () { panel.classList.remove('is-show'); });

        audio.addEventListener('ended', function () {
            var idx = currentIndex + 1;
            if (idx >= playlist.length) idx = 0;
            loadTrack(idx);
            audio.play();
        });
        audio.addEventListener('play', function () {
            toggle.classList.add('is-playing');
            setPlayIcon('<path d="M4 4h5v16H4ZM15 4h5v16h-5Z" fill="currentColor"/>');
        });
        audio.addEventListener('pause', function () {
            toggle.classList.remove('is-playing');
            setPlayIcon('<path d="M8 5v14l11-7Z" fill="currentColor"/>');
        });
    })();

    // ===== 友链在线申请 =====
    (function initLinkApply() {
        var form = document.getElementById('joe-link-apply-form');
        if (!form) return;
        var captchaBox = document.getElementById('joe-link-captcha');
        var captchaCode = '';

        function genCaptcha() {
            captchaCode = String(Math.floor(Math.random() * 9000 + 1000));
            if (captchaBox) captchaBox.textContent = captchaCode;
        }
        genCaptcha();
        if (captchaBox) captchaBox.addEventListener('click', genCaptcha);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('.joe-link-apply__btn');
            var msg = document.getElementById('joe-link-apply-msg');
            btn.classList.add('is-loading');
            btn.textContent = '提交中...';

            var fd = new FormData(form);
            fd.append('joe_action', 'link_apply');
            fd.append('captcha', captchaCode);

            fetch(location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btn.classList.remove('is-loading');
                    btn.textContent = '提交申请';
                    if (msg) {
                        msg.textContent = data.msg;
                        msg.style.color = data.code === 1 ? 'var(--primary)' : '#ef4444';
                    }
                    if (data.code === 1) {
                        form.reset();
                        genCaptcha();
                    } else {
                        genCaptcha();
                    }
                })
                .catch(function () {
                    btn.classList.remove('is-loading');
                    btn.textContent = '提交申请';
                    if (msg) { msg.textContent = '网络错误，请重试'; msg.style.color = '#ef4444'; }
                    genCaptcha();
                });
        });
    })();


    // ===== 底部鱼群跳跃特效 =====
    (function initFishEffect() {
        if (document.body.getAttribute('data-fish-effect') !== '1') return;
        var fishEl = document.createElement('div');
        fishEl.className = 'joe-fish';
        document.body.appendChild(fishEl);

        var fish = ['🐟', '🐠', '🐡', '🦈', '🐳', '🐬'];
        function spawnFish() {
            var el = document.createElement('span');
            el.className = 'joe-fish__item';
            el.textContent = fish[Math.floor(Math.random() * fish.length)];
            el.style.left = Math.random() * 90 + '%';
            el.style.animationDelay = Math.random() * 2 + 's';
            el.style.animationDuration = (Math.random() * 2 + 2) + 's';
            fishEl.appendChild(el);
            setTimeout(function () { el.remove(); }, 4000);
        }

        // 初始投射几条
        for (var i = 0; i < 5; i++) {
            setTimeout(function () { spawnFish(); }, i * 400);
        }
        setInterval(spawnFish, 2500);
    })();

    // ===== SSL安全认证图标 =====
    (function initSslBadge() {
        if (document.body.getAttribute('data-ssl-badge') !== '1') return;
        var badge = document.createElement('a');
        badge.className = 'joe-ssl-badge';
        badge.href = 'https://' + location.hostname;
        badge.target = '_blank';
        badge.title = 'SSL安全认证';
        badge.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 2L3 7v5c0 4.4 2.4 8.5 6 10.9V12H5V8.5l7-3.9 7 3.9V12h-4v10.9c3.6-2.4 6-6.5 6-10.9V7l-9-5z" fill="currentColor"/></svg>';
        document.body.appendChild(badge);
    })();


    // ===== 动态星空背景 =====
    (function initStarryBg() {
        if (document.body.getAttribute('data-starry-bg') !== '1') return;
        var canvas = document.createElement('canvas');
        canvas.id = 'joe-starry-canvas';
        document.body.prepend(canvas);
        var ctx = canvas.getContext('2d');
        var stars = [];
        var maxStars = 80;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < maxStars; i++) {
            stars.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 2 + 0.5,
                speed: Math.random() * 0.5 + 0.2,
                opacity: Math.random() * 0.8 + 0.2,
                twinkle: Math.random() * Math.PI * 2
            });
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            stars.forEach(function (s) {
                s.twinkle += 0.02;
                s.y += s.speed;
                if (s.y > canvas.height + 5) { s.y = -5; s.x = Math.random() * canvas.width; }
                var alpha = s.opacity * (0.5 + 0.5 * Math.sin(s.twinkle));
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,255,255,' + alpha + ')';
                ctx.fill();
            });
            requestAnimationFrame(draw);
        }
        draw();
    })();

    // ===== 百度收录手动提交 =====
    (function initBaiduSubmit() {
        var submitBtn = document.getElementById('joe-baidu-submit');
        if (!submitBtn) return;
        var checkEl = document.getElementById('joe-baidu-check');
        var url = checkEl ? checkEl.getAttribute('data-url') : '';
        submitBtn.addEventListener('click', function () {
            if (submitBtn.classList.contains('is-submitted')) return;
            submitBtn.textContent = '提交中...';
            submitBtn.classList.add('is-submitted');

            var fd = new FormData();
            fd.append('action', 'joe_baidu_push');
            fd.append('url', url);

            fetch(location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    submitBtn.textContent = data.code === 1 ? '已提交' : '提交失败';
                    setTimeout(function () {
                        if (data.code !== 1) {
                            submitBtn.classList.remove('is-submitted');
                            submitBtn.textContent = '重试提交';
                        }
                    }, 2000);
                })
                .catch(function () {
                    submitBtn.classList.remove('is-submitted');
                    submitBtn.textContent = '提交失败，点击重试';
                });
        });
    })();

    // ===== 文章复制版权弹窗 =====
    (function initCopyToast() {
        var content = document.querySelector('.joe-content, .joe-article__content');
        if (!content) return;
        var toast = document.createElement('div');
        toast.className = 'joe-copy-toast';
        toast.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg><span>内容已复制，转载请注明出处</span>';
        document.body.appendChild(toast);

        var timer = null;
        content.addEventListener('copy', function () {
            toast.classList.add('is-show');
            clearTimeout(timer);
            timer = setTimeout(function () { toast.classList.remove('is-show'); }, 2000);
        });
    })();

    // ===== 缩略图加载失败自动替换 =====
    (function initImgFallback() {
        var defaultThumb = document.querySelector('meta[name="joe-default-thumb"]');
        var fallbackSrc = defaultThumb ? defaultThumb.getAttribute('content') : '';
        if (!fallbackSrc) return;

        document.addEventListener('error', function (e) {
            var img = e.target;
            if (img.tagName !== 'IMG') return;
            if (img.dataset.fallbackTried) return;
            img.dataset.fallbackTried = '1';
            // 仅处理文章缩略图和友链头像
            if (img.closest('.joe-postlist__thumb, .joe-links__avatar, .joe-sticky__thumb, .joe-related__thumb')) {
                img.src = fallbackSrc;
                img.classList.add('is-fallback');
            }
        }, true);
    })();


    // ===== 文章导读浮动卡片 =====
    (function initGuideCard() {
        if (document.body.getAttribute('data-reading-guide-card') !== '1') return;
        var content = document.getElementById('joe-content');
        if (!content) return;
        var headings = content.querySelectorAll('h2, h3');
        if (headings.length < 2) return;

        // 创建浮动按钮
        var card = document.createElement('div');
        card.className = 'joe-guide-card';
        card.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
        document.body.appendChild(card);

        // 创建面板
        var panel = document.createElement('div');
        panel.className = 'joe-guide-card__panel';
        headings.forEach(function (h, i) {
            if (!h.id) h.id = 'guide-' + i;
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent.substring(0, 25);
            if (h.tagName === 'H3') a.classList.add('is-h3');
            a.addEventListener('click', function (e) {
                e.preventDefault();
                panel.classList.remove('is-show');
                var el = document.getElementById(h.id);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            panel.appendChild(a);
        });
        card.appendChild(panel);

        card.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle('is-show');
        });
        document.addEventListener('click', function () {
            panel.classList.remove('is-show');
        });
    })();

    // ===== 移动端侧边栏壁纸 =====
    (function initSidebarWallpaper() {
        // 已通过 CSS body.has-sidebar-wallpaper 处理，无需额外 JS
    })();

    // ===== 页面切换预加载优化 =====
    (function initPagePrefetch() {
        document.addEventListener('mouseover', function (e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto')) return;
            if (link.dataset.prefetched) return;
            link.dataset.prefetched = '1';
            var prefetch = document.createElement('link');
            prefetch.rel = 'prefetch';
            prefetch.href = href;
            document.head.appendChild(prefetch);
        });
    })();


    // ===== 移动端热门文章数量控制 =====
    (function initMobileHotCount() {
        var style = getComputedStyle(document.body);
        var count = parseInt(style.getPropertyValue('--mobile-hot-count') || '4');
        var items = document.querySelectorAll('.joe-hotlist__item');
        if (!items.length) return;
        var isMobile = window.matchMedia('(max-width: 768px)').matches;
        function apply() {
            items.forEach(function (item, i) {
                if (isMobile && i >= count) {
                    item.classList.add('joe-hotlist--hidden-mobile');
                } else {
                    item.classList.remove('joe-hotlist--hidden-mobile');
                }
            });
        }
        apply();
        window.addEventListener('resize', function () {
            isMobile = window.matchMedia('(max-width: 768px)').matches;
            apply();
        });
    })();

    // ===== 移动端TOC点击后自动关闭 =====
    (function initTocAutoClose() {
        var drawer = document.getElementById('joe-toc-drawer');
        if (!drawer) return;
        var drawerBody = drawer.querySelector('.joe-toc__drawer-body');
        if (!drawerBody) return;
        drawerBody.addEventListener('click', function (e) {
            var a = e.target.closest('a[data-toc-id]');
            if (!a) return;
            setTimeout(function () {
                drawer.classList.remove('is-open');
                document.body.style.overflow = '';
            }, 100);
        });
    })();


    // ===== H2/H3 标题锚点链接（点击复制） =====
    (function initHeadingAnchors() {
        var content = document.querySelector('.joe-content');
        if (!content) return;
        var headings = content.querySelectorAll('h2, h3');
        headings.forEach(function(h) {
            if (!h.id) return;
            var anchor = document.createElement('a');
            anchor.className = 'joe-heading-anchor';
            anchor.href = '#' + h.id;
            anchor.textContent = '#';
            anchor.title = '复制链接';
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                var url = location.origin + location.pathname + '#' + h.id;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        anchor.textContent = '✓';
                        setTimeout(function(){ anchor.textContent = '#'; }, 1500);
                    });
                }
            });
            h.appendChild(anchor);
        });
    })();

    // ===== 文章引用短代码点击事件 =====
    (function initPostRef() {
        document.addEventListener('click', function (e) {
            var ref = e.target.closest('.joe-post-ref');
            if (!ref || ref.classList.contains('is-error')) return;
            var cid = ref.getAttribute('data-cid');
            if (!cid) return;
            var target = document.getElementById('post-' + cid);
            if (target) {
                e.preventDefault();
                window.scrollTo({
                    top: target.getBoundingClientRect().top + window.pageYOffset - 80,
                    behavior: 'smooth'
                });
            }
        });
    })();

    // ===== 全站快捷键系统 =====
    (function initKeyboardShortcuts() {
        // 检查配置是否启用
        if (!document.querySelector('meta[name="joe-shortcut"]')) return;

        var isEditable = function(el) {
            var tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
        };

        document.addEventListener('keydown', function(e) {
            // 输入框中不触发快捷键（但 ESC 除外）
            if (isEditable(e.target) && e.key !== 'Escape') return;

            var key = e.key;
            var isSingle = !!document.querySelector('.joe-post, .joe-article');

            switch (key) {
                // === 上一篇文章 ===
                case 'j':
                case 'J':
                case 'ArrowLeft':
                    if (!isSingle) break;
                    var prev = document.querySelector('.joe-neighbors__item.is-prev');
                    if (prev && !prev.classList.contains('is-disabled') && prev.href) {
                        e.preventDefault();
                        window.location.href = prev.href;
                    }
                    break;

                // === 下一篇文章 ===
                case 'k':
                case 'K':
                case 'ArrowRight':
                    if (!isSingle) break;
                    var next = document.querySelector('.joe-neighbors__item.is-next');
                    if (next && !next.classList.contains('is-disabled') && next.href) {
                        e.preventDefault();
                        window.location.href = next.href;
                    }
                    break;

                // === 返回顶部 ===
                case 't':
                case 'T':
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    break;

                // === 搜索 ===
                case 's':
                case 'S':
                case '/':
                    e.preventDefault();
                    var search = document.querySelector('#joe-search');
                    if (search) {
                        search.classList.add('is-show');
                        var input = search.querySelector('.joe-search__input');
                        if (input) setTimeout(function() { input.focus(); }, 100);
                    }
                    break;

                // === 暗黑模式切换 ===
                case 'd':
                case 'D':
                    e.preventDefault();
                    var themeBtn = document.querySelector('.joe-theme__toggle');
                    if (themeBtn) themeBtn.click();
                    break;

                // === 目录切换 (仅文章页) ===
                case 'c':
                case 'C':
                    if (!isSingle) break;
                    e.preventDefault();
                    var tocFab = document.querySelector('.joe-toc__fab');
                    if (tocFab) tocFab.click();
                    break;

                // === ESC 关闭弹窗/搜索/灯箱 ===
                case 'Escape':
                    var searchBox = document.querySelector('#joe-search');
                    if (searchBox && searchBox.classList.contains('is-show')) {
                        searchBox.classList.remove('is-show');
                        break;
                    }
                    var lightbox = document.querySelector('.joe-lightbox.is-show');
                    if (lightbox) {
                        var lbClose = lightbox.querySelector('.joe-lightbox__close');
                        if (lbClose) lbClose.click();
                        break;
                    }
                    // 关闭其他可能的弹窗
                    var modals = document.querySelectorAll('.is-show[class*="modal"], .is-show[class*="drawer"]');
                    modals.forEach(function(m) { m.classList.remove('is-show'); });
                    break;

                // === 帮助面板 ===
                case '?':
                    if (e.shiftKey) {
                        e.preventDefault();
                        showShortcutHelp();
                    }
                    break;
            }
        });

        // 快捷键帮助面板
        function showShortcutHelp() {
            var existing = document.querySelector('.joe-shortcut-help');
            if (existing) {
                existing.classList.toggle('is-show');
                return;
            }

            var shortcuts = [
                { key: 'J / ←', action: '上一篇文章' },
                { key: 'K / →', action: '下一篇文章' },
                { key: 'T', action: '返回顶部' },
                { key: 'S / /', action: '打开搜索' },
                { key: 'D', action: '切换暗黑模式' },
                { key: 'C', action: '切换目录' },
                { key: 'ESC', action: '关闭弹窗' },
                { key: '?', action: '显示此帮助' },
            ];

            var html = '<div class="joe-shortcut-help is-show"><div class="joe-shortcut-help__overlay"></div><div class="joe-shortcut-help__panel"><div class="joe-shortcut-help__head"><h3>快捷键帮助</h3><button class="joe-shortcut-help__close" aria-label="关闭">&times;</button></div><div class="joe-shortcut-help__body"><table>';

            shortcuts.forEach(function(s) {
                html += '<tr><td class="joe-shortcut-help__key"><kbd>' + s.key + '</kbd></td><td>' + s.action + '</td></tr>';
            });

            html += '</table></div></div></div>';

            var el = document.createElement('div');
            el.innerHTML = html;
            document.body.appendChild(el.firstElementChild);

            var panel = document.querySelector('.joe-shortcut-help');
            var close = panel.querySelector('.joe-shortcut-help__close, .joe-shortcut-help__overlay');
            close.addEventListener('click', function() {
                panel.classList.remove('is-show');
            });
        }
    })();

    // ===== 评论点赞 =====
    (function initCommentLike() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.joe-comment__like');
            if (!btn || btn.classList.contains('is-liked') || btn.classList.contains('is-loading')) return;
            e.preventDefault();

            var coid = btn.getAttribute('data-coid');
            if (!coid) return;

            btn.classList.add('is-loading');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                btn.classList.remove('is-loading');
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.code === 1) {
                        btn.classList.add('is-liked');
                        var countEl = btn.querySelector('.joe-comment__like-count');
                        if (countEl) countEl.textContent = r.count;
                    }
                } catch (ex) {}
            };
            xhr.onerror = function() {
                btn.classList.remove('is-loading');
            };
            xhr.send('action=joe_comment_like&coid=' + encodeURIComponent(coid));
        });
    })();

    // ===== 时光机加载更多 =====
    (function initTimelineLoadMore() {
        var btn = document.getElementById('joe-timeline-more');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (btn.classList.contains('is-loading') || btn.classList.contains('is-end')) return;
            btn.classList.add('is-loading');
            var span = btn.querySelector('span');
            if (span) span.textContent = '加载中...';

            var page = parseInt(btn.getAttribute('data-page')) + 1;

            var xhr = new XMLHttpRequest();
            xhr.open('GET', window.location.origin + '?action=joe_timeline&page=' + page, true);
            xhr.onload = function () {
                btn.classList.remove('is-loading');
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.code === 1 && r.html) {
                        var list = document.getElementById('joe-timeline-list');
                        if (list) {
                            var div = document.createElement('div');
                            div.innerHTML = r.html;
                            while (div.firstChild) {
                                list.appendChild(div.firstChild);
                            }
                        }
                        btn.setAttribute('data-page', page);
                        if (!r.hasMore) {
                            btn.classList.add('is-end');
                            if (span) span.textContent = '没有更多了';
                        } else {
                            if (span) span.textContent = '加载更多';
                        }
                    }
                } catch (ex) {
                    if (span) span.textContent = '加载失败，点击重试';
                }
            };
            xhr.onerror = function () {
                btn.classList.remove('is-loading');
                if (span) span.textContent = '加载失败，点击重试';
            };
            xhr.send();
        });
    })();

    // ===== 移动端悬浮操作栏 =====
    (function initMobileBar() {
        var bar = document.getElementById('joe-mobile-bar');
        if (!bar) return;

        bar.addEventListener('click', function(e) {
            var btn = e.target.closest('.joe-mobile-bar__btn');
            if (!btn) return;
            var action = btn.getAttribute('data-action');

            switch (action) {
                case 'agree':
                    var agreeBtn = document.getElementById('joe-agree-btn');
                    if (agreeBtn) agreeBtn.click();
                    btn.classList.add('is-liked');
                    break;

                case 'comment':
                    var comments = document.getElementById('comments');
                    if (comments) {
                        comments.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        var commentBox = document.getElementById('respond');
                        if (commentBox) commentBox.scrollIntoView({ behavior: 'smooth' });
                    }
                    break;

                case 'share':
                    if (navigator.share) {
                        navigator.share({
                            title: document.title,
                            url: window.location.href
                        }).catch(function() {});
                    } else {
                        var textarea = document.createElement('textarea');
                        textarea.value = window.location.href;
                        textarea.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
                        document.body.appendChild(textarea);
                        textarea.select();
                        try { document.execCommand('copy'); } catch(e) {}
                        document.body.removeChild(textarea);

                        var toast = document.querySelector('.joe-copy-toast');
                        if (toast) {
                            toast.classList.add('is-show');
                            clearTimeout(toast._timer);
                            toast._timer = setTimeout(function() { toast.classList.remove('is-show'); }, 2000);
                        }
                    }
                    break;

                case 'top':
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    break;
            }
        });
    })();

    // ===== 代码块复制按钮 =====
    (function initCodeCopy() {
        var content = document.querySelector('.joe-content');
        if (!content) return;

        content.querySelectorAll('pre').forEach(function(pre) {
            if (pre.parentNode.classList.contains('joe-code-wrapper')) return;

            var wrapper = document.createElement('div');
            wrapper.className = 'joe-code-wrapper';
            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            var btn = document.createElement('button');
            btn.className = 'joe-code-copy';
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M9 9h10v10H9z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" stroke="currentColor" stroke-width="2" fill="none"/></svg><span>复制</span>';

            btn.addEventListener('click', function() {
                var code = pre.querySelector('code') || pre;
                var text = code.textContent;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        btn.classList.add('is-copied');
                        var s = btn.querySelector('span');
                        if (s) s.textContent = '已复制';
                        setTimeout(function() {
                            btn.classList.remove('is-copied');
                            if (s) s.textContent = '复制';
                        }, 2000);
                    }).catch(function() { fallbackCopy(text); });
                } else {
                    fallbackCopy(text);
                }

                function fallbackCopy(text) {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.cssText = 'position:fixed;opacity:0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); } catch(e) {}
                    document.body.removeChild(ta);
                    btn.classList.add('is-copied');
                    var s2 = btn.querySelector('span');
                    if (s2) s2.textContent = '已复制';
                    setTimeout(function() {
                        btn.classList.remove('is-copied');
                        if (s2) s2.textContent = '复制';
                    }, 2000);
                }
            });
            wrapper.appendChild(btn);
        });
    })();

    // ===== 字体大小调节 =====
    (function initFontSize() {
        var el = document.getElementById('joe-fontsize');
        if (!el) return;
        var KEY = 'joe-font-size';
        var content = document.querySelector('.joe-content');
        if (!content) return;
        var sizes = { '-1': 14, '0': 16, '1': 18 };
        var saved = parseInt(localStorage.getItem(KEY)) || 0;

        function applySize(lv) {
            content.style.fontSize = (sizes[String(lv)] || 16) + 'px';
            el.querySelectorAll('.joe-fontsize__btn').forEach(function(b) {
                b.classList.toggle('is-active', parseInt(b.getAttribute('data-size')) === lv);
            });
            localStorage.setItem(KEY, lv);
        }
        if (saved !== 0) applySize(saved);
        el.addEventListener('click', function(e) {
            var btn = e.target.closest('.joe-fontsize__btn');
            if (!btn) return;
            applySize(parseInt(btn.getAttribute('data-size')));
        });
    })();

    // ===== 图片画廊：将连续图片包裹为画廊 =====
    (function initGallery() {
        var content = document.querySelector('.joe-content');
        if (!content) return;
        // 找到所有只含单张图片的 p 标签
        var imgs = content.querySelectorAll('p > img:only-child');
        if (imgs.length < 2) return;
        var groups = [];
        var current = [];
        imgs.forEach(function(img, i) {
            var p = img.parentNode;
            if (p.tagName !== 'P') return;
            current.push(p);
            // 检测是否有连续的下一个
            var nextP = p.nextElementSibling;
            var isLast = !nextP || !(nextP.tagName === 'P' && nextP.children.length === 1 && nextP.children[0].tagName === 'IMG');
            if (isLast && current.length >= 2) {
                groups.push(current.slice());
                current = [];
            } else if (!isLast) {
                // continue
            } else {
                current = [];
            }
        });
        groups.forEach(function(group) {
            var gallery = document.createElement('div');
            gallery.className = 'joe-gallery';
            group.forEach(function(p) {
                gallery.appendChild(p);
            });
            group[0].parentNode.insertBefore(gallery, group[0]);
        });
    })();


    // ===== 标签卡切换 =====
    (function initTabs() {
        document.querySelectorAll('.joe-tabs').forEach(function(tabs) {
            var nav = tabs.querySelector('.joe-tabs__nav');
            if (!nav) return;
            nav.addEventListener('click', function(e) {
                var tab = e.target.closest('.joe-tabs__tab');
                if (!tab) return;
                var idx = tab.getAttribute('data-tab');
                tabs.querySelectorAll('.joe-tabs__tab, .joe-tabs__panel').forEach(function(el) {
                    el.classList.remove('is-active');
                });
                tab.classList.add('is-active');
                var panel = tabs.querySelector('.joe-tabs__panel[data-panel="' + idx + '"]');
                if (panel) panel.classList.add('is-active');
            });
        });
    })();

    // ===== 选中文字分享弹窗 =====
    (function initSharePopup() {
        var popup = document.createElement('div');
        popup.className = 'joe-share-popup';
        popup.innerHTML = '<button class="joe-share-popup__btn" data-action="copy"><svg viewBox="0 0 24 24" width="12" height="12"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" fill="none"/></svg>复制</button><button class="joe-share-popup__btn" data-action="twitter"><svg viewBox="0 0 24 24" width="12" height="12"><path d="M22 5.8c-.8.4-1.6.6-2.5.8A4.3 4.3 0 0 0 12 3.6a4.3 4.3 0 0 0-1.2 3.1A12 12 0 0 1 3 4.6s-2 4.5 1 7.3c-.7 0-1.4-.2-2-.5 0 2.2 1.6 4.1 3.6 4.5a4.4 4.4 0 0 1-2 0A4.3 4.3 0 0 0 7.6 19a8.7 8.7 0 0 1-6.3 1.8A12.2 12.2 0 0 0 9 23c8 0 12.5-6.7 12.5-12.5v-.6a8.7 8.7 0 0 0 2.2-2.3Z" stroke="currentColor" stroke-width="1.6" fill="none"/></svg></button>';
        document.body.appendChild(popup);

        document.addEventListener('mouseup', function(e) {
            setTimeout(function() {
                var sel = window.getSelection();
                var text = sel.toString().trim();
                if (!text || text.length < 5) { popup.classList.remove('is-show'); return; }
                var range = sel.getRangeAt(0);
                var rect = range.getBoundingClientRect();
                popup.style.left = (rect.left + rect.width / 2) + 'px';
                popup.style.top = (rect.top - 42 + window.pageYOffset) + 'px';
                popup.classList.add('is-show');
            }, 10);
        });

        document.addEventListener('mousedown', function(e) {
            if (!popup.contains(e.target)) popup.classList.remove('is-show');
        });

        popup.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) return;
            var action = btn.getAttribute('data-action');
            var text = window.getSelection().toString().trim() + '\n\n—— 来自 ' + location.href;
            if (action === 'copy') {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                    btn.textContent = '已复制';
                    setTimeout(function(){ btn.innerHTML = '<svg viewBox="0 0 24 24" width="12" height="12"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" fill="none"/></svg>复制'; }, 1500);
                }
            } else if (action === 'twitter') {
                window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text), '_blank', 'width=600,height=400');
            }
            popup.classList.remove('is-show');
        });
    })();

    // ===== 代码块全选按钮 =====
    (function initSelectAll() {
        document.querySelectorAll('.joe-content pre, .joe-code__toolbar').forEach(function(el) {
            var btn = document.createElement('button');
            btn.className = 'joe-code-selectall';
            btn.textContent = '全选';
            btn.addEventListener('click', function() {
                var code = el.querySelector('code') || el.querySelector('pre code') || el;
                var range = document.createRange();
                range.selectNodeContents(code);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                btn.textContent = '已选';
                setTimeout(function(){ btn.textContent = '全选'; }, 1500);
            });
            el.style.position = el.style.position || 'relative';
            el.appendChild(btn);
        });
    })();

    // ▸ 文章朗读
    (function initTTS() {
        var ttsBtn = document.getElementById('joe-tts-btn');
        if (!ttsBtn) return;
        var speaking = false;
        var utterance = null;
        
        function stop() {
            if (utterance) {
                window.speechSynthesis.cancel();
                utterance = null;
            }
            speaking = false;
            ttsBtn.classList.remove('is-speaking');
            var text = ttsBtn.querySelector('.joe-tts-btn__text');
            if (text) text.textContent = '朗读';
        }
        
        ttsBtn.addEventListener('click', function () {
            if (speaking) {
                stop();
                return;
            }
            if (!('speechSynthesis' in window)) {
                alert('您的浏览器不支持语音朗读');
                return;
            }
            var article = document.querySelector('.joe-content') || document.querySelector('.joe-article__content');
            if (!article) return;
            var text = article.textContent.replace(/\s+/g, ' ').trim();
            if (!text) return;
            
            utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'zh-CN';
            utterance.rate = 0.9;
            utterance.onend = function () { stop(); };
            utterance.onerror = function () { stop(); };
            
            speaking = true;
            ttsBtn.classList.add('is-speaking');
            var label = ttsBtn.querySelector('.joe-tts-btn__text');
            if (label) label.textContent = '停止';
            window.speechSynthesis.speak(utterance);
        });
    })();

