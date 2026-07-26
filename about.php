<?php
/**
 * 关于页
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

// 自定义字段：nickname / sign / location / email / github / weibo / skills / timeline
$nick  = $this->fields->nickname ? $this->fields->nickname : $this->user->screenName;
$sign  = $this->fields->sign ? $this->fields->sign : '热爱生活，热爱代码。';
$loc   = $this->fields->location ?: '';
$email = $this->fields->email ?: '';
$github= $this->fields->github ?: '';
$weibo = $this->fields->weibo ?: '';
$avatar= $this->fields->avatar ?: '';

// skills 格式：PHP:90,JavaScript:80,HTML/CSS:95
$skillsRaw = trim($this->fields->skills ?: '');
$skills = [];
if ($skillsRaw) {
    foreach (explode(',', $skillsRaw) as $s) {
        $parts = array_map('trim', explode(':', $s));
        if (count($parts) === 2 && is_numeric($parts[1])) {
            $skills[] = ['name' => $parts[0], 'value' => min(100, max(0, (int)$parts[1]))];
        }
    }
}
if (empty($skills)) {
    $skills = [
        ['name' => 'PHP',       'value' => 85],
        ['name' => 'JavaScript','value' => 80],
        ['name' => 'HTML / CSS','value' => 90],
        ['name' => 'MySQL',     'value' => 75],
        ['name' => 'Typecho',   'value' => 95],
    ];
}

// timeline 格式：2023-01-01|标题|描述;...
$timelineRaw = trim($this->fields->timeline ?: '');
$timeline = [];
if ($timelineRaw) {
    foreach (explode(';', $timelineRaw) as $t) {
        $parts = array_map('trim', explode('|', $t));
        if (count($parts) >= 2) {
            $timeline[] = [
                'date'  => $parts[0],
                'title' => $parts[1],
                'desc'  => isset($parts[2]) ? $parts[2] : '',
            ];
        }
    }
}
if (empty($timeline)) {
    $timeline = [
        ['date' => '2020', 'title' => '开始写博客', 'desc' => '用 Typecho 搭建了第一个博客，记录学习与生活。'],
        ['date' => '2022', 'title' => '转向前端开发', 'desc' => '从后端到全栈，开始接触 Vue / React 等现代前端框架。'],
        ['date' => '2024', 'title' => '持续学习', 'desc' => '保持对新技术的好奇心，努力成为更好的工程师。'],
    ];
}
?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article joe-about">
                <!-- 面包屑 -->
                <nav class="joe-breadcrumb">
                    <a href="<?php $this->options->siteUrl(); ?>">首页</a>
                    <span class="joe-breadcrumb__sep">/</span>
                    <span class="joe-breadcrumb__current"><?php $this->title(); ?></span>
                </nav>

                <!-- 个人信息 -->
                <section class="joe-about__hero">
                    <div class="joe-about__avatar">
                        <?php if ($avatar): ?>
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($nick); ?>" loading="lazy">
                        <?php else: ?>
                            <?php echo mb_substr($nick, 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="joe-about__info">
                        <h2 class="joe-about__name"><?php echo htmlspecialchars($nick); ?></h2>
                        <p class="joe-about__desc"><?php echo htmlspecialchars($sign); ?></p>
                        <div class="joe-about__meta">
                            <?php if ($loc): ?>
                            <span class="joe-about__meta-item">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                <?php echo htmlspecialchars($loc); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($email): ?>
                            <span class="joe-about__meta-item">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M3 7h18v12H3z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="m3 7 9 7 9-7" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                                <?php echo htmlspecialchars($email); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($github): ?>
                            <a class="joe-about__meta-item" href="<?php echo htmlspecialchars($github); ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-2c-2.8.6-3.4-1.3-3.4-1.3-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.6.3-1.1.6-1.4-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.5-1.3.1-2.7 0 0 .8-.3 2.8 1a9.6 9.6 0 0 1 5 0c2-1.3 2.8-1 2.8-1 .6 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.9-2.4 4.7-4.6 5 .4.3.7.9.7 1.8v2.6c0 .3.2.6.7.5A10 10 0 0 0 12 2Z" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linejoin="round"/></svg>
                                GitHub
                            </a>
                            <?php endif; ?>
                            <?php if ($weibo): ?>
                            <a class="joe-about__meta-item" href="<?php echo htmlspecialchars($weibo); ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M10.5 18c-3.6 0-6.5-2.2-6.5-5s2.9-5 6.5-5c3.6 0 6.5 2.2 6.5 5s-2.9 5-6.5 5Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M18 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm-1.5 2a4 4 0 1 0 0-8" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                                微博
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- 统计卡片 -->
                <section class="joe-about__section">
                    <h3 class="joe-about__section-title"><span class="joe-section__bar"></span>博客数据</h3>
                    <div class="joe-info-grid">
                        <div class="joe-info-card">
                            <div class="joe-info-card__icon">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/><path d="M4 9h16M8 5v14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                            </div>
                            <div class="joe-info-card__title">文章数量</div>
                            <div class="joe-info-card__value"><?php joe_article_count(); ?></div>
                        </div>
                        <div class="joe-info-card">
                            <div class="joe-info-card__icon">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                            </div>
                            <div class="joe-info-card__title">总浏览量</div>
                            <div class="joe-info-card__value"><?php $s = joe_site_stats(); echo $s['views']; ?></div>
                        </div>
                        <div class="joe-info-card">
                            <div class="joe-info-card__icon">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l2-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="joe-info-card__title">评论数量</div>
                            <div class="joe-info-card__value"><?php joe_comment_count(); ?></div>
                        </div>
                        <div class="joe-info-card">
                            <div class="joe-info-card__icon">
                                <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                            </div>
                            <div class="joe-info-card__title">运行天数</div>
                            <div class="joe-info-card__value"><?php echo joe_site_age(); ?> 天</div>
                        </div>
                    </div>
                </section>

                <!-- 技能条 -->
                <section class="joe-about__section">
                    <h3 class="joe-about__section-title"><span class="joe-section__bar"></span>技能树</h3>
                    <div class="joe-skills">
                        <?php foreach ($skills as $s): ?>
                        <div class="joe-skill" style="--v: <?php echo $s['value']; ?>%;">
                            <div class="joe-skill__head">
                                <span class="joe-skill__name"><?php echo htmlspecialchars($s['name']); ?></span>
                                <span class="joe-skill__value"><?php echo $s['value']; ?>%</span>
                            </div>
                            <div class="joe-skill__bar">
                                <div class="joe-skill__fill"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- 时间线 -->
                <section class="joe-about__section">
                    <h3 class="joe-about__section-title"><span class="joe-section__bar"></span>成长轨迹</h3>
                    <div class="joe-timeline">
                        <?php foreach ($timeline as $t): ?>
                        <div class="joe-timeline__item">
                            <div class="joe-timeline__date"><?php echo htmlspecialchars($t['date']); ?></div>
                            <div class="joe-timeline__title"><?php echo htmlspecialchars($t['title']); ?></div>
                            <?php if ($t['desc']): ?>
                            <div class="joe-timeline__desc"><?php echo htmlspecialchars($t['desc']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- 正文内容（编辑区写的内容） -->
                <section class="joe-about__section">
                    <div class="joe-article__content joe-content">
                        <?php echo joe_add_heading_ids($this->content); ?>
                    </div>
                </section>

            </article>

            <!-- 评论区 -->
            <?php $this->need('comments.php'); ?>
        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>
