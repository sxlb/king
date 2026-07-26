<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// PHP版本检测
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    if (class_exists('Widget_Notice')) {
        Widget_Notice::alloc()->set(_t('KingJoe主题需要PHP 7.4及以上版本，当前版本：' . PHP_VERSION), 'error');
    }
    return;
}

function themeInit($archive)
{
    // 安全响应头
    if (function_exists('joe_security_headers')) {
        joe_security_headers();
    }

    // 自动添加数据库字段
    if (function_exists('joe_install')) {
        joe_install();
    }

    // Sitemap 输出（必须在任何输出之前）
    if (function_exists('joe_sitemap_output')) {
        joe_sitemap_output();
    }

    // 点赞 AJAX 处理
    if (function_exists('joe_agree_handle')) {
        joe_agree_handle();
    }

    // 评论点赞 AJAX 处理
    if (function_exists('joe_comment_like_handle')) {
        joe_comment_like_handle();
    }

    // 时光机 AJAX 处理
    if (function_exists('joe_timeline_ajax')) {
        joe_timeline_ajax();
    }

    // 友链在线申请处理
    if (function_exists('joe_link_apply_handler')) {
        joe_link_apply_handler();
    }

    // 百度推送 AJAX 处理
    if (function_exists('joe_baidu_push_ajax')) {
        joe_baidu_push_ajax();
    }

    // 文章内容过滤
    if ($archive->is('single')) {
        // 链接新窗口
        $archive->content = preg_replace('/<a\b(?![^>]*target=)/i', '<a target="_blank" rel="noopener noreferrer"', (string) $archive->content);
        // 视频短代码
        $archive->content = joe_video_shortcode($archive->content);
        // 蓝凑云短代码
        $archive->content = joe_lanzou_shortcode($archive->content);
        // 广告短代码
        $archive->content = joe_ad_shortcode($archive->content);
        // 增强短代码
        $archive->content = joe_tips_shortcode($archive->content);
        $archive->content = joe_collapse_shortcode($archive->content);
        $archive->content = joe_btn_shortcode($archive->content);
        $archive->content = joe_tabs_shortcode($archive->content);
        $archive->content = joe_steps_shortcode($archive->content);
        $archive->content = joe_ruby_shortcode($archive->content);
        $archive->content = joe_diff_shortcode($archive->content);
        // 排版增强短代码
        $archive->content = joe_link_card_shortcode($archive->content);
        $archive->content = joe_progress_shortcode($archive->content);
        $archive->content = joe_post_ref_shortcode($archive->content);
        $archive->content = joe_highlight_shortcode($archive->content);
        $archive->content = joe_keyboard_shortcode($archive->content);
        // 内容顶底部广告
        $archive->content = joe_content_ad_insert($archive->content);
        // 回复可见
        $archive->content = joe_reply_visible($archive->content, $archive);
        // [toc] 短代码
        $archive->content = joe_toc_shortcode($archive->content);
        // 图片灯箱：给文章图片加 data-lightbox 属性
        if (joe_get('imageLightbox') === '1') {
            $archive->content = preg_replace_callback('/<img([^>]+)>/i', function ($m) {
                $attrs = $m[1];
                if (strpos($attrs, 'data-lightbox') !== false) return $m[0];
                // 提取 src 和 alt
                preg_match('/src=([\'"])([^\'"]+)\1/i', $attrs, $sm);
                $src = !empty($sm[2]) ? $sm[2] : '';
                preg_match('/alt=([\'"])([^\'"]*)\1/i', $attrs, $am);
                $alt = !empty($am[2]) ? $am[2] : '';
                return '<img' . $attrs . ' data-lightbox-item="1" data-src="' . htmlspecialchars($src) . '" data-caption="' . htmlspecialchars($alt) . '">';
            }, $archive->content);
        }
    }

    // 文章阅读量统计
    joe_track_view($archive);

    // 评论内容 XSS 过滤钩子
    Typecho_Plugin::factory('Widget_Feedback')->comment = 'joe_comment_filter';

    // 评论成功后记录已评论 cookie（用于回复可见）+ 私密评论标记 + 邮件通知
    Typecho_Plugin::factory('Widget_Feedback')->finishComment = function ($comment, $post) {
        joe_mark_commented($comment, $post);
        joe_set_private_comment($comment);
        joe_comment_notify($comment, $post);
        return $comment;
    };

    // 文章发布推送（百度+必应合并到一个回调）
    Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = function ($cid, $contents) {
        if (empty($contents['cid'])) return;
        joe_baidu_push($contents['cid'], true);
        joe_bing_push($contents['cid']);
    };

    // RSS 全文输出
    Typecho_Plugin::factory('Widget_Archive')->beforeRender = function ($archive) {
        if ($archive->is('feed')) {
            Typecho_Plugin::factory('Widget_Archive')->excerpt = function () {};
            Typecho_Plugin::factory('Widget_Archive')->content = function () {};
            // 通过 contentEx 过滤
            Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = 'joe_rss_full_content';
        }
    };

    // 图片 alt 自动补全
    Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = function ($content, $archive) {
        $content = joe_fix_img_alt($content);
        $content = joe_anti_hotlink($content);
        return $content;
    };

    // 后台编辑器增强
    Typecho_Plugin::factory('admin/header.php')->header = 'joe_admin_editor_enhance';
}

/**
 * 主题配置项 */
function themeConfig($form)
{
    $logoText = new Typecho_Widget_Helper_Form_Element_Text('logoText', null, 'KingJoe', _t('站点 Logo 文字'), _t('显示在顶部导航左侧的文字 Logo'));
    $form->addInput($logoText);

    // ---- 主题色 ----
    $primaryColor = new Typecho_Widget_Helper_Form_Element_Text('primaryColor', null, '#5b6cff', _t('主题主色'), _t('主题强调色，按钮/链接/进度条等元素的主色，支持 #RRGGBB'));
    $form->addInput($primaryColor);

    // ---- Pjax 无刷新加载 ----
    $pjaxToggle = new Typecho_Widget_Helper_Form_Element_Radio('pjaxToggle',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('Pjax 无刷新加载'), _t('开启后页面切换无需整页刷新，保持播放器状态不中断。注意：如果主题有自定义 JS 请确保兼容 Pjax 事件'));
    $form->addInput($pjaxToggle);

    // ---- 登录页 ----
    $loginBg = new Typecho_Widget_Helper_Form_Element_Text('loginBg', null, '', _t('登录页背景图'), _t('自定义登录页面背景图 URL，留空使用默认渐变背景'));
    $form->addInput($loginBg);

    $accentColor = new Typecho_Widget_Helper_Form_Element_Text('accentColor', null, '#2bd4c8', _t('主题辅色'), _t('辅助色，用于渐变、标签等装饰元素'));
    $form->addInput($accentColor);

    $radiusBase = new Typecho_Widget_Helper_Form_Element_Text('radiusBase', null, '10px', _t('基础圆角'), _t('卡片/按钮等通用圆角，例如：10px / 12px'));
    $form->addInput($radiusBase);

    $radiusLg = new Typecho_Widget_Helper_Form_Element_Text('radiusLg', null, '16px', _t('大圆角'), _t('大卡片/弹层等大元素圆角'));
    $form->addInput($radiusLg);

    $shadowCard = new Typecho_Widget_Helper_Form_Element_Text('shadowCard', null, '0 2px 12px rgba(0,0,0,.06)', _t('卡片阴影'), _t('普通卡片的阴影样式'));
    $form->addInput($shadowCard);

    $favicon = new Typecho_Widget_Helper_Form_Element_Text('favicon', null, '', _t('Favicon 地址'), _t('留空则使用默认'));
    $form->addInput($favicon);

    $navHtml = new Typecho_Widget_Helper_Form_Element_Textarea('navHtml', null,
        "<a href=\"/\">首页</a>\n<a href=\"/about.html\">关于</a>\n<a href=\"/links.html\">友链</a>",
        _t('自定义导航链接（HTML）'), _t('每行一个 &lt;a&gt; 标签'));
    $form->addInput($navHtml);

    $bannerTitle = new Typecho_Widget_Helper_Form_Element_Text('bannerTitle', null, 'Hello, KingJoe', _t('首页 Banner 标题'), _t('留空则不显示 Banner'));
    $form->addInput($bannerTitle);

    $bannerDesc = new Typecho_Widget_Helper_Form_Element_Text('bannerDesc', null, '一款仿 Joe 风格的 Typecho 主题，简洁现代，支持暗黑模式', _t('首页 Banner 描述'));
    $form->addInput($bannerDesc);

    $bannerBtnText = new Typecho_Widget_Helper_Form_Element_Text('bannerBtnText', null, '开始阅读', _t('Banner 按钮文字'), _t('留空则不显示按钮'));
    $form->addInput($bannerBtnText);

    $bannerBtnLink = new Typecho_Widget_Helper_Form_Element_Text('bannerBtnLink', null, '#main', _t('Banner 按钮链接'));
    $form->addInput($bannerBtnLink);

    $authorName = new Typecho_Widget_Helper_Form_Element_Text('authorName', null, 'King', _t('侧边栏作者名称'));
    $form->addInput($authorName);

    $authorAvatar = new Typecho_Widget_Helper_Form_Element_Text('authorAvatar', null, '', _t('侧边栏作者头像 URL'), _t('留空使用首字母'));
    $form->addInput($authorAvatar);

    $authorDesc = new Typecho_Widget_Helper_Form_Element_Text('authorDesc', null, '热爱代码与设计，记录生活与技术。', _t('侧边栏作者描述'));
    $form->addInput($authorDesc);

    $authorSocial = new Typecho_Widget_Helper_Form_Element_Textarea('authorSocial', null,
        "github|https://github.com/\nmail|mailto:king@example.com",
        _t('侧边栏作者社交链接'), _t('格式：标识|URL，每行一条。支持：github / mail / rss / weibo / twitter'));
    $form->addInput($authorSocial);

    $defaultThumb = new Typecho_Widget_Helper_Form_Element_Text('defaultThumb', null, '', _t('默认缩略图 URL'), _t('文章没有图片时使用，留空则显示首字母占位'));
    $form->addInput($defaultThumb);

    $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', null, '', _t('备案号'), _t('底部显示，如：京ICP备XXXX号'));
    $form->addInput($icp);

    $customCss = new Typecho_Widget_Helper_Form_Element_Textarea('customCss', null, '', _t('自定义 CSS'), _t('附加到主题样式末尾'));
    $form->addInput($customCss);

    $customJs = new Typecho_Widget_Helper_Form_Element_Textarea('customJs', null, '', _t('自定义 JS'), _t('页脚自定义 JS 代码，无需 &lt;script&gt; 标签'));
    $form->addInput($customJs);

    $defaultDark = new Typecho_Widget_Helper_Form_Element_Radio('defaultDark',
        ['0' => _t('否'), '1' => _t('是')], '0', _t('默认暗黑模式'), _t('开启后页面默认进入暗黑模式（访客仍可手动切换，并以本地存储优先）'));
    $form->addInput($defaultDark);

    $codeHighlight = new Typecho_Widget_Helper_Form_Element_Radio('codeHighlight',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('代码高亮（Prism.js）'), _t('在文章/独立页面的代码块启用 Prism.js 高亮，自适应明暗主题'));
    $form->addInput($codeHighlight);

    $codeLineNumbers = new Typecho_Widget_Helper_Form_Element_Radio('codeLineNumbers',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('代码行号'), _t('配合代码高亮使用，仅文章页生效'));
    $form->addInput($codeLineNumbers);

    $linksData = new Typecho_Widget_Helper_Form_Element_Textarea('linksData', null,
        "友情站点\n".
        "Joe | https://blog.lete114.top | https://blog.lete114.top/usr/themes/Joe/assets/img/favicon.ico | Joe 主题作者\n".
        "Typecho | https://typecho.org | https://typecho.org/favicon.ico | Typecho 官方\n".
        "\n".
        "工具导航\n".
        "MDN | https://developer.mozilla.org | | Web 开发文档\n".
        "GitHub | https://github.com | | 全球最大代码托管平台",
        _t('友链数据'),
        _t('在「新建独立页面 → 自定义模板」选择「友链」后生效。格式如下：第一行分组名；其后每行「站点名 | URL | 头像(可空) | 描述(可空)」；空行分隔不同分组'));
    $form->addInput($linksData);

    $randomCover = new Typecho_Widget_Helper_Form_Element_Radio('randomCover',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('随机图床'), _t('文章未设置缩略图时，使用随机图床作为封面。基于文章 cid 作种子，保证同一篇文章每次加载图片相同'));
    $form->addInput($randomCover);

    $randomCoverApi = new Typecho_Widget_Helper_Form_Element_Text('randomCoverApi', null,
        'https://picsum.photos/seed/{seed}/800/450',
        _t('随机图床 API'),
        _t('支持占位符：{seed}（文章 cid 或标题哈希）、{w}（宽）、{h}（高）。推荐：Picsum / Unsplash Source'));
    $form->addInput($randomCoverApi);

    $lazyload = new Typecho_Widget_Helper_Form_Element_Radio('lazyload',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('图片懒加载占位'), _t('文章列表/封面图加载前显示占位图，加载完成后淡入。开启后依赖 JS，原生 loading=lazy 仍保留作回退'));
    $form->addInput($lazyload);

    $readingProgress = new Typecho_Widget_Helper_Form_Element_Radio('readingProgress',
        ['0' => _t('关闭'), '1' => _t('顶部进度条'), '2' => _t('右下角浮动按钮')], '1', _t('阅读进度'), _t('文章页显示阅读进度条。顶部进度条：页面顶部彩色进度条；右下角浮动：圆形进度按钮'));
    $form->addInput($readingProgress);

    $readingTime = new Typecho_Widget_Helper_Form_Element_Radio('readingTime',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('阅读时长'), _t('文章标题下显示预计阅读时长（按 400 字/分钟估算）'));
    $form->addInput($readingTime);

    $shareBtn = new Typecho_Widget_Helper_Form_Element_Radio('shareBtn',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('文章分享按钮'), _t('文章末尾显示分享到微信/微博/复制链接按钮'));
    $form->addInput($shareBtn);

    // ---- SEO 优化 ----
    $seoTitle = new Typecho_Widget_Helper_Form_Element_Text('seoTitle', null, '', _t('首页 SEO 标题'), _t('留空则使用站点标题。格式示例：博客名 - 专注于xxx'));
    $form->addInput($seoTitle);

    $seoDesc = new Typecho_Widget_Helper_Form_Element_Text('seoDesc', null, '', _t('站点 SEO 描述'), _t('显示在搜索引擎结果中的站点描述，150 字以内'));
    $form->addInput($seoDesc);

    $seoKeywords = new Typecho_Widget_Helper_Form_Element_Text('seoKeywords', null, '', _t('站点关键词'), _t('多个关键词用英文逗号分隔'));
    $form->addInput($seoKeywords);

    $jsonLd = new Typecho_Widget_Helper_Form_Element_Radio('jsonLd',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('结构化数据（JSON-LD）'), _t('输出 Article / WebSite 结构化数据，帮助搜索引擎理解内容'));
    $form->addInput($jsonLd);

    $sitemap = new Typecho_Widget_Helper_Form_Element_Radio('sitemap',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('站点地图 Sitemap'), _t('访问 域名/sitemap.xml 即可获取，提交给搜索引擎可加快收录'));
    $form->addInput($sitemap);

    // ---- 安全加固 ----
    $xssFilter = new Typecho_Widget_Helper_Form_Element_Radio('xssFilter',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('评论内容 XSS 过滤'), _t('对访客评论内容进行 HTML 标签和危险属性过滤，防范 XSS 攻击'));
    $form->addInput($xssFilter);

    // ---- 回复可见 ----
    $replyVisible = new Typecho_Widget_Helper_Form_Element_Radio('replyVisible',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('回复可见功能'), _t('在文章中使用 [reply]内容[/reply]，访客评论后可见隐藏内容'));
    $form->addInput($replyVisible);

    // ---- 视频功能 ----
    $videoPlayer = new Typecho_Widget_Helper_Form_Element_Radio('videoPlayer',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('文章视频播放器'), _t('支持 [video]MP4地址[/video]、[bilibili]BV号[/bilibili]、[youtube]视频ID[/youtube] 快捷标签'));
    $form->addInput($videoPlayer);

    // ---- 百度推送 ----
    $baiduPush = new Typecho_Widget_Helper_Form_Element_Radio('baiduPush',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('百度自动推送'), _t('文章发布时自动推送到百度收录，需填写下方 token'));
    $form->addInput($baiduPush);

    $baiduToken = new Typecho_Widget_Helper_Form_Element_Text('baiduToken', null, '', _t('百度推送 Token'), _t('在百度搜索资源平台获取的推送 token'));
    $form->addInput($baiduToken);

    // ---- 文章点赞 ----
    $agreeBtn = new Typecho_Widget_Helper_Form_Element_Radio('agreeBtn',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('文章点赞按钮'), _t('文章末尾显示点赞按钮，每篇文章同一 IP 24 小时内只能点赞一次'));
    $form->addInput($agreeBtn);

    // ---- 文章过期提醒 ----
    $overdueDays = new Typecho_Widget_Helper_Form_Element_Text('overdueDays', null, '', _t('文章过期提醒天数'), _t('文章最后修改超过 N 天，在文章顶部显示过期提醒。留空或 0 表示关闭'));
    $form->addInput($overdueDays);

    // ---- 相关文章 ----
    $relatedPosts = new Typecho_Widget_Helper_Form_Element_Radio('relatedPosts',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('相关文章推荐'), _t('文章底部显示同分类相关文章推荐'));
    $form->addInput($relatedPosts);

    $relatedNum = new Typecho_Widget_Helper_Form_Element_Text('relatedNum', null, '6', _t('相关文章数量'), _t('相关文章推荐显示的数量，默认 6 篇'));
    $form->addInput($relatedNum);

    // ---- 图片灯箱 ----
    $imageLightbox = new Typecho_Widget_Helper_Form_Element_Radio('imageLightbox',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('图片灯箱'), _t('点击文章内图片可放大查看，支持左右切换和 ESC 关闭'));
    $form->addInput($imageLightbox);

    // ---- 鼠标特效 ----
    $cursorEffect = new Typecho_Widget_Helper_Form_Element_Select('cursorEffect',
        ['off' => _t('关闭'), 'click' => _t('点击爱心'), 'text' => _t('点击文字'), 'particle' => _t('粒子跟随')], 'off', _t('鼠标特效'), _t('选择一种鼠标交互特效，提升页面趣味性'));
    $form->addInput($cursorEffect);

    // ---- 底部自定义 ----
    $footerLeft = new Typecho_Widget_Helper_Form_Element_Text('footerLeft', null, '', _t('底部左侧文字'), _t('页脚左侧显示的内容，支持 HTML。留空使用默认'));
    $form->addInput($footerLeft);

    $footerRight = new Typecho_Widget_Helper_Form_Element_Text('footerRight', null, '', _t('底部右侧文字'), _t('页脚右侧显示的内容，支持 HTML。留空使用默认'));
    $form->addInput($footerRight);

    // ---- 页脚社交图标 ----
    $footerSocial = new Typecho_Widget_Helper_Form_Element_Textarea('footerSocial', null, '', _t('页脚社交图标'), _t('格式：图标名|链接地址，每行一个。图标名可选：github/twitter/weibo/qq/wechat/email/rss/bilibili'));
    $form->addInput($footerSocial);

    // ---- CDN 资源切换 ----
    $cdnUrl = new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', null, '', _t('静态资源 CDN 地址'), _t('将主题静态资源（CSS/JS/图片）加载到指定 CDN 域名。留空则使用本地地址。格式如：https://cdn.example.com/usr/themes/KingJoe'));
    $form->addInput($cdnUrl);

    $gravatarCdn = new Typecho_Widget_Helper_Form_Element_Select('gravatarCdn',
        ['default' => _t('官方默认'), 'https://gravatar.loli.net/avatar/' => _t('Loli'), 'https://cdn.v2ex.com/gravatar/' => _t('V2EX'), 'https://dn-qiniu-avatar.qbox.me/avatar/' => _t('七牛'), 'https://sdn.geekzu.org/avatar/' => _t('极客族')],
        'default', _t('Gravatar 头像源'), _t('选择 Gravatar 头像的 CDN 加速源，国内服务器建议更换'));
    $form->addInput($gravatarCdn);

    // ---- 文章顶置 ----
    $stickyCids = new Typecho_Widget_Helper_Form_Element_Text('stickyCids', null, '', _t('置顶文章 CID'), _t('首页置顶的文章 ID，多个用英文逗号分隔，如：1,3,5。留空关闭置顶'));
    $form->addInput($stickyCids);

    // ---- 页面加载动画 ----
    $pageLoader = new Typecho_Widget_Helper_Form_Element_Radio('pageLoader',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('页面加载动画'), _t('页面顶部加载进度条，提升高级感'));
    $form->addInput($pageLoader);

    // ---- 打赏设置 ----
    $donateQr = new Typecho_Widget_Helper_Form_Element_Radio('donateQr',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('打赏功能'), _t('文章底部显示打赏按钮，需上传微信/支付宝二维码'));
    $form->addInput($donateQr);

    $donateWechat = new Typecho_Widget_Helper_Form_Element_Text('donateWechat', null, '', _t('微信收款码 URL'), _t('上传微信收款二维码图片地址'));
    $form->addInput($donateWechat);

    $donateAlipay = new Typecho_Widget_Helper_Form_Element_Text('donateAlipay', null, '', _t('支付宝收款码 URL'), _t('上传支付宝收款二维码图片地址'));
    $form->addInput($donateAlipay);

    // ---- 顶部公告栏 ----
    $noticeBar = new Typecho_Widget_Helper_Form_Element_Textarea('noticeBar', null, '', _t('全站公告'), _t('显示在页面顶部的公告内容，支持 HTML。留空不显示'));
    $form->addInput($noticeBar);

    // ---- 无限滚动 ----
    $infiniteScroll = new Typecho_Widget_Helper_Form_Element_Radio('infiniteScroll',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('首页无限滚动'), _t('滚动到底部自动加载下一页文章'));
    $form->addInput($infiniteScroll);

    // ---- 首页轮播图 ----
    $carouselSlides = new Typecho_Widget_Helper_Form_Element_Textarea('carouselSlides', null, '', _t('首页轮播图'), _t('格式：图片URL|链接|标题，每行一个。留空不显示。支持选择文章缩略图作为图片'));
    $form->addInput($carouselSlides);

    // ---- 首页大屏图片 ----
    $heroImage = new Typecho_Widget_Helper_Form_Element_Text('heroImage', null, '', _t('首页大屏图片'), _t('首页顶部大图背景 URL，留空不启用'));
    $form->addInput($heroImage);

    $heroTitle = new Typecho_Widget_Helper_Form_Element_Text('heroTitle', null, '', _t('大屏标题'), _t('大屏图片上的标题文字'));
    $form->addInput($heroTitle);

    $heroSubtitle = new Typecho_Widget_Helper_Form_Element_Text('heroSubtitle', null, '', _t('大屏副标题'), _t('大屏图片上的副标题文字'));
    $form->addInput($heroSubtitle);

    // ---- 全局音乐播放器 ----
    $musicPlayer = new Typecho_Widget_Helper_Form_Element_Radio('musicPlayer',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('全局音乐播放器'), _t('页面底部固定音乐播放器，支持网易云等平台'));
    $form->addInput($musicPlayer);

    $musicId = new Typecho_Widget_Helper_Form_Element_Text('musicId', null, '', _t('歌单/歌曲 ID'), _t('网易云歌单或歌曲 ID'));
    $form->addInput($musicId);

    $musicServer = new Typecho_Widget_Helper_Form_Element_Radio('musicServer',
        ['netease' => _t('网易云'), 'tencent' => _t('QQ音乐'), 'kugou' => _t('酷狗')],
        'netease', _t('音乐平台'), _t('选择音乐来源平台'));
    $form->addInput($musicServer);

    $musicType = new Typecho_Widget_Helper_Form_Element_Radio('musicType',
        ['playlist' => _t('歌单'), 'song' => _t('单曲')],
        'playlist', _t('播放类型'), _t('歌单或单曲'));
    $form->addInput($musicType);

    $musicAutoPlay = new Typecho_Widget_Helper_Form_Element_Radio('musicAutoPlay',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('自动播放'), _t('部分浏览器可能禁用自动播放'));
    $form->addInput($musicAutoPlay);

    // ---- 那年今日 ----
    $onThisDay = new Typecho_Widget_Helper_Form_Element_Radio('onThisDay',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('那年今日'), _t('侧边栏展示历史上今天的文章'));
    $form->addInput($onThisDay);

    // ---- 随机一言 ----
    $hitokoto = new Typecho_Widget_Helper_Form_Element_Radio('hitokoto',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('随机一言'), _t('调用一言 API 在侧边栏展示随机句子'));
    $form->addInput($hitokoto);

    // ---- 全站飘落特效 ----
    $fallingEffect = new Typecho_Widget_Helper_Form_Element_Radio('fallingEffect',
        ['off' => _t('关闭'), 'snow' => _t('雪花'), 'petal' => _t('花瓣'), 'star' => _t('星星')],
        'off', _t('全站飘落特效'), _t('页面飘落装饰特效'));
    $form->addInput($fallingEffect);

    // ---- 全局灰色模式 ----
    $grayMode = new Typecho_Widget_Helper_Form_Element_Radio('grayMode',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('全局灰色模式'), _t('适用于全国哀悼日等特殊纪念日'));
    $form->addInput($grayMode);

    // ---- Logo 扫光特效 ----
    $logoShine = new Typecho_Widget_Helper_Form_Element_Radio('logoShine',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('Logo 扫光特效'), _t('Logo 文字上的流光扫过动画'));
    $form->addInput($logoShine);

    // ---- 友链在线申请 ----
    $linkApply = new Typecho_Widget_Helper_Form_Element_Radio('linkApply',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('友链在线申请'), _t('允许访客在前端提交友链申请。需配置邮箱发送功能'));
    $form->addInput($linkApply);

    // ---- 底部鱼群跳跃 ----
    $fishEffect = new Typecho_Widget_Helper_Form_Element_Radio('fishEffect',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('底部鱼群特效'), _t('页面底部灵动鱼群跳跃动画'));
    $form->addInput($fishEffect);

    // ---- SSL安全认证图标 ----
    $sslBadge = new Typecho_Widget_Helper_Form_Element_Radio('sslBadge',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('SSL安全认证图标'), _t('右下角显示SSL安全认证标识'));
    $form->addInput($sslBadge);

    // ---- 文章底部提示 ----
    $postFooterTip = new Typecho_Widget_Helper_Form_Element_Text('postFooterTip', null, '', _t('文章底部提示'), _t('文章末尾显示的自定义提示文字，支持HTML'));
    $form->addInput($postFooterTip);

    // ---- 友链随机排序 ----
    $linksRandom = new Typecho_Widget_Helper_Form_Element_Radio('linksRandom',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('友链随机排序'), _t('每次加载友链页面时随机排列顺序'));
    $form->addInput($linksRandom);

    // ---- 必应收录推送 ----
    $bingPush = new Typecho_Widget_Helper_Form_Element_Radio('bingPush',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('必应收录推送'), _t('文章发布时自动向Bing推送。需填写Bing API Key'));
    $form->addInput($bingPush);

    $bingApiKey = new Typecho_Widget_Helper_Form_Element_Text('bingApiKey', null, '', _t('Bing API Key'), _t('必应站长平台的API密钥'));
    $form->addInput($bingApiKey);

    // ---- 导航栏毛玻璃 ----
    $navFrosted = new Typecho_Widget_Helper_Form_Element_Radio('navFrosted',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('导航栏毛玻璃'), _t('PC端导航栏毛玻璃半透明效果'));
    $form->addInput($navFrosted);

    // ---- 反图片防盗链 ----
    $antiHotlink = new Typecho_Widget_Helper_Form_Element_Radio('antiHotlink',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('反图片防盗链'), _t('为文章图片添加 referrerpolicy 防止被第三方引用'));
    $form->addInput($antiHotlink);

    // ---- 暗黑模式独立Logo ----
    $darkLogo = new Typecho_Widget_Helper_Form_Element_Text('darkLogo', null, '', _t('暗黑模式Logo'), _t('暗黑模式下显示的Logo图片URL，留空则使用默认Logo'));
    $form->addInput($darkLogo);

    // ---- 首页文章筛选 ----
    $homeFilter = new Typecho_Widget_Helper_Form_Element_Radio('homeFilter',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('首页文章筛选'), _t('首页显示 最新/热门/随机 筛选选项卡'));
    $form->addInput($homeFilter);

    $homeHotCount = new Typecho_Widget_Helper_Form_Element_Text('homeHotCount', null, '6', _t('首页热门显示数'), _t('首页筛选热门时显示的文章数量'));
    $form->addInput($homeHotCount);

    // ---- 百度收录检测 ----
    $baiduCheck = new Typecho_Widget_Helper_Form_Element_Radio('baiduCheck',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('百度收录检测'), _t('文章页显示百度收录状态，未收录可手动提交'));
    $form->addInput($baiduCheck);

    // ---- 文章页顶部大图 ----
    $articleHeroImage = new Typecho_Widget_Helper_Form_Element_Radio('articleHeroImage',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('文章顶部大图'), _t('文章页顶部使用缩略图作为背景大图'));
    $form->addInput($articleHeroImage);

    // ---- 文章标题居中 ----
    $titleCenter = new Typecho_Widget_Helper_Form_Element_Radio('titleCenter',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('文章标题居中'), _t('文章详情页标题居中显示'));
    $form->addInput($titleCenter);

    // ---- 动态星空背景 ----
    $starryBg = new Typecho_Widget_Helper_Form_Element_Radio('starryBg',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('动态星空背景'), _t('全站Canvas动态星空背景'));
    $form->addInput($starryBg);

    // ---- 页面切换过渡动画 ----
    $pageTransition = new Typecho_Widget_Helper_Form_Element_Radio('pageTransition',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('页面过渡动画'), _t('页面切换时的淡入淡出效果'));
    $form->addInput($pageTransition);

    // ---- 文章导读卡片 ----
    $readingGuideCard = new Typecho_Widget_Helper_Form_Element_Radio('readingGuideCard',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('文章导读卡片'), _t('右下角显示文章目录浮动卡片'));
    $form->addInput($readingGuideCard);

    // ---- 移动端侧边栏壁纸 ----
    $sidebarWallpaper = new Typecho_Widget_Helper_Form_Element_Text('sidebarWallpaper', null, '', _t('移动端侧边栏壁纸'), _t('移动端侧边栏顶部背景图URL'));
    $form->addInput($sidebarWallpaper);

    // ---- 轮播图新窗口 ----
    $carouselNewTab = new Typecho_Widget_Helper_Form_Element_Radio('carouselNewTab',
        ['0' => _t('当前窗口'), '1' => _t('新窗口')], '0', _t('轮播图打开方式'), _t('轮播图点击链接的打开方式'));
    $form->addInput($carouselNewTab);

    // ---- 全站背景壁纸 ----
    $bgWallpaper = new Typecho_Widget_Helper_Form_Element_Text('bgWallpaper', null, '', _t('全站背景壁纸'), _t('网站全局背景图片URL，留空不使用'));
    $form->addInput($bgWallpaper);

    $bgWallpaperOpacity = new Typecho_Widget_Helper_Form_Element_Text('bgWallpaperOpacity', null, '0.08', _t('背景壁纸透明度'), _t('0.01-1.0，越小越透明'));
    $form->addInput($bgWallpaperOpacity);

    // ---- QQ微信防红 ----
    $antiRed = new Typecho_Widget_Helper_Form_Element_Radio('antiRed',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('QQ/微信防红跳转'), _t('QQ和微信内置浏览器打开时提示用系统浏览器打开'));
    $form->addInput($antiRed);

    // ---- 文章内广告 ----
    $adContentTop = new Typecho_Widget_Helper_Form_Element_Textarea('adContentTop', null, '', _t('文章顶部广告'), _t('插入在文章内容顶部的广告代码，支持HTML/JS'));
    $form->addInput($adContentTop);

    $adContentBottom = new Typecho_Widget_Helper_Form_Element_Textarea('adContentBottom', null, '', _t('文章底部广告'), _t('插入在文章内容底部的广告代码，支持HTML/JS'));
    $form->addInput($adContentBottom);

    // ---- 检测更新 ----
    $updateCheck = new Typecho_Widget_Helper_Form_Element_Radio('updateCheck',
        ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('检测更新'), _t('在主题设置页显示更新检测按钮'));
    $form->addInput($updateCheck);

    // ---- 文章列表布局 ----
    $listLayout = new Typecho_Widget_Helper_Form_Element_Radio('listLayout',
        ['card' => _t('卡片布局'), 'timeline' => _t('时间线布局'), 'masonry' => _t('瀑布流布局')],
        'card', _t('首页列表布局'), _t('文章列表的展示样式'));
    $form->addInput($listLayout);

    // ---- 置顶文章去重 ----
    $stickyDedup = new Typecho_Widget_Helper_Form_Element_Radio('stickyDedup',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('置顶文章去重'), _t('置顶文章不会在普通列表中重复出现'));
    $form->addInput($stickyDedup);

    // ---- 移动端热门文章数量 ----
    $mobileHotCount = new Typecho_Widget_Helper_Form_Element_Text('mobileHotCount', null, '4', _t('移动端热门显示数'), _t('移动端侧边栏热门文章显示数量'));
    $form->addInput($mobileHotCount);

    // ---- 文章摘要清理 ----
    $excerptClean = new Typecho_Widget_Helper_Form_Element_Radio('excerptClean',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('摘要过滤短代码'), _t('文章摘要中自动过滤 [lanzou]/[ad] 等短代码标签'));
    $form->addInput($excerptClean);

    // ---- 快捷键系统 ----
    $keyboardShortcuts = new Typecho_Widget_Helper_Form_Element_Radio('keyboardShortcuts',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('全站快捷键'), _t('J/K切换文章 ←→方向键导航 S搜索 D暗黑模式 T回顶 C目录 ?查看帮助'));
    $form->addInput($keyboardShortcuts);

    // ---- 私密评论 ----
    $privateComment = new Typecho_Widget_Helper_Form_Element_Radio('privateComment',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('私密评论'), _t('评论时可选私密模式，仅博主可见'));
    $form->addInput($privateComment);

    // ---- 评论点赞 ----
    $commentLike = new Typecho_Widget_Helper_Form_Element_Radio('commentLike',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('评论点赞'), _t('支持对评论点赞，24小时内同一IP限点一次'));
    $form->addInput($commentLike);

    // ---- 评论邮件通知 ----
    $commentNotify = new Typecho_Widget_Helper_Form_Element_Radio('commentNotify',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('评论回复邮件通知'), _t('当评论被回复时，自动发送邮件通知原评论者。需服务器支持 mail() 函数'));
    $form->addInput($commentNotify);

    // ---- SMTP 邮件配置 ----
    $mailHost = new Typecho_Widget_Helper_Form_Element_Text('mailHost', null, '', _t('SMTP 服务器地址'), _t('邮件发送服务器地址，如 smtp.qq.com'));
    $form->addInput($mailHost);

    $mailPort = new Typecho_Widget_Helper_Form_Element_Text('mailPort', null, '465', _t('SMTP 端口'), _t('通常为 465（SSL）或 587（TLS）'));
    $form->addInput($mailPort);

    $mailUser = new Typecho_Widget_Helper_Form_Element_Text('mailUser', null, '', _t('SMTP 登录账号'), _t('完整的邮箱地址，如 your@qq.com'));
    $form->addInput($mailUser);

    $mailPass = new Typecho_Widget_Helper_Form_Element_Text('mailPass', null, '', _t('SMTP 授权码/密码'), _t('QQ邮箱等需使用授权码而非登录密码'));
    $form->addInput($mailPass);

    // ---- 时光机 ----
    $timelineCat = new Typecho_Widget_Helper_Form_Element_Text('timelineCat', null, '', _t('时光机分类'), _t('时光机页面对应的分类缩略名（slug），留空则关闭该功能。需先创建独立页面选择"时光机"模板'));
    $form->addInput($timelineCat);

    $timelineTitle = new Typecho_Widget_Helper_Form_Element_Text('timelineTitle', null, '时光机', _t('时光机页面标题'), _t('显示在页面顶部的标题'));
    $form->addInput($timelineTitle);

    $timelineDesc = new Typecho_Widget_Helper_Form_Element_Text('timelineDesc', null, '记录生活的点滴瞬间', _t('时光机描述'), _t('标题下方的简短描述'));
    $form->addInput($timelineDesc);

    $timelinePageSize = new Typecho_Widget_Helper_Form_Element_Text('timelinePageSize', null, '10', _t('时光机每页条数'), _t('每次加载显示的数量'));
    $form->addInput($timelinePageSize);

    // ---- 豆瓣清单 ----
    $doubanId = new Typecho_Widget_Helper_Form_Element_Text('doubanId', null, '', _t('豆瓣用户ID/域名'), _t('你的豆瓣数字ID或个性域名，留空关闭。需先创建独立页面选择"豆瓣清单"模板'));
    $form->addInput($doubanId);

    $doubanTitle = new Typecho_Widget_Helper_Form_Element_Text('doubanTitle', null, '豆瓣清单', _t('豆瓣页面标题'), _t('显示在页面顶部的标题'));
    $form->addInput($doubanTitle);

    // ---- 移动端悬浮操作栏 ----
    $mobileActionBar = new Typecho_Widget_Helper_Form_Element_Radio('mobileActionBar',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('移动端悬浮操作栏'), _t('文章页底部固定操作栏（点赞/评论/分享/回顶），仅移动端显示'));
    $form->addInput($mobileActionBar);

    // ---- 字体缩放 ----
    $fontSizeAdjust = new Typecho_Widget_Helper_Form_Element_Radio('fontSizeAdjust',
        ['0' => _t('关闭'), '1' => _t('开启')], '1', _t('文章字体缩放'), _t('文章页支持读者调节正文字体大小（A⁻/A/A⁺）'));
    $form->addInput($fontSizeAdjust);

    // ---- 统计代码 ----
    $analyticsCode = new Typecho_Widget_Helper_Form_Element_Textarea('analyticsCode', null, '', _t('统计代码'), _t('Google Analytics、百度统计等，会自动插入到页脚 &lt;/body&gt; 前'));
    $form->addInput($analyticsCode);

    // ---- SSL认证图标 ----
    $sslIcon = new Typecho_Widget_Helper_Form_Element_Textarea('sslIcon', null, '', _t('SSL认证图标'), _t('页脚显示的安全认证图标HTML代码，如 TrustAsia/沃通等'));
    $form->addInput($sslIcon);

    // 显示更新检测结果
    if (joe_get('updateCheck') === '1' && function_exists('joe_check_update')) {
        joe_check_update();
    }
}

/**
 * 检测主题更新
 */
function joe_check_update()
{
    if (joe_get('updateCheck') !== '1') return;
    $current = '1.0.0';
    $updateUrl = 'https://raw.githubusercontent.com/example/kingjoe/main/version.json';
    $cacheKey = 'joe_update_check';
    $cache = Typecho_Widget::widget('Widget_Options')->{$cacheKey};
    $cacheTime = Typecho_Widget::widget('Widget_Options')->{$cacheKey . '_time'} ?? 0;
    if ($cache && (time() - $cacheTime) < 86400) {
        $data = json_decode($cache, true);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $res = @file_get_contents($updateUrl, false, $ctx);
        $data = $res ? json_decode($res, true) : null;
        try {
            $db = Typecho_Db::get();
            $db->query($db->insert('table.options')
                ->rows(['name' => $cacheKey, 'user' => 0, 'value' => $res ?: ''])
                ->onDuplicateKeyUpdate(['value' => $res ?: '']));
        } catch (Exception $e) {}
    }
    if (!$data || empty($data['version'])) return;
    if (version_compare($data['version'], $current, '>')) {
        echo '<div class="message notice" style="margin:12px 0"><strong>KingJoe 新版本 ' . joe_esc($data['version']) . ' 可用！</strong> ';
        if (!empty($data['url'])) {
            echo '<a href="' . joe_esc($data['url']) . '" target="_blank">前往下载</a>';
        }
        echo '</div>';
    } else {
        echo '<div class="message notice" style="margin:12px 0">当前 KingJoe ' . $current . ' 已是最新版本 ✓</div>';
    }
}
function joe_install()
{
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select()->from('table.contents')->page(1, 1));
    // PHP 8.x 兼容：fetchRow 可能返回 null
    if ($row === null || !is_array($row)) return;
    try {
        if (!array_key_exists('views', $row)) {
            $db->query("ALTER TABLE `{$prefix}contents` ADD `views` INT DEFAULT 0;");
        }
    } catch (Exception $e) {}
    try {
        if (!array_key_exists('agree', $row)) {
            $db->query("ALTER TABLE `{$prefix}contents` ADD `agree` INT DEFAULT 0;");
        }
    } catch (Exception $e) {}
}
