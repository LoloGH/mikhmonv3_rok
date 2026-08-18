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

if ($removesecr != "") {
	// Drop the active connection first, otherwise the client stays online.
	$getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$removesecr"));
	$sname = $getsecret[0]['name'];
	$getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
	if ($getactive[0]['.id'] != "") {
		$API->comm("/ppp/active/remove", array(".id" => $getactive[0]['.id']));
	}
	$API->comm("/ppp/secret/remove", array(
		".id" => "$removesecr",
	));
} elseif ($enablesecr != "") {
	$API->comm("/ppp/secret/set", array(
		".id"      => "$enablesecr",
		"disabled" => "no",
	));
} elseif ($disablesecr != "") {
	$getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$disablesecr"));
	$sname = $getsecret[0]['name'];
	$API->comm("/ppp/secret/set", array(
		".id"      => "$disablesecr",
		"disabled" => "yes",
	));
	$getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
	if ($getactive[0]['.id'] != "") {
		$API->comm("/ppp/active/remove", array(".id" => $getactive[0]['.id']));
	}
}

if ($_SESSION['sbp'] != "") {
	echo "<script>window.location='./?ppp=secrets&pprofile=" . $_SESSION['sbp'] . "&session=" . $session . "'</script>";
} else {
	echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
}
?>
