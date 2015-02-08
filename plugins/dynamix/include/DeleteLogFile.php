<?PHP
/* Copyright 2014, Bergware International.
 * Copyright 2014, Lime Technology
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
array_map('unlink', glob($_GET['log'], GLOB_NOSORT));
?>
<html>
<head><script>var enablePage=parent.location;</script></head>
<body onLoad="parent.location=enablePage;"></body>
</html>