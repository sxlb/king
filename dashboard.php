<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Dashboard
 * @description 主题数据看板，展示站点统计、内容分析、性能指标
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<?php
$db = Typecho_Db::get();
$prefix = $db->getPrefix();

// 统计数据
$stats = joe_site_stats();

// 月发布趋势（最近12个月）
$monthlyPosts = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $count = $db->fetchObject($db->select(['COUNT(*)' => 'c'])->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish')
        ->where('created >= ?', strtotime($month . '-01'))
        ->where('created < ?', strtotime(date('Y-m-d', strtotime($month . '-01 +1 month'))))
    );
    $monthlyPosts[$month] = $count ? (int)$count->c : 0;
}

// 热门文章 TOP 5
$hotPosts = $db->fetchAll($db->select('title', 'slug', 'views', 'agree', 'commentsNum')
    ->from('table.contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
    ->order('views', Typecho_Db::SORT_DESC)
    ->limit(10));

// 评论趋势（最近30天）
$dailyComments = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $count = $db->fetchObject($db->select(['COUNT(*)' => 'c'])->from('table.comments')
        ->where('created >= ?', strtotime($day))
        ->where('created < ?', strtotime($day . ' +1 day'))
    );
    $dailyComments[$day] = $count ? (int)$count->c : 0;
}

$maxDayComments = $dailyComments ? max($dailyComments) : 1;
$maxMonthPosts = $monthlyPosts ? max($monthlyPosts) : 1;
?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap">
        <div class="joe-main" style="width:100%;max-width:none;">

            <div class="joe-dashboard-header">
                <h1 class="joe-dashboard-header__title">📊 数据看板</h1>
                <p class="joe-dashboard-header__desc">站点运行状态与内容分析</p>
            </div>

            <!-- 统计卡片 -->
            <div class="joe-dashboard-cards">
                <div class="joe-dashboard-card">
                    <div class="joe-dashboard-card__icon" style="background:var(--primary-light);color:var(--primary);">
                        <svg viewBox="0 0 24 24" width="24" height="24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <div class="joe-dashboard-card__body">
                        <div class="joe-dashboard-card__value"><?php echo number_format($stats['posts']); ?></div>
                        <div class="joe-dashboard-card__label">文章总数</div>
                    </div>
                </div>
                <div class="joe-dashboard-card">
                    <div class="joe-dashboard-card__icon" style="background:rgba(26,188,156,.12);color:#1abc9c;">
                        <svg viewBox="0 0 24 24" width="24" height="24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <div class="joe-dashboard-card__body">
                        <div class="joe-dashboard-card__value"><?php echo number_format($stats['comments']); ?></div>
                        <div class="joe-dashboard-card__label">评论总数</div>
                    </div>
                </div>
                <div class="joe-dashboard-card">
                    <div class="joe-dashboard-card__icon" style="background:rgba(243,156,18,.12);color:#f39c12;">
                        <svg viewBox="0 0 24 24" width="24" height="24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <div class="joe-dashboard-card__body">
                        <div class="joe-dashboard-card__value"><?php echo number_format($stats['views']); ?></div>
                        <div class="joe-dashboard-card__label">总浏览量</div>
                    </div>
                </div>
                <div class="joe-dashboard-card">
                    <div class="joe-dashboard-card__icon" style="background:rgba(155,89,182,.12);color:#9b59b6;">
                        <svg viewBox="0 0 24 24" width="24" height="24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <div class="joe-dashboard-card__body">
                        <div class="joe-dashboard-card__value"><?php echo $stats['days']; ?></div>
                        <div class="joe-dashboard-card__label">运行天数</div>
                    </div>
                </div>
            </div>

            <!-- 内容构成 -->
            <div class="joe-dashboard-row">
                <div class="joe-dashboard-section joe-card">
                    <h3 class="joe-dashboard-section__title">目录构成</h3>
                    <div class="joe-dashboard-bars">
                        <?php foreach ([
                            ['label' => '文章', 'value' => $stats['posts'], 'color' => 'var(--primary)'],
                            ['label' => '页面', 'value' => $stats['pages'], 'color' => '#1abc9c'],
                            ['label' => '分类', 'value' => $stats['categories'], 'color' => '#f39c12'],
                            ['label' => '标签', 'value' => $stats['tags'], 'color' => '#9b59b6'],
                        ] as $item): 
                            $max = max($stats['posts'], $stats['pages'], $stats['categories'], $stats['tags'], 1);
                            $pct = round($item['value'] / $max * 100);
                        ?>
                        <div class="joe-dashboard-bar">
                            <div class="joe-dashboard-bar__label"><?php echo $item['label']; ?></div>
                            <div class="joe-dashboard-bar__track">
                                <div class="joe-dashboard-bar__fill" style="width:<?php echo $pct; ?>%;background:<?php echo $item['color']; ?>;"></div>
                            </div>
                            <div class="joe-dashboard-bar__value"><?php echo number_format($item['value']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="joe-dashboard-section joe-card">
                    <h3 class="joe-dashboard-section__title">热门文章 TOP 10</h3>
                    <div class="joe-dashboard-list">
                        <?php $rank = 1; foreach ($hotPosts as $hp): ?>
                        <a href="<?php echo joe_esc($this->options->siteUrl . '/index.php/archives/' . $hp['slug'] . '/'); ?>" class="joe-dashboard-list__item">
                            <span class="joe-dashboard-list__rank"><?php echo $rank++; ?></span>
                            <span class="joe-dashboard-list__title"><?php echo joe_esc($hp['title']); ?></span>
                            <span class="joe-dashboard-list__stat">👁 <?php echo (int)$hp['views']; ?></span>
                            <span class="joe-dashboard-list__stat">❤ <?php echo (int)$hp['agree']; ?></span>
                            <span class="joe-dashboard-list__stat">💬 <?php echo (int)$hp['commentsNum']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 趋势图 -->
            <div class="joe-dashboard-row">
                <div class="joe-dashboard-section joe-card">
                    <h3 class="joe-dashboard-section__title">月发布趋势</h3>
                    <div class="joe-dashboard-chart" id="monthly-chart">
                        <?php foreach ($monthlyPosts as $m => $c): 
                            $h = $maxMonthPosts > 0 ? round($c / $maxMonthPosts * 160) : 0;
                            $label = date('n月', strtotime($m));
                        ?>
                        <div class="joe-dashboard-chart__col">
                            <div class="joe-dashboard-chart__bar" style="height:<?php echo max($h, 2); ?>px;" title="<?php echo $m . ': ' . $c . ' 篇'; ?>">
                                <span class="joe-dashboard-chart__val"><?php echo $c; ?></span>
                            </div>
                            <div class="joe-dashboard-chart__label"><?php echo $label; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="joe-dashboard-section joe-card">
                    <h3 class="joe-dashboard-section__title">近30天评论趋势</h3>
                    <div class="joe-dashboard-chart" id="daily-chart">
                        <?php foreach ($dailyComments as $d => $c): 
                            $h = $maxDayComments > 0 ? round($c / $maxDayComments * 160) : 0;
                            $label = date('d', strtotime($d));
                        ?>
                        <div class="joe-dashboard-chart__col">
                            <div class="joe-dashboard-chart__bar joe-dashboard-chart__bar--accent" style="height:<?php echo max($h, 2); ?>px;" title="<?php echo $d . ': ' . $c . ' 条'; ?>">
                                <span class="joe-dashboard-chart__val"><?php echo $c; ?></span>
                            </div>
                            <div class="joe-dashboard-chart__label"><?php echo $label; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php $this->need('footer.php'); ?>
