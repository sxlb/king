<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Categories
 * @description 分类目录页，按分类浏览文章
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main">

            <!-- 页面头部 -->
            <section class="joe-page-header">
                <h1 class="joe-page-header__title">
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                    <?php $this->title(); ?>
                </h1>
                <?php if ($this->fields->desc): ?>
                <p class="joe-page-header__desc"><?php $this->fields->desc(); ?></p>
                <?php endif; ?>
                <?php
                // 统计
                $catsWidget = Typecho_Widget::widget('Widget_Metas_Category_List');
                $catCount = 0;
                $totalPosts = 0;
                $allCats = [];
                while ($catsWidget->next()) {
                    $catCount++;
                    $totalPosts += $catsWidget->count;
                    $allCats[] = [
                        'mid' => $catsWidget->mid,
                        'name' => $catsWidget->name,
                        'slug' => $catsWidget->slug,
                        'count' => $catsWidget->count,
                        'permalink' => $catsWidget->permalink,
                        'description' => $catsWidget->description,
                    ];
                }
                ?>
                <div class="joe-page-header__stats">
                    <span class="joe-page-header__stat"><?php echo $catCount; ?> 个分类</span>
                    <span class="joe-page-header__sep">·</span>
                    <span class="joe-page-header__stat">共 <?php echo $totalPosts; ?> 篇文章</span>
                </div>
            </section>

            <!-- 分类列表 -->
            <section class="joe-catlist">
                <?php if ($catCount > 0): ?>
                <div class="joe-catlist__grid">
                    <?php foreach ($allCats as $cat): ?>
                    <div class="joe-catlist__item">
                        <div class="joe-catlist__info">
                            <h3 class="joe-catlist__name">
                                <a href="<?php echo $cat['permalink']; ?>">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </h3>
                            <?php if ($cat['description']): ?>
                            <p class="joe-catlist__desc"><?php echo htmlspecialchars($cat['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="joe-catlist__meta">
                            <span class="joe-catlist__count"><?php echo $cat['count']; ?> 篇</span>
                            <a href="<?php echo $cat['permalink']; ?>" class="joe-catlist__link">
                                浏览全部
                                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="joe-empty">
                    <div class="joe-empty__icon">
                        <svg viewBox="0 0 24 24" width="56" height="56"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/></svg>
                    </div>
                    <h3>暂无分类</h3>
                    <p>还没有创建分类目录</p>
                </div>
                <?php endif; ?>
            </section>

        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<style>
/* 页面头部 - 复用标签云页面的样式 */
.joe-page-header {
    background: linear-gradient(135deg, var(--primary-light, rgba(0,112,243,.06)), transparent);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg, 12px);
    padding: 28px 24px;
    margin-bottom: 24px;
    text-align: center;
}
.joe-page-header__title {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-size: 22px; font-weight: 700; color: var(--text); margin: 0 0 8px;
}
.joe-page-header__title svg { color: var(--primary); }
.joe-page-header__desc { color: var(--text-soft); font-size: 14px; margin: 0 0 12px; }
.joe-page-header__stats { font-size: 13px; color: var(--text-mute); }
.joe-page-header__sep { margin: 0 6px; }

/* 分类列表 */
.joe-catlist {
    margin-bottom: 24px;
}
.joe-catlist__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}
.joe-catlist__item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg, 12px);
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all .2s ease;
    min-height: 120px;
}
.joe-catlist__item:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 16px rgba(91,108,255,.1);
    transform: translateY(-2px);
}
.joe-catlist__info { flex: 1; }
.joe-catlist__name { margin: 0 0 8px; }
.joe-catlist__name a {
    display: flex; align-items: center; gap: 8px;
    font-size: 17px; font-weight: 600;
    color: var(--text);
    text-decoration: none;
    transition: color .2s;
}
.joe-catlist__name a:hover { color: var(--primary); }
.joe-catlist__name svg { color: var(--primary); flex-shrink: 0; }
.joe-catlist__desc {
    font-size: 13px; color: var(--text-mute);
    margin: 0 0 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.joe-catlist__meta {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}
.joe-catlist__count {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}
.joe-catlist__link {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px;
    color: var(--text-soft);
    text-decoration: none;
    transition: color .2s;
}
.joe-catlist__link:hover { color: var(--primary); }

/* 响应式 */
@media (max-width: 768px) {
    .joe-catlist__grid {
        grid-template-columns: 1fr;
    }
}

/* 空状态 - 复用标签云页面样式 */
.joe-empty {
    text-align: center; padding: 48px 20px 60px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg, 12px);
}
.joe-empty__icon { color: var(--text-mute); opacity: .35; margin-bottom: 16px; }
.joe-empty h3 { font-size: 18px; font-weight: 600; color: var(--text); margin: 0 0 8px; }
.joe-empty p { font-size: 14px; color: var(--text-mute); margin: 0; }
</style>

<?php $this->need('footer.php'); ?>
