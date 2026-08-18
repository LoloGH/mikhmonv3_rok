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

include_once('./ppp/pppmisc.php');

date_default_timezone_set($_SESSION['timezone']);

$getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$renewsecret"));
$secret = $getsecret[0];
$sname = $secret['name'];
$sprofile = $secret['profile'];
$sremote = $secret['remote-address'];
$scallerid = $secret['caller-id'];
$scomment = $secret['comment'];

if ($sname != "") {

	$getp = $API->comm("/ppp/profile/print", array("?name" => "$sprofile"));
	$meta = pppMeta($getp[0]['on-up']);
	$seconds = pppValidityToSeconds($meta['validity']);

	if ($seconds > 0) {
		// Keep the free note of the client, only the date moves.
		if (pppIsExpDate($scomment)) {
			$note = trim(substr($scomment, 19));
			$from = pppExpStamp($scomment);
		} else {
			$note = $scomment;
			if (substr($note, 0, 3) == "pp-") {
				$note = substr($note, 3);
			}
			$from = 0;
		}
		// An expired client restarts from now, an active one is extended.
		if ($from < time()) {
			$from = time();
		}
		$expired = date("Y-m-d H:i:s", $from + $seconds);

		$API->comm("/ppp/secret/set", array(
			".id"      => "$renewsecret",
			"disabled" => "no",
			"comment"  => trim($expired . " " . $note),
		));

		// Record the renewal when the profile is in a "& Record" mode.
		if ($meta['expmode'] == "remc" || $meta['expmode'] == "disc") {
			pppAddRecord($API, $sname, $meta['price'], $sremote, $scallerid, $meta['validity'], $sprofile, $note);
		}
	} else {
		// Without a validity on the profile there is nothing to extend,
		// the client is simply re-enabled.
		$API->comm("/ppp/secret/set", array(
			".id"      => "$renewsecret",
			"disabled" => "no",
		));
	}
}

if ($_SESSION['sbp'] != "") {
	echo "<script>window.location='./?ppp=secrets&pprofile=" . $_SESSION['sbp'] . "&session=" . $session . "'</script>";
} else {
	echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
}
?>
