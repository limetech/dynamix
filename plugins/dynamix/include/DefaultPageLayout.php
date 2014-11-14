<?PHP
/* Copyright 2010-2014, Lime Technology
 * Copyright 2014, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<title><?=$var['NAME']?>/<?=$myPage['name']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="MSThemeCompatible" content="no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="robots" content="noindex">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-<?=$display['theme']?>.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/dynamix-<?=$display['theme']?>.css">
<link type="image/png" rel="shortcut icon" href="/webGui/images/<?=$var['mdColor']?>.png">

<style>
.inline_help {display:none;}
<?if (!$display['icons']):?>
.tab [type=radio]+label img.icon {display:none;} #title img.icon {display:none;}
<?endif;?>
</style>

<script type="text/javascript" src="/webGui/scripts/dynamix.js"></script>
<script>
Shadowbox.init({skipSetup:true});

// server uptime
var uptime = <?=strtok(exec("cat /proc/uptime"),' ')?>;

// Page refresh timer
var update = <?=abs($display['refresh'])/1000?>;
var counting = update;

// page timer events
var timers = {};

function pauseEvents(){
  $.each(timers, function(i, timer) {
    clearTimeout(timer);
  });
}
function resumeEvents(){
  var startDelay = 50;
  $.each(timers, function(i, timer) {
    timers[i] = setTimeout(i + '()', startDelay);
    startDelay += 50;
  });
}

function plus(value, label, last) {
  return value>0 ? (value+' '+label+(value!=1?'s':'')+(last?'':', ')) : '';
}
function updateTime() {
  days = parseInt(uptime/86400);
  hour = parseInt(uptime/3600%24);
  mins = parseInt(uptime/60%60);
  $('#uptime').html(((days|hour|mins)?plus(days,'day',(hour|mins)==0)+plus(hour,'hour',mins==0)+plus(mins,'minute',true):'less than a minute'));
  uptime++;
  setTimeout(updateTime,1000);
}
function refresh() {
  for (var i=0,element; element=document.querySelectorAll('input,button,select')[i]; i++) { element.disabled = true; }
  for (var i=0,link; link=document.getElementsByTagName('a')[i]; i++) { link.style.color = "gray"; } //fake disable
  location = location;
}
function initab() {
  $.removeCookie('one',{path:'/'});
  $.removeCookie('tab',{path:'/'});
}
function settab(tab) {
<?switch ($myPage['name']):?>
<?case'Main':?>
  $.cookie('tab',tab,{path:'/'});
<?if ($var['fsState']=='Started'):?>
  $.cookie('one','tab1',{path:'/'});
<?endif;?>
<?break;?>
<?case'Cache':case'Data':case'Flash':case'Parity':?>
  $.cookie('one',tab,{path:'/'});
<?break;?>
<?default:?>
  $.cookie($.cookie('one')==null?'tab':'one',tab,{path:'/'});
<?endswitch;?>
}
function done() {
  var path = location.pathname;
  var x = path.indexOf("/",1);
  if (x!=-1) path = path.substring(0,x);
  $.removeCookie('one',{path:'/'});
  location.replace(path);
}
function chkDelete(form, button) {
  button.value = form.confirmDelete.checked ? 'Delete' : 'Apply';
}
function openBox(cmd,title,height,width,load) {
  // open shadowbox window (run in foreground)
  var run = cmd.split('?')[0].substr(-4)=='.php' ? cmd : '/logging.htm?cmd='+cmd;
  var options = load ? {modal:true,onClose:function(){location=location;}} : {modal:true};
  Shadowbox.open({content:run, player:'iframe', title:title, height:height, width:width, options:options});
}
function openWindow(cmd,title,height,width) {
  // open regular window (run in background)
  var run = '/logging.htm?title='+title+'&cmd='+cmd;
  var top = (screen.height-height)/2;
  var left = (screen.width-width)/2;
  var options = 'resizeable=yes,scrollbars=yes,height='+height+',width='+width+',top='+top+',left='+left;
  window.open(run, '', options);
}
function showStatus(name) {
  $.post('/webGui/include/ProcessStatus.php',{name:name},function(status){$(".tabs").append(status);});
}
function notifier() {
  $.post('/webGui/include/Notify.php',{cmd:'get'},function(data) {
    if (data) {
      var json = $.parseJSON(data);
      $.each(json, function(i, object) {
        var notify = $.parseJSON(object);
        $.jGrowl(notify.subject+'<br>'+notify.description, {
          sticky: true,
          position: '<?=$notify['position']?>',
          header: notify.event+': '+notify.timestamp,
          theme: notify.importance+' '+notify.file,
          beforeOpen: function(e,m,o) {if ($('.jGrowl-notify').hasClass(notify.file)) {return(false);}},
          close: function(e,m,o) {$.post('/webGui/include/Notify.php',{cmd:'archive',file:notify.file}); return(false);}
        });
      });
<?if ($display['refresh']>0 || ($display['refresh']<0 && $var['mdResync']==0)):?>
      timers.notifier = setTimeout(notifier,<?=max(5000,abs($display['refresh']))?>);
<?endif;?>
    }
  });
}
function monitor() {
  $.post('/webGui/include/Monitor.php',{hot:<?=$display['hot']?>,max:<?=$display['max']?>},function(data) {
<?if ($display['refresh']>0 || ($display['refresh']<0 && $var['mdResync']==0)):?>
    timers.monitor = setTimeout(monitor,<?=max(60000,abs($display['refresh']))?>);
<?endif;?>
  });
}
function watchdog() {
  $.post('/webGui/include/Watchdog.php',{mode:<?=$display['refresh']?>,dot:'<?=substr($display['number'],0,1)?>'},function(data) {
    if (data) {
      $.each(data.split('#'),function(k,v) {
<?if ($display['refresh']>0 || ($display['refresh']<0 && $var['mdResync']==0)):?>
        if (v!='stop') $('#statusbar').html(v); else setTimeout(refresh,0);
      });
      timers.watchdog = setTimeout(watchdog,<?=abs($display['refresh'])?>);
<?else:?>
        if (v!='stop') $('#statusbar').html(v);
      });
<?endif;?>
    }
  });
}
function countDown() {
  counting--;
  if (counting==0) counting = update;
  $('#countdown').html('<small>Page refresh in '+counting+' sec</small>');
  setTimeout(countDown,1000);
}
$(function() {
  var tab = $.cookie('one')||$.cookie('tab')||'tab1';
  if (tab=='tab0') tab = 'tab'+$('input[name$="tabs"]').length; else if ($('#'+tab).length==0) {initab(); tab = 'tab1';}
  if ($.cookie('help')=='help') {$('.inline_help').show(); $('#nav-item.HelpButton').addClass('nav-button-active');}
  $('#'+tab).attr('checked', true);
<?if ($display['refresh']>0 || ($display['refresh']<0 && $var['mdResync']==0)):?>
  if (update>1) setTimeout(countDown,1000);
<?endif;?>
  updateTime();
  $.jGrowl.defaults.closer = false;
  $.post('/webGui/include/Notify.php',{cmd:'init'},function(x){timers.notifier = setTimeout(notifier,0);});
  Shadowbox.setup('a.sb-enable', {modal:true});
<?if ($confirm['warn']):?>
  $('form').find('select,input[type=text],input[type=password]').each(function() {$(this).change(function() {$.jGrowl('You have uncommitted form changes',{sticky:false,theme:'bottom',position:'bottom',life:5000});});});
<?endif;?>
  timers.monitor = setTimeout(monitor,100);
  timers.watchdog = setTimeout(watchdog,50);
});

var mobiles=['ipad','iphone','ipod','android'];
var device=navigator.platform.toLowerCase();
for (var i=0,mobile; mobile=mobiles[i]; i++) {
  if (device.indexOf(mobile)>=0) {$('#footer').css('position','static'); break;}
}
</script>
</head>
<body>
 <div id="template">
  <div id="header" class="<?=$display['banner']?>">
   <div class="logo">
   <a href="http://lime-technology.com"><img src="/webGui/images/logo-<?=$display['theme']?>.png" title="unRAID" border="0"/><br/>
   <strong>unRAID Server <?=$var['regTy']?></strong></a>
   </div>
   <div class="block">
    <span class="text-left">Server<br/>Description<br/>Version<br/>Uptime</span>
    <span class="text-right"><?=$var['NAME'].($var['IPADDR'] ? " &bullet; {$var['IPADDR']}" : "")?><br/><?=$var['COMMENT']?><br/><?=$var['version']?><br/><span id="uptime"></span></span>
   </div>
  </div>
<?
// Build page menus
echo "<div id='menu'><div id='nav-block'><div id='nav-left'>";
$pages = find_pages('Tasks');
foreach ($pages as $page) {
  $pagename = $page['name'];
  echo "<div id='nav-item'";
  echo $pagename==$task ? " class='active'>" : ">";
  echo "<a href='/$pagename' onclick='initab()'>$pagename</a></div>";
}
if ($display['usage']) my_usage();
echo "</div>";
echo "<div id='nav-right'>";
$pages = find_pages('Buttons');
foreach ($pages as $page) {
  eval("?>{$page['text']}");
  if (empty($page['Link']))
    echo "<div id='nav-item' class='{$page['name']}'><a href='#' onclick='{$page['name']}();return false;'><img src='/{$page['root']}/icons/{$page['Icon']}' class='system'>{$page['Title']}</a></div>";
  else
    echo "<div id='{$page['Link']}'></div>";
}
echo "</div></div></div>";

// Build page content
echo "<div class='tabs'>";
$tab = 1;
$view = $myPage['name'];
$pages = array();
$pages[$view] = $myPage;
if ($myPage['Type']=='xmenu') $pages = array_merge($pages, find_pages($view));
if (isset($myPage['Tabs'])) $display['tabs'] = strtolower($myPage['Tabs'])=='true' ? 0 : 1;
$tabbed = $display['tabs']==0 && count($pages)>(empty($myPage['Title'])?2:1);

foreach ($pages as $page) {
  $close = false;
  if (isset($page['Title'])) {
    eval("\$title=\"{$page['Title']}\";");
    if ($tabbed) {
      echo "<div class='tab'><input type='radio' id='tab{$tab}' name='tabs' onclick='settab(this.id)'><label for='tab{$tab}'>";
      echo tab_title($title,$page['root']);
      echo "</label><div class='content'>";
      $close = true;
    } else {
      if ($tab==1) echo "<div class='tab'><input type='radio' id='tab{$tab}' name='tabs'><div class='content shift'>";
      echo "<div id='title'><span class='left'>";
      echo tab_title($title,$page['root']);
      echo "</span></div>";
    }
    $tab++;
  }
  if ($page['Type']=='menu') {
    $pgs = find_pages($page['name']);
    foreach ($pgs as $pg) {
      @eval("\$title=\"{$pg['Title']}\";");
      $link = "$path/{$pg['name']}";
      if ($icon = isset($pg['Icon'])) {
        $icon = "{$pg['root']}/images/{$pg['Icon']}";
        if (!file_exists($icon)) { $icon = "{$pg['root']}/{$pg['Icon']}"; if (!file_exists($icon)) $icon = false; }
      }
      if (!$icon) $icon = "/webGui/images/default.png";
      echo "<div class=\"Panel\"><a href=\"$link\" onclick=\"$.cookie('one','tab1',{path:'/'})\"><img class=\"PanelImg\" src=\"$icon\" title=\"$title\"><br><div class=\"PanelText\">$title</div></a></div>";
    }
  }
  $text = Markdown($page['text']);
  $file = @file_get_contents("{$page['root']}/{$page['name']}.php");
  eval("?>$text$file");
  if ($close) echo "</div></div>";
}
?>
 </div></div>
 <iframe id="progressFrame" name="progressFrame" frameborder="0"></iframe>
<?
// Build footer
echo '<div id="footer"><span id="statusraid"><span id="statusbar">';
switch ($var['fsState']) {
case 'Stopped':
  echo '<span class="red"><strong>Array Stopped</strong></span>'; break;
case 'Starting':
  echo '<span class="orange"><strong>Array Starting</strong></span>'; break;
default:
  echo '<span class="green"><strong>Array Started</strong></span>'; break;
}
echo "</span>&bullet;&nbsp;<span class='bitstream'>Dynamix webGui v";
echo exec("/usr/local/sbin/plugin version /var/log/plugins/dynamix.plg");
echo "</span></span><span id='countdown'></span><span id='copyright'>unRAID&trade; webGui &copy; 2014, Lime Technology LLC.";
if (isset($myPage['Author'])) {
  echo "&nbsp;|&nbsp;Page author: {$myPage['Author']}";
  if (isset($myPage['Version'])) echo ", version: {$myPage['Version']}";
}
echo "</span></div>";
?>
</body>
</html>
