<?php
/**
 * 留言板
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

// 统计总留言数
$db = Typecho_Db::get();
$totalComments = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))
    ->from('table.comments')
    ->where('status = ?', 'approved')
    ->where('cid = ?', $this->cid))->num;

// 获取活跃访客（按留言数排）
$activeGuests = $db->fetchAll($db->select('author', 'mail', array('COUNT(*)' => 'num'))
    ->from('table.comments')
    ->where('status = ?', 'approved')
    ->where('cid = ?', $this->cid)
    ->where('authorId = ?', 0)
    ->group('mail')
    ->order('num', Typecho_Db::SORT_DESC)
    ->limit(8));
?>
<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article">
                <!-- 留言板头部 -->
                <header class="joe-leaving__head">
                    <div class="joe-leaving__title"><?php $this->title(); ?></div>
                    <p class="joe-leaving__desc">👋 留下你的足迹，让我们互相认识吧~</p>
                    <div class="joe-leaving__stats">
                        <div class="joe-leaving__stat">
                            <span class="joe-leaving__stat-num"><?php echo $totalComments; ?></span>
                            <span class="joe-leaving__stat-label">条留言</span>
                        </div>
                        <div class="joe-leaving__stat">
                            <span class="joe-leaving__stat-num"><?php echo count($activeGuests); ?></span>
                            <span class="joe-leaving__stat-label">位伙伴</span>
                        </div>
                    </div>
                </header>

                <!-- 活跃访客 -->
                <?php if (!empty($activeGuests)): ?>
                <section class="joe-leaving__guests">
                    <h3 class="joe-leaving__guests-title">🌟 活跃小伙伴</h3>
                    <div class="joe-leaving__guests-list">
                        <?php foreach ($activeGuests as $g): ?>
                        <div class="joe-leaving__guest" title="<?php echo htmlspecialchars($g['author']); ?> · <?php echo $g['num']; ?> 条留言">
                            <img src="<?php echo joe_gravatar($g['mail'], 80, 'mm'); ?>" alt="<?php echo htmlspecialchars($g['author']); ?>" class="joe-leaving__guest-avatar" loading="lazy">
                            <span class="joe-leaving__guest-name"><?php echo htmlspecialchars($g['author']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 页面正文（可选） -->
                <?php if (trim($this->content)): ?>
                <div class="joe-article__content joe-content">
                    <?php echo joe_add_heading_ids($this->content); ?>
                </div>
                <?php endif; ?>

                <!-- 评论区 -->
                <?php if ($this->allow('comment')): ?>
                    <?php $this->need('comments.php'); ?>
                <?php endif; ?>
            </article>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
