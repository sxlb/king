<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html lang="<?php $this->options->language(); ?>">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <!-- DNS 预解析 & 预连接（性能优化） -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//secure.gravatar.com">
    <?php if (joe_get('cdnUrl')): ?>
    <link rel="dns-prefetch" href="//<?php echo parse_url(joe_get('cdnUrl'), PHP_URL_HOST); ?>">
    <?php endif; ?>

    <title><?php echo joe_esc(joe_seo_title()); ?></title>
    <meta name="description" content="<?php echo joe_esc(joe_seo_description()); ?>">
    <?php if (joe_get('seoKeywords')): ?>
    <meta name="keywords" content="<?php joe_opt('seoKeywords'); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo joe_seo_canonical(); ?>">

    <!-- Open Graph -->
    <meta property="og:site_name" content="<?php $this->options->title(); ?>">
    <meta property="og:type" content="<?php echo $this->is('single') ? 'article' : 'website'; ?>">
    <meta property="og:title" content="<?php echo joe_esc(joe_seo_title(false)); ?>">
    <meta property="og:description" content="<?php echo joe_esc(joe_seo_description()); ?>">
    <meta property="og:url" content="<?php echo joe_seo_canonical(); ?>">
    <?php if ($this->is('single') && joe_has_thumb($this)): ?>
    <meta property="og:image" content="<?php echo joe_esc(joe_thumb($this)); ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo joe_esc(joe_seo_title(false)); ?>">
    <meta name="twitter:description" content="<?php echo joe_esc(joe_seo_description()); ?>">
    <?php if ($this->is('single') && joe_has_thumb($this)): ?>
    <meta name="twitter:image" content="<?php echo joe_esc(joe_thumb($this)); ?>">
    <?php endif; ?>

    <!-- JSON-LD 结构化数据 -->
    <?php if (joe_get('jsonLd') !== '0'): ?>
    <?php if ($this->is('single')): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "<?php echo joe_esc($this->title); ?>",
      "image": ["<?php echo joe_has_thumb($this) ? joe_esc(joe_thumb($this)) : ''; ?>"],
      "datePublished": "<?php echo date('c', $this->created); ?>",
      "dateModified": "<?php echo date('c', $this->modified); ?>",
      "author": {
        "@type": "Person",
        "name": "<?php echo joe_esc($this->author->screenName); ?>"
      },
      "publisher": {
        "@type": "Organization",
        "name": "<?php $this->options->title(); ?>",
        "logo": {
          "@type": "ImageObject",
          "url": ""
        }
      },
      "description": "<?php echo joe_esc(joe_seo_description()); ?>",
      "mainEntityOfPage": "<?php echo joe_seo_canonical(); ?>"
    }
    </script>
    <?php else: ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?php $this->options->title(); ?>",
      "url": "<?php $this->options->siteUrl(); ?>",
      "description": "<?php echo joe_esc(joe_seo_description()); ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?php $this->options->siteUrl(); ?>search/{s}",
        "query-input": "required name=s"
      }
    }
    </script>
    <?php endif; ?>
    <?php endif; ?>
    <link rel="shortcut icon" href="<?php echo joe_esc(joe_get('favicon')) ?: $this->options->siteUrl . 'favicon.ico'; ?>">
    <?php if (joe_get('defaultThumb')): ?>
    <meta name="joe-default-thumb" content="<?php joe_opt('defaultThumb'); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo joe_asset('style.css'); ?>?v=1.0.0">
    <?php if (joe_get('primaryColor') || joe_get('accentColor') || joe_get('radiusBase') || joe_get('bgWallpaper')): ?>
    <style>
    :root {
        <?php if ($c = joe_get('primaryColor')): ?>--primary: <?php echo joe_esc($c); ?>;--primary-light: <?php echo joe_esc($c); ?>20;<?php endif; ?>

        <?php if ($c = joe_get('accentColor')): ?>--accent: <?php echo joe_esc($c); ?>;<?php endif; ?>

        <?php if ($v = joe_get('radiusBase')): ?>--radius: <?php echo joe_esc($v); ?>;--radius-sm: <?php echo joe_esc($v); ?>;<?php endif; ?>

        <?php if ($v = joe_get('radiusLg')): ?>--radius-lg: <?php echo joe_esc($v); ?>;<?php endif; ?>

        <?php if ($v = joe_get('shadowCard')): ?>--shadow-card: <?php echo joe_esc($v); ?>;<?php endif; ?>

        <?php if ($v = joe_get('bgWallpaper')): ?>--bg-wallpaper: url('<?php echo joe_esc($v); ?>');<?php endif; ?>
        <?php if ($v = joe_get('bgWallpaperOpacity')): ?>--bg-wallpaper-opacity: <?php echo joe_esc($v); ?>;<?php endif; ?>
        <?php if ($v = joe_get('sidebarWallpaper')): ?>--sidebar-wallpaper: url('<?php echo joe_esc($v); ?>');<?php endif; ?>
        <?php if ($v = joe_get('mobileHotCount')): ?>--mobile-hot-count: <?php echo intval($v); ?>;<?php endif; ?>

    }
    </style>
    <?php endif; ?>
    <?php if (joe_get('customCss')): ?>
    <style><?php echo str_replace('</style>', '<\/style>', joe_get('customCss')); ?></style>
    <?php endif; ?>
    <?php if (joe_get('keyboardShortcuts') !== '0'): ?>
    <meta name="joe-shortcut" content="1">
    <?php endif; ?>
    <?php if (joe_get('codeHighlight') === '1' && $this->is('single')): ?>
    <link rel="stylesheet" href="<?php echo joe_asset('assets/css/prism.css'); ?>?v=1.0.0">
    <?php endif; ?>
    <?php $this->header('commentReply=&description=&keywords='); ?>
</head>
<body<?php
$bodyClass = '';
if (joe_get('defaultDark')) $bodyClass .= ' is-dark';
if (joe_get('grayMode') === '1') $bodyClass .= ' is-gray';
if (joe_get('sidebarWallpaper')) $bodyClass .= ' has-sidebar-wallpaper';
if ($bodyClass) echo ' class="' . trim($bodyClass) . '"';
?> data-cursor-effect="<?php echo joe_esc(joe_get('cursorEffect')) ?: 'off'; ?>" data-infinite-scroll="<?php echo joe_get('infiniteScroll') === '1' ? '1' : '0'; ?>" data-falling-effect="<?php echo joe_esc(joe_get('fallingEffect')) ?: 'off'; ?>" data-fish-effect="<?php echo joe_get('fishEffect') === '1' ? '1' : '0'; ?>" data-nav-frosted="<?php echo joe_get('navFrosted') !== '0' ? '1' : '0'; ?>" data-starry-bg="<?php echo joe_get('starryBg') === '1' ? '1' : '0'; ?>" data-page-transition="<?php echo joe_get('pageTransition') === '1' ? '1' : '0'; ?>" data-reading-guide-card="<?php echo joe_get('readingGuideCard') !== '0' ? '1' : '0'; ?>" data-ssl-badge="<?php echo joe_get('sslBadge') !== '0' ? '1' : '0'; ?>">
<?php if (joe_get('pageLoader') === '1'): ?>
<div class="joe-pageloader" id="joe-pageloader">
    <div class="joe-pageloader__bar"></div>
</div>
<?php endif; ?>

<!-- 全站公告栏 -->
<?php if (joe_get('noticeBar')): ?>
<div class="joe-notice" id="joe-notice" data-notice-id="<?php echo md5(joe_get('noticeBar')); ?>">
    <div class="joe-notice__inner">
        <?php echo joe_esc(joe_get('noticeBar')); ?>
    </div>
    <button class="joe-notice__close" id="joe-notice-close" aria-label="关闭公告">×</button>
</div>
<?php endif; ?>
<script>
  // 暗黑模式防闪烁：localStorage 优先 > 系统偏好 > 主题默认
  (function(){
    try {
      var saved = localStorage.getItem('kingjoe-theme');
      if (saved === 'dark') { document.body.classList.add('is-dark'); }
      else if (saved === 'light') { document.body.classList.remove('is-dark'); }
      else {
        // 无手动设置时跟随系统
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          document.body.classList.add('is-dark');
        }
      }
    } catch(e){}
  })();
</script>
<div class="joe-wrapper" id="app">

    <!-- 顶部导航 -->
    <header class="joe-header">
        <div class="joe-header__inner">
            <a href="<?php $this->options->siteUrl(); ?>" class="joe-logo<?php if (joe_get('logoShine') === '1') echo ' joe-logo--shine'; ?>">
                <?php if (joe_get('authorAvatar')): ?>
                    <img src="<?php joe_opt('authorAvatar'); ?>" alt="logo" class="joe-logo__img">
                    <?php if (joe_get('darkLogo')): ?>
                    <img src="<?php joe_opt('darkLogo'); ?>" alt="logo" class="joe-logo__img joe-logo__img--dark">
                    <?php endif; ?>
                <?php endif; ?>
                <span class="joe-logo__text"><?php echo joe_esc(joe_get('logoText')) ?: $this->options->title(); ?></span>
            </a>

            <nav class="joe-nav">
                <?php
                $nav = joe_get('navHtml');
                if ($nav) {
                    echo $nav;
                } else {
                    echo '<a href="' . $this->options->siteUrl . '">首页</a>';
                }
                ?>
            </nav>

            <div class="joe-actions">
                <button class="joe-actions__btn joe-search__trigger" aria-label="搜索">
                    <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <button class="joe-actions__btn joe-theme__toggle" aria-label="切换暗黑模式">
                    <svg class="i-sun" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <svg class="i-moon" viewBox="0 0 24 24" width="18" height="18"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                </button>
                <!-- 移动端汉堡菜单 -->
                <button class="joe-hamburger joe-actions__btn" id="joe-hamburger" aria-label="导航菜单">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <!-- 搜索弹层 -->
        <div class="joe-search" id="joe-search">
            <div class="joe-search__inner">
                <form class="joe-search__form" method="post" action="<?php $this->options->siteUrl(); ?>">
                    <input type="text" name="s" class="joe-search__input" placeholder="搜索文章..." autocomplete="off">
                    <button type="submit" class="joe-search__btn" aria-label="搜索">
                        <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </form>
            </div>
            <div class="joe-search__overlay" data-close-search></div>
        </div>
    </header>

    <!-- 移动端菜单 -->
    <div class="joe-drawer" id="joe-drawer">
        <div class="joe-drawer__overlay" data-close-drawer></div>
        <aside class="joe-drawer__panel">
            <nav class="joe-drawer__nav">
                <?php
                $nav = joe_get('navHtml');
                if ($nav) {
                    echo $nav;
                } else {
                    echo '<a href="' . $this->options->siteUrl . '">首页</a>';
                }
                ?>
            </nav>
        </aside>
    </div>
