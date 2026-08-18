<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
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

// Shared helpers of the PPPoE module.
// Follows the same principle as the Mikhmon hotspot module : the commercial
// data (expired mode, price, validity, selling price, lock) is stored inside
// the "on-up" script of the PPP profile, and the expiration date of a client
// is stored inside the "comment" of the PPP secret.

if (substr($_SERVER["REQUEST_URI"], -11) == "pppmisc.php") {
  header("Location:../");
}

// ---------------------------------------------------------------------------
// Read commercial data out of a PPP profile "on-up" script.
// Layout is identical to the hotspot one :
//   :put (",<expmode>,<price>,<validity>,<sprice>,,<lock>,")
// ---------------------------------------------------------------------------
if (!function_exists('pppMeta')) {
  function pppMeta($onup)
  {
    $p = explode(",", $onup);
    return array(
      'expmode'  => trim($p[1]),
      'price'    => trim($p[2]),
      'validity' => trim($p[3]),
      'sprice'   => trim($p[4]),
      'lock'     => trim($p[6]),
    );
  }
}

// Human readable expired mode.
if (!function_exists('pppExpModeName')) {
  function pppExpModeName($expmode)
  {
    if ($expmode == "rem") {
      return "Remove";
    } elseif ($expmode == "dis") {
      return "Disable";
    } elseif ($expmode == "remc") {
      return "Remove & Record";
    } elseif ($expmode == "disc") {
      return "Disable & Record";
    }
    return "";
  }
}

// True when the comment of a secret holds an expiration date (yyyy-mm-dd hh:mm:ss).
if (!function_exists('pppIsExpDate')) {
  function pppIsExpDate($comment)
  {
    return (strlen($comment) >= 10 && substr($comment, 4, 1) == "-" && substr($comment, 7, 1) == "-");
  }
}

// Expiration date of a secret as a unix timestamp, 0 when not activated yet.
if (!function_exists('pppExpStamp')) {
  function pppExpStamp($comment)
  {
    if (!pppIsExpDate($comment)) {
      return 0;
    }
    $stamp = strtotime(substr($comment, 0, 19));
    if ($stamp === false) {
      return 0;
    }
    return $stamp;
  }
}

// Normalise a validity typed by the operator.
// A bare number is ambiguous : Mikrotik reads it as seconds in a scheduler
// interval, while the operator typing "30" in a field documented as
// "30d = 30 days" means days. Anchor it to days so that the on-up script and
// the PHP side (immediate activation, renewal) can never disagree.
if (!function_exists('pppNormalizeValidity')) {
  function pppNormalizeValidity($validity)
  {
    $validity = strtolower(trim($validity));
    if ($validity === "") {
      return "";
    }
    if (preg_match('/^\d+$/', $validity)) {
      return $validity . "d";
    }
    return $validity;
  }
}

// Convert a Mikrotik validity ("30d", "12h", "5h30m", "4w3d") to seconds.
if (!function_exists('pppValidityToSeconds')) {
  function pppValidityToSeconds($validity)
  {
    $validity = strtolower(trim($validity));
    if ($validity == "") {
      return 0;
    }
    $unit = array('w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1);
    $total = 0;
    $found = preg_match_all('/(\d+)\s*([wdhms])/', $validity, $parts, PREG_SET_ORDER);
    if ($found) {
      foreach ($parts as $part) {
        $total = $total + ((int) $part[1] * $unit[$part[2]]);
      }
      return $total;
    }
    // Plain number is understood as a number of days.
    if (is_numeric($validity)) {
      return (int) $validity * 86400;
    }
    return 0;
  }
}

// Format a price with the currency of the session.
if (!function_exists('pppPrice')) {
  function pppPrice($price, $currency, $cekindo)
  {
    $price = trim($price);
    if ($price == "" || $price == "0") {
      return "";
    }
    if ($currency == in_array($currency, $cekindo['indo'])) {
      return number_format((float) $price, 0, ",", ".");
    }
    return number_format((float) $price, 2);
  }
}

// Status of a secret : "waiting" (never connected), "active" or "expired".
if (!function_exists('pppStatus')) {
  function pppStatus($comment, $disabled)
  {
    $stamp = pppExpStamp($comment);
    if ($stamp == 0) {
      return "waiting";
    }
    if ($stamp <= time()) {
      return "expired";
    }
    if ($disabled == "true") {
      return "disabled";
    }
    return "active";
  }
}

// Build the sales record appended to /system script, read back by the
// selling report (same "-|-" layout as the hotspot record).
if (!function_exists('pppAddRecord')) {
  function pppAddRecord($API, $name, $price, $address, $callerid, $validity, $profile, $comment)
  {
    $date = date("Y-m-d");
    $time = date("H:i:s");
    $owner = date("mY");
    $API->comm("/system/script/add", array(
      "name"    => $date . "-|-" . $time . "-|-" . $name . "-|-" . $price . "-|-" . $address . "-|-" . $callerid . "-|-" . $validity . "-|-" . $profile . "-|-" . $comment,
      "owner"   => "$owner",
      "source"  => "$date",
      "comment" => "mikhmon",
    ));
  }
}
