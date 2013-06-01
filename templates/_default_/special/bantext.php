<?php
    if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Ваш доступ к сайту заблокирован</title>
        <meta http-equiv="refresh" content="25;URL=/">
        <style type="text/css">
            * { font-family: Arial; }
            html, body { height:100%; margin:0px; }
            h2 { color: #9F3535; margin:0px; }
            p { margin:0px; margin-top:4px; font-size:14px; }
        </style>
    </head>
    <body>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
            <tr>
                <td align="center">
                    <table border="0" cellpadding="0" cellspacing="0" >
                        <tr>
                            <td width="150">
                                <img src="/templates/<?php echo cmsConfig::getConfig('template');?>/special/images/banned.png" />
                            </td>
                            <td>
                                <div style="color:red"><strong>Ваш доступ к сайту заблокирован</strong></div>
                                <div style="padding:15px;">
                                    <div><strong>Дата блокировки:</strong> <?php echo $ban['bandate'] ?></div>
                                    <?php if ($ban['int_num']<=0){ ?>
                                        <div><strong>Срок блокировки:</strong> бесконечен</div>
                                    <?php } else { ?>
                                        <div><strong>Срок блокировки:</strong> <?php echo $ban['enddate'] ?></div>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
