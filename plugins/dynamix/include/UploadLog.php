<?
$url = "http://unraid.mylog.ml";
$max_size = 2097152; # in bytes

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
  $notify = "/usr/local/sbin/notify";
  $server = strtoupper($var['NAME']);
  $out = json_decode($out, TRUE);
  if (isset($out['result']['error'])){
    echo shell_exec("$notify -e '$title upload failed' -s 'Alert [$server] - $title upload failed.' -d 'Upload of $title has failed: ".$out['result']['error']."' -i 'alert 1'");
    echo '{"result":"failed"}';
  } else {
    $resp = "${url}/".$out['result']['id']."/".$out['result']['hash'];
    exec("$notify -e '$title uploaded - [".$out['result']['id']."]' -s 'Notice [$server] - $title uploaded.' -d 'A new copy of $title has been uploaded: $resp' -i 'normal 1'");
    echo '{"result":"'.$resp.'"}';
  }
}

if ($_POST['pastebin']){
  $title = $_POST['title'];
  $text  = $_POST['text'];
  if (!$text) exit(0);
  switch ($_POST['type']) {
    case 'file':
      sendPaste(shell_exec("/usr/bin/cat '$text' 2>&1 | tail -c $max_size -"), $title);
      break;
    case 'command':
      sendPaste(shell_exec($text." 2>&1 | tail -c $max_size -"), $title);
      break;
    case 'text':
      sendPaste(shell_exec("/usr/bin/echo '$text' 2>&1 | tail -c $max_size -"), $title);
      break;
  }
}
?>