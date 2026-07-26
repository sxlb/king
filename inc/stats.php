<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 获取文章浏览数（优先使用数据库内置字段，兼容 Views 插件） */
function joe_views($archive)
{
    $cid = $archive->cid;
    $db = Typecho_Db::get();
    // 优先使用 contents 表的 views 字段
    try {
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid)->limit(1));
        if ($row && isset($row['views']) && (int)$row['views'] > 0) return (int)$row['views'];
    } catch (Exception $e) {}
    // 兼容 Views 插件
    if (class_exists('Views_Plugin')) {
        try {
            return Views_Plugin::viewsCount($cid);
        } catch (Exception $e) {}
    }
    // 兼容 fields.views
    try {
        $row = $db->fetchRow($db->select('str_value')->from('table.fields')
            ->where('cid = ?', $cid)->where('name = ?', 'views')->limit(1));
        if ($row) return (int) $row['str_value'];
    } catch (Exception $e) {}
    return 0;
}

/**
 * 文章阅读量加 1（访问文章时调用） */
function joe_track_view($archive)
{
    if (!$archive->is('single')) return;
    $cid = $archive->cid;
    $db = Typecho_Db::get();
    try {
        // 检查字段是否存在
        $row = $db->fetchRow($db->select()->from('table.contents')->page(1, 1));
        if (!isset($row['views'])) return;
        $db->query($db->update('table.contents')
            ->expression('views', 'views + 1')
            ->where('cid = ?', $cid));
    } catch (Exception $e) {}
}

/**
 * 估算文章阅读时长（按 400 字/分钟），返回 "约 X 分钟"
 */
function joe_reading_time($archive)
{
    $text = strip_tags((string) $archive->content);
    // 中文字数
    $cn = preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $text, $_m) ? count($_m[0]) : 0;
    // 英文单词数
    $en = preg_match_all('/[a-zA-Z]+/', $text, $_m) ? count($_m[0]) : 0;
    // 总字数换算：中文 400 字/分，英文 200 字/分，取较大值
    $minutes = max(1, (int) ceil(($cn / 400) + ($en / 200)));
    return '约' . $minutes . ' 分钟';
}

/**
 * 统计：文章总数（直接输出）
 */
function joe_article_count()
{
    $db = Typecho_Db::get();
    $count = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'))->num;
    echo $count;
}

/**
 * 统计：评论总数（直接输出）
 */
function joe_comment_count()
{
    $db = Typecho_Db::get();
    $count = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))
        ->from('table.comments')
        ->where('status = ?', 'approved'))->num;
    echo $count;
}

/**
 * 统计：网站运行天数
 * 基于第一篇文章创建时间 */
function joe_site_age()
{
    $db = Typecho_Db::get();
    $row = $db->fetchObject($db->select('created')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->order('created', Typecho_Db::SORT_ASC)
        ->limit(1));
    if (!$row) return 0;
    return max(1, (int) floor((time() - $row->created) / 86400));
}

/**
 * 获取博客统计数据
 */
function joe_site_stats()
{
    $db = Typecho_Db::get();

    // PHP 8.x 安全：fetchObject 可能返回 null
    $count = function ($query) use ($db) {
        $row = $db->fetchObject($query);
        return $row ? (int) $row->num : 0;
    };

    // 文章数
    $postCount = $count($db->select(array('COUNT(*)' => 'num'))
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'));

    // 页面数
    $pageCount = $count($db->select(array('COUNT(*)' => 'num'))
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'page'));

    // 评论数
    $commentCount = $count($db->select(array('COUNT(*)' => 'num'))
        ->from('table.comments')
        ->where('status = ?', 'approved'));

    // 分类数
    $catCount = $count($db->select(array('COUNT(*)' => 'num'))
        ->from('table.metas')
        ->where('type = ?', 'category'));

    // 标签数
    $tagCount = $count($db->select(array('COUNT(*)' => 'num'))
        ->from('table.metas')
        ->where('type = ?', 'tag'));

    // 总浏览量
    $views = $count($db->select(array('SUM(views)' => 'num'))
        ->from('table.contents'));

    // 运行天数
    $first = $db->fetchObject($db->select('created')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->order('created', Typecho_Db::SORT_ASC)
        ->limit(1));
    $days = $first ? floor((time() - $first->created) / 86400) : 1;

    $siteStart = $first ? date('Y-m-d', $first->created) : date('Y-m-d');

    return [
        'posts' => $postCount,
        'pages' => $pageCount,
        'comments' => $commentCount,
        'categories' => $catCount,
        'tags' => $tagCount,
        'views' => $views ?: 0,
        'days' => max(1, $days),
        'siteStart' => $siteStart,
    ];
}
