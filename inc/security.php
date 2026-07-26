<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * XSS 过滤：清理访客提交的评论内容
 * 保留有限的安全标签与属性，剥离危险标签、事件、协议
 */
function joe_xss_filter($text)
{
    if (trim($text) === '') return $text;

    // 移除 script / style / iframe / object / embed / form 等危险标签
    $text = preg_replace('/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|link|meta|base|applet|svg|math|xml)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $text);
    $text = preg_replace('/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta|base|applet|svg|math)\b[^>]*\/?\s*>/i', '', $text);

    // 移除事件属性 onxxx=
    $text = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $text);

    // 移除 javascript: / data: / vbscript: 伪协议
    $text = preg_replace('/(href|src|action|background|formaction)\s*=\s*["\']\s*(javascript|vbscript|data)\s*:[^"\']*["\']/i', '$1="#"', $text);
    $text = preg_replace('/(href|src|action|background|formaction)\s*=\s*(javascript|vbscript|data)\s*:[^\s>]+/i', '$1="#"', $text);

    // 移除 expression() / url(javascript:...)
    $text = preg_replace('/expression\s*\(/i', 'expr(', $text);

    return $text;
}

/**
 * 挂载评论内容 XSS 过滤
 */
function joe_comment_filter($comment, $post)
{
    if (joe_get('xssFilter') === '0') return $comment;
    if (empty($comment['text'])) return $comment;

    // 仅对访客过滤，管理员/编辑不过滤
    $user = Typecho_Widget::widget('Widget_User');
    if ($user->hasLogin() && $user->group !== 'visitor') {
        return $comment;
    }

    $comment['text'] = joe_xss_filter($comment['text']);
    return $comment;
}

/**
 * 输出安全响应头（在 header 输出前调用）
 */
function joe_security_headers()
{
    if (headers_sent()) return;
    // XSS 保护
    @header('X-XSS-Protection: 1; mode=block');
    // 禁用 iframe 嵌套（可选同源）
    @header('X-Frame-Options: SAMEORIGIN');
    // MIME 类型嗅探
    @header('X-Content-Type-Options: nosniff');
    // Referrer 策略
    @header('Referrer-Policy: no-referrer-when-downgrade');
}

/**
 * 反图片防盗链：给文章图片添加 referrerpolicy="no-referrer"
 */
function joe_anti_hotlink($content)
{
    if (joe_get('antiHotlink') !== '1') return $content;
    return preg_replace('/<img\b/i', '<img referrerpolicy="no-referrer"', $content);
}
