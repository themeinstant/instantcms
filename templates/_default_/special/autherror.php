<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Ошибка авторизации</title>
        <meta http-equiv="refresh" content="5;URL=/login">
        <style type="text/css">
            * { font-family: Arial; }
            html, body { height:100%; margin:0px; }
            h2 { color: red; margin:0px; }
            p { margin:0px; margin-top:4px; }
        </style>
    </head>
    <body>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
            <tr>
                <td align="center">
                    <table border="0" cellpadding="0" cellspacing="0" >
                        <tr>
                            <td width="150">
                                <img src="/templates/<?php echo cmsConfig::getConfig('template');?>/special/images/autherror.png" />
                            </td>
                            <td>
                                <h2>Ошибка авторизации</h2>
                                <p>Проверьте правильность ввода имени и пароля.</p>
                                <p>Через несколько секунд вы будете перенаправлены.</p>
                                <p><a href="/login">Назад</a></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
