<?php
/**
 * KingJoe - A Clean, Modern Typecho Theme
 * 
 * @package     KingJoe
 * @author      KingJoe Team
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 * @license     MIT
 * 
 * 模块化结构:
 *   inc/init.php       - 主题初始化、配置项、安装检测
 *   inc/helpers.php    - 核心辅助函数 (opt/get/esc/excerpt/date 等)
 *   inc/thumb.php      - 缩略图、占位 SVG、懒加载图片
 *   inc/seo.php        - SEO (标题/描述/Canonical/Sitemap)
 *   inc/security.php   - XSS 过滤、安全响应头、防盗链
 *   inc/comments.php   - 评论解析、通知、点赞、UA 徽章
 *   inc/content.php    - 内容处理 (回复可见/私密评论/RSS)
 *   inc/shortcodes.php - 短代码解析器 (视频/下载/广告/折叠/标签卡等)
 *   inc/push.php       - 百度/必应收录推送
 *   inc/stats.php      - 浏览量、阅读时长、站点统计
 *   inc/template.php   - 模板标签 (友链/TOC/点赞/相关文章/热门文章等)
 *   inc/editor.php     - 后台编辑器增强
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// ── 加载所有功能模块 ──────────────────────────────────────────
$incDir = __DIR__ . '/inc';

require_once $incDir . '/init.php';
require_once $incDir . '/helpers.php';
require_once $incDir . '/thumb.php';
require_once $incDir . '/seo.php';
require_once $incDir . '/security.php';
require_once $incDir . '/comments.php';
require_once $incDir . '/content.php';
require_once $incDir . '/shortcodes.php';
require_once $incDir . '/push.php';
require_once $incDir . '/stats.php';
require_once $incDir . '/template.php';
require_once $incDir . '/editor.php';
