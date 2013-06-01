<form action="/core/ajax/wall.php" method="POST" id="add_wall_form">
    <input type="hidden" name="target_id" value="{$target_id}" />
    <input type="hidden" name="component" value="{$component}" />
    <input type="hidden" name="do_wall" value="add" />
    <input type="hidden" name="submit" value="1" />
    <input type="hidden" name="csrf_token" value="{csrf_token}" />
    <div class="usr_msg_bbcodebox">{$bb_toolbar}</div>
    <div class="cm_smiles">{$smilies}</div>
    <div class="cm_editor">
        <textarea name="message" id="message" class="ajax_autogrowarea"></textarea>
    </div>
</form>
<br />
<div class="sess_messages" style="display:none">
  <div class="message_info" id="error_mess"></div>
</div>
<script type="text/javascript" src="/includes/jquery/jquery.form.js"></script>
{literal}
<script type="text/javascript">
    $(document).ready(function(){
        $('#message').focus();
    });
</script>
{/literal}