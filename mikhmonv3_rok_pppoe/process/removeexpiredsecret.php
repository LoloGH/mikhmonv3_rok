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
session_start();
// hide all error
error_reporting(0);
ini_set('max_execution_time', 300);

include_once('./ppp/pppmisc.php');

date_default_timezone_set($_SESSION['timezone']);

$pprofile = $_GET['pprofile'];
if ($pprofile != "" && $pprofile != "all") {
	$getsecret = $API->comm("/ppp/secret/print", array("?profile" => "$pprofile"));
} else {
	$getsecret = $API->comm("/ppp/secret/print");
}

$TotalReg = count($getsecret);
for ($i = 0; $i < $TotalReg; $i++) {
	$secret = $getsecret[$i];
	if (pppStatus($secret['comment'], $secret['disabled']) == "expired") {
		$getactive = $API->comm("/ppp/active/print", array("?name" => $secret['name']));
		if ($getactive[0]['.id'] != "") {
			$API->comm("/ppp/active/remove", array(".id" => $getactive[0]['.id']));
		}
		$API->comm("/ppp/secret/remove", array(".id" => $secret['.id']));
	}
}

echo "<script>window.location='./?ppp=secrets&pprofile=" . ($pprofile == "" ? "all" : $pprofile) . "&session=" . $session . "'</script>";
?>
