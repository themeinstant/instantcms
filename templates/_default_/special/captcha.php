<?php
    if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }
?>
<table align="left" cellpadding="2" cellspacing="0">
    <tr>
        <td valign="middle" width="130" style="padding-left:0"><img id="<?php echo $input_id; ?>" class="captcha" src="/includes/codegen/cms_codegen.php" border="0" /></td>
        <td valign="middle">
            <div>Введите код:</div>
            <div><input name="<?php echo $input_name; ?>" type="text" style="width:120px" class="text-input" /></div>
            <div><a href="javascript:reloadCaptcha('<?php echo $input_id;  ?>')"><small>Обновить картинку</small></a></div>
        </td>
    </tr>
</table>