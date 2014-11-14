<?PHP
// write syslinux file
write_log("Saving file $file");
file_put_contents($file, str_replace(array("\r\n","\r"), "\n", $_POST['text']));
// discard settings
$save = false;
?>