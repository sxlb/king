<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Archive
 * @description 文章归档页、时间轴视图、搜索整合
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main">
            <section class="joe-postlist">
                <div class="joe-section__head">
                    <h2 class="joe-section__title">
                        <span class="joe-section__bar"></span>
                        <?php $this->archiveTitle([
                            'category' => _t('分类：%s'),
                            'tag'      => _t('标签：%s'),
                            'search'   => _t('搜索：%s'),
                            'author'   => _t('作者：%s'),
                            'date'     => _t('归档：%s'),
                        ], '', ''); ?>
                    </h2>
                </div>

                <?php if ($this->have()):
                    // 搜索结果显示统计
                    if ($this->is('search')): ?>
                    <div class="joe-search-info">搜索 "<b><?php echo isset($_GET['s']) ? htmlspecialchars($_GET['s']) : (isset($_POST['s']) ? htmlspecialchars($_POST['s']) : ''); ?></b>"，找到 <b><?php echo $this->getTotal(); ?></b> 条结果</div>
                    <?php endif;

                    $isTimeline = $this->is('archive') && !$this->is('category') && !$this->is('tag') && !$this->is('search') && !$this->is('author');
                    if ($isTimeline):
                        // 时间轴视图：按年份分组
                        $currentYear = '';
                        while ($this->next()):
                            $year = date('Y', $this->created);
                            if ($year !== $currentYear):
                                if ($currentYear !== '') echo '</div>';
                                $currentYear = $year;
                            ?>
                            <div class="joe-archive__year">
                                <h3 class="joe-archive__year-title"><?php echo $year; ?></h3>
                            <?php endif; ?>
                                <div class="joe-archive__post">
                                    <span class="joe-archive__post-time"><?php echo date('m-d', $this->created); ?></span>
                                    <a href="<?php $this->permalink() ?>" class="joe-archive__post-title"><?php $this->title() ?></a>
                                    <?php if ($this->category): ?>
                                    <span class="joe-archive__post-cat"><?php $this->category(',', false); ?></span>
                                    <?php endif; ?>
                                </div>
                        <?php endwhile; ?>
                        <?php if ($currentYear !== '') echo '</div>'; ?>
                    <?php else: ?>
                        <?php while ($this->next()): ?>
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
                                    <a href="<?php $this->permalink() ?>"><?php echo joe_search_highlight($this->title); ?></a>
                                </h3>
                                <p class="joe-postlist__excerpt"><?php echo joe_search_highlight(joe_excerpt($this, 120)); ?></p>
                                <div class="joe-postlist__meta">
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
                    <?php endif; ?>
                <?php else: ?>
                <div class="joe-empty joe-empty--big">:)</div>
                <?php endif; ?>

                <nav class="joe-pagination">
                    <?php $this->pageNav(
                        '上一页',
                        '下一页',
                        1, '...',
                        ['wrapTag' => 'ul', 'wrapClass' => 'joe-pagination__list', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'is-active', 'prevClass' => 'is-prev', 'nextClass' => 'is-next']
                    ); ?>
                </nav>
            </section>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
