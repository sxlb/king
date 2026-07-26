<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * SEO 标题
 * @param bool $with_suffix 是否拼接站点名
 */
function joe_seo_title($with_suffix = true)
{
    $archive = Typecho_Widget::widget('Widget_Archive');
    $title = '';
    if ($archive->is('single')) {
        $title = $archive->title;
    } elseif ($archive->is('category') || $archive->is('tag') || $archive->is('author') || $archive->is('date')) {
        $title = trim($archive->getArchiveTitle());
    } elseif ($archive->is('search')) {
        $title = '搜索：' . htmlspecialchars($archive->keywords);
    } elseif ($archive->is('page') || $archive->is('post')) {
        $title = $archive->title;
    }
    $title = trim($title);

    $siteTitle = Helper::options()->title;
    $seoTitle = joe_get('seoTitle');

    if ($archive->is('index') && !$archive->is('paged')) {
        return $seoTitle ? $seoTitle : $siteTitle;
    }

    if ($with_suffix) {
        return $title . ' - ' . $siteTitle;
    }
    return $title ?: $siteTitle;
}

/**
 * SEO 描述
 */
function joe_seo_description()
{
    $archive = Typecho_Widget::widget('Widget_Archive');
    if ($archive->is('single') || $archive->is('page')) {
        $desc = strip_tags(joe_excerpt($archive, 120));
        return trim($desc);
    }
    $desc = joe_get('seoDesc') ?: Helper::options()->description;
    return trim($desc);
}

/**
 * 规范链接 canonical
 */
function joe_seo_canonical()
{
    $archive = Typecho_Widget::widget('Widget_Archive');
    if ($archive->is('single') || $archive->is('page')) {
        return $archive->permalink;
    }
    return rtrim(Helper::options()->siteUrl, '/') . htmlspecialchars($_SERVER['REQUEST_URI']);
}

/**
 * 输出 sitemap.xml
 */
function joe_sitemap_output()
{
    if (joe_get('sitemap') === '0') return;
    $uri = Typecho_Request::getInstance()->getRequestUri();
    // 去掉 query string
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path !== '/sitemap.xml') return;

    $options = Helper::options();
    $siteUrl = rtrim($options->siteUrl, '/');
    $db = Typecho_Db::get();

    // 查询所有已发布文章
    $posts = $db->fetchAll($db->select('cid', 'created', 'modified', 'slug', 'type')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type IN ?', ['post', 'page'])
        ->order('created', Typecho_Db::SORT_DESC));

    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";

    // 首页
    echo "  <url>\n    <loc>{$siteUrl}/</loc>\n    <lastmod>", date('c'), "</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

    foreach ($posts as $p) {
        // 用 Widget_Abstract 生成永久链接
        $t = new Typecho_Widget_Helper_Empty();
        $t->cid = $p['cid'];
        $t->slug = $p['slug'];
        $t->type = $p['type'];
        $t->created = $p['created'];
        try {
            $routed = Typecho_Router::url($p['type'], $t, $siteUrl);
        } catch (Exception $e) {
            $routed = $siteUrl . '/index.php/' . $p['cid'] . '.html';
        }
        echo "  <url>\n";
        echo "    <loc>", $routed, "</loc>\n";
        echo "    <lastmod>", date('c', $p['modified']), "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>", ($p['type'] === 'post' ? '0.8' : '0.6'), "</priority>\n";
        echo "  </url>\n";
    }

    echo '</urlset>';
    exit;
}
