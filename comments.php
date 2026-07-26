<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php /** @var Widget_Archive $this */ ?>
<?php if ($this->allow('comment')): ?>
<section class="joe-comments" id="comments">
    <div class="joe-section__head">
        <h2 class="joe-section__title"><span class="joe-section__bar"></span>评论</h2>
    </div>

    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
    <ol class="joe-comments__list">
        <?php while ($comments->next()): ?>
        <?php
        // 私密评论处理：非博主/作者不可见
        $isPrivate = joe_is_private_comment($comments->content);
        $isOwner = $this->user->hasLogin() && ($this->user->uid === $comments->authorId || $this->user->pass('administrator', true));
        if ($isPrivate && !$isOwner) continue;
        ?>
        <li class="joe-comment<?php if ($isPrivate) echo ' is-private'; ?>" id="<?php $comments->theId(); ?>">
            <div class="joe-comment__avatar">
                <?php $comments->gravatar(48); ?>
            </div>
            <div class="joe-comment__body">
                <div class="joe-comment__head">
                    <span class="joe-comment__author"><?php $comments->author(); ?></span>
                    <?php if ($isPrivate): ?>
                    <span class="joe-comment__private-badge" title="仅博主可见">
                        <svg viewBox="0 0 24 24" width="12" height="12"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 11V7a5 5 0 1 1 10 0v4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        私密
                    </span>
                    <?php endif; ?>
                    <?php if (!$comments->authorId): ?>
                    <span class="joe-comment__ua"><?php echo joe_user_agent_badge($comments->agent); ?></span>
                    <?php endif; ?>
                    <span class="joe-comment__date"><?php $comments->date('Y-m-d H:i'); ?></span>
                </div>
                <div class="joe-comment__text">
                    <?php if ($isPrivate && !$isOwner): ?>
                    <em class="joe-comment__private-hint">该评论为私密评论，仅博主可见</em>
                    <?php else: ?>
                    <?php echo joe_owo_parse($isPrivate ? joe_unwrap_private($comments->content) : $comments->content); ?>
                    <?php endif; ?>
                </div>
                <div class="joe-comment__action">
                    <?php $comments->reply('<span class="joe-comment__reply">回复</span>'); ?>
                    <?php if (joe_get('commentLike') !== '0'): ?>
                    <button class="joe-comment__like" data-coid="<?php echo $comments->coid; ?>" aria-label="点赞">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        <span class="joe-comment__like-count"><?php echo joe_comment_likes($comments->coid); ?></span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php if ($comments->children): ?>
                <ol class="joe-comment__children"><?php $comments->threadedComments(); ?></ol>
                <?php endif; ?>
            </div>
        </li>
        <?php endwhile; ?>
    </ol>
    <?php $comments->pageNav('«', '»', 1, '...', ['wrapTag' => 'nav', 'wrapClass' => 'joe-pagination']); ?>
    <?php else: ?>
    <p class="joe-empty">还没有评论，快来抢沙发吧～</p>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
    <div class="joe-commentbox" id="respond">
        <h3 class="joe-commentbox__title"><?php _e('发表评论'); ?> <small><?php $comments->cancelReply(); ?></small></h3>
        <form method="post" action="<?php $this->commentUrl(); ?>" class="joe-commentbox__form" id="comment-form">
            <?php if ($this->user->hasLogin()): ?>
            <p class="joe-commentbox__hint">
                已登录<a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> ·
                <a href="<?php $this->options->logoutUrl(); ?>">退出</a>
            </p>
            <?php else: ?>
            <div class="joe-commentbox__fields">
                <input type="text" name="author" placeholder="昵称 *" class="joe-input" value="<?php $this->remember('author'); ?>" required>
                <input type="email" name="mail" placeholder="邮箱 *" class="joe-input" value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail) echo 'required'; ?>>
                <input type="url" name="url" placeholder="网址" class="joe-input" value="<?php $this->remember('url'); ?>" <?php if ($this->options->commentsRequireURL) echo 'required'; ?>>
            </div>
            <?php endif; ?>
            <textarea name="text" id="comment-textarea" class="joe-textarea" placeholder="说点什么吧，支持基础 Markdown 语法" required><?php $this->remember('text'); ?></textarea>
            <div class="joe-commentbox__foot">
                <div class="joe-commentbox__tools">
                    <div class="joe-toolbar" data-target="#comment-textarea"></div>
                    <div class="joe-owo" data-target="#comment-textarea"></div>
                </div>
                <div class="joe-commentbox__actions">
                    <?php if (joe_get('privateComment') !== '0'): ?>
                    <label class="joe-private-label" title="勾选后仅博主可见">
                        <input type="checkbox" name="private_comment" value="1" class="joe-private-check">
                        <span class="joe-private-icon">
                            <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 11V7a5 5 0 1 1 10 0v4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                        </span>
                        私密
                    </label>
                    <?php endif; ?>
                    <button type="submit" class="joe-btn joe-btn--primary">发表评论</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
