<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Login
 * @description 登录页面
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>

$options = Helper::options();
$siteName = $options->title;
$logoText = $options->logoText ?? $siteName;
$loginBg = $options->loginBg ?? '';
$loginColor = $options->primaryColor ?? '#5b6cff';

// 获取登录错误信息
$notice = Typecho_Widget::widget('Widget_Notice');
$errors = $notice->getErrors();
$messages = $notice->getMessages();

// CSRF token
$security = Typecho_Widget::widget('Widget_Security');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="<?php $options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>登录 - <?php echo htmlspecialchars($siteName); ?></title>
    <style>
        :root {
            --primary: <?php echo $loginColor; ?>;
            --primary-hover: <?php echo $loginColor; ?>dd;
            --bg: #f5f6fa;
            --bg-card: #ffffff;
            --text: #1a1a2e;
            --text-soft: #6b7280;
            --text-mute: #9ca3af;
            --border: #e5e7eb;
            --danger: #ef4444;
            --success: #10b981;
            --radius: 10px;
            --radius-sm: 6px;
        }

        <?php if ($loginBg): ?>
        body::before {
            content: '';
            position: fixed; inset: 0;
            background: url('<?php echo htmlspecialchars($loginBg); ?>') center/cover no-repeat;
            opacity: 0.08;
            z-index: -1;
        }
        <?php endif; ?>

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea22, #764ba222, var(--bg));
            color: var(--text);
            line-height: 1.6;
        }

        /* 容器 */
        .joe-login {
            width: 100%; max-width: 420px;
            padding: 0 20px;
            animation: joe-login-in .5s ease;
        }
        @keyframes joe-login-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 卡片 */
        .joe-login__card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 40px 32px 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06), 0 1px 4px rgba(0,0,0,.04);
        }

        /* Logo */
        .joe-login__logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .joe-login__logo-text {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -.5px;
        }
        .joe-login__logo-dot {
            color: var(--primary);
        }
        .joe-login__desc {
            font-size: 13px;
            color: var(--text-mute);
            margin-top: 6px;
        }

        /* 表单 */
        .joe-login__form {}
        .joe-login__group {
            margin-bottom: 18px;
            position: relative;
        }
        .joe-login__label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-soft);
            margin-bottom: 6px;
        }
        .joe-login__input {
            width: 100%;
            height: 46px;
            padding: 0 16px;
            font-size: 15px;
            color: var(--text);
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .joe-login__input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(91,108,255,.12);
        }
        .joe-login__input::placeholder {
            color: var(--text-mute);
        }

        /* 复选框 */
        .joe-login__check-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .joe-login__check {
            display: flex; align-items: center; gap: 6px;
            color: var(--text-soft);
            cursor: pointer;
        }
        .joe-login__check input {
            width: 16px; height: 16px;
            accent-color: var(--primary);
        }
        .joe-login__forgot {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .joe-login__forgot:hover { text-decoration: underline; }

        /* 按钮 */
        .joe-login__btn {
            width: 100%; height: 46px;
            background: var(--primary);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            letter-spacing: 1px;
        }
        .joe-login__btn:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 14px rgba(91,108,255,.3);
        }
        .joe-login__btn:active { transform: scale(.98); }

        /* 返回链接 */
        .joe-login__back {
            text-align: center;
            margin-top: 20px;
        }
        .joe-login__back a {
            color: var(--text-mute);
            text-decoration: none;
            font-size: 13px;
            transition: color .2s;
        }
        .joe-login__back a:hover { color: var(--primary); }

        /* 错误/成功提示 */
        .joe-login__notice {
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .joe-login__notice--error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .joe-login__notice--success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        /* 隐藏字段 */
        .joe-hidden { display: none; }

        /* 页脚 */
        .joe-login__footer {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: var(--text-mute);
        }

        /* 响应式 */
        @media (max-width: 480px) {
            .joe-login__card {
                padding: 28px 20px 24px;
                border-radius: 12px;
            }
            .joe-login__logo-text { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="joe-login">
    <div class="joe-login__card">
        <!-- Logo -->
        <div class="joe-login__logo">
            <div class="joe-login__logo-text">
                <?php echo htmlspecialchars($logoText); ?><span class="joe-login__logo-dot">.</span>
            </div>
            <p class="joe-login__desc">欢迎回来，请登录您的账号</p>
        </div>

        <!-- 错误/成功提示 -->
        <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
        <div class="joe-login__notice joe-login__notice--error">
            <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8v4M12 16h0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($messages): ?>
        <?php foreach ($messages as $message): ?>
        <div class="joe-login__notice joe-login__notice--success">
            <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- 登录表单 -->
        <form class="joe-login__form" action="<?php $options->loginAction(); ?>" method="post">
            <div class="joe-login__group">
                <label class="joe-login__label" for="name">用户名</label>
                <input type="text" id="name" name="name" class="joe-login__input" placeholder="请输入用户名" autofocus required autocomplete="username">
            </div>
            <div class="joe-login__group">
                <label class="joe-login__label" for="password">密码</label>
                <input type="password" id="password" name="password" class="joe-login__input" placeholder="请输入密码" required autocomplete="current-password">
            </div>
            <div class="joe-login__check-row">
                <label class="joe-login__check">
                    <input type="checkbox" name="remember" value="1" checked>
                    记住我
                </label>
            </div>
            <button type="submit" class="joe-login__btn">登 录</button>
            <input type="hidden" name="referer" value="<?php $options->adminUrl(); ?>">
        </form>

        <!-- 返回 -->
        <div class="joe-login__back">
            <a href="<?php $options->siteUrl(); ?>">← 返回首页</a>
        </div>
    </div>

    <div class="joe-login__footer">
        Powered by Typecho · Theme KingJoe
    </div>
</div>

</body>
</html>
