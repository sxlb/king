<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Index
 * @description 首页、文章列表、轮播图、热门推荐
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <!-- SEO 隐藏 H1 -->
    <h1 class="joe-seo-h1"><?php $this->options->title(); ?> - <?php $this->options->description(); ?></h1>
    <div class="joe-main__wrap">
        <div class="joe-main">
            <!-- 首页大屏 Hero -->
            <?php if ($this->is('index') && $this->_currentPage <= 1 && joe_get('heroImage')): ?>
            <section class="joe-hero" style="background-image:url('<?php echo joe_esc(joe_get('heroImage')); ?>')">
                <div class="joe-hero__inner">
                    <?php if (joe_get('heroTitle')): ?>
                    <h1 class="joe-hero__title"><?php echo joe_esc(joe_get('heroTitle')); ?></h1>
                    <?php endif; ?>
                    <?php if (joe_get('heroSubtitle')): ?>
                    <p class="joe-hero__subtitle"><?php echo joe_esc(joe_get('heroSubtitle')); ?></p>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 首页轮播图 -->
            <?php $slides = joe_carousel_slides(); ?>
            <?php if ($this->is('index') && $this->_currentPage <= 1 && !empty($slides)): ?>
            <section class="joe-carousel" id="joe-carousel">
                <div class="joe-carousel__track" id="joe-carousel-track">
                    <?php foreach ($slides as $i => $s): ?>
                    <a href="<?php echo joe_esc($s['url']); ?>" class="joe-carousel__slide<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>"<?php if (joe_get('carouselNewTab') === '1') echo ' target="_blank" rel="noopener"'; ?>>
                        <img src="<?php echo joe_esc($s['image']); ?>" alt="<?php echo joe_esc($s['title']); ?>" class="joe-carousel__img">
                        <?php if ($s['title']): ?>
                        <span class="joe-carousel__caption"><?php echo joe_esc($s['title']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (count($slides) > 1): ?>
                <button class="joe-carousel__prev" id="joe-carousel-prev" aria-label="上一张">
                    <svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button class="joe-carousel__next" id="joe-carousel-next" aria-label="下一张">
                    <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="joe-carousel__dots" id="joe-carousel-dots">
                    <?php foreach ($slides as $i => $s): ?>
                    <button class="joe-carousel__dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="第<?php echo $i + 1; ?>张"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <!-- 首页大图 Banner -->
            <?php if ($this->is('index') && joe_get('bannerTitle')): ?>
            <section class="joe-banner">
                <div class="joe-banner__inner">
                    <h1 class="joe-banner__title"><?php joe_opt('bannerTitle'); ?></h1>
                    <p class="joe-banner__desc"><?php joe_opt('bannerDesc'); ?></p>
                    <?php if (joe_get('bannerBtnText')): ?>
                    <a href="<?php joe_opt('bannerBtnLink'); ?>" class="joe-banner__btn">
                        <?php joe_opt('bannerBtnText'); ?>
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 置顶文章 -->
            <?php if ($this->is('index') && $this->_currentPage == 1): ?>
            <?php $sticky = joe_sticky_posts(); ?>
            <?php $stickyCids = array_column($sticky, 'cid'); ?>
            <?php if (!empty($sticky)): ?>
            <section class="joe-sticky">
                <div class="joe-section__head">
                    <h2 class="joe-section__title"><span class="joe-section__bar"></span>置顶推荐</h2>
                </div>
                <div class="joe-sticky__list">
                    <?php foreach ($sticky as $s): ?>
                    <a href="<?php echo $s['permalink']; ?>" class="joe-sticky__item">
                        <span class="joe-sticky__pin">📌</span>
                        <span class="joe-sticky__title"><?php echo joe_esc($s['title']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            <?php endif; ?>

            <!-- 文章列表 -->
            <section class="joe-postlist<?php $layout = joe_get('listLayout'); if ($layout === 'timeline') echo ' joe-postlist--timeline'; elseif ($layout === 'masonry') echo ' joe-postlist--masonry'; ?>">
                <div class="joe-section__head">
                    <?php if ($this->is('index') && joe_get('homeFilter') === '1'): ?>
                    <div class="joe-section__tabs" id="joe-home-tabs">
                        <a href="<?php $this->options->siteUrl(); ?>" class="joe-section__tab<?php if (!isset($_GET['tab']) || $_GET['tab'] === 'latest') echo ' is-active'; ?>" data-tab="latest">最新</a>
                        <a href="<?php $this->options->siteUrl(); ?>?tab=hot" class="joe-section__tab<?php if (isset($_GET['tab']) && $_GET['tab'] === 'hot') echo ' is-active'; ?>" data-tab="hot">热门</a>
                        <a href="<?php $this->options->siteUrl(); ?>?tab=random" class="joe-section__tab<?php if (isset($_GET['tab']) && $_GET['tab'] === 'random') echo ' is-active'; ?>" data-tab="random">随机</a>
                    </div>
                    <?php else: ?>
                    <h2 class="joe-section__title"><span class="joe-section__bar"></span>最新文章</h2>
                    <?php endif; ?>
                </div>

                <?php if ($this->is('index') && joe_get('homeFilter') === '1' && isset($_GET['tab']) && $_GET['tab'] === 'hot'): ?>
                <!-- 热门模式 -->
                <?php $limit = (int)(joe_get('homeHotCount') ?: 6); ?>
                <?php foreach (joe_hot_posts($limit, 'views') as $hp): ?>
                <article class="joe-postlist__item">
                    <a href="<?php echo $hp['permalink']; ?>" class="joe-postlist__thumb">
                        <span class="joe-postlist__placeholder"><?php echo mb_substr($hp['title'], 0, 1); ?></span>
                    </a>
                    <div class="joe-postlist__body">
                        <h3 class="joe-postlist__title">
                            <a href="<?php echo $hp['permalink']; ?>"><?php echo joe_esc($hp['title']); ?></a>
                        </h3>
                        <p class="joe-postlist__excerpt"><?php echo $hp['views']; ?> 次阅读</p>
                        <div class="joe-postlist__meta">
                            <span class="joe-meta__item"><?php echo date('Y-m-d', $hp['created']); ?></span>
                            <span class="joe-meta__item">🔥 <?php echo $hp['views']; ?> 阅读</span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php elseif ($this->is('index') && joe_get('homeFilter') === '1' && isset($_GET['tab']) && $_GET['tab'] === 'random'): ?>
                <!-- 随机模式 -->
                <?php foreach (joe_random_posts(12) as $rp): ?>
                <article class="joe-postlist__item">
                    <a href="<?php echo $rp['permalink']; ?>" class="joe-postlist__thumb">
                        <span class="joe-postlist__placeholder"><?php echo mb_substr($rp['title'], 0, 1); ?></span>
                    </a>
                    <div class="joe-postlist__body">
                        <h3 class="joe-postlist__title">
                            <a href="<?php echo $rp['permalink']; ?>"><?php echo joe_esc($rp['title']); ?></a>
                        </h3>
                        <div class="joe-postlist__meta">
                            <span class="joe-meta__item"><?php echo date('Y-m-d', $rp['created']); ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php else: ?>
                <?php while ($this->next()): ?>
                <?php if (joe_get('stickyDedup') === '1' && isset($stickyCids) && in_array($this->cid, $stickyCids)) continue; ?>
                <article class="joe-postlist__item">
                    <a href="<?php $this->permalink() ?>" class="joe-postlist__thumb<?php if (!joe_has_thumb($this)) echo ' is-none'; ?>">
                        <?php if (joe_has_thumb($this)): ?>
                            <?php echo joe_lazy_img(joe_thumb($this), $this->title, 'joe-postlist__img', 400, 260); ?>
                        <?php else: ?>
                            <span class="joe-postlist__placeholder"><?php echo mb_substr($this->title, 0, 1); ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="joe-postlist__body">
                        <h3 class="joe-postlist__title">
                            <a href="<?php $this->permalink() ?>"><?php $this->title() ?></a>
                            <?php if ($this->category): ?>
                            <span class="joe-postlist__cat"><?php $this->category(',', false); ?></span>
                            <?php endif; ?>
                        </h3>
                        <p class="joe-postlist__excerpt"><?php echo joe_excerpt($this, 120); ?></p>
                        <div class="joe-postlist__meta">
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                                <?php $this->author(); ?>
                            </span>
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M3 9h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                                <?php echo joe_format_date($this->created); ?>
                            </span>
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                                <?php $this->commentsNum('%d'); ?>
                            </span>
                        </div>
                    </div>
                </article>
                <?php endwhile; ?>

                <!-- 分页 -->
                <nav class="joe-pagination">
                    <?php $this->pageNav(
                        '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        1, '...',
                        ['wrapTag' => 'ul', 'wrapClass' => 'joe-pagination__list', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'is-active', 'prevClass' => 'is-prev', 'nextClass' => 'is-next']
                    ); ?>
                </nav>
                <?php endif; ?>
            </section>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
