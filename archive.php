<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Archive
 * @description 文章归档页、时间轴视图、贡献日历、搜索整合
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<?php
$db = Typecho_Db::get();
$allPosts = $db->fetchAll($db->select('created')->from('table.contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
    ->order('created', Typecho_Db::SORT_ASC));
$postDates = [];
foreach ($allPosts as $p) {
    $d = date('Y-m-d', $p['created']);
    $postDates[$d] = ($postDates[$d] ?? 0) + 1;
}
?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap"><div class="joe-main">
<?php $keyword = htmlspecialchars($this->request->s ?? ''); ?>
<div class="joe-archive-header">
<?php if ($keyword): ?>
<h1 class="joe-archive-header__title">搜索：<?php echo $keyword; ?></h1>
<p class="joe-archive-header__desc">找到 <?php $this->count(); ?> 条结果</p>
<?php else: ?>
<h1 class="joe-archive-header__title">文章归档</h1>
<p class="joe-archive-header__desc">博学之，审问之，慎思之，明辨之，笃行之</p>
<?php endif; ?>
</div>
<?php if (!$keyword): ?>
<div class="joe-calendar joe-card"><div class="joe-calendar__header"><h3 class="joe-calendar__title">写作日历</h3><p class="joe-calendar__subtitle">过去一年的创作足迹</p></div>
<div class="joe-calendar__grid" id="joe-calendar-grid">
<?php
$today = new DateTime();
$oneYearAgo = (new DateTime())->modify('-365 days');
$weeks = [];
$current = clone $oneYearAgo;
$levelColors = [0 => 'var(--bg-code)', 1 => '#9be9a8', 2 => '#40c463', 3 => '#30a14e', 4 => '#216e39'];
while ($current <= $today) {
    $week = (int)$current->format('W');
    $day = (int)$current->format('N') - 1;
    $dateStr = $current->format('Y-m-d');
    $count = $postDates[$dateStr] ?? 0;
    if ($count >= 4) $level = 4;
    elseif ($count >= 3) $level = 3;
    elseif ($count >= 2) $level = 2;
    elseif ($count >= 1) $level = 1;
    else $level = 0;
    $weeks[$week][$day] = ['date' => $dateStr, 'count' => $count, 'level' => $level];
    $current->modify('+1 day');
}
?>
<div class="joe-calendar__weekdays"><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span><span>日</span></div>
<div class="joe-calendar__months" id="joe-calendar-months">
<?php
$allCells = [];
foreach ($weeks as $days) for ($d = 0; $d < 7; $d++) $allCells[] = $days[$d] ?? null;
foreach ($allCells as $cell):
    if ($cell === null): ?><div class="joe-calendar__cell is-empty"></div>
<?php else: ?>
<div class="joe-calendar__cell" data-date="<?php echo $cell['date']; ?>" data-count="<?php echo $cell['count']; ?>" data-level="<?php echo $cell['level']; ?>" style="background:<?php echo $levelColors[$cell['level']]; ?>" title="<?php echo $cell['date'].' · '.$cell['count'].' 篇文章'; ?>"></div>
<?php endif; endforeach; ?>
</div></div>
<div class="joe-calendar__legend">
<span class="joe-calendar__legend-text">少</span>
<span class="joe-calendar__legend-box" style="background:var(--bg-code)"></span>
<span class="joe-calendar__legend-box" style="background:#9be9a8"></span>
<span class="joe-calendar__legend-box" style="background:#40c463"></span>
<span class="joe-calendar__legend-box" style="background:#30a14e"></span>
<span class="joe-calendar__legend-box" style="background:#216e39"></span>
<span class="joe-calendar__legend-text">多</span>
</div></div>
<?php endif; ?>

<?php if ($this->have()): ?>
<?php
$currentYear = '';
while ($this->next()):
    $year = date('Y', $this->created);
    if ($year != $currentYear):
        if ($currentYear !== ''): ?></div></div>
<?php endif;
        $currentYear = $year; ?>
<div class="joe-archive-year"><h2 class="joe-archive-year__title"><?php echo $currentYear; ?></h2><div class="joe-archive-year__list">
<?php endif; ?>
<article class="joe-archive-item">
<time class="joe-archive-item__date" datetime="<?php $this->date('c'); ?>"><?php $this->date('m/d'); ?></time>
<a class="joe-archive-item__link" href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
<span class="joe-archive-item__cat"><?php $this->category(',', false); ?></span>
</article>
<?php endwhile; ?>
</div></div>
<?php $totalPage = method_exists($this, 'getTotalPage') ? $this->getTotalPage() : ($this->parameter->pageSize ?? 0); ?>
<?php $curPage = method_exists($this, 'getCurrentPage') ? $this->getCurrentPage() : $this->_currentPage; ?>
<?php if ($curPage < $totalPage): ?>
<div class="joe-pagination"><?php $this->pageLink('查看更多', 'next'); ?></div>
<?php endif; ?>
<?php elseif ($keyword): ?>
<div class="joe-card" style="text-align:center;padding:40px"><p style="color:var(--text-muted)">没有找到相关内容，换个关键词试试？</p></div>
<?php endif; ?>
</div><?php $this->need('sidebar.php'); ?></div>
</main>
<?php $this->need('footer.php'); ?>