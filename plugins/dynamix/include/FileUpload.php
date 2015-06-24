<?PHP
$cmd  = isset($_POST['cmd']) ? $_POST['cmd'] : 'load';
$file = rawurldecode($_POST['filename']);
$path = "/boot/config/plugins/dynamix/users";
$temp = "/var/tmp";

switch ($cmd) {
case 'load':
  if (isset($_POST['filedata'])) {
    exec("rm -f $temp/*.png");
    $result = file_put_contents("$temp/$file", base64_decode(str_replace(array('data:image/png;base64,',' '),array('','+'),$_POST['filedata'])));
  }
  break;
case 'save':
  exec("mkdir -p $path");
  $result = @rename("$temp/$file", "$path/{$_POST['user']}.png");
  break;
case 'delete':
  @unlink("$temp/$file");
  @unlink("$path/{$_POST['user']}.png");
  $result = true;
  break;
}
echo ($result ? '200 OK' : '500 Internal Error');
?>
