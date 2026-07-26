<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// PHP版本检测
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    if (class_exists('Widget_Notice')) {
        Widget_Notice::alloc()->set(_t('KingJoe主题需要PHP 7.4及以上版本，当前版本：' . PHP_VERSION), 'error');
    }
    return;
}

/**
 * 主题初始化 */
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
 * 百度收录检测
 */
function joe_baidu_check($url)
{
    if (joe_get('baiduCheck') !== '1') return -1; // 未开启
    $cache_key = 'joe_baidu_check_' . md5($url);
    $cache = Typecho_Widget::widget('Widget_Options')->{$cache_key};
    if ($cache !== null) return (int)$cache;

    $checkUrl = 'https://www.baidu.com/s?wd=' . urlencode($url);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (compatible; BaiduCheck/1.0)\r\n",
            'timeout' => 5,
        ],
    ]);
    $html = @file_get_contents($checkUrl, false, $ctx);
    $indexed = 0;
    if ($html !== false) {
        // 检测页面中是否有搜索结果（排除安全验证页面）
        if (strpos($html, '百度安全验证') === false &&
            (strpos($html, 'class="result') !== false || strpos($html, 'class="c-abstract') !== false)) {
            $indexed = 1;
        }
    }
    // 缓存24小时
    try {
        $db = Typecho_Db::get();
        $db->query($db->insert('table.options')
            ->rows(['name' => $cache_key, 'user' => 0, 'value' => $indexed])
            ->onDuplicateKeyUpdate(['value' => $indexed]));
    } catch (Exception $e) {}
    return $indexed;
}

/**
 * 百度手动推送 AJAX 处理
 */
function joe_baidu_push_ajax()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $action = $_POST['action'] ?? '';
    if ($action !== 'joe_baidu_push') return;

    header('Content-Type: application/json');
    $token = trim(joe_get('baiduToken') ?: '');
    if (!$token) {
        echo json_encode(['code' => 0, 'msg' => '未配置百度推送Token'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $url = trim(strip_tags($_POST['url'] ?? ''));
    if (empty($url)) {
        echo json_encode(['code' => 0, 'msg' => 'URL不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $siteUrl = Helper::options()->siteUrl;
    $host = parse_url($siteUrl, PHP_URL_HOST);
    $api = 'http://data.zz.baidu.com/urls?site=' . urlencode($host) . '&token=' . urlencode($token);

    $fp = @fsockopen('data.zz.baidu.com', 80, $errno, $errstr, 3);
    if (!$fp) {
        echo json_encode(['code' => 0, 'msg' => '推送服务连接失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = $url;
    $path = '/urls?site=' . urlencode($host) . '&token=' . urlencode($token);
    $out = "POST {$path} HTTP/1.1\r\n";
    $out .= "Host: data.zz.baidu.com\r\n";
    $out .= "Content-Type: text/plain\r\n";
    $out .= "Content-Length: " . strlen($data) . "\r\n";
    $out .= "Connection: Close\r\n\r\n";
    $out .= $data;
    @fwrite($fp, $out);
    @fclose($fp);

    echo json_encode(['code' => 1, 'msg' => '已提交百度收录'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 百度主动推送（文章发布时调用）
 */
function joe_baidu_push($cid, $publish = true)
{
    if (joe_get('baiduPush') !== '1') return;
    $token = trim(joe_get('baiduToken') ?: '');
    if (!$token) return;

    $options = Helper::options();
    $siteUrl = rtrim($options->siteUrl, '/');

    try {
        $db = Typecho_Db::get();
        $row = $db->fetchRow($db->select('cid', 'slug', 'created', 'type')
            ->from('table.contents')
            ->where('cid = ?', $cid));
        if (!$row) return;
        $t = new Typecho_Widget_Helper_Empty();
        $t->cid = $row['cid'];
        $t->slug = $row['slug'];
        $t->type = $row['type'];
        $t->created = $row['created'];
        $url = Typecho_Router::url($row['type'], $t, $siteUrl);
    } catch (Exception $e) {
        $url = $siteUrl . '/index.php/' . $cid . '.html';
    }

    $api = 'http://data.zz.baidu.com/urls?site=' . urlencode(parse_url($siteUrl, PHP_URL_HOST)) . '&token=' . urlencode($token);
    if (!$publish) {
        $api = 'http://data.zz.baidu.com/del?site=' . urlencode(parse_url($siteUrl, PHP_URL_HOST)) . '&token=' . urlencode($token);
    }

    // 使用 fsockopen 避免 curl 依赖
    $host = 'data.zz.baidu.com';
    $path = parse_url($api, PHP_URL_PATH) . '?' . parse_url($api, PHP_URL_QUERY);
    $data = $url;

    $fp = @fsockopen($host, 80, $errno, $errstr, 3);
    if (!$fp) return;

    $out = "POST {$path} HTTP/1.1\r\n";
    $out .= "Host: {$host}\r\n";
    $out .= "Content-Type: text/plain\r\n";
    $out .= "Content-Length: " . strlen($data) . "\r\n";
    $out .= "Connection: Close\r\n\r\n";
    $out .= $data;
    @fwrite($fp, $out);
    @fclose($fp);
}

/**
 * 必应收录推送
 */
function joe_bing_push($cid)
{
    if (joe_get('bingPush') !== '1') return;
    $apiKey = trim(joe_get('bingApiKey') ?: '');
    if (!$apiKey) return;

    $siteUrl = rtrim(Helper::options()->siteUrl, '/');
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('cid', 'slug', 'created', 'type')
        ->from('table.contents')
        ->where('cid = ?', $cid));
    if (!$row) return;
    $t = new Typecho_Widget_Helper_Empty();
    $t->cid = $row['cid'];
    $t->slug = $row['slug'];
    $t->type = $row['type'];
    $t->created = $row['created'];
    try {
        $url = Typecho_Router::url($row['type'], $t, $siteUrl);
    } catch (Exception $e) {
        $url = $siteUrl . '/index.php/' . $cid . '.html';
    }

    $apiHost = parse_url($siteUrl, PHP_URL_HOST);
    $api = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl?site=' . urlencode($apiHost) . '&apikey=' . urlencode($apiKey);
    $body = json_encode(['siteUrl' => $apiHost, 'url' => $url]);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n",
            'content' => $body,
            'timeout' => 5,
        ],
    ]);
    @file_get_contents($api, false, $ctx);
}

/**
 * 反图片防盗链：给文章图片添加 referrerpolicy="no-referrer"
 */
function joe_anti_hotlink($content)
{
    if (joe_get('antiHotlink') !== '1') return $content;
    return preg_replace('/<img\b/i', '<img referrerpolicy="no-referrer"', $content);
}

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

/**
 * 后台编辑器增强 - 写文章体验优化
 */
function joe_admin_editor_enhance()
{
    echo '<style>
        /* ===== 编辑器整体布局 ===== */
        #write .wmd-container { max-width: 860px; margin: 0 auto; position: relative; }
        /* 标题区域美化 */
        #write #title {
            font-size: 1.5rem !important;
            padding: 14px 18px !important;
            border-radius: 8px !important;
            border: 2px solid #e2e8f0 !important;
            background: #fff !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
        }
        #write #title:focus {
            border-color: #5b6cff !important;
            box-shadow: 0 0 0 3px rgba(91,108,255,.12) !important;
            outline: none !important;
        }
        /* 全宽编辑区 */
        #write .typecho-post-option { max-width: 860px; margin: 0 auto; }
        #write .url-slug { max-width: 860px; margin: 0 auto; }
        #write .submit { max-width: 860px; margin: 16px auto 0; display: flex !important; align-items: center; gap: 12px; }
        /* 标签/分类美化 */
        #write .typecho-label { font-weight: 600; color: #334155; font-size: 13px; margin-bottom: 6px; }
        #write select, #write input[type="text"] {
            border-radius: 6px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 8px 12px !important;
            transition: border-color .2s !important;
        }
        #write select:focus, #write input[type="text"]:focus {
            border-color: #5b6cff !important;
            box-shadow: 0 0 0 3px rgba(91,108,255,.1) !important;
        }
        /* 编辑器工具栏 */
        .joe-editor-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            user-select: none;
        }
        .joe-editor-toolbar__group {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .joe-editor-toolbar__sep {
            width: 1px;
            height: 22px;
            background: #e2e8f0;
            margin: 0 6px;
        }
        .joe-editor-toolbar__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid transparent;
            border-radius: 6px;
            background: transparent;
            color: #475569;
            font-size: 15px;
            cursor: pointer;
            transition: all .15s ease;
            position: relative;
        }
        .joe-editor-toolbar__btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        .joe-editor-toolbar__btn:active {
            background: #cbd5e1;
        }
        .joe-editor-toolbar__btn[data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 110%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #fff;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 10;
        }
        .joe-editor-toolbar__btn svg {
            width: 16px;
            height: 16px;
            pointer-events: none;
        }
        /* 短代码按钮高亮 */
        .joe-editor-toolbar__btn.is-shortcode {
            color: #5b6cff;
            font-size: 12px;
            font-weight: 600;
            width: auto;
            padding: 0 10px;
        }
        .joe-editor-toolbar__btn.is-shortcode:hover {
            background: rgba(91,108,255,.1);
            color: #4a5ae5;
        }
        /* 预览按钮 */
        .joe-editor-toolbar__btn.is-preview {
            margin-left: auto;
            width: auto;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 500;
            gap: 4px;
        }
        .joe-editor-toolbar__btn.is-preview.is-active {
            background: #5b6cff;
            color: #fff;
        }
        /* 编辑器主体 */
        #write #text {
            font-family: "JetBrains Mono", "Fira Code", "SF Mono", Menlo, Consolas, monospace !important;
            font-size: 14px !important;
            line-height: 1.8 !important;
            padding: 16px !important;
            border-radius: 0 0 8px 8px !important;
            border: 2px solid #e2e8f0 !important;
            border-top: none !important;
            background: #fafbfc !important;
            min-height: 420px !important;
            tab-size: 4;
            resize: vertical;
        }
        #write #text:focus {
            border-color: #5b6cff !important;
            box-shadow: none !important;
            outline: none !important;
        }
        /* 预览区 */
        .joe-editor-preview {
            display: none;
            font-size: 15px;
            line-height: 1.8;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            background: #fff;
            min-height: 420px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .joe-editor-preview.is-show { display: block; }
        .joe-editor-preview h1, .joe-editor-preview h2, .joe-editor-preview h3 {
            margin: 16px 0 8px;
            color: #1e293b;
        }
        .joe-editor-preview h1 { font-size: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
        .joe-editor-preview h2 { font-size: 20px; }
        .joe-editor-preview h3 { font-size: 17px; }
        .joe-editor-preview p { margin: 8px 0; color: #334155; }
        .joe-editor-preview code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: "JetBrains Mono", "Fira Code", monospace;
            font-size: 13px;
            color: #e11d48;
        }
        .joe-editor-preview pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .joe-editor-preview pre code {
            background: none;
            color: inherit;
            padding: 0;
        }
        .joe-editor-preview blockquote {
            border-left: 4px solid #5b6cff;
            padding: 8px 16px;
            margin: 12px 0;
            background: #f8fafc;
            color: #475569;
            border-radius: 0 6px 6px 0;
        }
        .joe-editor-preview a { color: #5b6cff; }
        .joe-editor-preview img { max-width: 100%; border-radius: 6px; }
        .joe-editor-preview table { border-collapse: collapse; width: 100%; margin: 12px 0; }
        .joe-editor-preview th, .joe-editor-preview td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        .joe-editor-preview th { background: #f8fafc; font-weight: 600; }
        /* 发布按钮 */
        .submit .primary {
            background: #5b6cff !important;
            border-color: #5b6cff !important;
            border-radius: 8px !important;
            padding: 8px 24px !important;
            font-weight: 600 !important;
            transition: all .2s !important;
        }
        .submit .primary:hover {
            background: #4a5ae5 !important;
            box-shadow: 0 4px 12px rgba(91,108,255,.3) !important;
        }
        /* 暗色模式 */
        @media (prefers-color-scheme: dark) {
            #write #title {
                background: #1e293b !important;
                border-color: #334155 !important;
                color: #e2e8f0 !important;
            }
            #write #title:focus {
                border-color: #6366f1 !important;
                box-shadow: 0 0 0 3px rgba(99,102,241,.2) !important;
            }
            #write #text {
                background: #0f172a !important;
                border-color: #334155 !important;
                color: #e2e8f0 !important;
            }
            #write #text:focus { border-color: #6366f1 !important; }
            .joe-editor-toolbar {
                background: #1e293b;
                border-color: #334155;
            }
            .joe-editor-toolbar__sep { background: #334155; }
            .joe-editor-toolbar__btn { color: #94a3b8; }
            .joe-editor-toolbar__btn:hover { background: #334155; color: #e2e8f0; }
            .joe-editor-toolbar__btn:active { background: #475569; }
            .joe-editor-toolbar__btn.is-shortcode { color: #818cf8; }
            .joe-editor-toolbar__btn.is-shortcode:hover { background: rgba(129,140,248,.15); }
            .joe-editor-preview { background: #1e293b; border-color: #334155; color: #e2e8f0; }
            .joe-editor-preview h1, .joe-editor-preview h2, .joe-editor-preview h3 { color: #f1f5f9; }
            .joe-editor-preview p { color: #cbd5e1; }
            .joe-editor-preview code { background: #334155; color: #f87171; }
            .joe-editor-preview blockquote { background: #1e293b; color: #94a3b8; border-left-color: #818cf8; }
            .joe-editor-preview th { background: #1e293b; }
            .joe-editor-preview th, .joe-editor-preview td { border-color: #334155; }
            #write select, #write input[type="text"] {
                background: #1e293b !important;
                border-color: #334155 !important;
                color: #e2e8f0 !important;
            }
        }
        /* ===== 全屏编辑器 ===== */
        .joe-editor-fullscreen {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9999 !important;
            max-width: none !important;
            background: #fff !important;
            padding: 16px !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .joe-editor-fullscreen .joe-editor-toolbar {
            flex-shrink: 0;
            border-radius: 8px 8px 0 0;
        }
        .joe-editor-fullscreen #text {
            flex: 1 !important;
            min-height: 0 !important;
            border-radius: 0 0 8px 8px !important;
        }
        .joe-editor-fullscreen .joe-editor-preview {
            flex: 1 !important;
            min-height: 0 !important;
            max-height: none !important;
        }
        .joe-editor-fullscreen .joe-editor-outline {
            position: absolute;
            right: 16px;
            top: 72px;
            bottom: 16px;
            width: 200px;
        }
        /* ===== 字数统计 ===== */
        .joe-editor-wordcount {
            margin-left: auto;
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            padding: 0 8px;
        }
        /* ===== 草稿状态 ===== */
        .joe-editor-draft-status {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            transition: color .2s;
        }
        .joe-editor-draft-status.is-saved { color: #22c55e; }
        .joe-editor-draft-status.is-restored { color: #f59e0b; }
        /* ===== 大纲侧边栏 ===== */
        .joe-editor-outline {
            display: none;
            position: absolute;
            right: -210px;
            top: 0;
            width: 190px;
            max-height: 500px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            z-index: 10;
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        @media (min-width: 1200px) {
            .joe-editor-outline { display: block; }
        }
        .joe-editor-outline__head {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .joe-editor-outline__list {
            padding: 6px 0;
            max-height: 450px;
            overflow-y: auto;
        }
        .joe-editor-outline__empty {
            padding: 14px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }
        .joe-editor-outline__item {
            display: block;
            padding: 4px 14px;
            font-size: 12px;
            color: #475569;
            text-decoration: none;
            transition: all .15s;
            border-left: 2px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .joe-editor-outline__item:hover {
            background: #f1f5f9;
            color: #5b6cff;
        }
        .joe-editor-outline__item.is-h1 { font-weight: 600; font-size: 13px; }
        .joe-editor-outline__item.is-h2 { padding-left: 20px; }
        .joe-editor-outline__item.is-h3 { padding-left: 28px; font-size: 11px; }
        .joe-editor-outline__item.is-h4 { padding-left: 36px; font-size: 11px; color: #94a3b8; }
        /* 暗色模式 - 大纲 */
        @media (prefers-color-scheme: dark) {
            .joe-editor-fullscreen { background: #0f172a !important; }
            .joe-editor-outline {
                background: #1e293b;
                border-color: #334155;
            }
            .joe-editor-outline__head {
                background: #0f172a;
                color: #cbd5e1;
                border-color: #334155;
            }
            .joe-editor-outline__item { color: #94a3b8; }
            .joe-editor-outline__item:hover { background: #334155; color: #818cf8; }
            .joe-editor-outline__item.is-h4 { color: #64748b; }
        }
        /* ===== Emoji 面板 ===== */
        .joe-editor-emoji {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 0;
            width: 340px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
            z-index: 20;
            overflow: hidden;
        }
        .joe-editor-emoji.is-show { display: block; }
        .joe-editor-emoji__tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .joe-editor-emoji__tab {
            padding: 6px 12px;
            font-size: 12px;
            color: #64748b;
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
            transition: all .15s;
        }
        .joe-editor-emoji__tab.is-active { color: #5b6cff; border-bottom-color: #5b6cff; }
        .joe-editor-emoji__body { padding: 8px; max-height: 180px; overflow-y: auto; display: grid; grid-template-columns: repeat(10, 1fr); gap: 2px; }
        .joe-editor-emoji__item {
            font-size: 18px;
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            border: none;
            background: none;
            border-radius: 4px;
            transition: background .1s;
        }
        .joe-editor-emoji__item:hover { background: #f1f5f9; }
        /* 查找替换面板 */
        .joe-editor-search {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
            padding: 8px;
            z-index: 20;
            min-width: 280px;
        }
        .joe-editor-search.is-show { display: flex; flex-direction: column; gap: 6px; }
        .joe-editor-search__row { display: flex; align-items: center; gap: 6px; }
        .joe-editor-search__input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            outline: none;
        }
        .joe-editor-search__input:focus { border-color: #5b6cff; }
        .joe-editor-search__btn {
            font-size: 11px;
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f8fafc;
            cursor: pointer;
            transition: all .15s;
        }
        .joe-editor-search__btn:hover { background: #e2e8f0; }
        .joe-editor-search__count { font-size: 11px; color: #94a3b8; }
        /* 表格插入对话框 */
        .joe-editor-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.3);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .joe-editor-modal-overlay.is-show { display: flex; }
        .joe-editor-modal {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            width: 320px;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .joe-editor-modal h4 { margin: 0 0 16px; font-size: 15px; color: #1e293b; }
        .joe-editor-modal__row { display: flex; gap: 12px; margin-bottom: 12px; }
        .joe-editor-modal__label { font-size: 12px; color: #64748b; width: 60px; line-height: 32px; }
        .joe-editor-modal__input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            outline: none;
        }
        .joe-editor-modal__input:focus { border-color: #5b6cff; }
        .joe-editor-modal__actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
        .joe-editor-modal__btn {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: all .15s;
        }
        .joe-editor-modal__btn--primary {
            background: #5b6cff;
            border-color: #5b6cff;
            color: #fff;
        }
        .joe-editor-modal__btn--primary:hover { background: #4a5ae5; }
        /* 快捷键帮助 */
        .joe-editor-shortcuts {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 10001;
            align-items: center;
            justify-content: center;
        }
        .joe-editor-shortcuts.is-show { display: flex; }
        .joe-editor-shortcuts__panel {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            width: 420px;
            max-height: 70vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .joe-editor-shortcuts__panel h4 { margin: 0 0 16px; font-size: 15px; }
        .joe-editor-shortcuts__row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; border-bottom: 1px solid #f1f5f9; }
        .joe-editor-shortcuts__key { background: #f1f5f9; padding: 1px 6px; border-radius: 3px; font-family: monospace; font-size: 11px; color: #5b6cff; }
        /* 暗色模式 */
        @media (prefers-color-scheme: dark) {
            .joe-editor-emoji { background: #1e293b; border-color: #334155; }
            .joe-editor-emoji__tabs { background: #0f172a; border-color: #334155; }
            .joe-editor-emoji__tab { color: #94a3b8; }
            .joe-editor-emoji__tab.is-active { color: #818cf8; }
            .joe-editor-emoji__item:hover { background: #334155; }
            .joe-editor-search { background: #1e293b; border-color: #334155; }
            .joe-editor-search__input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
            .joe-editor-search__input:focus { border-color: #818cf8; }
            .joe-editor-search__btn { background: #334155; border-color: #475569; color: #e2e8f0; }
            .joe-editor-search__btn:hover { background: #475569; }
            .joe-editor-search__count { color: #64748b; }
            .joe-editor-modal, .joe-editor-shortcuts__panel { background: #1e293b; color: #e2e8f0; }
            .joe-editor-modal h4, .joe-editor-shortcuts__panel h4 { color: #f1f5f9; }
            .joe-editor-modal__input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
            .joe-editor-modal__btn { background: #334155; border-color: #475569; color: #e2e8f0; }
        }
    </style>';

    // 编辑器工具栏 JS
    echo '<script>
(function(){
    "use strict";
    var textarea = document.getElementById("text");
    if (!textarea) return;

    // ===== 构建工具栏 =====
    var toolbar = document.createElement("div");
    toolbar.className = "joe-editor-toolbar";

    // Markdown 格式化按钮
    var mdButtons = [
        { icon: \'<svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M6 12h9a4 4 0 1 1 0 8H6Z" stroke="currentColor" stroke-width="2" fill="none"/></svg>\', md: "**|**", tip: "粗体 (Ctrl+B)", key: "b" },
        { icon: \'<svg viewBox="0 0 24 24"><path d="M19 4h-8a4 4 0 0 0-4 4 4 4 0 0 0 4 4h8v2h-8" stroke="currentColor" stroke-width="2" fill="none"/></svg>\', md: "*|*", tip: "斜体 (Ctrl+I)", key: "i" },
        { icon: \'<svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>\', md: "~~|~~", tip: "删除线" },
        { icon: \'<svg viewBox="0 0 24 24"><path d="M7 8l-4 4 4 4M17 8l4 4-4 4M14 4l-4 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>\', md: "`|`", tip: "行内代码" },
    ];

    var headingButtons = [
        { label: "H2", md: "\\n## ", tip: "二级标题", block: true },
        { label: "H3", md: "\\n### ", tip: "三级标题", block: true },
        { label: "H4", md: "\\n#### ", tip: "四级标题", block: true },
    ];

    var insertButtons = [
        { icon: \'<svg viewBox="0 0 24 24"><path d="M13.5 4.5 9 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="2" fill="none"/></svg>\', md: "[文本](https://)", tip: "插入链接" },
        { icon: \'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><path d="M21 15 16 10 5 21" stroke="currentColor" stroke-width="2" fill="none"/></svg>\', md: "![描述](https://)", tip: "插入图片" },
        { icon: \'<svg viewBox="0 0 24 24"><path d="M3 5h18M3 10h12M3 15h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>\', md: "\\n> ", tip: "引用 (Ctrl+Q)", key: "q", block: true },
        { icon: \'<svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>\', md: "\\n---\\n", tip: "分割线", block: true },
    ];

    var shortcodeButtons = [
        { label: "视频", tip: "插入 MP4 视频", sc: "[video]\\n[/video]" },
        { label: "B站", tip: "插入 Bilibili", sc: "[bilibili]BV1xx411c7mD[/bilibili]" },
        { label: "油管", tip: "插入 YouTube", sc: "[youtube]dQw4w9WgXcQ[/youtube]" },
        { label: "回复可见", tip: "评论后可见内容", sc: "[reply]\\n这里写隐藏内容\\n[/reply]" },
        { label: "蓝凑云", tip: "插入下载链接", sc: "[lanzou]https://wwz.lanzou.com/xxx[/lanzou]" },
    ];

    // ===== 渲染按钮 =====
    function addBtn(html, tip, css) {
        var b = document.createElement("button");
        b.type = "button";
        b.className = "joe-editor-toolbar__btn" + (css ? " " + css : "");
        b.innerHTML = html;
        if (tip) b.setAttribute("data-tooltip", tip);
        return b;
    }

    // 格式化按钮组
    var g1 = document.createElement("div");
    g1.className = "joe-editor-toolbar__group";
    mdButtons.forEach(function(bd){
        var btn = addBtn(bd.icon, bd.tip);
        btn.addEventListener("click", function(){ insertMd(bd.md, bd.block); });
        g1.appendChild(btn);
    });
    toolbar.appendChild(g1);

    // 分隔线
    var sep = document.createElement("span");
    sep.className = "joe-editor-toolbar__sep";
    toolbar.appendChild(sep);

    // 标题按钮组
    var g2 = document.createElement("div");
    g2.className = "joe-editor-toolbar__group";
    headingButtons.forEach(function(bd){
        var btn = addBtn(bd.label, bd.tip);
        btn.style.fontWeight = "600";
        btn.addEventListener("click", function(){ insertMd(bd.md, bd.block); });
        g2.appendChild(btn);
    });
    toolbar.appendChild(g2);

    var sep2 = document.createElement("span");
    sep2.className = "joe-editor-toolbar__sep";
    toolbar.appendChild(sep2);

    // 插入按钮组
    var g3 = document.createElement("div");
    g3.className = "joe-editor-toolbar__group";
    insertButtons.forEach(function(bd){
        var btn = addBtn(bd.icon, bd.tip);
        btn.addEventListener("click", function(){ insertMd(bd.md, bd.block); });
        g3.appendChild(btn);
    });
    toolbar.appendChild(g3);

    var sep3 = document.createElement("span");
    sep3.className = "joe-editor-toolbar__sep";
    toolbar.appendChild(sep3);

    // 短代码按钮组
    var g4 = document.createElement("div");
    g4.className = "joe-editor-toolbar__group";
    shortcodeButtons.forEach(function(bd){
        var btn = addBtn(bd.label, bd.tip, "is-shortcode");
        btn.addEventListener("click", function(){ insertMd(bd.sc, true); });
        g4.appendChild(btn);
    });
    toolbar.appendChild(g4);

    // 预览按钮
    var previewBtn = document.createElement("button");
    previewBtn.type = "button";
    previewBtn.className = "joe-editor-toolbar__btn is-preview";
    previewBtn.innerHTML = \'<svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2" fill="none"/></svg><span>预览</span>\';
    toolbar.appendChild(previewBtn);

    // ===== 创建预览区 =====
    var preview = document.createElement("div");
    preview.className = "joe-editor-preview";
    preview.id = "joe-editor-preview";

    // ===== 插入 DOM =====
    var textParent = textarea.parentNode;
    textParent.insertBefore(toolbar, textarea);
    textParent.insertBefore(preview, textarea.nextSibling);

    // ===== 插入 Markdown =====
    function insertMd(md, isBlock) {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var sel = textarea.value.substring(start, end);
        var parts = md.split("|");
        var prefix = parts[0] || "";
        var suffix = parts.length > 1 ? parts[1] : "";

        if (isBlock) {
            // 需要换行前缀
            var before = textarea.value.substring(0, start);
            var needNewline = before.length > 0 && before[before.length - 1] !== "\\n";
            if (prefix.indexOf("\\n") === 0) {
                prefix = prefix.replace("\\n", needNewline ? "\\n" : "");
                suffix = suffix.replace("\\n", "");
            }
        }
        if (md.indexOf("\\n---\\n") === 0 && !textarea.value.substring(0, start).endsWith("\\n")) {
            prefix = "\\n" + prefix;
        }
        var replacement = prefix + (sel || suffix ? "" : suffix) + suffix;
        textarea.setRangeText(replacement, start, end, "end");
        textarea.focus();

        // 如果有选中文字，重新选中（用于链接、图片等模板）
        if (parts.length > 1 && !sel && prefix.length) {
            var newPos = start + prefix.length;
            textarea.setSelectionRange(newPos, newPos);
        }
    }

    // ===== 预览切换 =====
    var isPreview = false;
    previewBtn.addEventListener("click", function(){
        isPreview = !isPreview;
        if (isPreview) {
            preview.innerHTML = renderMarkdown(textarea.value);
            preview.classList.add("is-show");
            textarea.style.display = "none";
            previewBtn.classList.add("is-active");
            previewBtn.querySelector("span").textContent = "编辑";
        } else {
            preview.classList.remove("is-show");
            textarea.style.display = "";
            previewBtn.classList.remove("is-active");
            previewBtn.querySelector("span").textContent = "预览";
        }
    });

    // ===== 实时预览更新 =====
    textarea.addEventListener("input", function(){
        if (isPreview) {
            preview.innerHTML = renderMarkdown(textarea.value);
        }
    });

    // ===== 简易 Markdown 渲染 =====
    function renderMarkdown(text) {
        var h = text;
        // 转义 HTML
        h = h.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        // 代码块（先处理，避免内部被后续规则影响）
        h = h.replace(/```(\\w*)\\n([\\s\\S]*?)```/g, function(_, lang, code){
            return "<pre><code class=\\"language-" + (lang || "plain") + "\\">" + code + "</code></pre>";
        });
        // 标题
        h = h.replace(/^#### (.+)$/gm, "<h4>$1</h4>");
        h = h.replace(/^### (.+)$/gm, "<h3>$1</h3>");
        h = h.replace(/^## (.+)$/gm, "<h2>$1</h2>");
        h = h.replace(/^# (.+)$/gm, "<h1>$1</h1>");
        // 粗体/斜体
        h = h.replace(/\\*\\*(.+?)\\*\\*/g, "<strong>$1</strong>");
        h = h.replace(/\\*(.+?)\\*/g, "<em>$1</em>");
        // 行内代码
        h = h.replace(/`(.+?)`/g, "<code>$1</code>");
        // 图片
        h = h.replace(/!\\[(.*?)\\]\\((.*?)\\)/g, \'<img src="$2" alt="$1">\');
        // 链接
        h = h.replace(/\\[(.*?)\\]\\((.*?)\\)/g, \'<a href="$2" target="_blank">$1</a>\');
        // 删除线
        h = h.replace(/~~(.+?)~~/g, "<del>$1</del>");
        // 分割线
        h = h.replace(/^---$/gm, "<hr>");
        // 引用
        h = h.replace(/^&gt; (.+)$/gm, "<blockquote>$1</blockquote>");
        // 有序/无序列表
        h = h.replace(/^- (.+)$/gm, "<li>$1</li>");
        h = h.replace(/^\\d+\\. (.+)$/gm, "<li>$1</li>");
        h = h.replace(/(<li>.*?<\\/li>\\n?)+/g, "<ul>$&</ul>");
        // 表格
        h = h.replace(/^\\|(.+)\\|$/gm, function(line){
            var cells = line.split("|").filter(function(c){ return c.trim(); });
            if (cells.every(function(c){ return /^[-:]+$/.test(c.trim()); })) return ""; // 分隔行
            var tag = "td";
            return "<tr>" + cells.map(function(c){ return "<" + tag + ">" + c.trim() + "</" + tag + ">"; }).join("") + "</tr>";
        });
        h = h.replace(/(<tr>.*?<\\/tr>\\n?)+/g, "<table>$&</table>");
        // 段落
        var lines = h.split("\\n");
        var out = [];
        var inList = false;
        for (var i = 0; i < lines.length; i++) {
            var l = lines[i];
            if (l.indexOf("<li>") === 0 && !inList) { out.push("<ul>"); inList = true; }
            if (l.indexOf("<li>") !== 0 && inList) { out.push("</ul>"); inList = false; }
            if (!l.trim()) { out.push("<br>"); continue; }
            if (!l.match(/^<(h[1-4]|pre|blockquote|ul|ol|li|table|tr|hr|img)/)) {
                out.push("<p>" + l + "</p>");
            } else {
                out.push(l);
            }
        }
        if (inList) out.push("</ul>");
        return out.join("\\n");
    }

    // ===== 键盘快捷键 =====
    textarea.addEventListener("keydown", function(e){
        if (!(e.ctrlKey || e.metaKey)) return;
        var key = e.key.toLowerCase();
        if (key === "b") { e.preventDefault(); insertMd("**|**"); }
        else if (key === "i") { e.preventDefault(); insertMd("*|*"); }
        else if (key === "q") { e.preventDefault(); insertMd("\\n> ", true); }
        else if (key === "k") { e.preventDefault(); insertMd("[文本](https://)"); }
        else if (key === "`") { e.preventDefault(); insertMd("`|`"); }
    });

    // ===== Tab 键插入缩进 =====
    textarea.addEventListener("keydown", function(e){
        if (e.key === "Tab") {
            e.preventDefault();
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            if (start !== end) {
                // 多行缩进
                var before = textarea.value.substring(0, start);
                var selected = textarea.value.substring(start, end);
                var after = textarea.value.substring(end);
                var lines = selected.split("\\n");
                var indented = lines.map(function(l){ return "    " + l; }).join("\\n");
                textarea.value = before + indented + after;
                textarea.selectionStart = start;
                textarea.selectionEnd = start + indented.length;
            } else {
                textarea.setRangeText("    ", start, end, "end");
            }
        }
    });

    // ===== 全屏编辑模式 =====
    var isFullscreen = false;
    var fullscreenBtn = document.createElement("button");
    fullscreenBtn.type = "button";
    fullscreenBtn.className = "joe-editor-toolbar__btn is-preview";
    fullscreenBtn.style.marginLeft = "8px";
    fullscreenBtn.innerHTML = \'<svg viewBox="0 0 24 24" width="14" height="14"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg><span>全屏</span>\';
    toolbar.appendChild(fullscreenBtn);

    fullscreenBtn.addEventListener("click", function(){
        isFullscreen = !isFullscreen;
        var wrap = textarea.closest(".wmd-container") || textarea.parentNode;
        if (isFullscreen) {
            wrap.classList.add("joe-editor-fullscreen");
            document.body.style.overflow = "hidden";
            fullscreenBtn.querySelector("span").textContent = "退出";
            fullscreenBtn.classList.add("is-active");
            // 保存滚动位置
            textarea.dataset.scrollTop = window.pageYOffset;
            window.scrollTo(0, 0);
        } else {
            wrap.classList.remove("joe-editor-fullscreen");
            document.body.style.overflow = "";
            fullscreenBtn.querySelector("span").textContent = "全屏";
            fullscreenBtn.classList.remove("is-active");
            if (textarea.dataset.scrollTop) {
                window.scrollTo(0, parseInt(textarea.dataset.scrollTop));
            }
        }
        textarea.focus();
    });

    // ESC 退出全屏
    document.addEventListener("keydown", function(e){
        if (e.key === "Escape" && isFullscreen) {
            fullscreenBtn.click();
        }
    });

    // ===== 字数统计 =====
    var wordCount = document.createElement("div");
    wordCount.className = "joe-editor-wordcount";
    toolbar.appendChild(wordCount);

    function updateWordCount(){
        var t = textarea.value.replace(/\s/g, "");
        var cn = (t.match(/[\u4e00-\u9fa5]/g) || []).length;
        var en = (t.match(/[a-zA-Z0-9]/g) || []).length;
        var lines = textarea.value.split("\n").length;
        wordCount.textContent = cn + " 字 | " + lines + " 行";
    }
    textarea.addEventListener("input", updateWordCount);
    updateWordCount();

    // ===== 大纲侧边栏 =====
    var outline = document.createElement("div");
    outline.className = "joe-editor-outline";
    outline.innerHTML = \'<div class="joe-editor-outline__head">大纲</div><div class="joe-editor-outline__list"></div>\';
    var textParent2 = textarea.parentNode;
    textParent2.appendChild(outline);

    var outlineList = outline.querySelector(".joe-editor-outline__list");

    function updateOutline(){
        var headings = textarea.value.match(/^(#{1,4})\s+(.+)$/gm);
        outlineList.innerHTML = "";
        if (!headings) { outlineList.innerHTML = \'<div class="joe-editor-outline__empty">暂无标题</div>\'; return; }
        headings.forEach(function(h, i){
            var level = (h.match(/^#+/) || [""])[0].length;
            var text = h.replace(/^#+\s+/, "");
            var item = document.createElement("a");
            item.className = "joe-editor-outline__item is-h" + level;
            item.textContent = text;
            item.href = "javascript:void(0)";
            item.addEventListener("click", function(){
                // 查找对应标题在 textarea 中的位置
                var idx = textarea.value.indexOf(h);
                if (idx >= 0) {
                    textarea.focus();
                    textarea.setSelectionRange(idx, idx + h.length);
                    // 滚动 textarea 到对应位置
                    var lineNum = textarea.value.substring(0, idx).split("\n").length;
                    var lineH = parseInt(getComputedStyle(textarea).lineHeight) || 25;
                    textarea.scrollTop = (lineNum - 3) * lineH;
                }
            });
            outlineList.appendChild(item);
        });
    }
    textarea.addEventListener("input", updateOutline);
    updateOutline();

    // ===== 自动保存草稿 =====
    var draftKey = "kingjoe-draft-" + (document.querySelector("input[name=\"cid\"]") ? document.querySelector("input[name=\"cid\"]").value : "new");
    var draftStatus = document.createElement("span");
    draftStatus.className = "joe-editor-draft-status";
    toolbar.appendChild(draftStatus);

    function saveDraft(){
        try {
            var data = {
                text: textarea.value,
                title: document.getElementById("title") ? document.getElementById("title").value : "",
                time: Date.now()
            };
            localStorage.setItem(draftKey, JSON.stringify(data));
            draftStatus.textContent = "已保存 " + new Date().toLocaleTimeString();
            draftStatus.className = "joe-editor-draft-status is-saved";
            setTimeout(function(){ draftStatus.className = "joe-editor-draft-status"; }, 2000);
        } catch(e) {}
    }

    function loadDraft(){
        try {
            var raw = localStorage.getItem(draftKey);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (!textarea.value && data.text) {
                textarea.value = data.text;
                draftStatus.textContent = "已恢复草稿";
                draftStatus.className = "joe-editor-draft-status is-restored";
                setTimeout(function(){ draftStatus.className = "joe-editor-draft-status"; }, 3000);
                updateWordCount();
                updateOutline();
            }
        } catch(e) {}
    }

    // 加载草稿
    loadDraft();

    // 定时保存（30秒）+ 失焦保存
    var saveTimer = setInterval(saveDraft, 30000);
    textarea.addEventListener("blur", saveDraft);

    // Ctrl+S 手动保存
    textarea.addEventListener("keydown", function(e){
        if ((e.ctrlKey || e.metaKey) && e.key === "s") {
            e.preventDefault();
            saveDraft();
        }
    });

    // 提交成功后清除草稿
    var form = textarea.closest("form");
    if (form) {
        form.addEventListener("submit", function(){
            try { localStorage.removeItem(draftKey); } catch(e) {}
        });
    }

    // ===== 粘贴图片上传 =====
    textarea.addEventListener("paste", function(e){
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf("image") === 0) {
                e.preventDefault();
                var blob = items[i].getAsFile();
                uploadImage(blob);
                return;
            }
        }
    });

    function uploadImage(file) {
        // 显示上传中占位
        var placeholder = "[上传中...]";
        var start = textarea.selectionStart;
        textarea.setRangeText(placeholder, start, start, "end");

        var fd = new FormData();
        fd.append("name", file.name || "paste-" + Date.now() + ".png");
        fd.append("file", file);

        // Typecho 附件上传接口
        var uploadUrl = document.querySelector("form.enable-upload") ? document.querySelector("form.enable-upload").getAttribute("action") : "";
        if (!uploadUrl) {
            // 尝试构造上传地址
            var adminUrl = (document.querySelector("a[href*=\"login.php\"]") || {}).href || "";
            uploadUrl = adminUrl.replace(/login\.php.*/, "") + "action/upload?___multipartFormData=1";
        }

        fetch(uploadUrl, { method: "POST", body: fd, credentials: "same-origin" })
            .then(function(r){ return r.json(); })
            .then(function(data){
                var oldVal = textarea.value;
                var url = "";
                if (Array.isArray(data) && data[0] && data[0].url) {
                    url = data[0].url;
                } else if (data.url) {
                    url = data.url;
                } else if (data.attachment && data.attachment.url) {
                    url = data.attachment.url;
                }
                if (url) {
                    textarea.value = oldVal.replace(placeholder, "![" + (file.name || "图片") + "](" + url + ")");
                } else {
                    textarea.value = oldVal.replace(placeholder, "");
                }
                textarea.focus();
                updateWordCount();
            })
            .catch(function(){
                textarea.value = textarea.value.replace(placeholder, "");
            });
    }

    // ===== Emoji 表情面板 =====
    var emojiBtn = addBtn(\'<svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="15" cy="10" r="1" fill="currentColor"/><path d="M8 14c1.5 2 3.5 3 5.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>\', "表情");
    emojiBtn.style.position = "relative";
    toolbar.insertBefore(emojiBtn, toolbar.querySelector(".joe-editor-toolbar__sep:last-of-type") || toolbar.lastChild);

    var emojiData = {
        "常用": ["😀","😂","🤣","😍","😘","🥰","😎","🤩","😭","😡","👍","👎","❤️","🔥","⭐","🎉","💯","✅","❌","⚠️"],
        "表情": ["😀","😃","😄","😁","😅","😂","🤣","😊","🙂","😉","😌","😍","🥰","😘","😗","😙","😚","😋","😛","😝","😜","🤪","🤨","🧐","🤓","😎","🤩","🥳","😏","😒","😞","😔","😟","😕","🙁","☹️","😣","😖","😫","😩","🥺","😢","😭","😤","😠","😡","🤬","🤯","😳","🥵","🥶","😱","😨","😰","😥","😓","🤗","🤔","🤭","🤫","🤥","😶","😐","😑","😬","🙄","😯","😦","😧","😮","😲","🥱","😴","🤤","😪","😵"],
        "手势": ["👍","👎","👌","✌️","🤞","🤟","🤘","🤙","👋","🤚","🖐","✋","🖖","👏","🙌","🤝","💪","👆","👇","👉","👈","✍️","🙏","💅","🤳"],
        "符号": ["❤️","🧡","💛","💚","💙","💜","🖤","🤍","🤎","💔","❣️","💕","💞","💓","💗","💖","💘","💝","💟","☮️","✝️","☪️","🕉","☸️","✡️","🔯","🕎","☯️","☦️","🛐","⛎","♈","♉","♊","♋","♌","♍","♎","♏","♐","♑","♒","♓","🆔","⚛️","🉑","☢️","☣️","📴","📳","🈶","🈚","🈸","🈺","🈷️","✴️","🆚","💮","🉐","㊙️","㊗️","🈴","🈵","🈹","🈲","🅰️","🅱️","🆎","🆑","🅾️","🆘","❌","⭕","🛑","⛔","📛","🚫","💯","♻️","🚮","🚰","♿","🚭","🚾","🅿️","⚠️","🚸","⛔"]
    };

    var emojiPanel = document.createElement("div");
    emojiPanel.className = "joe-editor-emoji";
    emojiBtn.appendChild(emojiPanel);

    var emojiTabs = document.createElement("div");
    emojiTabs.className = "joe-editor-emoji__tabs";
    var emojiBody = document.createElement("div");
    emojiBody.className = "joe-editor-emoji__body";
    emojiPanel.appendChild(emojiTabs);
    emojiPanel.appendChild(emojiBody);

    var emojiCats = Object.keys(emojiData);
    var activeEmojiCat = emojiCats[0];

    function renderEmojiCat(cat){
        activeEmojiCat = cat;
        emojiTabs.querySelectorAll(".joe-editor-emoji__tab").forEach(function(t){ t.classList.remove("is-active"); });
        emojiTabs.querySelector(\'[data-cat="\' + cat + \'"]\').classList.add("is-active");
        emojiBody.innerHTML = "";
        (emojiData[cat] || []).forEach(function(ch){
            var el = document.createElement("button");
            el.type = "button";
            el.className = "joe-editor-emoji__item";
            el.textContent = ch;
            el.addEventListener("click", function(){
                var s = textarea.selectionStart;
                textarea.setRangeText(ch, s, textarea.selectionEnd, "end");
                textarea.focus();
            });
            emojiBody.appendChild(el);
        });
    }

    emojiCats.forEach(function(cat, i){
        var tab = document.createElement("button");
        tab.type = "button";
        tab.className = "joe-editor-emoji__tab" + (i === 0 ? " is-active" : "");
        tab.textContent = cat;
        tab.setAttribute("data-cat", cat);
        tab.addEventListener("click", function(e){ e.stopPropagation(); renderEmojiCat(cat); });
        emojiTabs.appendChild(tab);
    });
    renderEmojiCat(emojiCats[0]);

    emojiBtn.addEventListener("click", function(e){
        e.stopPropagation();
        emojiPanel.classList.toggle("is-show");
    });
    document.addEventListener("click", function(e){
        if (!emojiBtn.contains(e.target)) emojiPanel.classList.remove("is-show");
    });

    // ===== 字体缩放按钮 =====
    var zoomOutBtn = addBtn(\'<span style="font-size:12px;font-weight:700">A-</span>\', "缩小字体");
    var zoomInBtn = addBtn(\'<span style="font-size:15px;font-weight:700">A+</span>\', "放大字体");
    var currentFontSize = 14;

    toolbar.insertBefore(zoomOutBtn, emojiBtn);
    toolbar.insertBefore(zoomInBtn, emojiBtn);

    zoomOutBtn.addEventListener("click", function(){
        if (currentFontSize <= 10) return;
        currentFontSize -= 2;
        textarea.style.fontSize = currentFontSize + "px";
        if (preview.classList.contains("is-show")) preview.style.fontSize = currentFontSize + "px";
    });
    zoomInBtn.addEventListener("click", function(){
        if (currentFontSize >= 24) return;
        currentFontSize += 2;
        textarea.style.fontSize = currentFontSize + "px";
        if (preview.classList.contains("is-show")) preview.style.fontSize = currentFontSize + "px";
    });

    // ===== 列表按钮 =====
    var ulBtn = addBtn(\'<svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>\', "无序列表");
    var olBtn = addBtn(\'<svg viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>\', "有序列表");
    var tableBtn = addBtn(\'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 9h18M9 3v18" stroke="currentColor" stroke-width="2" fill="none"/></svg>\', "插入表格");

    toolbar.insertBefore(ulBtn, emojiBtn);
    toolbar.insertBefore(olBtn, emojiBtn);
    toolbar.insertBefore(tableBtn, emojiBtn);

    ulBtn.addEventListener("click", function(){
        insertMd("\\n- ", true);
    });
    olBtn.addEventListener("click", function(){
        insertMd("\\n1. ", true);
    });

    // ===== 表格插入对话框 =====
    var modal = document.createElement("div");
    modal.className = "joe-editor-modal-overlay";
    modal.innerHTML = \'<div class="joe-editor-modal"><h4>插入表格</h4><div class="joe-editor-modal__row"><span class="joe-editor-modal__label">行数</span><input class="joe-editor-modal__input" type="number" value="3" min="2" max="10" id="joe-table-rows"></div><div class="joe-editor-modal__row"><span class="joe-editor-modal__label">列数</span><input class="joe-editor-modal__input" type="number" value="3" min="2" max="10" id="joe-table-cols"></div><div class="joe-editor-modal__actions"><button class="joe-editor-modal__btn" id="joe-table-cancel">取消</button><button class="joe-editor-modal__btn joe-editor-modal__btn--primary" id="joe-table-ok">插入</button></div></div>\';
    document.body.appendChild(modal);

    tableBtn.addEventListener("click", function(){ modal.classList.add("is-show"); });
    document.getElementById("joe-table-cancel").addEventListener("click", function(){ modal.classList.remove("is-show"); });
    document.getElementById("joe-table-ok").addEventListener("click", function(){
        var rows = Math.max(2, Math.min(10, parseInt(document.getElementById("joe-table-rows").value) || 3));
        var cols = Math.max(2, Math.min(10, parseInt(document.getElementById("joe-table-cols").value) || 3));
        var header = "| " + Array(cols).fill("列").map(function(c,i){ return c + (i+1); }).join(" | ") + " |\\n";
        var sep =    "| " + Array(cols).fill("---").join(" | ") + " |\\n";
        var body = "";
        for (var r = 1; r < rows; r++) {
            body += "| " + Array(cols).fill("内容").join(" | ") + " |" + (r < rows-1 ? "\\n" : "");
        }
        insertMd(header + sep + body);
        modal.classList.remove("is-show");
    });
    modal.addEventListener("click", function(e){ if (e.target === modal) modal.classList.remove("is-show"); });

    // ===== 查找替换（Ctrl+F / Ctrl+H）=====
    var searchPanel = document.createElement("div");
    searchPanel.className = "joe-editor-search";
    searchPanel.innerHTML = \'<div class="joe-editor-search__row"><input class="joe-editor-search__input" id="joe-search-find" placeholder="查找..."><span class="joe-editor-search__count" id="joe-search-count"></span><button class="joe-editor-search__btn" id="joe-search-prev">上一个</button><button class="joe-editor-search__btn" id="joe-search-next">下一个</button><button class="joe-editor-search__btn" id="joe-search-close">×</button></div><div class="joe-editor-search__row"><input class="joe-editor-search__input" id="joe-search-replace" placeholder="替换为..."><button class="joe-editor-search__btn" id="joe-search-replace-btn">替换</button><button class="joe-editor-search__btn" id="joe-search-replace-all">全部替换</button></div>\';
    textarea.parentNode.style.position = textarea.parentNode.style.position || "relative";
    textarea.parentNode.appendChild(searchPanel);

    var findInput = document.getElementById("joe-search-find");
    var replaceInput = document.getElementById("joe-search-replace");
    var searchCount = document.getElementById("joe-search-count");
     var searchIndex = 0;
     var lastSearch = "";

    function doSearch(dir){
        var q = findInput.value;
        if (!q) return;
        if (q !== lastSearch) { lastSearch = q; searchIndex = 0; }
        var val = textarea.value;
        var idx = dir > 0 ? val.indexOf(q, textarea.selectionEnd) : val.lastIndexOf(q, textarea.selectionStart - 1);
        if (idx === -1) {
            idx = dir > 0 ? val.indexOf(q) : val.lastIndexOf(q);
        }
        if (idx >= 0) {
            textarea.focus();
            textarea.setSelectionRange(idx, idx + q.length);
        }
        // 统计
        var count = (val.match(new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, "\\\\$&"), "g")) || []).length;
        searchCount.textContent = count + " 个匹配";
    }

    document.getElementById("joe-search-next").addEventListener("click", function(){ doSearch(1); });
    document.getElementById("joe-search-prev").addEventListener("click", function(){ doSearch(-1); });
    document.getElementById("joe-search-close").addEventListener("click", function(){ searchPanel.classList.remove("is-show"); });
    document.getElementById("joe-search-replace-btn").addEventListener("click", function(){
        var q = findInput.value;
        var r = replaceInput.value;
        var sel = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
        if (sel === q) {
            textarea.setRangeText(r, textarea.selectionStart, textarea.selectionEnd, "end");
            updateWordCount(); updateOutline();
        }
        doSearch(1);
    });
    document.getElementById("joe-search-replace-all").addEventListener("click", function(){
        var q = findInput.value;
        var r = replaceInput.value;
        if (!q) return;
        var re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, "\\\\$&"), "g");
        textarea.value = textarea.value.replace(re, r);
        updateWordCount(); updateOutline();
    });

    // Ctrl+F 打开查找，Ctrl+H 打开替换，ESC 关闭
    textarea.addEventListener("keydown", function(e){
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "f") {
            e.preventDefault();
            searchPanel.classList.toggle("is-show");
            findInput.focus(); findInput.select();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "h") {
            e.preventDefault();
            searchPanel.classList.add("is-show");
            replaceInput.focus(); replaceInput.select();
        }
        if (e.key === "Escape" && searchPanel.classList.contains("is-show") && !isFullscreen) {
            searchPanel.classList.remove("is-show");
        }
    });

    // ===== 快捷键帮助面板 =====
    var shortcuts = document.createElement("div");
    shortcuts.className = "joe-editor-shortcuts";
    var sc = [
        ["Ctrl+B", "粗体"],["Ctrl+I", "斜体"],["Ctrl+Q", "引用"],["Ctrl+K", "插入链接"],
        ["Ctrl+`", "行内代码"],["Ctrl+S", "保存草稿"],["Ctrl+F", "查找"],["Ctrl+H", "替换"],
        ["Ctrl+Enter", "提交"],["Tab", "缩进"],["Shift+Tab", "减少缩进"],["ESC", "关闭面板/退出全屏"],
        ["Ctrl+Z", "撤销"],["Ctrl+Y", "重做"],["Ctrl+A", "全选"],["Ctrl+V", "粘贴(图片自动上传)"]
    ];
    shortcuts.innerHTML = \'<div class="joe-editor-shortcuts__panel"><h4>键盘快捷键</h4>\' +
        sc.map(function(s){ return \'<div class="joe-editor-shortcuts__row"><span>\' + s[1] + \'</span><span class="joe-editor-shortcuts__key">\' + s[0] + \'</span></div>\'; }).join("") +
        \'<div style="text-align:center;margin-top:12px;font-size:11px;color:#94a3b8">按 <span class="joe-editor-shortcuts__key">?</span> 键打开此面板</div></div>\';
    document.body.appendChild(shortcuts);

    document.addEventListener("keydown", function(e){
        if (e.key === "?" && !e.ctrlKey && !e.metaKey && document.activeElement === textarea) {
            e.preventDefault();
            shortcuts.classList.toggle("is-show");
        }
        if (e.key === "Escape" && shortcuts.classList.contains("is-show")) {
            shortcuts.classList.remove("is-show");
        }
    });
    shortcuts.addEventListener("click", function(e){ if (e.target === shortcuts) shortcuts.classList.remove("is-show"); });
})();
</script>';
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
