<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main">
            <div class="joe-archive-header">
                <h1 class="joe-archive-header__title">留言板</h1>
                <?php $total = $this->comments->size(); ?>
                <p class="joe-archive-header__desc">当前共有 <strong><?php echo $total; ?></strong> 条留言</p>
            </div>
            <div class="joe-card">
                <?php $this->need('comments.php'); ?>
            </div>
        </div>
        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>