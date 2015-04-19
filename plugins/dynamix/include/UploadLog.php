<?
$url = "http://162.243.138.66";

if (! isset($var)){
  if (! is_file("/usr/local/emhttp/state/var.ini")) shell_exec("wget -qO /dev/null localhost:$(ss -napt|grep emhttp|grep -Po ':\K\d+')");
  $var = @parse_ini_file("/usr/local/emhttp/state/var.ini");
}

function sendPaste($text, $title) {
  global $url, $var;
  $data = array('data'     => $text,
                'language' => 'text',
                'title'    => '[unRAID] '.$title,
                'private'  => true,
                'expire'   => '2592000');
  $tmpfile = "/tmp/tmp-".mt_rand().".json";
  file_put_contents($tmpfile, json_encode($data));
  $out = shell_exec("curl -s -k -L -X POST -H 'Content-Type: application/json' -d '@$tmpfile' ${url}/api/json/create");
  unlink($tmpfile);
  $out = json_decode($out, TRUE);
  if (isset($out['result'])){
    $resp = "${url}/".$out['result']['id']."/".$out['result']['hash'];
    $notify = "/usr/local/sbin/notify";
    $server = strtoupper($var['NAME']);
    exec("$notify -e '$title uploaded - [".$out['result']['id']."]' -s 'Notice [$server] - $title uploaded.' -d 'A new copy of $title has been uploaded: $resp' -i 'normal 1' -x");
    echo $resp;
  }
}

if ($_POST['pastebin']){
  $title = $_POST['title'];
  $text  = $_POST['text'];
  if (!$text) exit(0);
  switch ($_POST['type']) {
    case 'file':
      sendPaste(file_get_contents($text),$title);
      break;
    case 'command':
      sendPaste(shell_exec($text." 2>&1"), $title);
      break;
    case 'text':
      sendPaste($text, $title);
      break;
  }
}
?>