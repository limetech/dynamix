<?PHP
/* Copyright 2015, Lime Technology
 * Copyright 2015, Bergware International.
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
// Preselected SMART codes for notifications
$numbers   = [];
$preselect = [['code' =>   5, 'text' => 'Reallocated sectors count'],
              ['code' => 187, 'text' => 'Reported uncorrectable errors'],
              ['code' => 188, 'text' => 'Command time-out'],
              ['code' => 197, 'text' => 'Current pending sector count'],
              ['code' => 198, 'text' => 'Uncorrectable sector count']];

for ($x = 0; $x < count($preselect); $x++) $numbers[] = $preselect[$x]['code'];
?>