<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * KingJoe Theme
 *
 * @package     KingJoe
 * @template    Links
 * @description 友情链接页，支持分类展示与在线申请
 * @version     1.0.7
 * @link        https://github.com/sxlb/king
 */
?>
<?php $this->need('header.php'); ?>

<main class="joe-container" id="main">
    <div class="joe-main__wrap joe-main__wrap--single">
        <div class="joe-main joe-main--single">
            <article class="joe-article joe-links">
                <header class="joe-article__head">
                    <h1 class="joe-article__title"><?php $this->title(); ?></h1>
                </header>

                <div class="joe-article__content joe-content">
                    <?php echo joe_add_heading_ids($this->content); ?>
                </div>

                <?php
                $linksData = joe_get('linksData');
                $groups = joe_parse_links($linksData);
                if ($groups):
                    // 随机排序友链
                    if (joe_get('linksRandom') === '1') {
                        foreach ($groups as &$g) {
                            shuffle($g['items']);
                        }
                        unset($g);
                    }
                    foreach ($groups as $group):
                ?>
                <section class="joe-links__group">
                    <h2 class="joe-links__title">
                        <span class="joe-section__bar"></span>
                        <?php echo htmlspecialchars($group['name']); ?>
                        <small class="joe-links__count"><?php echo count($group['items']); ?></small>
                    </h2>
                    <ul class="joe-links__grid">
                        <?php foreach ($group['items'] as $item):
                            $name = $item['name'];
                            $url  = $item['url'];
                            $avatar = $item['avatar'];
                            $desc = $item['desc'];
                            $initial = mb_substr($name, 0, 1);
                        ?>
                        <li class="joe-links__item">
                            <a href="<?php echo joe_esc($url); ?>" target="_blank" rel="noopener noreferrer" class="joe-links__card">
                                <span class="joe-links__avatar">
                                    <?php if ($avatar): ?>
                                        <?php echo joe_lazy_img($avatar, $name, 'joe-links__avatar-img', 84, 84); ?>
                                    <?php else: ?>
                                        <span class="joe-links__initial"><?php echo $initial; ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="joe-links__info">
                                    <span class="joe-links__name"><?php echo htmlspecialchars($name); ?></span>
                                    <?php if ($desc): ?>
                                    <span class="joe-links__desc"><?php echo htmlspecialchars($desc); ?></span>
                                    <?php endif; ?>
                                </span>
                                <svg class="joe-links__arrow" viewBox="0 0 24 24" width="14" height="14"><path d="M7 17L17 7M9 7h8v8" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endforeach; else: ?>
                    <p class="joe-empty">还没有添加友链，请在「控制台 → 外观 → 设置外观」中配置</p>
                <?php endif; ?>

                <!-- 友链在线申请 -->
                <?php if (joe_get('linkApply') === '1'): ?>
                <section class="joe-link-apply">
                    <h2 class="joe-link-apply__title">申请友链</h2>
                    <form id="joe-link-apply-form">
                        <div class="joe-link-apply__group">
                            <label class="joe-link-apply__label">站点名称 *</label>
                            <input type="text" name="site_name" class="joe-link-apply__input" placeholder="你的站点名称" required>
                        </div>
                        <div class="joe-link-apply__group">
                            <label class="joe-link-apply__label">站点地址 *</label>
                            <input type="url" name="site_url" class="joe-link-apply__input" placeholder="https://example.com" required>
                        </div>
                        <div class="joe-link-apply__group">
                            <label class="joe-link-apply__label">站点描述</label>
                            <input type="text" name="site_desc" class="joe-link-apply__input" placeholder="一句话介绍你的站点">
                        </div>
                        <div class="joe-link-apply__group">
                            <label class="joe-link-apply__label">联系邮箱 *</label>
                            <input type="email" name="site_email" class="joe-link-apply__input" placeholder="your@email.com" required>
                        </div>
                        <div class="joe-link-apply__group">
                            <label class="joe-link-apply__label">验证码：请输入右侧数字</label>
                            <div class="joe-link-apply__captcha">
                                <input type="text" name="captcha" class="joe-link-apply__input" style="flex:1" placeholder="验证码" minlength="4" maxlength="4" required>
                                <span class="joe-link-apply__captcha-img" id="joe-link-captcha">1234</span>
                            </div>
                        </div>
                        <button type="submit" class="joe-link-apply__btn">提交申请</button>
                        <p class="joe-link-apply__msg" id="joe-link-apply-msg"></p>
                    </form>
                </section>
                <?php endif; ?>

                <?php if ($this->allow('comment')): ?>
                    <?php $this->need('comments.php'); ?>
                <?php endif; ?>
            </article>
        </div>

        <?php $this->need('sidebar.php'); ?>
    </div>
</main>

<?php $this->need('footer.php'); ?>
