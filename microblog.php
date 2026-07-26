<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Microblog
 * @description 推文/说说短内容，类似微博/Twitter 风格的时间线
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<?php $microblogCat = joe_get('microblogCategory') ?: 'microblog'; ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap"><div class="joe-main">
            <div class="joe-microblog-header">
                <h1 class="joe-microblog-header__title"><svg viewBox="0 0 24 24" width="28" height="28"><path d="M22 2s-7.6-.5-12 3.5C6.2 9 6.2 15 8 18c2 3.2 5.8 4 9 4 2.6 0 4.5-1 5-3 0 0-2-.2-3.5-1 0 0 3-.4 4.5-2.5 0 0-2 .2-4 .5 0 0 3.5-1.5 4-4C21.5 15 16.5 17 13 17c-3 0-5-1.5-6.5-4.5" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg> 推文</h1>
                <p class="joe-microblog-header__desc">碎片思考 · 随手记录</p>
            </div>
            <?php if ($this->have()): ?>
            <div class="joe-microblog-list">
                <?php while ($this->next()): ?>
                <article class="joe-microblog-item" id="post-<?php $this->cid(); ?>">
                    <div class="joe-microblog-item__header"><time class="joe-microblog-item__time" datetime="<?php $this->date('c'); ?>"><?php echo joe_format_date($this); ?></time></div>
                    <div class="joe-microblog-item__body joe-content"><?php $this->content('阅读全文 »'); ?></div>
                    <div class="joe-microblog-item__footer">
                        <button class="joe-microblog-item__action joe-microblog-share" data-url="<?php $this->permalink(); ?>" data-text="<?php echo htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8'); ?>"><svg viewBox="0 0 24 24" width="14" height="14"><circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2" fill="none"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5" stroke="currentColor" stroke-width="1.5"/><line x1="15.6" y1="6.5" x2="8.6" y2="10.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
                        <a href="<?php $this->permalink(); ?>" class="joe-microblog-item__action"><svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 11.5a8.5 8.5 0 01-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1121 11.5z" stroke="currentColor" stroke-width="2" fill="none"/></svg> <?php $this->commentsNum('%d'); ?></a>
                    </div>
                </article>
                <?php endwhile; ?>
            </div>
            <?php if ($this->_currentPage < $this->getTotalPage()): ?>
            <div class="joe-microblog-more" id="joe-microblog-more"><button class="joe-btn joe-btn--outline" data-page="<?php echo $this->_currentPage + 1; ?>">加载更多</button></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="joe-card" style="text-align:center;padding:60px 20px"><div style="font-size:48px;margin-bottom:16px;opacity:.4">🐦</div><p style="color:var(--text-muted)">还没有推文，去后台创建一篇分类为「<?php echo htmlspecialchars($microblogCat); ?>」的文章吧</p></div>
            <?php endif; ?>
    </div><?php $this->need('sidebar.php'); ?></div>
</main>
<?php $this->need('footer.php'); ?>