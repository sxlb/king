<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 获取标签云
 */
function joe_tags_cloud($limit = 30)
{
    $db = Typecho_Db::get();
    $tags = $db->fetchAll($db->select('mid', 'name', 'count')
        ->from('table.metas')
        ->where('type = ?', 'tag')
        ->order('count', Typecho_Db::SORT_DESC)
        ->limit($limit));
    return $tags;
}

/**
 * 上一篇/下一篇 */
function joe_post_neighbors($archive)
{
    $db = Typecho_Db::get();
    $prev = $db->fetchRow($archive->select()->where('created < ?', $archive->created)
        ->where('type = ?', 'post')->where('status = ?', 'publish')
        ->order('created', Typecho_Db::SORT_DESC)->limit(1));
    $next = $db->fetchRow($archive->select()->where('created > ?', $archive->created)
        ->where('type = ?', 'post')->where('status = ?', 'publish')
        ->order('created', Typecho_Db::SORT_ASC)->limit(1));
    return ['prev' => $prev, 'next' => $next];
}

/**
 * 获取文章 TOC 目录（基于 h2/h3） */
function joe_toc($content)
{
    $content = (string) $content;
    if (!preg_match_all('/<h([23])[^>]*id=["\']([^"\']+)["\'][^>]*>(.*?)<\/h\1>/is', $content, $m)) {
        return '';
    }
    $total = count($m[0]);
    $html = '<nav class="joe-toc"><div class="joe-toc__head"><span class="joe-toc__head-text">目录 (' . $total . ')</span>';
    if ($total > 5) {
        $html .= '<button class="joe-toc__toggle" aria-label="折叠目录" title="折叠/展开"><svg viewBox="0 0 24 24" width="14" height="14"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
    }
    $html .= '</div><ul class="joe-toc__list">';
    foreach ($m[1] as $i => $level) {
        $id = $m[2][$i];
        $text = strip_tags($m[3][$i]);
        $cls = $level == 2 ? 'joe-toc__item' : 'joe-toc__item is-sub';
        $html .= '<li class="' . $cls . '"><a href="#' . $id . '" data-toc-id="' . $id . '">' . $text . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/**
 * 给文章标题自动加 id（用于 TOC 锚点） */
function joe_add_heading_ids($content)
{
    $content = (string) $content;
    return preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/\1>/is', function ($m) {
        $tag = $m[1];
        $attrs = $m[2];
        $text = $m[3];
        if (preg_match('/id=["\']([^"\']+)/i', $attrs)) {
            return $m[0];
        }
        $id = 'h-' . preg_replace('/[^a-z0-9\x{4e00}-\x{9fa5}]+/iu', '-', strip_tags((string) $text));
        $id = trim($id, '-');
        if (!$id) $id = 'heading-' . rand(1000, 9999);
        return '<' . $tag . $attrs . ' id="' . $id . '">' . $text . '</' . $tag . '>';
    }, $content);
}

/**
 * 上下篇文章的链接
 */
function joe_neighbor_link($row)
{
    $slug = $row['slug'] ?: $row['cid'];
    return Typecho_Common::url($slug, Helper::options()->siteUrl);
}

/**
 * 解析友链数据
 *
 * 格式：
 *   分组名
 *   站点名 | URL | 头像(可空) | 描述(可空)
 *   ...
 *   <空行>
 *   分组名
 *   ...
 *
 * @param string $raw
 * @return array [['name' => '友情站点', 'items' => [['name','url','avatar','desc'], ...]], ...]
 */
function joe_parse_links($raw)
{
    if (!$raw) return [];
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    $groups = [];
    $current = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $current = null;
            continue;
        }
        if ($current === null) {
            $current = ['name' => $line, 'items' => []];
            $groups[] = &$current;
            continue;
        }
        $parts = array_pad(array_map('trim', explode('|', $line)), 4, '');
        if (!$parts[0] || !$parts[1]) continue;
        $current['items'][] = [
            'name'   => $parts[0],
            'url'    => $parts[1],
            'avatar' => $parts[2],
            'desc'   => $parts[3],
        ];
    }
    unset($current);
    // 过滤空分组
    return array_values(array_filter($groups, function ($g) { return !empty($g['items']); }));
}

/**
 * 获取文章点赞数
 */
function joe_agree($archive)
{
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $archive->cid));
    return $row ? (int)$row['agree'] : 0;
}

/**
 * 处理点赞 AJAX 请求
 */
function joe_agree_handle()
{
    if (!isset($_POST['action']) || $_POST['action'] !== 'joe_agree') return;
    if (!isset($_POST['cid']) || !is_numeric($_POST['cid'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '参数错误'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $cid = (int)$_POST['cid'];
    $ip = Typecho_Request::getInstance()->getIp();
    $db = Typecho_Db::get();

    // 24小时内同一 IP 只能点一次
    $key = 'joe_agree_' . $cid;
    $agreed = Typecho_Cookie::get($key, '');
    if ($agreed && time() - (int)$agreed < 86400) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '你已经点过赞了'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $row = $db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $cid));
    if (!$row) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '文章不存在'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->query($db->update('table.contents')->rows(['agree' => ($row['agree'] + 1)])->where('cid = ?', $cid));
    Typecho_Cookie::set($key, (string)time(), time() + 86400);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => '点赞成功', 'count' => $row['agree'] + 1], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 相关文章（同分类）
 */
function joe_related_posts($archive, $limit = 6)
{
    $db = Typecho_Db::get();
    $cid = $archive->cid;

    // 获取当前文章的分类 mid
    $cats = $db->fetchAll($db->select('mid')->from('table.relationships')->where('cid = ?', $cid));
    $mids = array_column($cats, 'mid');
    if (empty($mids)) return [];

    // 查询同分类下的其他文章
    $rows = $db->fetchAll($db->select('c.cid', 'c.title', 'c.slug', 'c.created', 'c.type')
        ->from('table.contents AS c')
        ->join('table.relationships AS r', 'c.cid = r.cid')
        ->where('r.mid IN ?', $mids)
        ->where('c.cid != ?', $cid)
        ->where('c.status = ?', 'publish')
        ->where('c.type = ?', 'post')
        ->order('c.created', Typecho_Db::SORT_DESC)
        ->limit($limit));

    // 生成永久链接
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
 * 那年今日 - 获取历史上今天发表的文章
 */
function joe_on_this_day()
{
    $db = Typecho_Db::get();
    $today = date('m-d');
    $rows = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'type')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where("DATE_FORMAT(FROM_UNIXTIME(created), '%m-%d') = ?", $today)
        ->where("DATE_FORMAT(FROM_UNIXTIME(created), '%Y') < ?", date('Y'))
        ->order('created', Typecho_Db::SORT_DESC)
        ->limit(5));
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
            'year' => date('Y', $row['created']),
        ];
    }
    return $result;
}

/**
 * 随机一言 - 调用 hitokoto API
 */
function joe_hitokoto()
{
    $cache_key = 'joe_hitokoto_cache';
    $cache = Typecho_Widget::widget('Widget_Options')->{$cache_key};
    $cache_time = Typecho_Widget::widget('Widget_Options')->{$cache_key . '_time'} ?? 0;
    if ($cache && (time() - $cache_time) < 3600) {
        return $cache;
    }
    $sentence = '';
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $res = @file_get_contents('https://v1.hitokoto.cn/?encode=json', false, $ctx);
        if ($res) {
            $json = json_decode($res, true);
            if (!empty($json['hitokoto'])) {
                $sentence = $json['hitokoto'];
                if (!empty($json['from'])) {
                    $sentence .= ' —— ' . $json['from'];
                }
            }
        }
    } catch (Exception $e) {}
    if (empty($sentence)) {
        $fallback = ['凡是过往，皆为序章。', '心之所向，素履以往。', '生如逆旅，一苇以航。', '念念不忘，必有回响。'];
        $sentence = $fallback[array_rand($fallback)];
    }
    try {
        $db = Typecho_Db::get();
        $db->query($db->insert('table.options')
            ->rows(['name' => $cache_key, 'user' => 0, 'value' => $sentence])
            ->onDuplicateKeyUpdate(['value' => $sentence]));
    } catch (Exception $e) {}
    return $sentence;
}

/**
 * 友链在线申请处理
 */
function joe_link_apply_handler()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (joe_get('linkApply') !== '1') return;

    $action = $_POST['joe_action'] ?? '';
    if ($action !== 'link_apply') return;

    $name = trim(strip_tags($_POST['site_name'] ?? ''));
    $url = trim(strip_tags($_POST['site_url'] ?? ''));
    $desc = trim(strip_tags($_POST['site_desc'] ?? ''));
    $email = trim(strip_tags($_POST['site_email'] ?? ''));

    if (empty($name) || empty($url) || empty($email)) {
        header('Content-Type: application/json');
        echo json_encode(['code' => 0, 'msg' => '请填写完整信息'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        header('Content-Type: application/json');
        echo json_encode(['code' => 0, 'msg' => '网站地址格式不正确'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 验证码（简单数字）
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    $captcha = $_POST['captcha'] ?? '';
    if (empty($captcha) || !isset($_SESSION['joe_link_captcha']) || intval($captcha) !== $_SESSION['joe_link_captcha']) {
        header('Content-Type: application/json');
        echo json_encode(['code' => 0, 'msg' => '验证码错误'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    unset($_SESSION['joe_link_captcha']);

    // 发送邮件通知站长
    $siteTitle = Helper::options()->title;
    $subject = '【' . $siteTitle . '】友链申请 - ' . $name;
    $body = "站点名称：{$name}\n站点地址：{$url}\n站点描述：{$desc}\n联系邮箱：{$email}\n\n请登录后台审核添加友链。";

    $mailConfig = [
        'host' => joe_get('mailHost') ?: '',
        'port' => joe_get('mailPort') ?: '465',
        'user' => joe_get('mailUser') ?: '',
        'pass' => joe_get('mailPass') ?: '',
    ];

    if (!empty($mailConfig['host']) && !empty($mailConfig['user'])) {
        // 尝试加载 PHPMailer，不存在则使用内置 mail()
        $phpmailer = __DIR__ . '/lib/class.phpmailer.php';
        $smtp = __DIR__ . '/lib/class.smtp.php';
        if (file_exists($phpmailer) && file_exists($smtp)) {
            require_once $smtp;
            require_once $phpmailer;

            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->Port = $mailConfig['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['user'];
            $mail->Password = $mailConfig['pass'];
            $mail->SMTPSecure = $mailConfig['port'] == '465' ? 'ssl' : 'tls';
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($mailConfig['user'], $siteTitle);
            $mail->addAddress($mailConfig['user'], $siteTitle);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($mail->send()) {
                header('Content-Type: application/json');
                echo json_encode(['code' => 1, 'msg' => '申请已提交，站长审核后会添加您的友链'], JSON_UNESCAPED_UNICODE);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['code' => 0, 'msg' => '发送失败，请稍后再试'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // PHPMailer 未安装，使用内置 mail() 作为后备
            $headers = "From: {$mailConfig['user']}\r\nContent-Type: text/plain; charset=UTF-8";
            @mail($mailConfig['user'], $subject, $body, $headers);
            header('Content-Type: application/json');
            echo json_encode(['code' => 1, 'msg' => '申请已提交，站长审核后会添加您的友链'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['code' => 0, 'msg' => '站长暂未配置邮箱通知，请直接联系站长'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * 获取热门文章
 * @param int $limit 数量
 * @param string $sort 排序方式：views 浏览量 / agree 点赞数 / date 最新
 * @return array
 */
function joe_hot_posts($limit = 6, $sort = 'views')
{
    $db = Typecho_Db::get();
    $query = $db->select('cid', 'title', 'slug', 'created', 'type', 'views', 'agree')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->limit($limit);

    if ($sort === 'agree') {
        $query->order('agree', Typecho_Db::SORT_DESC);
    } elseif ($sort === 'date') {
        $query->order('created', Typecho_Db::SORT_DESC);
    } else {
        $query->order('views', Typecho_Db::SORT_DESC);
    }

    $rows = $db->fetchAll($query);
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
            'views' => isset($row['views']) ? (int)$row['views'] : 0,
            'agree' => isset($row['agree']) ? (int)$row['agree'] : 0,
            'created' => $row['created'],
        ];
    }
    return $result;
}

/**
 * 时光机文章查询
 * @param string $slug 分类缩略名
 * @param int $page 页码
 * @param int $pageSize 每页条数
 * @return array
 */
function joe_timeline_posts($slug, $page = 1, $pageSize = 10)
{
    $db = Typecho_Db::get();
    // 查找分类
    $cat = $db->fetchRow($db->select('mid')->from('table.metas')
        ->where('slug = ? AND type = ?', $slug, 'category'));
    if (!$cat) return ['posts' => [], 'total' => 0, 'hasMore' => false];

    // 获取该分类下的文章总数
    $total = $db->fetchObject($db->select('COUNT(*)', 'num')
        ->from('table.relationships')
        ->join('table.contents', 'table.relationships.cid = table.contents.cid')
        ->where('table.relationships.mid = ? AND table.contents.status = ? AND table.contents.type = ?', $cat['mid'], 'publish', 'post'));
    $total = $total ? (int)$total->num : 0;

    $offset = ($page - 1) * $pageSize;
    $query = $db->select('cid', 'title', 'slug', 'text', 'created', 'type')
        ->from('table.relationships')
        ->join('table.contents', 'table.relationships.cid = table.contents.cid')
        ->where('table.relationships.mid = ? AND table.contents.status = ? AND table.contents.type = ?', $cat['mid'], 'publish', 'post')
        ->order('table.contents.created', Typecho_Db::SORT_DESC)
        ->offset($offset)
        ->limit($pageSize);

    $rows = $db->fetchAll($query);
    $posts = [];
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
        // 提取摘要（去除HTML标签）
        $text = strip_tags((string) $row['text']);
        $text = mb_strlen($text) > 280 ? mb_substr($text, 0, 280) . '...' : $text;
        // 提取第一张图片
        $image = '';
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $row['text'], $m)) {
            $image = $m[1];
        }
        $posts[] = [
            'cid' => $row['cid'],
            'title' => $row['title'],
            'permalink' => $permalink,
            'text' => $text,
            'image' => $image,
            'created' => $row['created'],
        ];
    }

    return [
        'posts' => $posts,
        'total' => $total,
        'hasMore' => $offset + $pageSize < $total,
    ];
}

/**
 * 时光机 AJAX 加载更多
 */
function joe_timeline_ajax()
{
    if (!isset($_GET['action']) || $_GET['action'] !== 'joe_timeline') return;
    $slug = joe_get('timelineCat');
    if (!$slug) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '未配置'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $pageSize = (int)(joe_get('timelinePageSize') ?: 10);
    $data = joe_timeline_posts($slug, $page, $pageSize);

    header('Content-Type: application/json; charset=utf-8');

    // 格式化输出
    $html = '';
    foreach ($data['posts'] as $post) {
        $time = joe_relative_time($post['created']);
        if ($post['image']) {
            $html .= '<div class="joe-timeline__item has-image">';
            $html .= '<div class="joe-timeline__media"><a href="' . joe_esc($post['permalink']) . '"><img src="' . joe_esc($post['image']) . '" alt="' . joe_esc($post['title']) . '" loading="lazy"></a></div>';
        } else {
            $html .= '<div class="joe-timeline__item">';
        }
        $html .= '<div class="joe-timeline__content">';
        $html .= '<div class="joe-timeline__text">' . joe_esc($post['text']) . '</div>';
        $html .= '<div class="joe-timeline__meta"><time datetime="' . date('c', $post['created']) . '">' . $time . '</time><a href="' . joe_esc($post['permalink']) . '" class="joe-timeline__more">查看详情</a></div>';
        $html .= '</div></div>';
    }

    echo json_encode([
        'code' => 1,
        'html' => $html,
        'hasMore' => $data['hasMore'],
        'total' => $data['total'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取随机文章
 */
function joe_random_posts($limit = 6)
{
    $db = Typecho_Db::get();
    $row = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'));
    $total = $row ? (int) $row->num : 0;

    if ($total <= $limit) {
        $offset = 0;
    } else {
        $offset = mt_rand(0, $total - $limit);
    }

    $rows = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'type')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->order('RAND()', Typecho_Db::SORT_ASC)
        ->limit($limit));

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
