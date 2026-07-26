<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Douban
 * @description 豆瓣清单页，展示读书/观影/音乐记录
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

$doubanId = joe_get('doubanId');
$hasDouban = (bool)$doubanId;

// 获取当前激活的 tab
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['book', 'movie', 'music']) ? $_GET['tab'] : 'book';

$tabs = [
    'book'  => ['name' => '读书', 'icon' => '📚'],
    'movie' => ['name' => '观影', 'icon' => '🎬'],
    'music' => ['name' => '音乐', 'icon' => '🎵'],
];

$items = $hasDouban ? joe_douban_fetch($doubanId, $activeTab) : [];
?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--full">
        <div class="joe-main">
            <article class="joe-article">
                <!-- 头部 -->
                <header class="joe-douban__head">
                    <h1 class="joe-douban__title"><?php echo joe_esc(joe_get('doubanTitle') ?: '豆瓣清单'); ?></h1>
                    <?php if ($hasDouban): ?>
                    <p class="joe-douban__user">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        <a href="https://www.douban.com/people/<?php echo joe_esc($doubanId); ?>/" target="_blank" rel="noopener"><?php echo joe_esc($doubanId); ?> 的豆瓣</a>
                    </p>
                    <?php endif; ?>
                </header>

                <?php if ($hasDouban): ?>
                <!-- Tab 切换 -->
                <nav class="joe-douban__tabs">
                    <?php foreach ($tabs as $key => $tab): ?>
                    <a href="?tab=<?php echo $key; ?>" class="joe-douban__tab<?php if ($activeTab === $key) echo ' is-active'; ?>">
                        <?php echo $tab['name']; ?>
                    </a>
                    <?php endforeach; ?>
                </nav>

                <!-- 内容区 -->
                <div class="joe-douban__body">
                    <?php if (!empty($items)): ?>
                    <div class="joe-douban__grid">
                        <?php foreach ($items as $item): ?>
                        <a href="<?php echo joe_esc($item['url'] ?? '#'); ?>" class="joe-douban__item" target="_blank" rel="noopener">
                            <div class="joe-douban__cover">
                                <?php if (!empty($item['cover'])): ?>
                                <img src="<?php echo joe_esc($item['cover']); ?>" alt="<?php echo joe_esc($item['title'] ?? ''); ?>" loading="lazy">
                                <?php else: ?>
                                <span class="joe-douban__cover-placeholder"><?php echo mb_substr($item['title'] ?? '', 0, 2); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['rating'])): ?>
                                <span class="joe-douban__rating">
                                    <?php echo joe_douban_stars($item['rating']); ?>
                                    <span class="joe-douban__rating-num"><?php echo $item['rating']; ?></span>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="joe-douban__info">
                                <h3 class="joe-douban__item-title"><?php echo joe_esc($item['title'] ?? ''); ?></h3>
                                <?php if (!empty($item['info'])): ?>
                                <p class="joe-douban__item-meta"><?php echo joe_esc($item['info']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['comment'])): ?>
                                <p class="joe-douban__item-comment">「<?php echo joe_esc($item['comment']); ?>」</p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif (empty($items)): ?>
                    <div class="joe-douban__empty">
                        <svg viewBox="0 0 24 24" width="48" height="48"><rect x="2" y="3" width="20" height="18" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 8h20" stroke="currentColor" stroke-width="1.5"/></svg>
                        <p>暂无收藏数据</p>
                        <p class="joe-douban__empty-hint">请确认豆瓣 ID 是否正确，或稍后重试（数据每6小时缓存一次）</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <!-- 未配置 -->
                <div class="joe-douban__empty">
                    <svg viewBox="0 0 24 24" width="48" height="48"><rect x="2" y="3" width="20" height="18" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 8h20" stroke="currentColor" stroke-width="1.5"/></svg>
                    <p>未配置豆瓣清单</p>
                    <p class="joe-douban__empty-hint">请在「控制台 → 外观 → 设置外观」中填写你的豆瓣用户 ID</p>
                </div>
                <?php endif; ?>

                <?php $this->need('comments.php'); ?>
            </article>
        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>
