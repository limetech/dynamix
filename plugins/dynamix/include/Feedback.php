<?PHP
/* Copyright 2015, Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
$var = parse_ini_file('state/var.ini');
$keyfile = trim(base64_encode(@file_get_contents($var['regFILE'])));
?>
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-white.css">
<script type="text/javascript" src="/webGui/javascript/dynamix.js"></script>
<script>
function checkEgg(keyfile) {
    var timestamp = <?=time()?>;
    if (!keyfile) return;
    $('#status_panel').slideUp('fast');
    // Nerds love spinners, Maybe place a spinner image next to the submit button; we'll show it now:
    $('#spinner_image').fadeIn('fast');

    $.post('https://keys.lime-technology.com/polls',{timestamp:timestamp,keyfile:keyfile},function(data) {
        $('#spinner_image').fadeOut('fast');
        $('#status_panel').hide().html(data).slideDown('fast');

    }).fail(function(data) {
        $('#spinner_image').fadeOut('fast');
        var status = data.status;
        var msg = "<p>Sorry, an error ("+status+") occurred.  Please try again later.";
        $('#status_panel').hide().html(msg).slideDown('fast');
    });
}
$(checkEgg('<?=$keyfile?>'));
</script>
<body>
<div style="margin-top:20px;font-size:12px;line-height:30px;color:#303030;margin-left:40px;margin-right:40px">
<div id="status_panel"></div>
<div style="text-align:center">
<hr>
<a href="https://lime-technology.com" target="_blank">Website</a>&nbsp;|&nbsp;
<a href="https://lime-technology.com/forum" target="_blank">Forum</a>&nbsp;|&nbsp;
<a href="https://lime-technology.com/wiki" target="_blank">Wiki</a>
</div>
</div>
</body>
