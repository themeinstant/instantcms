{add_js file="includes/jquery/lightbox/js/jquery.lightbox.js"}
{add_css file='includes/jquery/lightbox/css/jquery.lightbox.css'}

{if $is_admin || $is_moder || $is_member}
<div class="float_bar">{if $is_admin || $is_moder}<a class="ajaxlink usr_edit_album" href="javascript:void(0)" onclick="clubs.renameAlbum({$album.id});return false;">{$LANG.RENAME_ALBUM}</a> | <a class="ajaxlink usr_del_album" href="javascript:void(0)" onclick="clubs.deleteAlbum({$album.id}, '{csrf_token}');return false;">{$LANG.DELETE_ALBUM}</a> | {/if}<a class="photo_add_link" href="/clubs/addphoto{$album.id}.html">{$LANG.ADD_PHOTO_TO_ALBUM}</a></div>
{/if}

<h1 class="con_heading"><span id="album_title">{$album.title}</span> ({$total})</h1>
<div class="clear"></div>
		
{if $photos}
{assign var="col" value="1"}
<div class="photo_gallery">
    <table cellpadding="0" cellspacing="0" border="0">
        {foreach key=tid item=con from=$photos}
            {if $col==1} <tr> {/if}
            <td align="center" valign="middle" width="{math equation="100/x" x=$cfg.photo_maxcols}%">
                <div class="photo_thumb" align="center">
                    <a class="lightbox-enabled" rel="lightbox-galery" href="/images/photos/medium/{$con.file}" title="{$con.title|escape:'html'}">
                        <img class="photo_thumb_img" src="/images/photos/small/{$con.file}" alt="{$con.title|escape:'html'}" border="0" />
                    </a><br />
                    <a href="/clubs/photo{$con.id}.html" title="{$con.title|escape:'html'}">{$con.title|truncate:18}</a>
                    {if !$con.published}
                    	<div style="color:#F00; font-size:12px">{$LANG.WAIT_MODERING}</div>
                    {/if}
            	</div>
            </td>
        {if $col==$cfg.photo_maxcols} </tr> {assign var="col" value="1"} {else} {math equation="x + 1" x=$col assign="col"} {/if}
        {/foreach}
        {if $col>1} 
            <td colspan="{math equation="x - y + 1" x=$col y=$cfg.photo_maxcols}">&nbsp;</td></tr>
        {/if}
   </table>
</div>
{$pagebar}
{else}
<p>{$LANG.NOT_PHOTOS_IN_ALBUM}</p>    
{/if}