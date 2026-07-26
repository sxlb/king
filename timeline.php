<?php
/**
 * 时光机
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$slug = joe_get('timelineCat');
$pageSize = (int)(joe_get('timelinePageSize') ?: 10);
$data = $slug ? joe_timeline_posts($slug, 1, $pageSize) : ['posts' => [], 'total' => 0, 'hasMore' => false];
?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--full">
        <div class="joe-main">
            <article class="joe-article">
                <!-- 头部 -->
                <header class="joe-timeline__head">
                    <div class="joe-timeline__icon">
                        <svg viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    </div>
                    <h1 class="joe-timeline__title"><?php echo joe_esc(joe_get('timelineTitle') ?: '时光机'); ?></h1>
                    <?php if (joe_get('timelineDesc')): ?>
                    <p class="joe-timeline__desc"><?php joe_opt('timelineDesc'); ?></p>
                    <?php endif; ?>
                    <?php if ($data['total'] > 0): ?>
                    <p class="joe-timeline__stat">共 <b><?php echo $data['total']; ?></b> 条动态</p>
                    <?php endif; ?>
                </header>

                <div class="joe-timeline__body" id="joe-timeline-list">
                    <?php if (!empty($data['posts'])): ?>
                    <?php foreach ($data['posts'] as $index => $post): ?>
                    <div class="joe-timeline__item<?php if ($post['image']) echo ' has-image'; ?>">
                        <?php if ($post['image']): ?>
                        <div class="joe-timeline__media">
                            <a href="<?php echo joe_esc($post['permalink']); ?>">
                                <img src="<?php echo joe_esc($post['image']); ?>" alt="<?php echo joe_esc($post['title']); ?>" loading="lazy">
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="joe-timeline__content">
                            <?php if ($post['title']): ?>
                            <h3 class="joe-timeline__item-title">
                                <a href="<?php echo joe_esc($post['permalink']); ?>"><?php echo joe_esc($post['title']); ?></a>
                            </h3>
                            <?php endif; ?>
                            <div class="joe-timeline__text"><?php echo joe_esc($post['text']); ?></div>
                            <div class="joe-timeline__meta">
                                <time datetime="<?php echo date('c', $post['created']); ?>"><?php echo joe_relative_time($post['created']); ?></time>
                                <a href="<?php echo joe_esc($post['permalink']); ?>" class="joe-timeline__more">查看详情</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="joe-timeline__empty">
                        <svg viewBox="0 0 24 24" width="48" height="48"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
                        <p>还没有动态</p>
                        <?php if (!$slug): ?>
                        <p class="joe-timeline__empty-hint">请在「控制台 → 外观 → 设置外观」中设置时光机分类</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($data['hasMore']): ?>
                <div class="joe-timeline__more-wrap" id="joe-timeline-more-wrap">
                    <button class="joe-timeline__more-btn" id="joe-timeline-more" data-page="1" data-slug="<?php echo joe_esc($slug); ?>" data-pagesize="<?php echo $pageSize; ?>">
                        <span>加载更多</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php $this->need('comments.php'); ?>
            </article>
        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>
