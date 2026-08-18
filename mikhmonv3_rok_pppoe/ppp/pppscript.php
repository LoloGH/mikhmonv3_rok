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

// Builder of the Mikrotik scripts used by the PPPoE module.
// Same principle as the hotspot profile : the "on-up" script carries the
// commercial data and stamps the expiration date on the secret at the first
// connection, while a scheduler watches the secrets of the profile and
// removes / disables the expired ones.

if (substr($_SERVER["REQUEST_URI"], -13) == "pppscript.php") {
  header("Location:../");
}

// ---------------------------------------------------------------------------
// on-up script of a PPP profile.
// ---------------------------------------------------------------------------
if (!function_exists('pppOnUpScript')) {
  function pppOnUpScript($name, $expmode, $price, $sprice, $validity, $lockmode)
  {
    if ($lockmode == "Enable") {
      $lock = '; [:local lcid $"caller-id"; /ppp secret set caller-id=$lcid [find where name=$user]]';
    } else {
      $lock = "";
    }

    // Commercial data read back by PHP with explode(",", $onup).
    $head = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $lockmode . ',"); ';

    if ($expmode == "0" || $expmode == "") {
      if ($price != "" && $price != "0") {
        return ':put (",,' . $price . ',,' . $sprice . ',,noexp,")' . $lock;
      }
      return "";
    }

    // Stamp the expiration date on the secret at the first connection.
    // A temporary scheduler is used to let Mikrotik compute "now + validity".
    $stamp = '{:local comment [ /ppp secret get [/ppp secret find where name="$user"] comment];'
      . ' :local ucode [:pick $comment 0 2];'
      . ' :if ($ucode = "pp" or $comment = "") do={'
      . ' :local note ""; :if ($ucode = "pp") do={:set note [:pick $comment 3 [:len $comment]]};'
      . ' :local date [ /system clock get date ]; :local year [ :pick $date 0 4 ]; :local month [ :pick $date 5 7 ];'
      . ' /sys sch add name="$user" disabled=no start-date=$date interval="' . $validity . '"; :delay 5s;'
      . ' :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [:len $exp];'
      . ' :if ($getxp = 15) do={ :local d [:pick $exp 0 6]; :local t [:pick $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ppp secret set comment="$exp $note" [find where name="$user"];};'
      . ' :if ($getxp = 8) do={ /ppp secret set comment="$date $exp $note" [find where name="$user"];};'
      . ' :if ($getxp > 15) do={ /ppp secret set comment="$exp $note" [find where name="$user"];};'
      . ' :delay 5s; /sys sch remove [find where name="$user"]';

    // Sales record, read back by the selling report.
    $record = '; :local cid $"caller-id"; :local addr $"remote-address"; :local time [/system clock get time ];'
      . ' /system script add name="$date-|-$time-|-$user-|-' . $price . '-|-$addr-|-$cid-|-' . $validity . '-|-' . $name . '-|-$note"'
      . ' owner="$month$year" source="$date" comment="mikhmon"';

    if ($expmode == "remc" || $expmode == "disc") {
      return $head . $stamp . $record . $lock . "}}";
    }
    return $head . $stamp . $lock . "}}";
  }
}

// ---------------------------------------------------------------------------
// Action applied to an expired secret, derived from the expired mode.
// ---------------------------------------------------------------------------
if (!function_exists('pppExpAction')) {
  function pppExpAction($expmode)
  {
    if ($expmode == "rem" || $expmode == "remc") {
      return "remove";
    }
    return "set disabled=yes";
  }
}

// ---------------------------------------------------------------------------
// Scheduler watching the secrets of one profile.
// ---------------------------------------------------------------------------
if (!function_exists('pppMonitorScript')) {
  function pppMonitorScript($name, $expmode)
  {
    $mode = pppExpAction($expmode);

    return ':local dateint do={:local montharray ( "01","02","03","04","05","06","07","08","09","10","11","12" );'
      . ':local days [ :pick $d 8 10 ];:local month [ :pick $d 5 7 ];:local year [ :pick $d 0 4 ];'
      . ':local monthint ([ :find $montharray $month]);:local month ($monthint + 1);'
      . ':if ( [:len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}};'
      . ' :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; };'
      . ' :local date [ /system clock get date ]; :local time [ /system clock get time ];'
      . ' :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ;'
      . ' :foreach i in [ /ppp secret find where profile="' . $name . '" ] do={'
      . ' :local comment [ /ppp secret get $i comment]; :local name [ /ppp secret get $i name];'
      . ' :local gettime [:pick $comment 11 19];'
      . ' :if ([:pick $comment 4] = "-" and [:pick $comment 7] = "-") do={'
      . ' :local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ;'
      . ' :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={'
      . ' [ /ppp secret ' . $mode . ' $i ]; [ /ppp active remove [find where name=$name] ];}}}';
  }
}

// Name of the scheduler that monitors a PPP profile. Prefixed so that it can
// not collide with the hotspot profile monitor of the same name.
if (!function_exists('pppMonitorName')) {
  function pppMonitorName($name)
  {
    return "pppoe-" . $name;
  }
}
