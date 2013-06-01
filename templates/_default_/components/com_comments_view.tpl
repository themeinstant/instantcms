{* ================================================================================ *}
{* ========================== Вывод комментариев ================================== *}
{* ================================================================================ *}

<div class="cmm_heading">
	{$labels.comments} (<span id="comments_count">{$comments_count}</span>)
</div>

<div class="cm_ajax_list">
{if $cfg.cmm_ajax}
	<script type="text/javascript">
        {literal}
            var anc = '';
            if (window.location.hash){
                var anc = window.location.hash;
            }
        {/literal}
        loadComments('{$target}', {$target_id}, anc);
    </script>
{else}
	{$html}
{/if}
</div>

{* ===================== Ссылки на добавление комментария и подписку ========================== *}
<a name="c"></a>
<table cellspacing="0" cellpadding="2" style="margin:15px 0 0 0;">
    <tr>
        <td width="16"><img src="/templates/{template}/images/icons/comment.png" /></td>
        <td><a href="javascript:void(0);" onclick="{$add_comment_js}" class="ajaxlink">{$labels.add}</a></td>
        {if $cfg.subscribe}
            {if $is_user}
                {if !$user_subscribed}
                    <td width="16"><img src="/templates/{template}/images/icons/subscribe.png"/></td>
                    <td><a href="/subscribe/{$target}/{$target_id}">{$LANG.SUBSCRIBE_TO_NEW}</a></td>
                {else}
                    <td width="16"><img src="/templates/{template}/images/icons/unsubscribe.png"/></td>
                    <td><a href="/unsubscribe/{$target}/{$target_id}">{$LANG.UNSUBSCRIBE}</a></td>
                {/if}
            {/if}	
        {/if}
        {if $comments_count}
            <td width="16"><img src="/templates/{template}/images/icons/rss.png" border="0" alt="{$LANG.RSS}"/></td>
            <td><a href="/rss/comments/{$target}-{$target_id}/feed.rss">{$labels.rss}</a></td>
        {/if}
    </tr>
</table>	

<div id="cm_addentry0"></div>