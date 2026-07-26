<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Sidebar
 * @description 侧边栏、作者卡片、热门/随机文章、标签云
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<aside class="joe-sidebar">
    <!-- 作者卡片 -->
    <section class="joe-card joe-author">
        <div class="joe-author__head">
            <div class="joe-author__avatar">
                <?php if (joe_get('authorAvatar')): ?>
                    <?php echo joe_lazy_img(joe_get('authorAvatar'), joe_get('authorName'), 'joe-author__avatar-img', 56, 56); ?>
                <?php else: ?>
                    <span><?php echo mb_substr(joe_get('authorName') ?: 'K', 0, 1); ?></span>
                <?php endif; ?>
            </div>
            <div class="joe-author__info">
                <h3 class="joe-author__name"><?php joe_opt('authorName'); ?></h3>
                <p class="joe-author__desc"><?php joe_opt('authorDesc'); ?></p>
            </div>
        </div>
        <?php
        $social = joe_get('authorSocial');
        if ($social):
        $lines = explode("\n", trim($social));
        $icons = [
            'github' => '<path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.3 5.4 2.6 5.4 2.6a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
            'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="m3 7 9 6 9-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>',
            'rss'    => '<path d="M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><circle cx="5" cy="19" r="1.5" fill="currentColor"/>',
            'weibo'  => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/>',
            'twitter'=> '<path d="M22 5.8c-.8.4-1.6.6-2.5.8a4.3 4.3 0 0 0-7.4-3 4.3 4.3 0 0 0-1.2 3.1A12 12 0 0 1 3 4.6s-2 4.5 1 7.3c-.7 0-1.4-.2-2-.5 0 2.2 1.6 4.1 3.6 4.5a4.4 4.4 0 0 1-2 0 4.3 4.3 0 0 0 4 3 8.7 8.7 0 0 1-6.3 1.8A12.2 12.2 0 0 0 9 23c8 0 12.5-6.7 12.5-12.5v-.6a8.7 8.7 0 0 0 2.2-2.3Z" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linejoin="round"/>',
        ];
        ?>
        <div class="joe-author__social">
            <?php foreach ($lines as $line):
                $parts = explode('|', trim($line));
                if (count($parts) !== 2) continue;
                list($name, $url) = $parts;
                $icon = $icons[strtolower($name)] ?? '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/>';
            ?>
            <a href="<?php echo joe_esc($url); ?>" class="joe-author__social-item" target="_blank" rel="noopener noreferrer" title="<?php echo joe_esc(ucfirst($name)); ?>">
                <svg viewBox="0 0 24 24" width="16" height="16"><?php echo $icon; ?></svg>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- 热门文章 / 随机文章 -->
    <section class="joe-card">
        <div class="joe-card__head">
            <span class="joe-card__bar"></span>
            <div class="joe-card__tabs">
                <button class="joe-card__tab is-active" data-tab="hot">热门</button>
                <button class="joe-card__tab" data-tab="random">随机</button>
            </div>
        </div>
        <ul class="joe-hotlist" id="joe-tab-hot">
            <?php $hot = joe_hot_posts(6, 'views'); $i = 0;
            foreach ($hot as $post): $i++; ?>
            <li class="joe-hotlist__item">
                <span class="joe-hotlist__rank<?php if ($i <= 3) echo ' is-top'; ?>"><?php echo $i; ?></span>
                <div class="joe-hotlist__content">
                    <a href="<?php echo $post['permalink']; ?>" class="joe-hotlist__title"><?php echo joe_esc($post['title']); ?></a>
                    <span class="joe-hotlist__views"><?php echo $post['views']; ?> 阅读</span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <ul class="joe-hotlist" id="joe-tab-random" style="display:none">
            <?php $rand = joe_random_posts(6); $i = 0;
            foreach ($rand as $post): $i++; ?>
            <li class="joe-hotlist__item">
                <span class="joe-hotlist__rank"><?php echo $i; ?></span>
                <div class="joe-hotlist__content">
                    <a href="<?php echo $post['permalink']; ?>" class="joe-hotlist__title"><?php echo joe_esc($post['title']); ?></a>
                    <span class="joe-hotlist__views"><?php echo date('m-d', $post['created']); ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- 分类 -->
    <section class="joe-card">
        <div class="joe-card__head"><span class="joe-card__bar"></span>分类</div>
        <ul class="joe-catlist">
            <?php $this->widget('Widget_Metas_Category_List')->to($cats); ?>
            <?php while ($cats->next()): ?>
            <li class="joe-catlist__item">
                <a href="<?php $cats->permalink(); ?>"><?php $cats->name(); ?></a>
                <span class="joe-catlist__count"><?php $cats->count(); ?></span>
            </li>
            <?php endwhile; ?>
        </ul>
    </section>

    <!-- 标签云 -->
    <section class="joe-card">
        <div class="joe-card__head"><span class="joe-card__bar"></span>标签云</div>
        <div class="joe-tagcloud">
            <?php
            $tags = joe_tags_cloud(30);
            if ($tags):
                $max = $tags[0]['count'];
                foreach ($tags as $tag):
                    $size = 12 + round(($tag['count'] / max($max, 1)) * 8);
                    $url = Typecho_Common::url('tag/' . urlencode($tag['name']), Helper::options()->siteUrl);
            ?>
            <a href="<?php echo $url; ?>" class="joe-tagcloud__item" style="font-size:<?php echo $size; ?>px"><?php echo joe_esc($tag['name']); ?></a>
            <?php endforeach; else: ?>
            <p class="joe-empty">暂无标签</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- 最近回复 -->
    <section class="joe-card">
        <div class="joe-card__head"><span class="joe-card__bar"></span>最近回复</div>
        <ul class="joe-recentcomments">
            <?php $this->widget('Widget_Comments_Recent')->to($comments); ?>
            <?php while ($comments->next()): ?>
            <li class="joe-recentcomments__item">
                <div class="joe-recentcomments__author"><?php $comments->author(false); ?></div>
                <a href="<?php $comments->permalink(); ?>" class="joe-recentcomments__text"><?php echo mb_substr(strip_tags((string) $comments->text), 0, 30, 'UTF-8') . '...'; ?></a>
            </li>
            <?php endwhile; ?>
        </ul>
    </section>

    <!-- 那年今日 -->
    <?php if (joe_get('onThisDay') === '1' && $this->is('index')): ?>
    <?php $otd = joe_on_this_day(); if (!empty($otd)): ?>
    <section class="joe-card joe-otd">
        <div class="joe-card__head"><span class="joe-card__bar"></span>那年今日</div>
        <ul class="joe-otd__list">
            <?php foreach ($otd as $post): ?>
            <li class="joe-otd__item">
                <a href="<?php echo $post['permalink']; ?>" class="joe-otd__title"><?php echo joe_esc($post['title']); ?></a>
                <span class="joe-otd__year"><?php echo $post['year']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; endif; ?>

    <!-- 随机一言 -->
    <?php if (joe_get('hitokoto') === '1'): ?>
    <section class="joe-card joe-hitokoto">
        <div class="joe-card__head"><span class="joe-card__bar"></span>一言</div>
        <p class="joe-hitokoto__text"><?php echo joe_hitokoto(); ?></p>
    </section>
    <?php endif; ?>
</aside>
