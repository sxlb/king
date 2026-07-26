<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php $rawKeyword = $this->request->s ?? ''; ?>
<?php $keyword = htmlspecialchars($rawKeyword); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main">

            <!-- 搜索头部 -->
            <section class="joe-search-header">
                <div class="joe-search-header__inner">
                    <?php if ($this->have()): ?>
                    <h2 class="joe-search-header__title">
                        <span class="joe-search-header__icon">
                            <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                        </span>
                        找到 <b class="joe-search-header__count"><?php $this->archiveTitle(['search' => '%s'], '', ''); ?></b>
                    </h2>
                    <p class="joe-search-header__keyword">关键词：<mark><?php echo $keyword; ?></mark></p>
                    <?php else: ?>
                    <h2 class="joe-search-header__title">
                        <span class="joe-search-header__icon">
                            <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                        </span>
                        未找到相关内容
                    </h2>
                    <?php if ($keyword): ?>
                    <p class="joe-search-header__keyword">关键词：<mark><?php echo $keyword; ?></mark></p>
                    <p class="joe-search-header__hint">没有找到匹配的结果，试试别的关键词吧</p>
                    <?php endif; ?>
                    <?php endif; ?>
                    <!-- 搜索框 -->
                    <form class="joe-search-header__form" method="post" action="<?php $this->options->siteUrl(); ?>">
                        <input type="text" name="s" class="joe-search-header__input" value="<?php echo $keyword; ?>" placeholder="输入关键词搜索..." autocomplete="off" autofocus>
                        <button type="submit" class="joe-search-header__btn">
                            <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                        </button>
                    </form>
                </div>
            </section>

            <?php if ($this->have()): ?>
            <!-- 结果筛选栏 -->
            <div class="joe-search-filter" id="joe-search-filter">
                <div class="joe-search-filter__left">
                    <span class="joe-search-filter__label">筛选：</span>
                    <select class="joe-search-filter__select" id="joe-filter-category">
                        <option value="">全部分类</option>
                        <?php
                        $catsFilter = Typecho_Widget::widget('Widget_Metas_Category_List');
                        while ($catsFilter->next()):
                        ?>
                        <option value="<?php $catsFilter->name(); ?>"><?php $catsFilter->name(); ?> (<?php $catsFilter->count(); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="joe-search-filter__right">
                    <span class="joe-search-filter__label">排序：</span>
                    <select class="joe-search-filter__select" id="joe-filter-sort">
                        <option value="relevance">按相关度</option>
                        <option value="date-desc">按时间↓</option>
                        <option value="date-asc">按时间↑</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($this->have()): ?>
            <!-- 搜索结果列表 -->
            <section class="joe-postlist joe-search-results">
                <?php while ($this->next()): ?>
                <article class="joe-postlist__item" data-searchable data-category="<?php $this->category(',', false); ?>" data-date="<?php echo date('Y-m-d', $this->created); ?>">
                    <?php if (joe_has_thumb($this)): ?>
                    <a href="<?php $this->permalink(); ?>" class="joe-postlist__thumb">
                        <span class="joe-postlist__placeholder" style="display:none"></span>
                        <img src="<?php echo joe_thumb($this); ?>" alt="<?php $this->title(); ?>" loading="lazy">
                    </a>
                    <?php else: ?>
                    <a href="<?php $this->permalink(); ?>" class="joe-postlist__thumb">
                        <span class="joe-postlist__placeholder"><?php echo mb_substr($this->title, 0, 1); ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="joe-postlist__body">
                        <h3 class="joe-postlist__title joe-search-highlight-title">
                            <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                        </h3>
                        <p class="joe-postlist__excerpt joe-search-highlight-excerpt">
                            <?php echo joe_search_excerpt($this, $keyword, 150); ?>
                        </p>
                        <div class="joe-postlist__meta">
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                                <?php echo joe_format_date($this->created); ?>
                            </span>
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 9h16M9 4v16" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                <?php $this->category(',', false); ?>
                            </span>
                            <span class="joe-meta__item">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                <?php $this->commentsNum('%d'); ?>
                            </span>
                        </div>
                    </div>
                </article>
                <?php endwhile; ?>

                <?php $this->pageNav('«', '»', 1, '...', ['wrapTag' => 'nav', 'wrapClass' => 'joe-pagination']); ?>
            </section>
            <?php else: ?>
            <!-- 无结果 -->
            <section class="joe-search-empty">
                <div class="joe-search-empty__icon">
                    <svg viewBox="0 0 24 24" width="64" height="64"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
                </div>
                <h3>没有匹配的文章</h3>
                <p>换个关键词试试，或者浏览以下内容</p>
                <div class="joe-search-empty__links">
                    <a href="<?php $this->options->siteUrl(); ?>">回到首页</a>
                    <a href="<?php $this->options->siteUrl(); ?>archive.html">文章归档</a>
                    <?php
                    $cats = Typecho_Widget::widget('Widget_Metas_Category_List');
                    $i = 0;
                    while ($cats->next() && $i < 4):
                        $i++;
                    ?>
                    <a href="<?php $cats->permalink(); ?>"><?php $cats->name(); ?></a>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php if ($keyword): ?>
<script>
// 搜索结果关键词高亮
(function() {
    var keyword = <?php echo json_encode($rawKeyword); ?>;
    if (!keyword) return;
    var words = keyword.split(/\s+/).filter(Boolean);
    if (!words.length) return;

    function highlight(el) {
        if (!el) return;
        var html = el.innerHTML;
        words.forEach(function(w) {
            var re = new RegExp('(' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            html = html.replace(re, '<mark class="joe-search-hl">$1</mark>');
        });
        el.innerHTML = html;
    }

    document.querySelectorAll('.joe-search-highlight-title a').forEach(highlight);
    document.querySelectorAll('.joe-search-highlight-excerpt').forEach(highlight);
})();
</script>

<!-- 搜索结果筛选排序 -->
<script>
(function () {
    var catSelect = document.getElementById('joe-filter-category');
    var sortSelect = document.getElementById('joe-filter-sort');
    if (!catSelect && !sortSelect) return;

    var container = document.querySelector('.joe-search-results');
    if (!container) return;

    var items = container.querySelectorAll('.joe-postlist__item');

    function filterAndSort() {
        var selectedCat = catSelect ? catSelect.value : '';
        var sortBy = sortSelect ? sortSelect.value : 'relevance';

        var visible = [];
        items.forEach(function (item) {
            var cat = item.getAttribute('data-category') || '';
            if (selectedCat && cat.indexOf(selectedCat) === -1) {
                item.style.display = 'none';
            } else {
                item.style.display = '';
                visible.push(item);
            }
        });

        // 排序
        if (sortBy !== 'relevance') {
            visible.sort(function (a, b) {
                var da = a.getAttribute('data-date') || '';
                var db = b.getAttribute('data-date') || '';
                if (sortBy === 'date-desc') return db.localeCompare(da);
                return da.localeCompare(db);
            });
            visible.forEach(function (item) {
                container.appendChild(item);
            });
        }
    }

    if (catSelect) catSelect.addEventListener('change', filterAndSort);
    if (sortSelect) sortSelect.addEventListener('change', filterAndSort);
})();
</script>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
