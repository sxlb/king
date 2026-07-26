<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

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