<?PHP
/* Copyright 2015-2016, Bergware International.
 * Copyright 2015-2016, Lime Technology
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
$new = isset($default) ? array_replace_recursive($_POST, $default) : $_POST;

if (!empty($new['users'])) exec("sed -i 's/\\(^Menu=\"\\).*/\\1{$new['users']}\"/' {$_SERVER['DOCUMENT_ROOT']}/webGui/Users.page");
