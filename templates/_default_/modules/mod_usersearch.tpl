<script src="/includes/jquery/autocomplete/jquery.autocomplete.min.js" type="text/javascript"></script>
<link media="screen" rel="stylesheet" href="/includes/jquery/autocomplete/jquery.autocomplete.css" type="text/css">

<form id="mod_usr_search_form" method="post" action="/users">
    <table width="100%" border="0" cellspacing="0" cellpadding="4">
        <tr>
            <td width="70">
                <strong>{$LANG.FIND}</strong>
            </td>
            <td valign="middle">
                <select name="gender" id="gender" style="width:170px" class="text-input">
                    <option value="f">{$LANG.FIND_FEMALE}</option>
                    <option value="m">{$LANG.FIND_MALE}</option>
                    <option value="0" selected>{$LANG.FIND_ALL}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                {$LANG.AGE_FROM}
            </td>
            <td>
                <input style="text-align:center;width:70px" name="agefrom" type="text" id="agefrom" value="" class="text-input" />
                {$LANG.TO} <input style="text-align:center;width:71px" name="ageto" type="text" id="ageto" value="" class="text-input" />
            </td>
        </tr>
        <tr>
            <td>
                {$LANG.NAME}
            </td>
            <td><input style="text-align:center;width:170px" id="name" name="name" type="text" value="" class="text-input" /></td>
        </tr>
        <tr>
            <td>
                {$LANG.CITY}
            </td>
            <td>
                <input style="text-align:center;width:170px" id="city" name="city" type="text" value="" class="text-input" />
                <script type="text/javascript">
                    {$autocomplete_js}
                </script>
            </td>
        </tr>
        <tr>
            <td>
                {$LANG.HOBBY}
            </td>
            <td><input style="text-align:center;width:170px" id="hobby" name="hobby" type="text" value="" class="text-input" /></td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <input name="gosearch" type="submit" id="gosearch" value="{$LANG.SEARCH}" />
            </td>
        </tr>
    </table>
</form>