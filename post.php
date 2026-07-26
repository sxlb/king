<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Post
 * @description 文章详情页、正文内容、分享按钮、作者卡片
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article">
                <?php if (joe_get('readingProgress') === '1'): ?>
                <div class="joe-progressbar" id="joe-progressbar"><span class="joe-progressbar__fill" id="joe-progressbar-fill"></span></div>
                <?php endif; ?>
                <!-- 面包屑 -->
                <nav class="joe-breadcrumb">
                    <a href="<?php $this->options->siteUrl(); ?>">首页</a>
                    <span class="joe-breadcrumb__sep">/</span>
                    <?php $this->category(',', false); ?>
                    <span class="joe-breadcrumb__sep">/</span>
                    <span class="joe-breadcrumb__current"><?php $this->title(); ?></span>
                </nav>

                <!-- 文章顶部大图 -->
                <?php if (joe_get('articleHeroImage') === '1' && joe_has_thumb($this)): ?>
                <div class="joe-article__hero" style="background-image:url('<?php echo joe_esc(joe_thumb($this)); ?>')">
                    <div class="joe-article__hero-inner">
                        <h1 class="joe-article__hero-title"><?php $this->title(); ?></h1>
                        <div class="joe-article__hero-meta">
                            <span><?php $this->author(); ?></span>
                            <span>·</span>
                            <span><?php echo joe_format_date($this->created); ?></span>
                            <span>·</span>
                            <span><?php echo joe_views($this); ?> 阅读</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 文章标题 -->
                <header class="joe-article__head<?php if (joe_get('titleCenter') === '1') echo ' joe-article__head--center'; ?>">
                    <div class="joe-article__head-row">
                        <h1 class="joe-article__title"><?php $this->title(); ?></h1>
                        <div class="joe-article__tools">
                            <?php if (joe_get('fontSizeAdjust') !== '0'): ?>
                            <div class="joe-fontsize" id="joe-fontsize">
                                <button class="joe-fontsize__btn" data-size="-1" title="缩小字体" aria-label="缩小字体">A⁻</button>
                                <button class="joe-fontsize__btn is-active" data-size="0" title="默认大小" aria-label="默认字体">A</button>
                                <button class="joe-fontsize__btn" data-size="1" title="放大字体" aria-label="放大字体">A⁺</button>
                            </div>
                            <?php endif; ?>
                            <button class="joe-reader__btn" id="joe-reader-btn" aria-label="阅读模式" title="沉浸式阅读（双击正文也可切换）">
                                <svg viewBox="0 0 24 24" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 16h18M3 8h18" stroke="currentColor" stroke-width="1.5"/></svg>
                                <span>沉浸阅读</span>
                            </button>
                            <?php if (joe_get('ttsEnable') === '1'): ?>
                            <button class="joe-tts-btn" id="joe-tts-btn" title="朗读文章" aria-label="朗读文章">
                                <svg class="joe-tts-btn__icon" viewBox="0 0 24 24" width="18" height="18"><path d="M11 5L6 9H2v6h4l5 4V5z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M15.5 8.5a4.5 4.5 0 010 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M18.5 5.5a8.5 8.5 0 010 13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                                <span class="joe-tts-btn__text">朗读</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="joe-article__meta">
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                            <?php $this->author(); ?>
                        </span>
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M3 9h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                            <?php echo joe_format_date($this->created); ?>
                        </span>
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                            <?php echo joe_views($this); ?> 阅读
                        </span>
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                            <?php $this->commentsNum('%d'); ?> 评论
                        </span>
                        <?php if (joe_get('readingTime') === '1'): ?>
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                            <?php echo joe_reading_time($this); ?>
                        </span>
                        <?php endif; ?>
                        <span class="joe-meta__item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                            <?php echo joe_word_count($this); ?> 字
                        </span>
                    </div>
                </header>

                <!-- 百度收录检测 -->
                <?php if (joe_get('baiduCheck') === '1'):
                    $checkUrl = $this->permalink;
                    $indexed = joe_baidu_check($checkUrl);
                ?>
                <div class="joe-baidu-check" id="joe-baidu-check" data-url="<?php echo joe_esc($checkUrl); ?>" data-indexed="<?php echo $indexed; ?>">
                    <?php if ($indexed === 1): ?>
                    <span class="joe-baidu-check__status is-indexed">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        百度已收录
                    </span>
                    <?php elseif ($indexed === 0): ?>
                    <span class="joe-baidu-check__status is-not-indexed">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        暂未被百度收录
                    </span>
                    <button class="joe-baidu-check__submit" id="joe-baidu-submit">手动提交收录</button>
                    <?php else: ?>
                    <span class="joe-baidu-check__status">收录检测未开启或检测中...</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (joe_has_thumb($this)): ?>
                <div class="joe-article__cover">
                    <?php echo joe_lazy_img(joe_thumb($this), $this->title, 'joe-article__cover-img', 1200, 500); ?>
                </div>
                <?php endif; ?>

                <?php if (joe_is_overdue($this)): ?>
                <div class="joe-overdue">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 9v4l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 12h2M19 12h2M12 3v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <div class="joe-overdue__text">温馨提示：本文最后更新于 <b><?php echo joe_overdue_days($this); ?></b> 天前，部分内容可能已失效，请谨慎参考。</div>
                </div>
                <?php endif; ?>

                <!-- 正文 + TOC -->
                <div class="joe-article__layout">
                    <?php
                    $content = joe_add_heading_ids($this->content);
                    $toc = joe_toc($content);
                    if ($toc):
                    ?>
                    <aside class="joe-article__toc" id="joe-toc"><?php echo $toc; ?></aside>
                    <?php endif; ?>

                    <div class="joe-article__content joe-content" id="joe-content">
                        <?php echo $content; ?>
                    </div>
                </div>

                <?php if ($toc): ?>
                <!-- 移动端 TOC 抽屉触发按钮 -->
                <button class="joe-toc__fab" id="joe-toc-fab" aria-label="文章目录">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>

                <!-- 移动端 TOC 抽屉 -->
                <div class="joe-toc__drawer" id="joe-toc-drawer">
                    <div class="joe-toc__drawer-overlay" data-close-toc-drawer></div>
                    <aside class="joe-toc__drawer-panel">
                        <div class="joe-toc__drawer-head">
                            <span>文章目录</span>
                            <button class="joe-toc__drawer-close" data-close-toc-drawer aria-label="关闭">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <div class="joe-toc__drawer-body">
                            <?php echo $toc; ?>
                        </div>
                    </aside>
                </div>
                <?php endif; ?>

                <!-- 标签 -->
                <?php if ($this->tags): ?>
                <div class="joe-article__tags">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12.2V5a2 2 0 0 1 2-2h7.2a2 2 0 0 1 1.4.6l7 7a2 2 0 0 1 0 2.8Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/></svg>
                    <?php $this->tags(',', true, '<a class="joe-tag">', '</a>'); ?>
                </div>
                <?php endif; ?>

                <!-- 分享按钮 -->
                <?php if (joe_get('shareBtn') === '1'): ?>
                <div class="joe-share">
                    <span class="joe-share__label">分享到：</span>
                    <button class="joe-share__btn is-weibo" data-share="weibo" title="分享到微博">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M10.5 18c-3.6 0-6.5-2.2-6.5-5s2.9-5 6.5-5c3.6 0 6.5 2.2 6.5 5s-2.9 5-6.5 5Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M18 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm-1.5 2a4 4 0 1 0 0-8" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                        微博
                    </button>
                    <button class="joe-share__btn is-twitter" data-share="twitter" title="分享到 Twitter">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M22 5.8c-.8.4-1.6.6-2.5.8a4.2 4.2 0 0 0-7.4-3 4.2 4.2 0 0 0-1.2 3A12 12 0 0 1 3 4.6s-2 4.5 1 7.3c-.7 0-1.4-.2-2-.5 0 2.2 1.6 4.1 3.6 4.5a4.4 4.4 0 0 1-2 0 4.3 4.3 0 0 0 4 3 8.7 8.7 0 0 1-6.3 1.8A12.2 12.2 0 0 0 9 21c8 0 12.5-6.7 12.5-12.5v-.6c.8-.6 1.5-1.4 2-2.3Z" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linejoin="round"/></svg>
                        Twitter
                    </button>
                    <button class="joe-share__btn is-wechat" data-share="wechat" title="分享到微信">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M8.5 9.5c0-1.4 1.6-2.5 3.5-2.5s3.5 1.1 3.5 2.5S13.9 12 12 12s-3.5-1.1-3.5-2.5Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 3C6.5 3 2 6.6 2 11c0 2.5 1.5 4.8 4 6.2L5 20l3.5-2c1 .2 2.3.3 3.5.3" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M15 12c3 0 5.5 2 5.5 4.5 0 1.2-.6 2.4-1.5 3.3L20 21l-1.5-1.2c-.5.1-1 .1-1.5.1" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M18 15h.01M14 15h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        微信
                    </button>
                    <button class="joe-share__btn is-copy" data-share="copy" title="复制链接">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M9 9h10v10H9z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        复制链接
                    </button>
                    <div class="joe-share__qrcode" id="joe-share-qrcode">
                        <div class="joe-share__qrcode-tip">
                            <svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 3l8 4v10l-8 4-8-4V7l8-4Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M12 12V7M12 17v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>打开微信「扫一扫」分享</span>
                        </div>
                        <canvas id="joe-share-canvas" width="160" height="160"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 文章底部自定义提示 -->
                <?php if (joe_get('postFooterTip')): ?>
                <div class="joe-post-tip">
                    <?php echo joe_esc(joe_get('postFooterTip')); ?>
                </div>
                <?php endif; ?>

                <!-- 右下角浮动阅读进度 -->
                <?php if (joe_get('readingProgress') === '2'): ?>
                <div class="joe-float-progress" id="joe-float-progress">
                    <svg class="joe-float-progress__ring" viewBox="0 0 60 60">
                        <circle class="joe-float-progress__bg" cx="30" cy="30" r="27"/>
                        <circle class="joe-float-progress__fill" cx="30" cy="30" r="27" id="joe-float-progress-fill"/>
                    </svg>
                    <span class="joe-float-progress__text" id="joe-float-progress-text">0%</span>
                </div>
                <?php endif; ?>

                <?php if (joe_get('agreeBtn') === '1'): ?>
                <div class="joe-agree">
                    <button class="joe-agree__btn" id="joe-agree-btn" data-cid="<?php $this->cid(); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 21s-7-4.5-7-10a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 5.5-7 10-7 10Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                        <span class="joe-agree__text">点赞支持</span>
                        <span class="joe-agree__count"><?php echo joe_agree($this); ?></span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if (joe_get('donateQr') === '1'): ?>
                <div class="joe-donate">
                    <button class="joe-donate__toggle" id="joe-donate-toggle">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        投喂博主
                    </button>
                    <div class="joe-donate__body" id="joe-donate-body">
                        <div class="joe-donate__qrcodes">
                            <?php if (joe_get('donateWechat')): ?>
                            <div class="joe-donate__item">
                                <img src="<?php joe_opt('donateWechat'); ?>" alt="微信支付" loading="lazy">
                                <span>微信</span>
                            </div>
                            <?php endif; ?>
                            <?php if (joe_get('donateAlipay')): ?>
                            <div class="joe-donate__item">
                                <img src="<?php joe_opt('donateAlipay'); ?>" alt="支付宝" loading="lazy">
                                <span>支付宝</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 版权 -->
                <div class="joe-article__copyright">
                    <p>本文链接：<a href="<?php $this->permalink(); ?>"><?php $this->permalink(); ?></a></p>
                    <p>版权声明：本博客所有文章除特别声明外，均采用 <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/" target="_blank" rel="noopener noreferrer">CC BY-NC-SA 4.0</a> 许可协议。转载请注明来自 <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>。</p>
                </div>

                <!-- 作者卡片 -->
                <div class="joe-author-card">
                    <div class="joe-author-card__avatar">
                        <?php if (joe_get('authorAvatar')): ?>
                        <img src="<?php joe_opt('authorAvatar'); ?>" alt="<?php joe_opt('authorName'); ?>" loading="lazy">
                        <?php else: ?>
                        <span class="joe-author-card__avatar-placeholder"><?php echo mb_substr(joe_get('authorName') ?: 'A', 0, 1); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="joe-author-card__info">
                        <div class="joe-author-card__name"><?php joe_opt('authorName'); ?></div>
                        <?php if (joe_get('authorDesc')): ?>
                        <div class="joe-author-card__desc"><?php joe_opt('authorDesc'); ?></div>
                        <?php endif; ?>
                        <div class="joe-author-card__stats">
                            <?php
                            $authorPosts = joe_site_stats();
                            ?>
                            <span class="joe-author-card__stat">已发布 <b><?php echo $authorPosts['posts']; ?></b> 篇文章</span>
                            <span class="joe-author-card__stat">获得 <b><?php echo $authorPosts['comments']; ?></b> 条评论</span>
                        </div>
                    </div>
                </div>

                <!-- 上下篇 -->
                <?php $neighbors = joe_post_neighbors($this); ?>
                <nav class="joe-neighbors">
                    <?php if ($neighbors['prev']): ?>
                    <a href="<?php echo joe_neighbor_link($neighbors['prev']); ?>" class="joe-neighbors__item is-prev">
                        <div class="joe-neighbors__thumb">
                            <?php $prevThumb = joe_neighbor_thumb($neighbors['prev']); ?>
                            <?php if ($prevThumb): ?>
                            <img src="<?php echo $prevThumb; ?>" alt="<?php echo joe_esc($neighbors['prev']['title']); ?>" loading="lazy">
                            <?php else: ?>
                            <span class="joe-neighbors__placeholder"><?php echo mb_substr($neighbors['prev']['title'], 0, 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="joe-neighbors__info">
                            <span class="joe-neighbors__label">上一篇</span>
                            <span class="joe-neighbors__title"><?php echo joe_esc($neighbors['prev']['title']); ?></span>
                        </div>
                    </a>
                    <?php else: ?>
                    <span class="joe-neighbors__item is-prev is-disabled">
                        <div class="joe-neighbors__info">
                            <span class="joe-neighbors__label">上一篇</span>
                            <span class="joe-neighbors__title">没有更多了</span>
                        </div>
                    </span>
                    <?php endif; ?>
                    <?php if ($neighbors['next']): ?>
                    <a href="<?php echo joe_neighbor_link($neighbors['next']); ?>" class="joe-neighbors__item is-next">
                        <div class="joe-neighbors__info">
                            <span class="joe-neighbors__label">下一篇</span>
                            <span class="joe-neighbors__title"><?php echo joe_esc($neighbors['next']['title']); ?></span>
                        </div>
                        <div class="joe-neighbors__thumb">
                            <?php $nextThumb = joe_neighbor_thumb($neighbors['next']); ?>
                            <?php if ($nextThumb): ?>
                            <img src="<?php echo $nextThumb; ?>" alt="<?php echo joe_esc($neighbors['next']['title']); ?>" loading="lazy">
                            <?php else: ?>
                            <span class="joe-neighbors__placeholder"><?php echo mb_substr($neighbors['next']['title'], 0, 1); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php else: ?>
                    <span class="joe-neighbors__item is-next is-disabled">
                        <div class="joe-neighbors__info">
                            <span class="joe-neighbors__label">下一篇</span>
                            <span class="joe-neighbors__title">没有更多了</span>
                        </div>
                    </span>
                    <?php endif; ?>
                </nav>

                <?php if (joe_get('relatedPosts') === '1'): ?>
                <?php $related = joe_related_posts($this, (int)(joe_get('relatedNum') ?: 6)); ?>
                <?php if (!empty($related)): ?>
                <section class="joe-related">
                    <h3 class="joe-related__title">相关推荐</h3>
                    <ul class="joe-related__list">
                        <?php foreach ($related as $r): ?>
                        <li class="joe-related__item">
                            <a href="<?php echo $r['permalink']; ?>" class="joe-related__card">
                                <span class="joe-related__card-thumb">
                                    <?php if (!empty($r['thumb'])): ?>
                                    <img src="<?php echo joe_esc($r['thumb']); ?>" alt="<?php echo joe_esc($r['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                    <?php echo mb_substr($r['title'], 0, 1); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="joe-related__card-body">
                                    <span class="joe-related__card-title"><?php echo joe_esc($r['title']); ?></span>
                                    <span class="joe-related__card-date"><?php echo date('Y-m-d', $r['created']); ?></span>
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
                <?php endif; ?>

                <!-- 移动端悬浮操作栏 -->
                <?php if (joe_get('mobileActionBar') !== '0'): ?>
                <div class="joe-mobile-bar" id="joe-mobile-bar">
                    <button class="joe-mobile-bar__btn" data-action="agree" id="joe-mb-agree" data-cid="<?php $this->cid(); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 21s-7-4.5-7-10a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 5.5-7 10-7 10Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                        <span class="joe-mobile-bar__count" id="joe-mb-agree-count"><?php echo joe_agree($this); ?></span>
                    </button>
                    <button class="joe-mobile-bar__btn" data-action="comment">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        <span class="joe-mobile-bar__count" id="joe-mb-comment-count"><?php $this->commentsNum('%d'); ?></span>
                    </button>
                    <button class="joe-mobile-bar__btn" data-action="share" title="分享">
                        <svg viewBox="0 0 24 24" width="20" height="20"><circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2" fill="none"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5" stroke="currentColor" stroke-width="1.5"/><line x1="15.6" y1="6.5" x2="8.6" y2="10.5" stroke="currentColor" stroke-width="1.5"/></svg>
                    </button>
                    <button class="joe-mobile-bar__btn" data-action="top" title="返回顶部">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <?php endif; ?>

                <!-- 评论 -->
                <?php $this->need('comments.php'); ?>
            </article>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
