<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 获取文章缩略图 *
 * 优先级：自定义字段 thumb > 文章首图 > 默认缩略图 > 随机图床
 */
function joe_thumb($archive)
{
    $options = Helper::options();
    // 1. 自定义字段 thumb
    $thumb = $archive->fields->thumb;
    if ($thumb) return $thumb;
    // 2. 文章首图
    $content = (string) $archive->content;
    if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $content, $m)) {
        return $m[1];
    }
    // 3. 默认图
    if ($options->defaultThumb) return $options->defaultThumb;
    // 4. 随机图床（基于 cid 作种子，保证稳定）
    if ($options->randomCover === '1' && $options->randomCoverApi) {
        $seed = $archive->cid ?: substr(md5($archive->title), 0, 8);
        return strtr($options->randomCoverApi, [
            '{seed}' => $seed,
            '{w}'    => 800,
            '{h}'    => 450,
        ]);
    }
    return '';
}

function joe_has_thumb($archive)
{
    return joe_thumb($archive) !== '';
}

/**
 * 生成占位 SVG（data URI），用于图片懒加载前的占位 */
function joe_placeholder_svg($w = 800, $h = 450)
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">'
          . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
          . '<stop offset="0%" stop-color="#eef1f5"/>'
          . '<stop offset="100%" stop-color="#dde2ea"/>'
          . '</linearGradient></defs>'
          . '<rect width="100%" height="100%" fill="url(#g)"/>'
          . '<g fill="#a8b0bc" fill-opacity="0.6">'
          . '<circle cx="' . ($w/2) . '" cy="' . ($h/2 - 10) . '" r="14"/>'
          . '<path d="M' . ($w/2 - 28) . ' ' . ($h/2 + 28) . ' L' . ($w/2 - 8) . ' ' . ($h/2 + 8) . ' L' . ($w/2 + 4) . ' ' . ($h/2 + 20) . ' L' . ($w/2 + 16) . ' ' . ($h/2 + 8) . ' L' . ($w/2 + 28) . ' ' . ($h/2 + 28) . ' Z"/>'
          . '</g></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * 输出带懒加载占位的 <img>
 *
 * @param string $src       真实图片地址
 * @param string $alt       alt 文本
 * @param string $class     额外 class
 * @param int    $w         占位宽度
 * @param int    $h         占位高度 */
function joe_lazy_img($src, $alt = '', $class = '', $w = 800, $h = 450)
{
    $options = Helper::options();
    $extra = '';
    if ($options->lazyload === '1') {
        $placeholder = joe_placeholder_svg($w, $h);
        $extra = ' data-src="' . htmlspecialchars($src) . '" src="' . $placeholder . '" data-lazy="1"';
    } else {
        $extra = ' src="' . htmlspecialchars($src) . '"';
    }
    // 骨架屏包裹
    $ratio = $h > 0 ? round(($h / $w) * 100, 2) : 56.25;
    return '<span class="joe-lazy-wrap" style="display:block;padding-bottom:' . $ratio . '%;position:relative">' .
           '<img' . $extra . ' alt="' . htmlspecialchars($alt) . '" class="' . $class . '" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">' .
           '</span>';
}

/**
 * 从文章内容中提取第一张图片（用于上下篇缩略图）
 */
function joe_neighbor_thumb($neighbor)
{
    if (empty($neighbor['text'])) return '';
    if (preg_match('/<img[^>]+src=([\'"])([^\'"]+)\1/i', $neighbor['text'], $m)) {
        return $m[2];
    }
    return '';
}
