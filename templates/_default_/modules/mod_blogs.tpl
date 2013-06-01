{foreach key=tid item=post from=$posts}
    <div class="mod_latest_entry">

        <div class="mod_latest_image">
            <a href="{profile_url login=$post.login}" title="{$post.author|escape:'html'}"><img border="0" class="usr_img_small" src="{$post.author_avatar}" /></a>
        </div>

        <a class="mod_latest_blog_title" href="{$post.url}" title="{$post.title|escape:'html'}">{$post.title|truncate:70}</a>

        <div class="mod_latest_date">
            {$post.fpubdate} - <a href="{$post.blog_url}">{$post.blog_title}</a> - <a href="{$post.url}#c" title="{$post.comments_count|spellcount:$LANG.COMMENT1:$LANG.COMMENT2:$LANG.COMMENT10}" class="mod_latest_comments">{$post.comments_count}</a> - <span class="mod_latest_rating">{$post.rating|rating}</span>
        </div>

    </div>
{/foreach}

{if $cfg.showrss}
    <div class="mod_latest_rss">
        <a href="/rss/blogs/all/feed.rss">{$LANG.RSS}</a>
    </div>
{/if}