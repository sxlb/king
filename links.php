<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main">
            <div class="joe-archive-header">
                <h1 class="joe-archive-header__title">友链</h1>
                <p class="joe-archive-header__desc">欢迎交换友链，共同进步</p>
            </div>
            <div class="joe-links">
                <?php $links = joe_get_links(); ?>
                <?php if (!empty($links)): ?>
                    <?php foreach ($links as $link): ?>
                    <a href="<?php echo joe_esc($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="joe-card joe-link-item">
                        <img src="<?php echo joe_esc($link['avatar']); ?>" alt="<?php echo joe_esc($link['name']); ?>" class="joe-link-item__avatar" loading="lazy" onerror="this.src='<?php echo joe_esc(joe_get('defaultThumb')); ?>'">
                        <div class="joe-link-item__info">
                            <span class="joe-link-item__name"><?php echo joe_esc($link['name']); ?></span>
                            <span class="joe-link-item__desc"><?php echo joe_esc($link['description'] ?? ''); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="joe-card" style="text-align:center;padding:40px;color:var(--text-muted);">暂无友链</div>
                <?php endif; ?>
            </div>
        </div>
        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>