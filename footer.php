<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Footer
 * @description 全站底部、页脚信息、社交图标、统计代码
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
</div><!-- /.joe-wrapper -->

<!-- 底部 -->
<footer class="joe-footer">
    <div class="joe-footer__inner">
        <div class="joe-footer__col">
            <div class="joe-footer__brand"><?php echo joe_esc(joe_get('logoText')) ?: $this->options->title(); ?></div>
            <p class="joe-footer__desc"><?php $this->options->description(); ?></p>
        </div>
        <div class="joe-footer__col">
            <h4 class="joe-footer__title">导航</h4>
            <div class="joe-footer__links">
                <?php
                $nav = joe_get('navHtml');
                if ($nav) echo $nav;
                ?>
            </div>
        </div>
        <div class="joe-footer__col">
            <h4 class="joe-footer__title">关注</h4>
            <div class="joe-footer__links">
                <?php
                $social = joe_get('authorSocial');
                if ($social) {
                    $lines = explode("\n", trim($social));
                    foreach ($lines as $line) {
                        $parts = explode('|', trim($line));
                        if (count($parts) === 2) {
                            echo '<a href="' . joe_esc($parts[1]) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars(ucfirst($parts[0])) . '</a>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 社交图标行 -->
    <?php $footerSocial = joe_get('footerSocial'); if ($footerSocial): ?>
    <div class="joe-footer__social-row">
        <?php
        $socialLines = explode("\n", trim($footerSocial));
        foreach ($socialLines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 2) {
                $icon = trim($parts[0]);
                $url = trim($parts[1]);
                echo '<a href="' . joe_esc($url) . '" class="joe-footer__social-item" target="_blank" rel="noopener noreferrer" title="' . htmlspecialchars(ucfirst($icon)) . '">';
                echo joe_social_svg($icon);
                echo '</a>';
            }
        }
        ?>
    </div>
    <?php endif; ?>

    <div class="joe-footer__bottom">
        <?php $stats = joe_site_stats(); ?>
        <div class="joe-footer__stats">
            <span class="joe-footer__stat-item">
                <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 9h16M9 4v16" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                文章 <span class="joe-footer__stat-num"><?php echo $stats['posts']; ?></span>
            </span>
            <span class="joe-footer__stat-item">
                <svg viewBox="0 0 24 24"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                评论 <span class="joe-footer__stat-num"><?php echo $stats['comments']; ?></span>
            </span>
            <span class="joe-footer__stat-item">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                运行 <span class="joe-footer__stat-num" id="joe-runtime" data-start="<?php echo $stats['siteStart']; ?>"><?php echo $stats['days']; ?> 天</span>
            </span>
        </div>
        <div class="joe-footer__bottom-left">
            <?php if (joe_get('footerLeft')): ?>
            <div class="joe-footer__left-custom"><?php echo joe_esc(joe_get('footerLeft')); ?></div>
            <?php else: ?>
            <p>&copy; <?php echo date('Y'); ?> <?php $this->options->title(); ?> · Powered by Typecho · Theme KingJoe</p>
            <?php endif; ?>
            <?php if (joe_get('icp')): ?>
            <p><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php joe_opt('icp'); ?></a></p>
            <?php endif; ?>
        </div>
        <div class="joe-footer__bottom-right">
            <?php if (joe_get('footerRight')): ?>
            <div class="joe-footer__right-custom"><?php echo joe_esc(joe_get('footerRight')); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (joe_get('sslIcon')): ?>
    <div class="joe-footer__ssl">
        <?php echo joe_get('sslIcon'); ?>
    </div>
    <?php endif; ?>
</footer>

<?php if (joe_get('musicPlayer') === '1' && joe_get('musicId')): ?>
<!-- 全局音乐播放器 -->
<div class="joe-music" id="joe-music" data-id="<?php joe_opt('musicId'); ?>" data-server="<?php joe_opt('musicServer'); ?>" data-type="<?php joe_opt('musicType'); ?>" data-autoplay="<?php echo joe_get('musicAutoPlay') === '1' ? '1' : '0'; ?>">
    <button class="joe-music__toggle" id="joe-music-toggle" aria-label="打开音乐播放器">
        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M9 18V5l12-2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="18" cy="16" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
    </button>
    <div class="joe-music__panel" id="joe-music-panel">
        <div class="joe-music__cover" id="joe-music-cover"></div>
        <div class="joe-music__info">
            <div class="joe-music__title" id="joe-music-title">点击加载</div>
            <div class="joe-music__artist" id="joe-music-artist"></div>
        </div>
        <div class="joe-music__controls">
            <button class="joe-music__btn" id="joe-music-prev" aria-label="上一首">
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M19 20 9 12l10-8v16Z" fill="currentColor"/><path d="M5 19V5" stroke="currentColor" stroke-width="2" fill="none"/></svg>
            </button>
            <button class="joe-music__btn joe-music__play" id="joe-music-play" aria-label="播放">
                <svg viewBox="0 0 24 24" width="20" height="20"><path d="M8 5v14l11-7Z" fill="currentColor"/></svg>
            </button>
            <button class="joe-music__btn" id="joe-music-next" aria-label="下一首">
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M5 4 15 12l-10 8V4Z" fill="currentColor"/><path d="M19 5v14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
            </button>
        </div>
        <button class="joe-music__close" id="joe-music-close" aria-label="关闭">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    </div>
    <audio id="joe-music-audio"></audio>
</div>
<?php endif; ?>

<!-- 返回顶部 -->
<button class="joe-backtop" id="joe-backtop" aria-label="返回顶部" title="滚回顶部">
    <span class="joe-backtop__text" id="joe-backtop-text">TOP</span>
    <svg class="joe-backtop__progress" viewBox="0 0 44 44">
        <circle cx="22" cy="22" r="20"/>
    </svg>
    <svg class="joe-backtop__arrow" viewBox="0 0 24 24" width="16" height="16"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

<!-- 移动端底部导航 -->
<nav class="joe-mobile-nav">
    <div class="joe-mobile-nav__list">
        <a href="<?php $this->options->siteUrl(); ?>" class="joe-mobile-nav__item<?php if ($this->is('index')) echo ' is-current'; ?>">
            <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/></svg>
            首页
        </a>
        <?php
        $cats = Typecho_Widget::widget('Widget_Metas_Category_List');
        $hasCat = false;
        while ($cats->next()): if (!$hasCat) $hasCat = true;
        endwhile;
        if ($hasCat):
        ?>
        <a href="<?php $this->options->siteUrl(); ?>archive" class="joe-mobile-nav__item<?php if ($this->is('archive')) echo ' is-current'; ?>">
            <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
            分类
        </a>
        <?php endif; ?>
        <button class="joe-mobile-nav__item joe-theme__toggle" aria-label="切换主题">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            主题
        </button>
        <button class="joe-mobile-nav__item joe-search__trigger" aria-label="搜索">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M20 20l-3.3-3.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            搜索
        </button>
    </div>
</nav>

<script src="<?php echo joe_asset('assets/js/main.js'); ?>?v=1.0.0"></script>
<?php if (joe_get('pjaxToggle') === '1'): ?>
<script>var KINGJOE_PJAX = true;</script>
<script src="<?php echo joe_asset('assets/js/pjax.js'); ?>?v=1.0.0"></script>
<?php endif; ?>
<?php if ($this->is('single') && $this->allow('comment')): ?>
<script src="<?php echo joe_asset('assets/js/owo.js'); ?>?v=1.0.0"></script>
<?php endif; ?>
<?php if (joe_get('codeHighlight') === '1' && $this->is('single')): ?>
<!-- Prism.js 代码高亮 -->
<?php if (joe_get('codeLineNumbers') === '1'): ?>
<script>
  // 行号插件需要在 Prism 高亮前给 pre 添加 line-numbers 类
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('#joe-content pre, .joe-content pre').forEach(function(pre){
      pre.classList.add('line-numbers');
    });
  });
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<?php if (joe_get('codeLineNumbers') === '1'): ?>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<?php endif; ?>
<script src="<?php echo joe_asset('lib/prism/prism.js'); ?>?v=1.0.0"></script>
<?php endif; ?>
<?php if (joe_get('mermaidEnable') === '1'): ?>
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
(function() {
    var theme = document.body.classList.contains('is-dark') ? 'dark' : 'default';
    mermaid.initialize({ startOnLoad: true, theme: theme });
    // 监听暗黑模式切换
    var observer = new MutationObserver(function() {
        var newTheme = document.body.classList.contains('is-dark') ? 'dark' : 'default';
        mermaid.initialize({ startOnLoad: true, theme: newTheme });
        document.querySelectorAll('.mermaid').forEach(function(el) {
            if (el.getAttribute('data-processed')) {
                el.removeAttribute('data-processed');
            }
        });
        setTimeout(function() { mermaid.contentLoaded(); }, 100);
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
</script>
<?php endif; ?>
<?php if (joe_get('analyticsCode')): ?>
<!-- 统计代码 -->
<?php echo joe_get('analyticsCode'); ?>
<?php endif; ?>
<?php $this->footer(); ?>
<?php if (joe_get('customJs')): ?>
<script><?php echo joe_get('customJs'); ?></script>
<?php endif; ?>
<!-- QQ/微信防红 -->
<?php if (joe_get('antiRed') === '1'): ?>
<script>
(function(){
    var ua=navigator.userAgent.toLowerCase();
    var isQQ=ua.indexOf("qq/")>-1&&ua.indexOf("mqqbrowser")===-1;
    var isWx=ua.indexOf("micromessenger")>-1;
    if(!isQQ&&!isWx)return;
    var box=document.createElement("div");
    box.style.cssText="position:fixed;inset:0;background:linear-gradient(135deg,#667eea,#764ba2);z-index:99999;display:flex;align-items:center;justify-content:center;font-family:sans-serif";
    var inner=document.createElement("div");
    inner.style.cssText="text-align:center;color:#fff;padding:40px";
    inner.innerHTML='<div style="font-size:48px;margin-bottom:20px">⏫</div><h2 style="font-size:20px;margin-bottom:12px">请点击右上角</h2><p style="font-size:14px;opacity:0.85;margin-bottom:8px">选择「在浏览器中打开」</p><p style="font-size:12px;opacity:0.6">以获得更好的访问体验</p>';
    box.appendChild(inner);
    box.addEventListener("click",function(){box.style.display="none"});
    document.body.appendChild(box);
})();
</script>
<?php endif; ?>
<?php if (joe_get('pwaEnable') === '1'): ?>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?php $this->options->themeUrl(); ?>/sw.js', { scope: '/' });
}
</script>
<?php endif; ?>
</body>
</html>
