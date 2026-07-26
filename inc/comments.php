<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Owo 表情解析：把 [xxx] 标记转换为对应 emoji / 颜文字
 * 数据来源：assets/js/owo.js 中定义的 OWO_DATA，这里同步一份精简映射
 */
function joe_owo_parse($text)
{
    if (!$text) return '';
    // 表情标记 [xxx] -> unicode emoji
    static $map = null;
    if ($map === null) {
        $map = [
            '[微笑]' => '🙂', '[撇嘴]' => '😦', '[色]' => '😍', '[发呆]' => '😶',
            '[得意]' => '😎', '[流泪]' => '😢', '[害羞]' => '😳', '[闭嘴]' => '😶',
            '[睡]' => '😴', '[大哭]' => '😭', '[尴尬]' => '😅', '[发怒]' => '😠',
            '[调皮]' => '😜', '[呲牙]' => '😁', '[惊讶]' => '😮', '[难过]' => '😞',
            '[酷]' => '😎', '[冷汗]' => '😰', '[抓狂]' => '😫', '[吐]' => '🤮',
            '[偷笑]' => '🤭', '[可爱]' => '🥰', '[白眼]' => '🙄', '[傲慢]' => '😏',
            '[饥饿]' => '😋', '[困]' => '😪', '[惊恐]' => '😱', '[流汗]' => '😓',
            '[憨笑]' => '😄', '[大兵]' => '🤠', '[奋斗]' => '💪', '[咒骂]' => '🤬',
            '[疑问]' => '❓', '[嘘]' => '🤫', '[晕]' => '😵', '[折磨]' => '😩',
            '[衰]' => '😖', '[骷髅]' => '💀', '[敲打]' => '🤯', '[再见]' => '👋',
            '[擦汗]' => '😅', '[抠鼻]' => '🤏', '[鼓掌]' => '👏', '[糗大了]' => '🤦',
            '[坏笑]' => '😈', '[左哼哼]' => '😤', '[右哼哼]' => '😤', '[哈欠]' => '🥱',
            '[鄙视]' => '😒', '[委屈]' => '🥺', '[快哭了]' => '🥹', '[阴险]' => '😸',
            '[亲亲]' => '😘', '[吓]' => '😱', '[可怜]' => '🥺', '[菜刀]' => '🔪',
            '[西瓜]' => '🍉', '[啤酒]' => '🍺', '[篮球]' => '🏀', '[乒乓]' => '🏓',
            '[咖啡]' => '☕', '[饭]' => '🍚', '[猪头]' => '🐷', '[玫瑰]' => '🌹',
            '[凋谢]' => '🥀', '[示爱]' => '❤️', '[爱心]' => '❤️', '[心碎]' => '💔',
            '[蛋糕]' => '🎂', '[闪电]' => '⚡', '[炸弹]' => '💣', '[刀]' => '🔪',
            '[足球]' => '⚽', '[瓢虫]' => '🐞', '[棒棒糖]' => '🍭', '[药]' => '💊',
            '[手枪]' => '🔫', '[茶]' => '🍵', '[握手]' => '🤝', '[胜利]' => '✌️',
            '[抱拳]' => '🙏', '[勾引]' => '🤙', '[拳头]' => '👊', '[差劲]' => '👎',
            '[爱你]' => '🫶', '[NO]' => '🙅', '[OK]' => '🙆', '[爱情]' => '💑',
            '[飞吻]' => '😘', '[跳跳]' => '🐰', '[发抖]' => '😨', '[怄火]' => '😒',
            '[转圈]' => '眩晕', '[磕头]' => '🙇', '[回头]' => '🔙', '[跳绳]' => '🤸',
            '[激动]' => '🤩', '[乱舞]' => '🤪', '[献吻]' => '💋', '[左太极]' => '☯️',
            '[右太极]' => '☯️', '[阳光]' => '☀️', '[月亮]' => '🌙', '[赞]' => '👍',
            '[强]' => '👍', '[弱]' => '👎', '[火]' => '🔥', '[闪]' => '✨',
        ];
    }
    return strtr($text, $map);
}

/**
 * 判断当前评论者是否在当前文章评论过（用于回复可见）
 */
function joe_has_commented($archive)
{
    if (!$archive->is('single')) return false;
    $user = Typecho_Widget::widget('Widget_User');
    if ($user->hasLogin()) return true;

    $cid = $archive->cid;
    $db = Typecho_Db::get();

    // 从 cookie 中查找已评论的标记
    $cookie = Typecho_Cookie::get('joe_commented');
    if ($cookie) {
        $ids = array_filter(array_map('intval', explode(',', $cookie)));
        if (in_array($cid, $ids)) return true;
    }

    // 通过邮箱/IP 判断（更严格）
    $mail = Typecho_Cookie::get('__typecho_remember_mail', '');
    $ip = Typecho_Request::getInstance()->getIp();
    if ($mail || $ip) {
        $select = $db->select(array('COUNT(*)' => 'num'))
            ->from('table.comments')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'approved');
        if ($mail) {
            $select->where('mail = ?', $mail);
        }
        if ($ip) {
            $select->where('ip = ?', $ip);
        }
        $row = $db->fetchObject($select);
        if ($row && $row->num > 0) return true;
    }

    return false;
}

/**
 * 评论回复邮件通知
 */
function joe_comment_notify($comment, $post)
{
    // 后台关闭了通知功能
    if (joe_get('commentNotify') === '0') return;
    // 未评论成功的跳过
    if (!$comment) return;

    // 兼容对象和数组
    $coid  = is_object($comment) ? ($comment->coid ?? 0) : ($comment['coid'] ?? 0);
    $cid   = is_object($comment) ? ($comment->cid ?? 0) : ($comment['cid'] ?? 0);
    $parent = is_object($comment) ? ($comment->parent ?? 0) : ($comment['parent'] ?? 0);

    if (!$coid || !$cid) return;
    // 如果没有父评论（不是回复），跳过
    if (!$parent) return;

    $parentId = (int)$parent;
    $db = Typecho_Db::get();
    try {
        $parentRow = $db->fetchRow($db->select()->from('table.comments')
            ->where('coid = ?', $parentId)->limit(1));
        if (!$parentRow || empty($parentRow['mail'])) return;
        $commentMail = is_object($comment) ? ($comment->mail ?? '') : ($comment['mail'] ?? '');
        // 不给自己发通知
        if ($commentMail && $commentMail === $parentRow['mail']) return;
    } catch (Exception $e) {
        return;
    }

    // 邮件标题和内容
    $options = Helper::options();
    $siteTitle = $options->title;
    $postTitle = isset($post['title']) ? $post['title'] : '';
    $postUrl = isset($post['permalink']) ? $post['permalink'] : '';
    $commentUrl = $postUrl . '#comment-' . $coid;

    $author = is_object($comment) ? ($comment->author ?? '') : ($comment['author'] ?? '');
    $text   = is_object($comment) ? ($comment->text ?? '') : ($comment['text'] ?? '');

    $subject = '您在 [' . $siteTitle . '] 的评论有了新回复';
    $body = '<p style="color:#333;font-size:15px">Hi <strong>' . htmlspecialchars($parentRow['author']) . '</strong>：</p>';
    $body .= '<p style="color:#666">您在文章 <strong><a href="' . htmlspecialchars($postUrl) . '" style="color:#5b6cff">' . htmlspecialchars($postTitle) . '</a></strong> 的评论有了新回复：</p>';
    $body .= '<div style="background:#f5f5f5;padding:14px 18px;border-radius:8px;margin:12px 0;color:#444">';
    $body .= '<p style="margin:0 0 8px"><strong>' . htmlspecialchars($author) . '</strong> 回复说：</p>';
    $body .= '<p style="margin:0">' . nl2br(htmlspecialchars(mb_substr(strip_tags((string) $text), 0, 300))) . '</p>';
    $body .= '</div>';
    $body .= '<p><a href="' . htmlspecialchars($commentUrl) . '" style="display:inline-block;background:#5b6cff;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px">查看详情</a></p>';
    $body .= '<p style="color:#999;font-size:12px;margin-top:18px">此邮件由系统自动发送，请勿回复。如不想再收到通知，请在博客设置中调整。</p>';

    // 发送邮件
    $senderMail = $options->feedEmail ?: ('no-reply@' . parse_url($options->siteUrl, PHP_URL_HOST));
    @mail($parentRow['mail'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", [
        'From: =?UTF-8?B?' . base64_encode($siteTitle) . '?= <' . $senderMail . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]));
}

/**
 * 评论点赞数获取
 */
function joe_comment_likes($coid)
{
    if (joe_get('commentLike') === '0') return 0;
    try {
        $db = Typecho_Db::get();
        $db->query('CREATE TABLE IF NOT EXISTS ' . $db->getPrefix() . 'joe_comment_likes (
            coid INT UNSIGNED NOT NULL,
            ip VARCHAR(45) NOT NULL,
            created INT UNSIGNED NOT NULL DEFAULT 0,
            KEY (coid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $row = $db->fetchRow($db->select()->expression('COUNT(*)', 'cnt')
            ->from('table.joe_comment_likes')
            ->where('coid = ?', $coid));
        return $row ? (int)$row['cnt'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * 解析用户 UA 返回浏览器/系统图标标签
 */
function joe_user_agent_badge($ua)
{
    if (!$ua) return '';
    $os = '';
    $osIcon = '';
    $browser = '';
    $browserIcon = '';

    // 操作系统检测
    if (preg_match('/Windows NT/i', $ua)) {
        $os = 'Windows';
        $osIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><rect x="1" y="1" width="10" height="10" rx="1" fill="currentColor" opacity=".6"/><rect x="13" y="1" width="10" height="10" rx="1" fill="currentColor" opacity=".6"/><rect x="1" y="13" width="10" height="10" rx="1" fill="currentColor" opacity=".6"/><rect x="13" y="13" width="10" height="10" rx="1" fill="currentColor" opacity=".6"/></svg>';
    } elseif (preg_match('/Mac OS X/i', $ua)) {
        $os = 'macOS';
        $osIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><path d="M16 2c.6 1.5 0 3-1.5 4C13 7 11 7 10 5.5S9.5 2 11 2c0 0 1 .5 2.5-.5S16 2 16 2zM20 16c0 3-2 5-4 5s-3-2-5-2-3 2-5 2-4-2-4-5c0-4 3-8 7-8s4 4 6 4 3-2 5 0 0 4 0 4z" fill="currentColor"/></svg>';
    } elseif (preg_match('/Linux/i', $ua) && !preg_match('/Android/i', $ua)) {
        $os = 'Linux';
        $osIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zM8.5 9c.8 0 1.5.7 1.5 1.5S9.3 12 8.5 12 7 11.3 7 10.5 7.7 9 8.5 9zm7 0c.8 0 1.5.7 1.5 1.5S16.3 12 15.5 12 14 11.3 14 10.5s.7-1.5 1.5-1.5zM12 17c-2 0-3.5-1-4.5-2h9c-1 1-2.5 2-4.5 2z" fill="currentColor"/></svg>';
    } elseif (preg_match('/Android/i', $ua)) {
        $os = 'Android';
        $osIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><path d="M6 8.5c-.8 0-1.5.7-1.5 1.5v4c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5v-4c0-.8-.7-1.5-1.5-1.5zm12 0c-.8 0-1.5.7-1.5 1.5v4c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5v-4c0-.8-.7-1.5-1.5-1.5zM6.5 3h11l1-1.5h-13l1 1.5zm12.5 6.5V13c0 3-2 5.5-5 6.5v2h-4v-2c-3-1-5-3.5-5-6.5V9.5h14z" fill="currentColor"/></svg>';
    } elseif (preg_match('/iPhone|iPad/i', $ua)) {
        $os = 'iOS';
        $osIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><rect x="5" y="1" width="14" height="22" rx="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 18h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>';
    }

    // 浏览器检测
    if (preg_match('/Edg\//i', $ua)) {
        $browser = 'Edge';
        $browserIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><path d="M5 12c3.9-3.9 7.5-7.5 10.8-10.8C16.5 1.7 13.5 2 12 2 7.5 2 3.8 4.8 2.2 8.8 2.1 9.2 2 9.6 2 10c0 1.5.4 3 1.2 4.2l2.2-2.2c.8.8 1.8 1.4 3 1.8l-.5 2.7c0 .1 0 .2.1.3l1.8 1.8c.1 0 .2.1.2.1l2.7-.5c.4 1.2 1 2.2 1.8 3l-2.2 2.2c1.2.8 2.7 1.2 4.2 1.2.4 0 .8-.1 1.2-.2 4-1.6 6.7-5.3 6.7-9.8 0-1.5-.3-3-1-4.3L18.7 12c.8-.8 1.3-1.7 1.3-2.8 0-.8-.3-1.5-.7-2.2C17.2 6.2 14.9 5 12 5c-2.5 0-4.7.9-6.3 2.3L5 12z" fill="currentColor"/></svg>';
    } elseif (preg_match('/Chrome/i', $ua) && !preg_match('/Edg/i', $ua)) {
        $browser = 'Chrome';
        $browserIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10" fill="currentColor" opacity=".3"/><circle cx="12" cy="12" r="5" fill="currentColor"/><path d="M12 2a10 10 0 00-10 10h5a5 5 0 015-5V2z" fill="currentColor" opacity=".5"/></svg>';
    } elseif (preg_match('/Firefox/i', $ua)) {
        $browser = 'Firefox';
        $browserIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10" fill="currentColor" opacity=".3"/><path d="M12 2C6.5 2 2 6.5 2 12c0 .5 0 1 .1 1.5 0-.1 0-.2.1-.3C2.5 9 5.5 7 9 7c2 0 3.5 1 5 2.5C15 8 16.5 7 18 7c1.5 0 3 .5 4 1.5 0-4-4.5-6.5-10-6.5z" fill="currentColor"/></svg>';
    } elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) {
        $browser = 'Safari';
        $browserIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 7v5l-4 4" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M8 7l10 5-6 3-4-8z" fill="currentColor" opacity=".5"/></svg>';
    } elseif (preg_match('/MSIE|Trident/i', $ua)) {
        $browser = 'IE';
        $browserIcon = '<svg viewBox="0 0 24 24" width="12" height="12"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 8h10l-1 4 1 4H7l1-4-1-4z" fill="currentColor" opacity=".4"/></svg>';
    }

    if (!$os && !$browser) return '';

    $html = '<span class="joe-comment__ua-badge">';
    if ($osIcon && $os) {
        $html .= '<span class="joe-comment__ua-os" title="' . htmlspecialchars($os) . '">' . $osIcon . '</span>';
    }
    if ($os) $html .= htmlspecialchars($os);
    if ($browser) {
        if ($os) $html .= ' ';
        if ($browserIcon) {
            $html .= '<span class="joe-comment__ua-browser" title="' . htmlspecialchars($browser) . '">' . $browserIcon . '</span> ';
        }
        $html .= htmlspecialchars($browser);
    }
    $html .= '</span>';
    return $html;
}

/**
 * 处理评论点赞 AJAX 请求
 */
function joe_comment_like_handle()
{
    if (!isset($_POST['action']) || $_POST['action'] !== 'joe_comment_like') return;
    if (!isset($_POST['coid']) || !is_numeric($_POST['coid'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '参数错误'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $coid = (int)$_POST['coid'];
    $ip = Typecho_Request::getInstance()->getIp();
    $db = Typecho_Db::get();

    // 确保表存在
    $db->query('CREATE TABLE IF NOT EXISTS ' . $db->getPrefix() . 'joe_comment_likes (
        coid INT UNSIGNED NOT NULL,
        ip VARCHAR(45) NOT NULL,
        created INT UNSIGNED NOT NULL DEFAULT 0,
        KEY (coid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // 24小时内同一 IP 只能点一次
    $key = 'joe_cl_' . $coid;
    $liked = Typecho_Cookie::get($key, '');
    if ($liked && time() - (int)$liked < 86400) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '你已经点过赞了'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->query($db->insert('table.joe_comment_likes')->rows([
        'coid' => $coid,
        'ip' => $ip,
        'created' => time()
    ]));
    Typecho_Cookie::set($key, (string)time(), time() + 86400);

    // 获取最新点赞数
    $row = $db->fetchRow($db->select()->expression('COUNT(*)', 'cnt')
        ->from('table.joe_comment_likes')
        ->where('coid = ?', $coid));

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => '点赞成功', 'count' => $row ? (int)$row['cnt'] : 0], JSON_UNESCAPED_UNICODE);
    exit;
}
