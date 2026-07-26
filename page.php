<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article">
                <header class="joe-article__head">
                    <h1 class="joe-article__title"><?php $this->title(); ?></h1>
                </header>
                <div class="joe-article__content joe-content">
                    <?php echo joe_add_heading_ids($this->content); ?>
                </div>
                <?php if ($this->allow('comment')): ?>
                    <?php $this->need('comments.php'); ?>
                <?php endif; ?>
            </article>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
