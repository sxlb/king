<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 统一获取主题配置项（模板中用 joe_opt('key') 调用，自动 echo） */
function joe_opt($key)
{
    $options = Helper::options();
    $val = $options->{$key};
    echo htmlspecialchars($val ?? '');
}

/**
 * 统一获取主题配置项（返回值，不输出）
 */
function joe_get($key)
{
    $options = Helper::options();
    return $options->{$key} ?? null;
}

/**
 * 转义输出（安全输出到 HTML） */
function joe_esc($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 获取摘要
 */
function joe_excerpt($archive, $length = 100)
{
    $text = $archive->excerpt;
    if (!$text) {
        $text = strip_tags((string) $archive->content);
    }
    // 清理短代码标签
    if (joe_get('excerptClean') === '1') {
        $text = preg_replace('/\[(lanzou|ad|video|bilibili|youtube|reply)\b[^\]]*\]?.*?\[\/\1\]?/is', '', $text);
        $text = preg_replace('/\[lanzou\].*?\[\/lanzou\]/is', '', $text);
        $text = preg_replace('/\[reply\].*?\[\/reply\]/is', '', $text);
        $text = preg_replace('/\{.*?\}/', '', $text);
    }
    $text = trim(preg_replace('/\s+/', ' ', $text));
    return mb_substr($text, 0, $length, 'UTF-8') . '...';
}

/**
 * 格式化日期 */
function joe_format_date($timestamp)
{
    return date('Y-m-d', $timestamp);
}

/**
 * 解析轮播图数据
 */
function joe_carousel_slides()
{
    $raw = joe_get('carouselSlides');
    if (empty($raw)) return [];
    $lines = explode("\n", trim($raw));
    $slides = [];
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) >= 2) {
            $slides[] = [
                'image' => trim($parts[0]),
                'url' => trim($parts[1]),
                'title' => isset($parts[2]) ? trim($parts[2]) : '',
            ];
        }
    }
    return $slides;
}

/**
 * 判断文章是否过期
 */
function joe_is_overdue($archive)
{
    $days = (int)(joe_get('overdueDays') ?: 0);
    if ($days <= 0) return false;
    $diff = floor((time() - $archive->modified) / 86400);
    return $diff > $days;
}

/**
 * 获取文章过期天数
 */
function joe_overdue_days($archive)
{
    return floor((time() - $archive->modified) / 86400);
}

/**
 * 获取主题静态资源 URL（支持 CDN）
 */
function joe_asset($path = '')
{
    $cdn = trim(joe_get('cdnUrl') ?: '');
    $path = ltrim($path, '/');
    if ($cdn) {
        return rtrim($cdn, '/') . '/' . $path;
    }
    return Helper::options()->themeUrl . '/' . $path;
}

/**
 * 获取 Gravatar 头像 URL
 */
function joe_gravatar($email, $size = 80, $default = 'mm')
{
    $hash = md5(strtolower(trim($email)));
    $cdn = joe_get('gravatarCdn') ?: 'default';
    if ($cdn === 'default') {
        $base = 'https://secure.gravatar.com/avatar/';
    } else {
        $base = $cdn;
    }
    return $base . $hash . '?s=' . $size . '&d=' . urlencode($default);
}

/**
 * 相对时间格式化
 * @param int $timestamp Unix 时间戳
 * @return string
 */
function joe_relative_time($timestamp)
{
    $diff = time() - $timestamp;
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . ' 分钟前';
    if ($diff < 86400) return floor($diff / 3600) . ' 小时前';
    if ($diff < 259200) return floor($diff / 86400) . ' 天前';
    if ($diff < 31536000) return date('m-d H:i', $timestamp);
    return date('Y-m-d H:i', $timestamp);
}

/**
 * 获取豆瓣收藏数据（带缓存）
 * @param string $doubanId 豆瓣用户ID
 * @param string $type book|movie|music
 * @return array
 */
function joe_douban_fetch($doubanId, $type)
{
    $cacheKey = 'joe_douban_' . $type . '_' . md5($doubanId);
    $cacheTimeKey = $cacheKey . '_time';
    $options = Helper::options();

    // 读缓存（6小时）
    $cached = Typecho_Widget::widget('Widget_Options')->{$cacheKey};
    $cachedTime = Typecho_Widget::widget('Widget_Options')->{$cacheTimeKey} ?? 0;
    if ($cached && (time() - (int)$cachedTime) < 21600) {
        $data = json_decode($cached, true);
        if (is_array($data)) return $data;
    }

    // 构建请求 URL
    $urls = [
        'book'  => 'https://book.douban.com/people/' . urlencode($doubanId) . '/collect',
        'movie' => 'https://movie.douban.com/people/' . urlencode($doubanId) . '/collect',
        'music' => 'https://music.douban.com/people/' . urlencode($doubanId) . '/collect',
    ];

    if (!isset($urls[$type])) return [];

    $url = $urls[$type];
    $html = joe_http_get($url);
    if (!$html) return [];

    $items = [];
    // 解析豆瓣收藏列表 HTML
    if ($type === 'book') {
        $items = joe_parse_douban_books($html);
    } elseif ($type === 'movie') {
        $items = joe_parse_douban_movies($html);
    } elseif ($type === 'music') {
        $items = joe_parse_douban_music($html);
    }

    // 写入缓存
    try {
        $db = Typecho_Db::get();
        $db->query($db->update('table.options')
            ->rows(['value' => json_encode($items, JSON_UNESCAPED_UNICODE)])
            ->where('name = ?', $cacheKey));
        $db->query($db->update('table.options')
            ->rows(['value' => (string)time()])
            ->where('name = ?', $cacheTimeKey));
    } catch (Exception $e) {
        // silently ignore cache errors
    }

    return $items;
}

/**
 * HTTP GET 请求
 */
function joe_http_get($url, $timeout = 10)
{
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept-Language: zh-CN,zh;q=0.9'],
        ]);
        $data = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return $err ? '' : $data;
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept-Language: zh-CN,zh;q=0.9\r\n",
            'follow_location' => 1,
        ],
        'ssl' => ['verify_peer' => true],
    ]);
    return @file_get_contents($url, false, $ctx) ?: '';
}

/**
 * 解析豆瓣读书收藏
 */
function joe_parse_douban_books($html)
{
    $items = [];
    // 匹配每本书的条目
    if (preg_match_all('/<li\s+class="subject-item"[^>]*>(.*?)<\/li>/is', $html, $liMatches)) {
        foreach ($liMatches[1] as $li) {
            $item = [];
            // 封面
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $li, $m)) $item['cover'] = $m[1];
            // 书名
            if (preg_match('/<a[^>]+title="([^"]+)"[^>]*>/i', $li, $m)) $item['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            // 链接
            if (preg_match('/<a[^>]+href="([^"]+)"[^>]*title="/i', $li, $m)) $item['url'] = $m[1];
            // 评分
            if (preg_match('/allstar(\d+)/', $li, $m)) $item['rating'] = (int)$m[1] / 10;
            // 出版信息
            if (preg_match('/<div\s+class="pub"[^>]*>(.*?)<\/div>/i', $li, $m)) {
                $item['info'] = trim(strip_tags($m[1]));
            }
            // 短评
            if (preg_match('/<span\s+class="comment"[^>]*>(.*?)<\/span>/i', $li, $m)) {
                $item['comment'] = trim(strip_tags($m[1]));
            }
            if (!empty($item['title'])) $items[] = $item;
        }
    }
    return array_slice($items, 0, 24);
}

/**
 * 解析豆瓣观影收藏
 */
function joe_parse_douban_movies($html)
{
    $items = [];
    if (preg_match_all('/<div\s+class="item[^"]*"[^>]*>(.*?)<\/div>\s*(?=<div\s+class="item|$)/is', $html, $divMatches)) {
        foreach ($divMatches[1] as $div) {
            $item = [];
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $div, $m)) $item['cover'] = $m[1];
            if (preg_match('/<em[^>]*>(.*?)<\/em>/i', $div, $m)) $item['title'] = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
            elseif (preg_match('/<a[^>]+title="([^"]+)"[^>]*>/i', $div, $m)) $item['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>.*?<\/a>/i', $div, $m)) $item['url'] = $m[1];
            if (preg_match('/allstar(\d+)/', $div, $m)) $item['rating'] = (int)$m[1] / 10;
            if (preg_match('/<span\s+class="date"[^>]*>(.*?)<\/span>/i', $div, $m)) $item['date'] = trim(strip_tags($m[1]));
            if (preg_match('/<p\s+class="comment"[^>]*>(.*?)<\/p>/i', $div, $m)) $item['comment'] = trim(strip_tags($m[1]));
            if (!empty($item['title'])) $items[] = $item;
        }
    }
    return array_slice($items, 0, 24);
}

/**
 * 解析豆瓣音乐收藏
 */
function joe_parse_douban_music($html)
{
    $items = [];
    if (preg_match_all('/<li\s+class="subject-item"[^>]*>(.*?)<\/li>/is', $html, $liMatches)) {
        foreach ($liMatches[1] as $li) {
            $item = [];
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $li, $m)) $item['cover'] = $m[1];
            if (preg_match('/<a[^>]+title="([^"]+)"[^>]*>/i', $li, $m)) $item['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            if (preg_match('/<a[^>]+href="([^"]+)"[^>]*title="/i', $li, $m)) $item['url'] = $m[1];
            if (preg_match('/allstar(\d+)/', $li, $m)) $item['rating'] = (int)$m[1] / 10;
            if (preg_match('/<div\s+class="pub"[^>]*>(.*?)<\/div>/i', $li, $m)) $item['info'] = trim(strip_tags($m[1]));
            if (!empty($item['title'])) $items[] = $item;
        }
    }
    return array_slice($items, 0, 24);
}

/**
 * 搜索结果摘要（提取关键词周围文字）
 */
function joe_search_excerpt($archive, $keyword, $len = 150)
{
    $content = strip_tags((string) $archive->content);
    $content = preg_replace('/\s+/', ' ', $content);
    $kw = htmlspecialchars($keyword);
    if ($kw) {
        $pos = mb_stripos($content, $kw);
        if ($pos !== false) {
            $start = max(0, $pos - 30);
            $excerpt = mb_substr($content, $start, $len + 30);
            if ($start > 0) $excerpt = '...' . $excerpt;
            if (mb_strlen($content) > $start + $len + 30) $excerpt .= '...';
            return htmlspecialchars($excerpt);
        }
    }
    $excerpt = mb_substr($content, 0, $len);
    if (mb_strlen($content) > $len) $excerpt .= '...';
    return htmlspecialchars($excerpt);
}

/**
 * 豆瓣评分星星
 */
function joe_douban_stars($rating)
{
    $stars = '';
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    for ($i = 0; $i < $full; $i++) $stars .= '★';
    if ($half) $stars .= '☆';
    return $stars;
}

/**
 * 获取文章字数
 */
function joe_word_count($archive)
{
    $content = strip_tags((string) $archive->content);
    $content = preg_replace('/\s+/', '', $content);
    return mb_strlen($content, 'UTF-8');
}

/**
 * 获取置顶文章
 */
function joe_sticky_posts()
{
    $sticky = trim(joe_get('stickyCids') ?: '');
    if (!$sticky) return [];
    $cids = array_filter(array_map('intval', explode(',', $sticky)));
    if (empty($cids)) return [];

    $db = Typecho_Db::get();
    $rows = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'type')
        ->from('table.contents')
        ->where('cid IN ?', $cids)
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'));

    // 按照 cids 顺序排序
    $order = array_flip($cids);
    usort($rows, function ($a, $b) use ($order) {
        return ($order[$a['cid']] ?? 999) - ($order[$b['cid']] ?? 999);
    });

    $result = [];
    foreach ($rows as $row) {
        $t = new Typecho_Widget_Helper_Empty();
        $t->cid = $row['cid'];
        $t->slug = $row['slug'];
        $t->created = $row['created'];
        $t->type = $row['type'];
        try {
            $permalink = Typecho_Router::url($row['type'], $t, Helper::options()->siteUrl);
        } catch (Exception $e) {
            $permalink = rtrim(Helper::options()->siteUrl, '/') . '/index.php/' . $row['cid'] . '.html';
        }
        $result[] = [
            'cid' => $row['cid'],
            'title' => $row['title'],
            'permalink' => $permalink,
            'created' => $row['created'],
        ];
    }
    return $result;
}

/**
 * 搜索关键词高亮
 */
function joe_search_highlight($text)
{
    $keyword = isset($_GET['s']) ? trim($_GET['s']) : (isset($_POST['s']) ? trim($_POST['s']) : '');
    if (!$keyword) return $text;
    // 先分词，再对每个词单独 preg_quote
    $words = preg_split('/\s+/', $keyword);
    foreach ($words as $w) {
        $w = trim($w);
        if (mb_strlen($w) < 2) continue;
        $w = preg_quote($w, '/');
        $text = preg_replace('/(' . $w . ')(?![^<]*>)/iu', '<mark class="joe-search-hl">$1</mark>', $text);
    }
    return $text;
}

/**
 * 社交图标 SVG
 */
function joe_social_svg($icon)
{
    $icons = [
        'github'   => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.342-3.369-1.342-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 12 6.836c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z" fill="currentColor"/></svg>',
        'twitter'  => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" fill="currentColor"/></svg>',
        'weibo'    => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M10.098 20.323c-3.977.391-7.414-1.406-7.672-4.02-.259-2.609 2.759-5.047 6.74-5.441 3.979-.394 7.413 1.404 7.671 4.018.259 2.6-2.759 5.049-6.739 5.443zm-1.865-3.909c-.816.745-.27 2.294 1.21 3.459 1.479 1.164 3.342 1.606 4.156.85.815-.745.275-2.294-1.204-3.459-1.479-1.164-3.347-1.598-4.162-.85zm3.757-1.223c-2.149-.524-3.935.02-4.089 1.228-.155 1.207 1.485 2.643 3.634 3.168 2.149.524 3.935-.02 4.089-1.228.155-1.207-1.485-2.643-3.634-3.168zm5.606-8.314c2.663 5.183-2.445 12.361-9.192 11.769 4.747-1.913 6.224-7.47 3.695-11.333-1.361-2.04-3.083-2.707-4.768-2.21 2.765-1.15 7.605.596 10.265 1.774zM18.77 1c-1.873.672-2.901 2.842-2.294 4.763.606 1.921 2.508 3.076 4.383 2.404 1.873-.672 2.9-2.842 2.294-4.763-.607-1.921-2.508-3.076-4.383-2.404zm1.187 3.5c-.243.734-1.04 1.177-1.774.986-.734-.19-1.13-.963-.888-1.697.242-.734 1.039-1.177 1.773-.986.735.19 1.131.963.889 1.697z" fill="currentColor"/></svg>',
        'qq'       => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M21.395 15.035a40.63 40.63 0 0 0-.803-2.264l-1.079-2.695c.001-.032.014-.562.014-.836C19.526 4.632 16.351 1.5 12 1.5S4.474 4.632 4.474 9.241c0 .274.013.804.014.836l-1.08 2.695a38.708 38.708 0 0 0-.802 2.264c-1.021 3.283-.694 4.157-.563 5.164.385 2.983 5.69 3.3 9.957 3.3 4.267 0 9.572-.317 9.958-3.3.13-1.007.457-1.88-.563-5.164zM12 18.5c-4.264 0-7.781-.81-7.92-1.797-.058-.431.53-.314 1.043-.337 1.318 3.548 12.51 3.276 13.753.024.533.027 1.103-.07 1.044.313-.138.986-3.656 1.797-7.92 1.797z" fill="currentColor"/></svg>',
        'wechat'   => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.33.33 0 0 0 .167-.054l1.903-1.114a.86.86 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229a7.22 7.22 0 0 0 2.352-.396.63.63 0 0 1 .564.08l1.61.944a.24.24 0 0 0 .122.04c.118 0 .212-.096.212-.215 0-.054-.025-.106-.036-.158l-.329-1.253a.43.43 0 0 1 .156-.486C23.264 18.752 24 17.253 24 15.56c0-3.704-3.371-6.701-7.062-6.701zm-6.51 4.619c.48 0 .868.396.868.884a.876.876 0 0 1-.868.883.876.876 0 0 1-.868-.883c0-.488.389-.884.868-.884zm5.186 0c.48 0 .868.396.868.884a.876.876 0 0 1-.868.883.876.876 0 0 1-.868-.883c0-.488.389-.884.868-.884z" fill="currentColor"/></svg>',
        'email'    => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z" fill="currentColor"/></svg>',
        'rss'      => '<svg viewBox="0 0 24 24" width="20" height="20"><circle cx="6.18" cy="17.82" r="2.18" fill="currentColor"/><path d="M4 4.44v2.83c7.03 0 12.73 5.7 12.73 12.73h2.83c0-8.59-6.97-15.56-15.56-15.56zm0 5.66v2.83c3.9 0 7.07 3.17 7.07 7.07h2.83c0-5.47-4.43-9.9-9.9-9.9z" fill="currentColor"/></svg>',
        'bilibili' => '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M17.813 4.653h.854c1.51.054 2.769.578 3.773 1.574 1.004.995 1.524 2.249 1.56 3.76v7.36c-.036 1.51-.556 2.768-1.56 3.773s-2.262 1.524-3.773 1.56H5.333c-1.51-.036-2.769-.556-3.773-1.56S.036 18.858 0 17.347v-7.36c.036-1.511.556-2.765 1.56-3.76 1.004-.996 2.262-1.52 3.773-1.574h.774l-1.174-1.12a1.236 1.236 0 0 1-.373-.906c0-.356.124-.658.373-.907l.027-.027c.267-.249.573-.373.92-.373.347 0 .653.124.92.373L9.653 4.44c.071.071.134.142.187.213h4.267a.8.8 0 0 1 .16-.213l2.853-2.747c.267-.249.573-.373.92-.373.347 0 .662.151.929.4.267.249.391.551.391.907 0 .355-.124.657-.373.906zM5.333 7.24c-.746.018-1.373.276-1.88.773-.506.498-.769 1.13-.786 1.894v7.52c.017.764.28 1.395.786 1.893.507.498 1.134.756 1.88.773h13.334c.746-.017 1.373-.275 1.88-.773.506-.498.769-1.129.786-1.893v-7.52c-.017-.765-.28-1.396-.786-1.894-.507-.497-1.134-.755-1.88-.773zM8 11.107c.373 0 .684.124.933.373.25.249.383.569.4.96v1.173c-.017.391-.15.711-.4.96-.249.25-.56.374-.933.374s-.684-.125-.933-.374c-.25-.249-.383-.569-.4-.96V12.44c0-.373.129-.689.386-.947.258-.257.574-.386.947-.386zm8 0c.373 0 .684.124.933.373.25.249.383.569.4.96v1.173c-.017.391-.15.711-.4.96-.249.25-.56.374-.933.374s-.684-.125-.933-.374c-.25-.249-.383-.569-.4-.96V12.44c.017-.391.15-.711.4-.96.249-.249.56-.373.933-.373z" fill="currentColor"/></svg>',
    ];
    return isset($icons[$icon]) ? $icons[$icon] : '';
}
