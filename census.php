<?php
/**
 * 博客统计
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$stats = joe_site_stats();
?>
<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article">
                <header class="joe-article__head">
                    <h1 class="joe-article__title"><?php $this->title(); ?></h1>
                    <p class="joe-article__desc">记录博客成长的点点滴滴 📊</p>
                </header>
                <div class="joe-stats__grid">
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M4 9h16M9 4v16" stroke="currentColor" stroke-width="2" fill="none"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['posts']; ?></div>
                        <div class="joe-stats__label">篇文章</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['comments']; ?></div>
                        <div class="joe-stats__label">条评论</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['views']; ?></div>
                        <div class="joe-stats__label">总浏览</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['days']; ?></div>
                        <div class="joe-stats__label">运行天数</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12.2V5a2 2 0 0 1 2-2h7.2a2 2 0 0 1 1.4.6l7 7a2 2 0 0 1 0 2.8Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['tags']; ?></div>
                        <div class="joe-stats__label">个标签</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['categories']; ?></div>
                        <div class="joe-stats__label">个分类</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg></div>
                        <div class="joe-stats__num"><?php echo $stats['pages']; ?></div>
                        <div class="joe-stats__label">个页面</div>
                    </div>
                    <div class="joe-stats__card">
                        <div class="joe-stats__icon"><svg viewBox="0 0 24 24" width="24" height="24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        <div class="joe-stats__num"><?php echo round($stats['posts'] / max(1, $stats['days']) * 365, 1); ?></div>
                        <div class="joe-stats__label">篇/年均</div>
                    </div>
                </div>
                <?php if (trim($this->content)): ?>
                <div class="joe-article__content joe-content">
                    <?php echo joe_add_heading_ids($this->content); ?>
                </div>
                <?php endif; ?>
                <?php if ($this->allow('comment')): ?>
                    <?php $this->need('comments.php'); ?>
                <?php endif; ?>
            </article>
        </div>
        <?php $this->need('sidebar.php'); ?>
    </div>
</main>
<?php $this->need('footer.php'); ?>