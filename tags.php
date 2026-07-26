<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Tags
 * @description 标签聚合页，按标签浏览文章
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
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    <?php $this->title(); ?>
                </h1>
                <?php if ($this->fields->desc): ?>
                <p class="joe-page-header__desc"><?php $this->fields->desc(); ?></p>
                <?php endif; ?>
                <?php
                // 统计总标签数和文章数
                $tagsWidget = Typecho_Widget::widget('Widget_Metas_Tag_Cloud');
                $tagCount = 0;
                $totalPosts = 0;
                $allTags = [];
                while ($tagsWidget->next()) {
                    $tagCount++;
                    $totalPosts += $tagsWidget->count;
                    $allTags[] = ['name' => $tagsWidget->name, 'count' => $tagsWidget->count, 'permalink' => $tagsWidget->permalink, 'slug' => $tagsWidget->slug];
                }
                ?>
                <div class="joe-page-header__stats">
                    <span class="joe-page-header__stat"><?php echo $tagCount; ?> 个标签</span>
                    <span class="joe-page-header__sep">·</span>
                    <span class="joe-page-header__stat">覆盖 <?php echo $totalPosts; ?> 篇文章</span>
                </div>
            </section>

            <!-- 标签云 -->
            <section class="joe-tagcloud">
                <?php if ($tagCount > 0): ?>
                <div class="joe-tagcloud__list">
                    <?php
                    // 重新获取标签
                    $tagsWidget = Typecho_Widget::widget('Widget_Metas_Tag_Cloud');
                    // 按数量排序
                    $sortedTags = $allTags;
                    usort($sortedTags, function($a, $b) { return $b['count'] - $a['count']; });

                    // 计算字体大小级别
                    $maxCount = $sortedTags ? max(array_column($sortedTags, 'count')) : 1;
                    $minCount = $sortedTags ? min(array_column($sortedTags, 'count')) : 1;
                    $range = max(1, $maxCount - $minCount);

                    // 色彩层级
                    $colorLevels = ['#6b7280', '#8b5cf6', '#6366f1', '#5b6cff', '#4f46e5'];

                    foreach ($sortedTags as $tag):
                        $ratio = $range > 0 ? ($tag['count'] - $minCount) / $range : 0.5;
                        $fontSize = 13 + round($ratio * 16); // 13px ~ 29px
                        $colorIdx = min(count($colorLevels) - 1, (int)round($ratio * (count($colorLevels) - 1)));
                        $color = $colorLevels[$colorIdx];
                        // 根据文章数量添加权重 class
                        $weightClass = '';
                        if ($tag['count'] >= 10) $weightClass = 'is-xl';
                        elseif ($tag['count'] >= 5) $weightClass = 'is-lg';
                        elseif ($tag['count'] >= 3) $weightClass = 'is-md';
                    ?>
                    <a href="<?php echo $tag['permalink']; ?>"
                       class="joe-tagcloud__item <?php echo $weightClass; ?>"
                       style="font-size:<?php echo $fontSize; ?>px; color:<?php echo $color; ?>"
                       title="<?php echo $tag['count']; ?> 篇文章">
                        <?php echo htmlspecialchars($tag['name']); ?>
                        <sup class="joe-tagcloud__count"><?php echo $tag['count']; ?></sup>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="joe-empty">
                    <div class="joe-empty__icon">
                        <svg viewBox="0 0 24 24" width="56" height="56"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
                    </div>
                    <h3>暂无标签</h3>
                    <p>还没有创建任何标签，去发表文章时添加吧</p>
                </div>
                <?php endif; ?>
            </section>

            <!-- 按首字母分组浏览（可选） -->
            <?php if ($tagCount > 15): ?>
            <section class="joe-tagcloud-group">
                <h3 class="joe-section__title-sm">按字母浏览</h3>
                <div class="joe-tagcloud-group__nav" id="joe-tag-nav">
                    <?php
                    // 收集首字母
                    $alpha = [];
                    foreach ($sortedTags as $tag) {
                        $first = mb_strtoupper(mb_substr($tag['name'], 0, 1));
                        // 中文判断
                        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $first)) {
                            $first = '中';
                        } elseif (!preg_match('/[A-Z]/', $first)) {
                            $first = '#';
                        }
                        if (!isset($alpha[$first])) $alpha[$first] = [];
                        $alpha[$first][] = $tag;
                    }
                    ksort($alpha);
                    foreach ($alpha as $key => $tags):
                    ?>
                    <a href="#joe-tag-<?php echo $key; ?>" class="joe-tagcloud-group__link"><?php echo $key; ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="joe-tagcloud-group__content">
                    <?php foreach ($alpha as $key => $tags): ?>
                    <div class="joe-tagcloud-group__section" id="joe-tag-<?php echo $key; ?>">
                        <h4 class="joe-tagcloud-group__head"><?php echo $key; ?><span class="joe-tagcloud-group__head-count"><?php echo count($tags); ?></span></h4>
                        <div class="joe-tagcloud-group__tags">
                            <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo $tag['permalink']; ?>" class="joe-tagcloud-group__tag">
                                <?php echo htmlspecialchars($tag['name']); ?>
                                <span class="joe-tagcloud-group__count"><?php echo $tag['count']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<style>
/* 页面头部 */
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

/* 标签云主体 */
.joe-tagcloud {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg, 12px);
    padding: 28px 24px;
    margin-bottom: 24px;
}
.joe-tagcloud__list {
    display: flex; flex-wrap: wrap; gap: 10px;
    justify-content: center;
    line-height: 1.4;
}
.joe-tagcloud__item {
    display: inline-flex; align-items: center;
    padding: 6px 14px;
    border-radius: 20px;
    background: var(--bg-hover);
    text-decoration: none;
    font-weight: 500;
    transition: all .2s ease;
    border: 1px solid transparent;
}
.joe-tagcloud__item:hover {
    background: var(--primary);
    color: #fff !important;
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(91,108,255,.25);
}
.joe-tagcloud__item:hover .joe-tagcloud__count { color: rgba(255,255,255,.75); }
.joe-tagcloud__count {
    font-size: 11px;
    margin-left: 2px;
    color: inherit;
    opacity: .6;
}
.joe-tagcloud__item.is-xl { padding: 10px 20px; }
.joe-tagcloud__item.is-lg { padding: 8px 16px; }

/* 字母分组 */
.joe-tagcloud-group {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg, 12px);
    padding: 24px;
    margin-bottom: 24px;
}
.joe-section__title-sm {
    font-size: 15px; font-weight: 600; color: var(--text); margin: 0 0 16px;
}
.joe-tagcloud-group__nav {
    display: flex; flex-wrap: wrap; gap: 4px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}
.joe-tagcloud-group__link {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px;
    border-radius: 6px;
    text-decoration: none;
    color: var(--text-soft);
    font-size: 13px;
    font-weight: 600;
    background: var(--bg-hover);
    transition: all .15s;
}
.joe-tagcloud-group__link:hover {
    background: var(--primary);
    color: #fff;
}
.joe-tagcloud-group__section {
    margin-bottom: 20px;
}
.joe-tagcloud-group__section:last-child { margin-bottom: 0; }
.joe-tagcloud-group__head {
    display: flex; align-items: center; gap: 8px;
    font-size: 16px; font-weight: 700; color: var(--text);
    margin: 0 0 10px;
}
.joe-tagcloud-group__head-count {
    font-size: 12px; color: var(--text-mute); font-weight: 400;
}
.joe-tagcloud-group__tags {
    display: flex; flex-wrap: wrap; gap: 8px;
}
.joe-tagcloud-group__tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px;
    background: var(--bg-hover);
    border-radius: 16px;
    text-decoration: none;
    color: var(--text);
    font-size: 13px;
    transition: all .2s;
}
.joe-tagcloud-group__tag:hover {
    background: var(--primary);
    color: #fff;
}
.joe-tagcloud-group__count {
    font-size: 11px;
    opacity: .5;
}

/* 空状态 */
.joe-empty {
    text-align: center; padding: 48px 20px 60px;
}
.joe-empty__icon { color: var(--text-mute); opacity: .35; margin-bottom: 16px; }
.joe-empty h3 { font-size: 18px; font-weight: 600; color: var(--text); margin: 0 0 8px; }
.joe-empty p { font-size: 14px; color: var(--text-mute); margin: 0; }
</style>

<?php $this->need('footer.php'); ?>
