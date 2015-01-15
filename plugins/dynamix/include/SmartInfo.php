<?PHP
/* Copyright 2014, Lime Technology
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
<?
$start = 'Start Test';
$stop  = 'Cancel Test';
$check = 'Checking...';
$run   = '/webGui/include/Run.php';

$disk = $_GET['disk'];
$port = $_GET['port'];
$spin = exec("hdparm -C /dev/$port|grep 'active'");
$output = array();

echo "<div class='label'><span class='left infogap'>" . $disk . " attached to port: $port</span></div>";
echo "<table class='list infogap'>";

switch ($_GET['cmd']):
case "IDENTITY":
  exec("smartctl -i /dev/$port|awk 'NR>4'",$output);
  exec("smartctl -H /dev/$port|grep 'result'|sed 's/self-assessment test result//'",$output);
  foreach ($output as $line):
    if (!strlen($line)) continue;
    $info = explode(':', $line, 2);
    echo "<tr><td>$info[0]:</td><td>$info[1]</td></tr>";
  endforeach;
  break;
case "ATTRIBUTES":
  exec("smartctl -A /dev/$port|awk 'NR>6'",$output);
  $bold = count($output)<=2 ? "" : " style='font-weight:bold'";
  foreach ($output as $line):
    if (!$line) continue;
    $info = explode(' ', trim(preg_replace('/\s+/',' ',$line)), 10);
    $color = array_search($info[0], array(5,187,188,197,198))!==false && $info[9]>0 ? " class='red-text'" : "";
    echo "<tr{$color}{$bold}>";
    $bold = "";
    foreach ($info as $field):
      switch ($field):
      case '-'          : $field = 'Never'; break;
      case 'WHEN_FAILED': $field = 'FAILED'; break;
      case 'FAILING_NOW': $field = 'Now'; break;
      endswitch;
      echo "<td style='width:auto'>".str_replace('_',' ',$field)."</td>";
    endforeach;
    echo "</tr>";
  endforeach;
  break;
case "CAPABILITIES":
  exec("smartctl -c /dev/$port|awk 'NR>4'",$output);
  foreach ($output as $line):
    if (!$line) continue;
    $line = preg_replace('/^_/','__',preg_replace(array('/__+/','/_ +_/'),'_',str_replace(array(chr(9),')','('),'_',$line)));
    $info = explode('_', preg_replace('/_( +)?([0-9]+)_ /','__${2} ',$line), 3);
    echo "<tr><td>".(isset($info[0])?$info[0]:"")."</td><td>".(isset($info[1])?$info[1]:"")."</td><td>".(isset($info[2])?$info[2]:"")."</td></tr>";
  endforeach;
  break;
case "TESTLOG":
  exec("smartctl -l selftest /dev/$port|awk 'NR>5'",$output);
  if (strpos($output[0],'No self-tests')===0):
    echo "<tr><td>No self-tests logged on this disk</td></tr>";
    break;
  endif;
  $tr = "<tr style='font-weight:bold;'>";
  foreach ($output as $line):
    if (!$line) continue;
    $info = explode(' ', trim(preg_replace('/\s+/',' ',preg_replace('/(\w:?) ([a-zA-Z(])/','${1}_${2}',preg_replace('/^# ?/','',$line)))), 6);
    echo $tr;
    $tr = "<tr>";
    foreach ($info as $field):
      if ($field=='-') $field = 'None';
      echo "<td style='width:auto'>".str_replace('_',' ',$field)."</td>";
    endforeach;
    echo "</tr>";
  endforeach;
  break;
case "ERRORLOG":
  $output = shell_exec("smartctl -l error /dev/$port|awk 'NR>5'");
  if (strpos($output,'No Errors')===0):
    echo "<tr><td>No errors logged on this disk</td></tr>";
  else:
    echo "<pre style='margin-top:-12px'>$output</pre>";
  endif;
  break;
case "SELFTEST":
  echo "<tr><td ></td></tr>";
  echo "<tr id='update'></tr>";
  echo "<tr><td>SMART short self-test:</td>";
  echo $spin ? "<td><input type='button' value='$start' id='short'></td>" : "<td>Disk must be spun up before running test</td>";
  echo "</tr>";
  echo "<tr><td>SMART extended self-test:</td>";
  echo $spin ? "<td><input type='button' value='$start' id='long'></td>" : "<td>Disk must be spun up before running test</td>";
  echo "</tr>";
  break;
endswitch;

echo "</table>";
?>
<script>
var start = '<?=$start?>';
var stop = '<?=$stop?>';
var refresh = null;

function showProgress(){
  $.get('<?=$run?>',{cmd:'update',port:'<?=$port?>'},function(data){
    $('#update').html(data).trigger('smart');
    refresh=setTimeout(showProgress,3000);
  });
}
$(function(){
  $('#short').click(function(){
    if ($(this).val()==start){
      $(this).val(stop).attr('disabled',true);
      $('#long').attr('disabled',true);
      $.get('<?=$run?>',{cmd:'short',port:'<?=$port?>'},function(){setTimeout(showProgress,0);});
    } else {
      $(this).attr('disabled',true);
      $.get('<?=$run?>',{cmd:'stop',port:'<?=$port?>'});
    }
  });
  $('#long').click(function(){
    if ($(this).val()==start){
      $(this).val(stop).attr('disabled',true);
      $('#short').attr('disabled',true);
      $.get('<?=$run?>',{cmd:'long',port:'<?=$port?>'},function(){setTimeout(showProgress,0);});
    } else {
      $(this).attr('disabled',true);
      $.get('<?=$run?>',{cmd:'stop',port:'<?=$port?>'});
    }
  });
  $('#update').bind('smart', function(){
    if ($(this).html().search('%')<0){
      $('#short').val(start).attr('disabled',false);
      $('#long').val(start).attr('disabled',false);
      clearTimeout(refresh);
    } else {
      if ($('#short').val()==stop) $('#short').attr('disabled',false);
      if ($('#long').val()==stop) $('#long').attr('disabled',false);
    }
  });
  $.get('<?=$run?>',{cmd:'update',port:'<?=$port?>'},function(data){$('#update').html(data);});
});
</script>
