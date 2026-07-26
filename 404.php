<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    404
 * @description 404 错误页面
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <section class="joe-404">
                <div class="joe-404__code">404</div>
                <h1 class="joe-404__title">哎呀，页面走丢了 ~</h1>
                <p class="joe-404__desc">你访问的页面可能已被删除、移动，或者链接输入有误。不如试试搜索一下？</p>

                <div class="joe-404__actions">
                    <a href="<?php $this->options->siteUrl(); ?>" class="joe-404__btn joe-404__btn--primary">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/></svg>
                        返回首页
                    </a>
                    <button onclick="history.back()" class="joe-404__btn joe-404__btn--ghost">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                        返回上页
                    </button>
                </div>

                <form class="joe-404__search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
                    <input type="text" name="s" placeholder="搜索点什么看看..." required>
                </form>
            </section>
        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>
