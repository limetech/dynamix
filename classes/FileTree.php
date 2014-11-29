<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//
// Program update made by Bergware International (October 2014)

$path = urldecode($_POST['dir']);
$filter = $_POST['filter'];

if (!file_exists($path)) return; // skip invalid path
$files = scandir($path);
if (count($files)<=2) return; // skip empty folder
natcasesort($files);
echo "<ul class='jqueryFileTree' style='display:none;'>";
// All dirs
foreach ($files as $file) {
  if (is_dir($path.$file) && $file != '.' && $file != '..') echo "<li class='directory collapsed'><a href='#' rel='".htmlentities($path.$file)."/'>".htmlentities($file)."</a></li>";
}
// All files
foreach ($files as $file) {
  if (is_file($path.$file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (!$filter|$ext==$filter) echo "<li class='file ext_$ext'><a href='#' rel='".htmlentities($path.$file)."'>".htmlentities($file)."</a></li>";
  }
}
echo "</ul>";
?>
