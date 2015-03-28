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
require_once 'webGui/include/Markdown.php';

// Invoke the plugin command with indicated method
function plugin($method, $arg) {
  exec("/usr/local/sbin/plugin $method $arg", $output, $retval);
  if ($retval != 0) return false;
  return implode("\n", $output);
}

function make_link($method, $arg) {
  $id = basename($arg, ".plg").$method;
  $check = $method=='update' ? "" : "<input type='checkbox' onClick='document.getElementById(\"$id\").disabled=!this.checked'>";
  $disabled = $check ? " disabled" : "";
  $cmd = $method == "delete" ? "rm $arg" : "/usr/local/sbin/plugin $method $arg";
  return "{$check}<input type='button' id='$id' value='{$method}' onclick='openBox(\"{$cmd}\",\"{$method} Plugin\",600,900,true)'{$disabled}>";
}

// trying our best to find an icon
function icon($name) {
// this should be the default location and name
  $icon = "plugins/{$name}/images/{$name}.png";
  if (file_exists($icon)) return $icon;
// try alternatives if default is not present
  $plugin = strtok($name, '.');
  $icon = "plugins/{$plugin}/images/{$plugin}.png";
  if (file_exists($icon)) return $icon;
  $icon = "plugins/{$plugin}/images/{$name}.png";
  if (file_exists($icon)) return $icon;
  $icon = "plugins/{$plugin}/{$plugin}.png";
  if (file_exists($icon)) return $icon;
  $icon = "plugins/{$plugin}/{$name}.png";
  if (file_exists($icon)) return $icon;
// last resort - plugin manager icon
  return "plugins/dynamix.plugin.manager/images/dynamix.plugin.manager.png";
}

foreach (glob("/var/log/plugins/*.plg", GLOB_NOSORT) as $plugin_link) {
// only consider symlinks
  $plugin_file = @readlink($plugin_link);
  if ($plugin_file === false) continue;
// plugin name
  $name = plugin("name", $plugin_file);
  if ($name === false) $name = basename($plugin_file, ".plg");
// link/icon
  $icon = icon($name);
  if ($launch = plugin("launch", $plugin_file))
    $link = "<a href='/$launch'><img src='/$icon' width='48px'/></a>";
  else
    $link = "<img src='/{$icon}' width='48px'/>";
// desc
  $readme = "plugins/{$name}/README.md";
  if (file_exists($readme))
    $desc = Markdown(file_get_contents($readme));
  else
    $desc = Markdown("**{$name}**");
// author
  $author = plugin("author", $plugin_file);
  if ($author === false) $author = "anonymous";
// version
  $version = plugin("version", $plugin_file);
  if ($version === false) $version = "unknown";
// version info
  $version_info = $version;
// status info
  $status_info = "no update";
  $changes_file = $plugin_file;
  $URL = plugin("pluginURL", $plugin_file);
  if ($URL !== false) {
    $filename = "/tmp/plugins/".basename($URL);
    if (file_exists($filename)) {
      $latest = plugin("version", $filename);
      if ($latest && strcmp($latest, $version) > 0) {
        $version_info .= "<br><span class='red-text'>{$latest}</span>";
        $status_info = make_link("update", basename($plugin_file));
        $changes_file = $filename;
      } else {
        $status_info = "up-to-date";
      }
    } else {
      if ($tmp_stale) $status_info = "unknown";
    }
  }
  $changes = plugin("changes", $changes_file);
  if ($changes !== false) {
    $txtfile = "/tmp/plugins/".basename($plugin_file,'.plg').".txt";
    file_put_contents($txtfile, $changes);
    $version_info .= "&nbsp;<a href='#' title='View Release Notes' onclick=\"openBox('/plugins/dynamix.plugin.manager/include/ShowChanges.php?file=".urlencode($txtfile)."','Release Notes',600,900); return false\"><img src='/webGui/images/information.png' class='icon'></a>";
  }
// action
  $action = strpos($plugin_file, "/usr/local/emhttp/plugins") !== 0 ? make_link("remove", basename($plugin_file)) : "built-in";
// write plugin information
  echo "<tr>";
  echo "<td style='vertical-align:top'><p>{$link}</p></td>";
  echo "<td><span class='desc_readmore' style='display:block'>{$desc}</span></td>";
  echo "<td>{$author}</td>";
  echo "<td>{$version_info}</td>";
  echo "<td>{$status_info}</td>";
  echo "<td>{$action}</td>";
  echo "</tr>";
}
?>
