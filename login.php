<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main" style="width:100%;">
            <form action="<?php $this->options->loginAction(); ?>" method="post" class="joe-card joe-login-form">
                <h2>登录</h2>
                <p>
                    <label for="name">用户名</label>
                    <input type="text" name="name" id="name" class="joe-input" placeholder="请输入用户名" required>
                </p>
                <p>
                    <label for="password">密码</label>
                    <input type="password" name="password" id="password" class="joe-input" placeholder="请输入密码" required>
                </p>
                <p>
                    <button type="submit" class="joe-btn joe-btn--primary" style="width:100%;">登录</button>
                </p>
                <input type="hidden" name="referer" value="<?php echo joe_esc($this->request->get('referer', $this->options->siteUrl)); ?>">
            </form>
        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>