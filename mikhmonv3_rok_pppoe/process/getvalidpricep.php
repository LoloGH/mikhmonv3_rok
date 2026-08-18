<?php
/*
 *  Copyright (C) 2019 Laksamadi Guko.
 *  PPPoE client management module.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
// load session MikroTik
  $session = $_GET['session'];

// load config
  include('../include/config.php');
  $iphost = explode('!', $data[$session][1])[1];
  $userhost = explode('@|@', $data[$session][2])[1];
  $passwdhost = explode('#|#', $data[$session][3])[1];
  $curency = explode('&', $data[$session][6])[1];

// lang
  include('../include/lang.php');
  include('../lang/' . $langid . '.php');

  include_once('../lib/routeros_api.class.php');
  include_once('../ppp/pppmisc.php');

  $API = new RouterosAPI();
  $API->debug = false;
  $API->connect($iphost, $userhost, decrypt($passwdhost));

  $pprofname = $_GET['name'];
  if ($pprofname != "") {
    $getprofile = $API->comm("/ppp/profile/print", array("?name" => "$pprofname"));
    $ponup = $getprofile[0]['on-up'];
    $meta = pppMeta($ponup);

    $getvalid = $_validity . " : " . $meta['validity'];
    $getlock = "| " . $_lock_caller_id . " : " . $meta['lock'];
    $getmode = "| " . $_expired_mode . " : " . pppExpModeName($meta['expmode']);

    $price = "";
    if ($meta['price'] != "" && $meta['price'] != "0") {
      if ($curency == "Rp" || $curency == "rp" || $curency == "IDR" || $curency == "idr") {
        $price = "| " . $_price . " : " . $curency . " " . number_format((float) $meta['price'], 0, ",", ".");
      } else {
        $price = "| " . $_price . " : " . $curency . " " . number_format((float) $meta['price']);
      }
    }
    $sprice = "";
    if ($meta['sprice'] != "" && $meta['sprice'] != "0") {
      if ($curency == "Rp" || $curency == "rp" || $curency == "IDR" || $curency == "idr") {
        $sprice = "| " . $_selling_price . " : " . $curency . " " . number_format((float) $meta['sprice'], 0, ",", ".");
      } else {
        $sprice = "| " . $_selling_price . " : " . $curency . " " . number_format((float) $meta['sprice']);
      }
    }

    echo '<b id="getdata">' . $getvalid . ' ' . $price . ' ' . $sprice . ' ' . $getmode . ' ' . $getlock . '</b>';
    echo '<span id="validity" style="display:none;">' . $meta['validity'] . '</span> ';
  }
}
?>
