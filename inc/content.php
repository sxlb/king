<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 回复可见短代码：[reply]隐藏内容[/reply]
 * 文章详情页解析，已评论/登录用户可见原文，否则显示提示
 */
function joe_reply_visible($content, $archive)
{
    if (joe_get('replyVisible') === '0') return $content;
    if (!$archive->is('single')) return $content;
    if (strpos((string) $content, '[reply]') === false) return $content;

    $hasCommented = joe_has_commented($archive);

    if ($hasCommented) {
        // 已评论：去除短代码，显示内容
        $content = preg_replace('/\[reply\](.*?)\[\/reply\]/is', '<div class="joe-reply-box is-unlock"><div class="joe-reply-box__tip"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg> 您已评论，隐藏内容已显示</div>$1</div>', $content);
    } else {
        // 未评论：显示提示框
        $permalink = $archive->permalink;
        $replace = '<div class="joe-reply-box is-lock"><div class="joe-reply-box__icon"><svg viewBox="0 0 24 24" width="28" height="28"><path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><rect x="4" y="11" width="16" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/></svg></div><div class="joe-reply-box__title">此处内容需要评论回复后可见</div><div class="joe-reply-box__desc">发表评论后刷新页面即可查看隐藏内容</div><a href="' . htmlspecialchars($permalink) . '#comments" class="joe-reply-box__btn">去评论</a></div>';
        $content = preg_replace('/\[reply\](.*?)\[\/reply\]/is', $replace, $content);
    }

    return $content;
}

/**
 * 评论成功后，在 cookie 中标记当前文章已评论
 */
function joe_mark_commented($comment)
{
    if (empty($comment->cid)) return;
    $cid = $comment->cid;
    $cookie = Typecho_Cookie::get('joe_commented', '');
    $ids = $cookie ? array_filter(array_map('intval', explode(',', $cookie))) : [];
    if (!in_array($cid, $ids)) {
        $ids[] = $cid;
        $expire = time() + 86400 * 30;
        Typecho_Cookie::set('joe_commented', implode(',', $ids), $expire);
    }
}

/**
 * 私密评论：评论时如果勾选了私密，标记该评论为仅博主可见
 */
function joe_set_private_comment($comment)
{
    if (joe_get('privateComment') === '0') return;
    // 检查是否勾选了私密
    $isPrivate = !empty($_POST['private_comment']) && $_POST['private_comment'] === '1';
    if (!$isPrivate) return;
    try {
        $db = Typecho_Db::get();
        // 用特殊标记 [private] 作为评论内容前缀，博主可见时会解析
        $coid = is_object($comment) ? $comment->coid : $comment;
        $db->query($db->update('table.comments')
            ->expression('text', 'CONCAT("' . $db->quote('[private]') . '", text)')
            ->where('coid = ?', $coid));
    } catch (Exception $e) {
        // silently fail
    }
}

/**
 * 判断评论是否为私密评论
 */
function joe_is_private_comment($content)
{
    if ($content === null || $content === '') return false;
    return strpos($content, '[private]') === 0;
}

/**
 * 获取私密评论的真实内容（去掉标记前缀）
 */
function joe_unwrap_private($content)
{
    if (joe_is_private_comment($content)) {
        return substr($content, 9); // strlen('[private]') = 9
    }
    return $content;
}

/**
 * 自动补全图片 alt 属性（SEO 优化）
 */
function joe_fix_img_alt($content)
{
    $content = (string) $content;
    return preg_replace_callback('/<img([^>]+)>/i', function ($m) {
        $attrs = $m[1];
        // 已经有 alt 就跳过
        if (preg_match('/alt\s*=/i', $attrs)) return $m[0];
        // 尝试从 title 取
        if (preg_match('/title=([\'"])([^\'"]+)\1/i', $attrs, $tm)) {
            return '<img' . $attrs . ' alt="' . htmlspecialchars($tm[2]) . '">';
        }
        // 默认 alt
        return '<img' . $attrs . ' alt="图片">';
    }, $content);
}

/**
 * RSS 全文输出
 */
function joe_rss_full_content($content, $archive)
{
    if ($archive->is('feed')) {
        // 短代码解析
        $content = joe_video_shortcode($content);
        $content = joe_reply_visible($content, $archive);
        $content = joe_fix_img_alt($content);
    }
    return $content;
}
