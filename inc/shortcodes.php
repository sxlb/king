<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 文章视频短代码解析
 * [video]MP4地址[/video]
 * [bilibili]BV号[/bilibili]
 * [youtube]视频ID[/youtube]
 */
function joe_video_shortcode($content)
{
    if (joe_get('videoPlayer') === '0') return $content;

    // MP4
    $content = preg_replace_callback('/\[video\](.+?)\[\/video\]/is', function ($m) {
        $url = trim($m[1]);
        if (!$url) return '';
        return '<div class="joe-video joe-video--mp4"><video src="' . htmlspecialchars($url) . '" controls preload="metadata" playsinline></video></div>';
    }, $content);

    // Bilibili
    $content = preg_replace_callback('/\[bilibili\]([a-zA-Z0-9]+)\[\/bilibili\]/is', function ($m) {
        $bv = trim($m[1]);
        return '<div class="joe-video joe-video--bilibili"><iframe src="https://player.bilibili.com/player.html?bvid=' . urlencode($bv) . '&page=1&high_quality=1&danmaku=0" scrolling="no" frameborder="0" allowfullscreen="true" sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts allow-popups"></iframe></div>';
    }, $content);

    // YouTube
    $content = preg_replace_callback('/\[youtube\]([a-zA-Z0-9_-]+)\[\/youtube\]/is', function ($m) {
        $id = trim($m[1]);
        return '<div class="joe-video joe-video--youtube"><iframe src="https://www.youtube.com/embed/' . urlencode($id) . '?rel=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }, $content);

    return $content;
}

/**
 * 蓝凑云下载短代码：[lanzou]分享链接[/lanzou]
 */
function joe_lanzou_shortcode($content)
{
    return preg_replace_callback('/\[lanzou\](.*?)\[\/lanzou\]/is', function ($m) {
        $url = trim($m[1]);
        $label = '下载文件';
        // 尝试从链接提取文件名
        $parts = explode('/', $url);
        $last = end($parts);
        if (!empty($last)) $label = rawurldecode($last) ?: $label;
        return '<div class="joe-lanzou"><a href="' . joe_esc($url) . '" target="_blank" rel="noopener noreferrer" class="joe-lanzou__btn"><svg viewBox="0 0 24 24" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>' . joe_esc($label) . '</a></div>';
    }, $content);
}

/**
 * 文章内广告短代码：[ad] [/ad] 及内容顶底部广告插入
 */
function joe_ad_shortcode($content)
{
    // [ad]ID[/ad] 或 [ad] 插入通用广告
    return preg_replace_callback('/\[ad(?:\s+(\d+))?\]/i', function ($m) {
        $id = $m[1] ?? 1;
        // 从配置读取广告代码
        $adKey = 'adSlot' . $id;
        $adCode = joe_get($adKey);
        if (!$adCode) $adCode = joe_get('adContentTop'); // 回退
        if (!$adCode) return '';
        return '<div class="joe-ad joe-ad--inline">' . $adCode . '</div>';
    }, $content);
}

function joe_content_ad_insert($content)
{
    $top = joe_get('adContentTop');
    $bottom = joe_get('adContentBottom');
    if ($top) $content = '<div class="joe-ad joe-ad--top">' . $top . '</div>' . $content;
    if ($bottom) $content = $content . '<div class="joe-ad joe-ad--bottom">' . $bottom . '</div>';
    return $content;
}

/**
 * [toc] 短代码 —— 在正文任意位置插入目录
 */
function joe_toc_shortcode($content)
{
    if (strpos((string) $content, '[toc]') === false) return $content;
    $toc = joe_toc(joe_add_heading_ids($content));
    if (!$toc) return str_replace('[toc]', '', $content);
    $toc = str_replace('class="joe-toc"', 'class="joe-inline-toc"', $toc);
    $toc = str_replace('class="joe-toc__head"', 'class="joe-inline-toc__head"', $toc);
    $toc = str_replace('class="joe-toc__list"', 'class="joe-inline-toc__list"', $toc);
    $toc = str_replace('class="joe-toc__item', 'class="joe-inline-toc__item', $toc);
    $toc = str_replace('<div class="joe-inline-toc__head">目录</div>',
        '<div class="joe-inline-toc__head"><svg viewBox="0 0 24 24" width="16" height="16"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>文章目录</div>', $toc);
    return str_replace('[toc]', $toc, $content);
}

/* ==================== Handsome 风短代码系统 ==================== */

/**
 * 提示框 [tips type="info"]文字[/tips]
 * type: info / warning / danger / success
 */
function joe_tips_shortcode($content)
{
    return preg_replace_callback('/\[tips(?:\s+type=["\']?(\w+)["\']?)?\](.*?)\[\/tips\]/is', function ($m) {
        $type = !empty($m[1]) ? $m[1] : 'info';
        return '<div class="joe-tips joe-tips--' . $type . '">' . $m[2] . '</div>';
    }, $content);
}

/**
 * 折叠面板 [collapse title="标题"]内容[/collapse]
 */
function joe_collapse_shortcode($content)
{
    $idx = 0;
    return preg_replace_callback('/\[collapse\s+title=["\']?([^"\'\]]+)["\']?\](.*?)\[\/collapse\]/is', function ($m) use (&$idx) {
        $idx++;
        $id = 'joe-collapse-' . $idx;
        return '<div class="joe-collapse"><input type="checkbox" id="' . $id . '" class="joe-collapse__toggle"><label for="' . $id . '" class="joe-collapse__head"><svg viewBox="0 0 24 24" width="14" height="14"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>' . joe_esc($m[1]) . '</label><div class="joe-collapse__body">' . $m[2] . '</div></div>';
    }, $content);
}

/**
 * 按钮 [btn url="" type="primary" size=""]文字[/btn]
 */
function joe_btn_shortcode($content)
{
    return preg_replace_callback('/\[btn(?:\s+url=["\']?([^"\'\s]+)["\']?)?(?:\s+type=["\']?(\w+)["\']?)?\](.*?)\[\/btn\]/is', function ($m) {
        $url = !empty($m[1]) ? $m[1] : '#';
        $type = !empty($m[2]) ? $m[2] : 'primary';
        return '<a href="' . joe_esc($url) . '" class="joe-btn-custom joe-btn-custom--' . $type . '" target="_blank" rel="noopener">' . joe_esc($m[3]) . '</a>';
    }, $content);
}

/**
 * 标签卡 [tabs][tab name="标签1"]内容1[/tab][tab name="标签2"]内容2[/tab][/tabs]
 */
function joe_tabs_shortcode($content)
{
    return preg_replace_callback('/\[tabs\](.*?)\[\/tabs\]/is', function ($m) {
        $inner = $m[1];
        preg_match_all('/\[tab\s+name=["\']?([^"\'\]]+)["\']?\](.*?)\[\/tab\]/is', $inner, $tabs, PREG_SET_ORDER);
        if (empty($tabs)) return $m[0];
        $gid = 'joe-tabs-' . mt_rand(1000, 9999);
        $html = '<div class="joe-tabs" id="' . $gid . '"><div class="joe-tabs__nav">';
        foreach ($tabs as $i => $t) {
            $html .= '<button class="joe-tabs__tab' . ($i === 0 ? ' is-active' : '') . '" data-tab="' . $i . '">' . joe_esc($t[1]) . '</button>';
        }
        $html .= '</div><div class="joe-tabs__panels">';
        foreach ($tabs as $i => $t) {
            $html .= '<div class="joe-tabs__panel' . ($i === 0 ? ' is-active' : '') . '" data-panel="' . $i . '">' . $t[2] . '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }, $content);
}

/**
 * 步骤条 [steps]
 * [step title="步骤1"]描述1[/step]
 * [step title="步骤2"]描述2[/step]
 * [/steps]
 */
function joe_steps_shortcode($content)
{
    return preg_replace_callback('/\[steps\](.*?)\[\/steps\]/is', function ($m) {
        preg_match_all('/\[step\s+title=["\']?([^"\'\]]+)["\']?\](.*?)\[\/step\]/is', $m[1], $steps, PREG_SET_ORDER);
        if (empty($steps)) return $m[0];
        $html = '<div class="joe-steps">';
        foreach ($steps as $i => $s) {
            $num = $i + 1;
            $html .= '<div class="joe-steps__item"><div class="joe-steps__num">' . $num . '</div><div class="joe-steps__content"><div class="joe-steps__title">' . joe_esc($s[1]) . '</div><div class="joe-steps__desc">' . $s[2] . '</div></div></div>';
        }
        $html .= '</div>';
        return $html;
    }, $content);
}

/**
 * 注音 [ruby]漢字{かんじ}[/ruby]
 * 或 [ruby text="拼音"]汉字[/ruby]
 */
function joe_ruby_shortcode($content)
{
    return preg_replace_callback('/\[ruby(?:\s+text=["\']?([^"\'\]]+)["\']?)?\](.*?)\[\/ruby\]/is', function ($m) {
        $text = !empty($m[1]) ? $m[1] : '';
        $base = $m[2];
        // 支持 {注音} 语法
        if (empty($text) && preg_match('/^(.+)\{(.+)\}$/u', $base, $rb)) {
            $base = $rb[1];
            $text = $rb[2];
        }
        if (empty($text)) return $base;
        return '<ruby>' . $base . '<rt>' . joe_esc($text) . '</rt></ruby>';
    }, $content);
}

/**
 * 代码差异 [diff lang="php"]
 * + 新增行
 * - 删除行
 * [/diff]
 */
function joe_diff_shortcode($content)
{
    return preg_replace_callback('/\[diff(?:\s+lang=["\']?(\w+)["\']?)?\](.*?)\[\/diff\]/is', function ($m) {
        $lang = !empty($m[1]) ? $m[1] : '';
        $lines = explode("\n", trim($m[2]));
        $html = '<div class="joe-diff">';
        if ($lang) $html .= '<div class="joe-diff__lang">' . joe_esc($lang) . '</div>';
        $html .= '<table class="joe-diff__table">';
        $ln = 0;
        foreach ($lines as $line) {
            $ln++;
            $cls = '';
            $prefix = '';
            if (preg_match('/^\+\s*(.*)/', $line, $pm)) {
                $cls = 'is-add';
                $line = $pm[1];
                $prefix = '+';
            } elseif (preg_match('/^\-\s*(.*)/', $line, $mm)) {
                $cls = 'is-del';
                $line = $mm[1];
                $prefix = '-';
            }
            $html .= '<tr class="' . $cls . '"><td class="joe-diff__ln">' . $ln . '</td><td class="joe-diff__sign">' . $prefix . '</td><td class="joe-diff__code">' . htmlspecialchars($line) . '</td></tr>';
        }
        $html .= '</table></div>';
        return $html;
    }, $content);
}

/**
 * 链接卡片短代码
 * [link url="xxx" title="xxx" desc="xxx"]
 */
function joe_link_card_shortcode($content)
{
    return preg_replace_callback('/\[link(?:\s+url=["\']?([^"\'\s]+)["\']?)?(?:\s+title=["\']?([^"\'\]]+)["\']?)?(?:\s+desc=["\']?([^"\'\]]+)["\']?)?\]/is', function ($m) {
        $url   = !empty($m[1]) ? $m[1] : '#';
        $title = !empty($m[2]) ? $m[2] : '链接卡片';
        $desc  = !empty($m[3]) ? $m[3] : '';
        return '<div class="joe-link-card"><a href="' . joe_esc($url) . '" target="_blank" rel="noopener"><div class="joe-link-card__content"><div class="joe-link-card__title">' . joe_esc($title) . '</div>' . ($desc ? '<div class="joe-link-card__desc">' . joe_esc($desc) . '</div>' : '') . '<div class="joe-link-card__url">' . joe_esc($url) . '</div></div></a></div>';
    }, $content);
}

/**
 * 进度条短代码
 * [progress value="50" text="完成度"]
 */
function joe_progress_shortcode($content)
{
    return preg_replace_callback('/\[progress(?:\s+value=["\']?(\d+)["\']?)?(?:\s+text=["\']?([^"\'\]]+)["\']?)?\]/is', function ($m) {
        $value = !empty($m[1]) ? min(100, max(0, (int)$m[1])) : 50;
        $text  = !empty($m[2]) ? $m[2] : '';
        return '<div class="joe-progress-shortcode"><div class="joe-progress-shortcode__header">' . ($text ? '<span class="joe-progress-shortcode__text">' . joe_esc($text) . '</span>' : '') . '<span class="joe-progress-shortcode__value">' . $value . '%</span></div><div class="joe-progress-shortcode__bar"><div class="joe-progress-shortcode__fill" style="width:' . $value . '%"></div></div></div>';
    }, $content);
}

/**
 * 文章引用短代码
 * [post cid="123"]
 */
function joe_post_ref_shortcode($content)
{
    return preg_replace_callback('/\[post(?:\s+cid=["\']?(\d+)["\']?)?(?:\s+text=["\']?([^"\'\]]+)["\']?)?\]/is', function ($m) {
        $cid  = !empty($m[1]) ? (int)$m[1] : 0;
        $text = !empty($m[2]) ? $m[2] : '查看相关文章';
        if (!$cid) return '<span class="joe-post-ref is-error">无效的文章ID</span>';
        try {
            $db = Typecho_Db::get();
            $post = $db->fetchRow($db->select('title')->from('table.contents')->where('cid = ? AND type = ? AND status = ?', $cid, 'post', 'publish'));
            if ($post) {
                $title = $post['title'];
                return '<a href="#" class="joe-post-ref" data-cid="' . $cid . '"><span class="joe-post-ref__icon"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span><span>' . joe_esc($title) . '</span></a>';
            }
            return '<span class="joe-post-ref is-error">文章不存在或未发布</span>';
        } catch (Exception $e) {
            return '<span class="joe-post-ref is-error">查询失败</span>';
        }
    }, $content);
}

/**
 * 高亮标记短代码
 * [mark]内容[/mark]
 */
function joe_highlight_shortcode($content)
{
    return preg_replace_callback('/\[mark\](.*?)\[\/mark\]/is', function ($m) {
        return '<mark class="joe-highlight">' . joe_esc($m[1]) . '</mark>';
    }, $content);
}

/**
 * 键盘按键短代码
 * [key]Ctrl[/key]
 */
function joe_keyboard_shortcode($content)
{
    return preg_replace_callback('/\[key\](.*?)\[\/key\]/is', function ($m) {
        return '<kbd class="joe-kbd">' . joe_esc($m[1]) . '</kbd>';
    }, $content);
}

/**
 * [lock] 短代码 — 文章加密阅读
 * 用法: [lock password="123456"]加密内容[/lock]
 * 用户输入正确密码后显示内容，使用 session 记住解锁状态
 */
function joe_lock_shortcode($content)
{
    if (strpos((string) $content, '[lock') === false) return $content;
    
    return preg_replace_callback(
        '/\[lock\s+password="([^"]+)"\s*\](.*?)\[\/lock\]/is',
        function ($m) {
            $password = $m[1];
            $hiddenContent = $m[2];
            $lockId = 'joe_lock_' . md5($password . $hiddenContent);
            
            // 检查是否已解锁
            $unlocked = false;
            if (session_status() === PHP_SESSION_NONE) {
                @session_start([
                    'cookie_httponly' => true,
                    'cookie_secure' => isset($_SERVER['HTTPS']),
                    'cookie_samesite' => 'Lax',
                ]);
            }
            if (isset($_SESSION[$lockId]) && $_SESSION[$lockId] === true) {
                $unlocked = true;
            }
            
            // 检查 POST 提交的密码
            if (!$unlocked && isset($_POST['joe_lock_pwd']) && isset($_POST['joe_lock_id'])) {
                if ($_POST['joe_lock_id'] === $lockId && $_POST['joe_lock_pwd'] === $password) {
                    $_SESSION[$lockId] = true;
                    $unlocked = true;
                }
            }
            
            if ($unlocked) {
                return '<div class="joe-lock is-unlocked"><div class="joe-lock__badge">🔓 已解锁</div>' . $hiddenContent . '</div>';
            }
            
            $html = '<div class="joe-lock">';
            $html .= '<div class="joe-lock__inner">';
            $html .= '<div class="joe-lock__icon"><svg viewBox="0 0 24 24" width="40" height="40"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M8 11V7a4 4 0 018 0v4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></div>';
            $html .= '<div class="joe-lock__title">此内容已加密</div>';
            $html .= '<div class="joe-lock__desc">请输入密码后查看</div>';
            $html .= '<form class="joe-lock__form" method="post">';
            $html .= '<input type="hidden" name="joe_lock_id" value="' . htmlspecialchars($lockId) . '">';
            $html .= '<input type="password" name="joe_lock_pwd" class="joe-lock__input" placeholder="请输入密码" required>';
            $html .= '<button type="submit" class="joe-lock__btn">确认</button>';
            $html .= '</form>';
            $html .= '</div></div>';
            
            return $html;
        },
        $content
    );
}

/**
 * [mermaid] 短代码 — Mermaid 思维导图/流程图
 * 用法: [mermaid]graph TD; A-->B; B-->C;[/mermaid]
 */
function joe_mermaid_shortcode($content)
{
    if (strpos((string) $content, '[mermaid]') === false) return $content;
    
    return preg_replace_callback(
        '/\[mermaid\](.*?)\[\/mermaid\]/is',
        function ($m) {
            $code = htmlspecialchars_decode(trim($m[1]));
            $id = 'mermaid_' . substr(md5($code), 0, 8);
            return '<div class="joe-mermaid"><div class="mermaid" id="' . $id . '">' . $code . '</div></div>';
        },
        $content
    );
}
