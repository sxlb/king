/**
 * KingJoe — Owo 表情面板 + 评论 Markdown 工具栏
 *
 * 用法：在评论表单上准备容器：
 *   <div class="joe-toolbar" data-target="#comment-textarea"></div>
 *   <div class="joe-owo" data-target="#comment-textarea"></div>
 * 然后调用 window.KingOwo.init();
 */
(function () {
    'use strict';

    /* ============================================================
       表情数据
       ============================================================ */
    var OWO_DATA = {
        'Emoji': [
            '😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘','🥰','😗',
            '🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪',
            '😫','🥱','😴','😌','😛','😜','🤪','😝','🤤','😒','😓','😔','😕','🙃','🫠','🫡',
            '🤐','🤑','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','🥸','😎',
            '🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥',
            '😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿',
            '💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖','😺','😸','😹','😻','😼','😽',
            '🙀','😿','😾','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞',
            '💓','💗','💖','💘','💝','💟','👍','👎','👏','🙌','🙏','🤝','💪','🤞','✌️','🤟',
            '🤘','👌','🤌','🤏','👈','👉','👆','👇','☝️','✋','🤚','🖐️','🖖','👋','🤙','💪',
            '🔥','✨','🎉','🎊','🎈','🎁','🏆','🥇','⭐','🌟','💫','⚡','☀️','🌙','☁️','🌈'
        ],
        '颜文字': [
            '(๑•̀ㅂ•́)و✧','(¬‿¬)','(◕‿◕)','(｡◕‿‿◕｡)','(✿◡‿◡)','(◡‿◡✿)',
            '(≧◡≦)','(◑‿◐)','(๑•́ ₃ •̀๑)','( ˘ ³˘)♥','(´｡• ᵕ •｡`)','(❁´◡`❁)',
            '( ˘▽˘)っ♨','(•̀ᴗ•́)و ̑̑','(づ｡◕‿‿◕｡)づ','(っ˘̩╭╮˘̩)っ','(ノ°▽°)ノ︵┻━┻',
            '(╯°□°）╯︵ ┻━┻','┬─┬ ノ( \' - \'ノ)','( ﾟヮﾟ)','(づ￣ 3￣)づ','(。・ω・。)',
            '(=^･ω･^=)','(・ω・)','(｡･ω･｡)','ヾ(＾-＾)ノ','(^-^*)','(｡^··^｡)',
            '(✪ω✪)','(─‿─)','(¬_¬)','(；一_一)','ಠ_ಠ','ಥ_ಥ','(;_:;)',
            'QAQ','TAT','QAQ','Orz','OTL','(´；ω；`)','(；´д｀)ゞ','( ﹏﹏ )',
            '( ˘･з･)','(¬▂¬)','(￣ー￣)','(￣ω￣)','(¦3[▓▓]','(⌐■_■)','(•̀ᴗ•́)و',
            'ᕕ( ᐛ )ᕗ','ᕙ( • ‿ • )ᕗ','ᕦ( • ʖ • )ᕤ','( ＾∀＾）','(｡•̀ᴗ-)✧','(￣▽￣)"',
            '٩(๑❛ᴗ❛๑)۶','(*≧∪≦)','(*^▽^*)','(✿◡‿◡)っ','(‾▽‾)','(￣︶￣)'
        ],
        '符号': [
            '✔️','✖️','❓','❗','‼️','⁉️','💯','💢','💥','💫','💦','💨','🕳️','💣','💬','👁️‍🗨️',
            '🗨️','🗯️','💭','💤','🚫','⛔','🆔','🔞','📱','📲','✉️','📥','📤','📦','🏷️','📪',
            '📫','📬','📭','📮','📯','📜','📃','📄','📑','📊','📈','📉','📅','📆','📇','🗃️',
            '🗄️','📋','📁','📂','🗂️','🗞️','📰','📓','📔','📒','📕','📗','📘','📙','📚','📖',
            '🔖','🔗','📎','🖇️','📐','📏','📌','📍','✂️','🖊️','🖋️','✒️','🖌️','🖍️','📝','✏️',
            '🔍','🔎','🔏','🔐','🔒','🔓','❤','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔'
        ],
        '食物': [
            '🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝',
            '🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐',
            '🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🦴','🌭',
            '🍔','🍟','🍕','🥪','🥙','🧆','🌮','🌯','🥗','🥘','🥫','🍝','🍜','🍲','🍛','🍣',
            '🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧',
            '🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🥛','🍼','☕',
            '🍵','🧃','🥤','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾','🧊','🥄','🍴'
        ],
        '动物': [
            '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐽','🐸','🐵',
            '🙈','🙉','🙊','🐒','🦍','🦧','🐔','🐧','🐦','🐤','🐣','🐥','🦆','🦅','🦉','🦇',
            '🐺','🐗','🐴','🦄','🐝','🪱','🐛','🦋','🐌','🐞','🐜','🪰','🪲','🪳','🦟','🦗',
            '🕷️','🕸️','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟',
            '🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🐘','🦛','🦏','🐪','🐫','🦒',
            '🦘','🦬','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🦙','🐐','🦌','🐕','🐩','🦮','🐕‍🦺',
            '🐈','🐈‍⬛','🪶','🐓','🦃','🦤','🦚','🦜','🦢','🦩','🕊️','🐇','🦝','🦨','🦡','🦫'
        ],
        '哔哩': [
            '(゜-゜)つロ乾杯~','(｀・ω・´)','(〜￣△￣)〜','(･∀･)','(*°▽°*)╯',
            '_(:3」∠)_','(⌒▽⌒)','(°∀°)ﾉ','( ´_ゝ｀)','(=・ω・=)',
            '(･∀･)','(\'▽\'〃)','(oﾟvﾟ)ノ','(〜￣△￣)〜','∑(ι´Дン)ノ',
            '(´；ω；`)','(･ω<)☆','╮(￣▽￣)╭','(￣3￣)','(╯°口°)╯(┴—┴',
            '(ﾟДﾟ≡ﾟдﾟ)!?','( ﹁ ﹁ ) ~→','(・_・;)','(´-ι_-｀)','(>▽<)',
            '(;¬_¬)','(╥﹏╥)','_(ÒωÓ๑ゝ∠)_','(^・ω・^ )','(=^▽^=)',
            '_(:D)∠)_','(/TДT)/','（￣へ￣）','（*´▽｀*）','(●\'◡\'●)',
            '(°ー°〃)','（￣▽￣）','(ノ￣▽￣)ノ','ヾ(￣▽￣)','╰(￣▽￣)╭',
            '*'.ﾟ+⸜( ᐛ )⸝+ﾟ.*','ฅ\'ω\'ฅ','٩(◕‿◕｡)۶','₍₍ (̨̡ ‾᷄⌂‾᷅)̧̢ ₎₎',
            'ᐕ)⁾⁾','(∘¯̆᷇ 👍̆᷆ ￣)つ~','(っ ̯ -｡)','(｀Θ´)╯','(´ι=)ﾉ',
            '♥(´∀` )人','（●´∀｀）♥','(´◉ω◉`)','٩(ˊᗜˋ*)و','◟(∗❛ัᴗ❛ั∗)◞'
        ],
        '阿鲁': [
            '✧(≖ ◡ ≖✿)','(๑>m<๑)','(｡>ㅅ<｡)','٩(๑>∀<๑)۶','(❛ᴗ❛)',
            'ヽ(●´∀`●)ﾉ','ヾ(●´∀｀●)ﾉ','(ฅ\'ω\'ฅ)','(๑ơ ₃ ơ)♥','╰(✿´⌣`✿)╯',
            '(*•̀ᴗ•́*)و ̑̑','(๑•́ ₃ •̀๑)','(๑•̑з•̑๑)','✿✿ヽ(°▽°)ノ✿','(◕ㅅ◕✿)',
            'ヾ(◍\'౪`◍)ﾉﾞ','( ◜◡‾)(‾◡◝ )','ヾ(>ω<)ﾉ','( ･ิω･ิ)','(〃∀〃)',
            '✧*｡(ˊᗜˋ*)✧*｡','( ◞･౪･)◞','(´ΘωΘ`)','(╯✧∇✧)╯','(ΦωΦ)',
            '(:3[▓▓▓]','(ˊ● ω ●ˋ)','(๑╹ω╹๑ )','(๑>◡<๑)','ヾ(⌐■_■)ノ',
            '(¦3[▓▓]','＿|￣|○','(´_っ`)','(;´༎ຶД༎ຶ`)','_(:з」∠❀)_',
            '┌(┌ ､ﾝ､)┐','(☍﹏⁰)','(⁎˃ᆺ˂)','(∗˃̶ ᵕ ˂̶∗)','(๑•́ωก̀๑)',
            'ヾ(´︶`*)ﾉ♬','ʕ•̀ω•́ʔ✧','ʕु•̫͡•ʔु✧','(´◉ω◉`)','ლ(╹◡╹ლ)',
            '(⁎⁍̴̛ᴗ⁍̴̛⁎)','(=´∀｀)人(´∀｀=)','(๑˃̵ᴗ˂̵)و','۹( ÒہÓ )۶','ε٩(๑>₃<)۶з'
        ]
    };

    /* ============================================================
       工具栏按钮（Markdown）
       ============================================================ */
    var TOOLBAR = [
        { name: 'bold',   label: 'B',          title: '粗体 (Ctrl+B)', wrap: '**',        shortcut: 'B' },
        { name: 'italic', label: 'I',          title: '斜体 (Ctrl+I)', wrap: '*',         shortcut: 'I' },
        { name: 'del',    label: 'S',          title: '删除线',        wrap: '~~' },
        { name: 'code',   label: 'Code',       title: '行内代码',      wrap: '`' },
        { name: 'link',   label: 'Link',       title: '链接',          tpl: '[文本](https://)' },
        { name: 'image',  label: 'Img',        title: '图片',          tpl: '![描述](https://)' },
        { name: 'quote',  label: 'Quote',      title: '引用',          prefix: '> ' },
        { name: 'codeblock', label: '{}',      title: '代码块',        block: ['```', '```'] },
        { name: 'owo',    label: '😀',         title: '表情',          owo: true }
    ];

    /* ============================================================
       工具函数
       ============================================================ */
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function insertText(textarea, text, opts) {
        opts = opts || {};
        var start = textarea.selectionStart;
        var end   = textarea.selectionEnd;
        var value = textarea.value;
        var selText = value.substring(start, end);

        if (opts.wrap) {
            var inserted = opts.wrap + (selText || opts.placeholder || '') + opts.wrap;
            textarea.value = value.substring(0, start) + inserted + value.substring(end);
            var pos = start + opts.wrap.length;
            textarea.setSelectionRange(pos, pos + (selText || opts.placeholder || '').length);
        } else if (opts.block) {
            var blockText = opts.block[0] + '\n' + (selText || '') + '\n' + opts.block[1];
            textarea.value = value.substring(0, start) + blockText + value.substring(end);
            var p = start + opts.block[0].length + 1;
            textarea.setSelectionRange(p, p + selText.length);
        } else if (opts.prefix) {
            // 行首前缀（处理多行选择）
            var lines = (selText || value.substring(start, end) || '').split('\n');
            var prefixed = lines.map(function (l) { return opts.prefix + l; }).join('\n');
            textarea.value = value.substring(0, start) + prefixed + value.substring(end);
            textarea.setSelectionRange(start, start + prefixed.length);
        } else if (opts.tpl) {
            textarea.value = value.substring(0, start) + opts.tpl + value.substring(end);
            var s = start;
            // 选中文本部分（如 [文本](url) 选中「文本」）
            var m = opts.tpl.match(/\[([^\]]+)\]/);
            if (m) {
                textarea.setSelectionRange(s + 1, s + 1 + m[1].length);
            } else {
                textarea.setSelectionRange(start + opts.tpl.length, start + opts.tpl.length);
            }
        } else {
            // 直接插入（表情）
            var ins = opts.text || text;
            textarea.value = value.substring(0, start) + ins + value.substring(end);
            var np = start + ins.length;
            textarea.setSelectionRange(np, np);
        }
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /* ============================================================
       工具栏渲染
       ============================================================ */
    function renderToolbar(container, textarea) {
        container.className = (container.className || '') + ' joe-toolbar';
        TOOLBAR.forEach(function (btn) {
            var el = document.createElement('button');
            el.type = 'button';
            el.className = 'joe-toolbar__btn joe-toolbar__' + btn.name;
            el.setAttribute('title', btn.title || '');
            el.setAttribute('data-action', btn.name);
            el.innerHTML = '<span>' + btn.label + '</span>';
            el.addEventListener('click', function (e) {
                if (btn.owo) {
                    e.preventDefault();
                    toggleOwoPanel(container);
                    return;
                }
                insertText(textarea, '', btn);
            });
            container.appendChild(el);
        });

        // 快捷键
        textarea.addEventListener('keydown', function (e) {
            if (!(e.ctrlKey || e.metaKey)) return;
            var key = e.key.toUpperCase();
            var found = TOOLBAR.find(function (b) { return b.shortcut === key; });
            if (found) {
                e.preventDefault();
                insertText(textarea, '', found);
            }
        });
    }

    function toggleOwoPanel(toolbarEl) {
        var panel = toolbarEl.nextElementSibling;
        if (!panel || !panel.classList.contains('joe-owo')) return;
        panel.classList.toggle('is-open');
    }

    /* ============================================================
       表情面板渲染
       ============================================================ */
    function renderOwo(container, textarea) {
        container.className = (container.className || '') + ' joe-owo';
        var cats = Object.keys(OWO_DATA);

        var tabsHtml = cats.map(function (cat, i) {
            return '<button type="button" class="joe-owo__tab' + (i === 0 ? ' is-active' : '') + '" data-cat="' + cat + '">' + cat + '</button>';
        }).join('');

        container.innerHTML =
            '<div class="joe-owo__head">' + tabsHtml + '</div>' +
            '<div class="joe-owo__body"></div>';

        var body = $('.joe-owo__body', container);

        function showCat(cat) {
            var list = OWO_DATA[cat] || [];
            body.innerHTML = list.map(function (item, i) {
                // 区分 emoji（短字符串）和颜文字
                var isEmoji = item.length <= 4 && /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test(item);
                var cls = isEmoji ? 'joe-owo__item is-emoji' : 'joe-owo__item is-text';
                var data = item.replace(/"/g, '&quot;');
                return '<button type="button" class="' + cls + '" data-owo="' + data + '">' + item + '</button>';
            }).join('');

            $all('.joe-owo__item', body).forEach(function (el) {
                el.addEventListener('click', function () {
                    var val = el.getAttribute('data-owo');
                    insertText(textarea, '', { text: val });
                    // 表情面板点击后保持打开，方便连续插入
                });
            });
        }

        $all('.joe-owo__tab', container).forEach(function (tab) {
            tab.addEventListener('click', function () {
                $all('.joe-owo__tab', container).forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                showCat(tab.getAttribute('data-cat'));
            });
        });

        showCat(cats[0]);

        // 点击外部关闭
        document.addEventListener('click', function (e) {
            if (!container.classList.contains('is-open')) return;
            if (e.target.closest('.joe-owo') || e.target.closest('.joe-toolbar__owo')) return;
            container.classList.remove('is-open');
        });
    }

    /* ============================================================
       初始化
       ============================================================ */
    function init() {
        $all('.joe-toolbar').forEach(function (tb) {
            var sel = tb.getAttribute('data-target');
            if (!sel) return;
            var textarea = document.querySelector(sel);
            if (!textarea) return;
            renderToolbar(tb, textarea);

            // 找紧邻的 owo 容器
            var next = tb.nextElementSibling;
            if (next && next.classList.contains('joe-owo')) {
                renderOwo(next, textarea);
            }
        });
    }

    if (document.readyState !== 'loading') init();
    else document.addEventListener('DOMContentLoaded', init);

    // 暴露
    window.KingOwo = {
        init: init,
        data: OWO_DATA,
        insertText: insertText
    };
})();
