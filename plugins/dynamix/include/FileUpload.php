<?PHP
$path = '/boot/config/plugins/dynamix/users';
$user = isset($_POST['user']) ? rawurldecode($_POST['user']).'.png' : rawurldecode($_POST['filename']);

if (isset($_POST['filedata']) && $user) {
  exec("mkdir -p $path");
  file_put_contents("$path/$user", base64_decode(str_replace(array('data:image/png;base64,',' '),array('','+'),$_POST['filedata'])));
  echo '200 OK';
} else {
  echo '204 No Content';
}
?>
