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
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  include_once('./ppp/pppmisc.php');
  include_once('./ppp/pppscript.php');

  date_default_timezone_set($_SESSION['timezone']);

  $getprofile = $API->comm("/ppp/profile/print");

  if (isset($_POST['name'])) {
    $name = (preg_replace('/\s+/', '', $_POST['name']));
    $password = ($_POST['pass']);
    $profile = ($_POST['profile']);
    $service = ($_POST['service']);
    $localaddr = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $callerid = ($_POST['callerid']);
    $comment = ($_POST['comment']);
    $activation = ($_POST['activation']);

    // Commercial data of the profile.
    $getp = $API->comm("/ppp/profile/print", array("?name" => "$profile"));
    $meta = pppMeta($getp[0]['on-up']);

    // "now" activates the client immediately : the expiration date is written
    // straight away. "login" leaves it to the on-up script, which stamps the
    // date at the first connection (same principle as the hotspot voucher).
    if ($activation == "now" && $meta['validity'] != "" && $meta['expmode'] != "" && $meta['expmode'] != "0") {
      $seconds = pppValidityToSeconds($meta['validity']);
      $expired = date("Y-m-d H:i:s", time() + $seconds);
      $fullcomment = trim($expired . " " . $comment);
    } else {
      $fullcomment = "pp-" . $comment;
    }

    $API->comm("/ppp/secret/add", array(
      "name"           => "$name",
      "password"       => "$password",
      "profile"        => "$profile",
      "service"        => "$service",
      "local-address"  => "$localaddr",
      "remote-address" => "$remoteaddr",
      "caller-id"      => "$callerid",
      "disabled"       => "no",
      "comment"        => "$fullcomment",
    ));

    // Record the sale right away when the client is activated from Mikhmon
    // and the profile is in a "& Record" mode.
    if ($activation == "now" && ($meta['expmode'] == "remc" || $meta['expmode'] == "disc")) {
      pppAddRecord($API, $name, $meta['price'], $remoteaddr, $callerid, $meta['validity'], $profile, $comment);
    }

    $getsecret = $API->comm("/ppp/secret/print", array("?name" => "$name"));
    $sid = $getsecret[0]['.id'];
    echo "<script>window.location='./?secret=" . $sid . "&session=" . $session . "'</script>";
  }
}
?>
<script>
  function PassUser(){
    var x = document.getElementById('passUser');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
</script>
<div class="row">
<div class="col-8">
<div class="card box-bordered">
  <div class="card-header">
  <h3><i class="fa fa-user-plus"></i> <?= $_add_pppoe_client ?> <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small></h3>
  </div>
  <div class="card-body">
<form autocomplete="off" method="post" action="">
  <div>
    <a class="btn bg-warning" href="./?ppp=secrets&session=<?= $session; ?>"> <i class="fa fa-close"></i> <?= $_close ?></a>
    <button type="submit" onclick="loader()" class="btn bg-primary" name="save"><i class="fa fa-save"></i> <?= $_save ?></button>
  </div>

<table class="table">
  <tr>
    <td class="align-middle"><?= $_name ?></td><td><input class="form-control" type="text" autocomplete="off" name="name" value="" required="1" autofocus></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_password ?></td><td>
        <div class="input-group">
          <div class="input-group-11 col-box-10">
            <input class="group-item group-item-l" id="passUser" type="password" name="pass" autocomplete="new-password" value="" required="1">
          </div>
            <div class="input-group-1 col-box-2">
              <div class="group-item group-item-r pd-2p5 text-center">
              <input title="Show/Hide Password" type="checkbox" onclick="PassUser()">
            </div>
            </div>
        </div>
    </td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_profile ?></td><td>
      <select class="form-control" onchange="GetVPP('<?= $session; ?>');" id="pprof" name="profile" required="1">
        <?php $TotalReg = count($getprofile);
        for ($i = 0; $i < $TotalReg; $i++) {
          echo "<option>" . $getprofile[$i]['name'] . "</option>";
        }
        ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Service</td><td>
      <select class="form-control" name="service">
        <option value="pppoe">pppoe</option>
        <option value="any">any</option>
        <option value="pptp">pptp</option>
        <option value="l2tp">l2tp</option>
        <option value="sstp">sstp</option>
        <option value="ovpn">ovpn</option>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Local Address</td><td><input class="form-control" type="text" autocomplete="off" name="localaddr" value=""></td>
  </tr>
  <tr>
    <td class="align-middle">Remote Address</td><td><input class="form-control" type="text" autocomplete="off" name="remoteaddr" value=""></td>
  </tr>
  <tr>
    <td class="align-middle">Caller ID</td><td><input class="form-control" type="text" autocomplete="off" name="callerid" value="" placeholder="MAC address"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_activation ?></td><td>
      <select class="form-control" name="activation" required="1">
        <option value="login"><?= $_activation_login ?></option>
        <option value="now"><?= $_activation_now ?></option>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_comment ?></td><td><input class="form-control" type="text" id="comment" autocomplete="off" name="comment" value="" placeholder="<?= $_client_note ?>"></td>
  </tr>
  <tr>
    <td colspan="2" class="align-middle" id="GetValidPrice"></td>
  </tr>
</table>
</form>
</div>
</div>
</div>
<div class="col-4">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-book"></i> <?= $_readme ?></h3>
    </div>
    <div class="card-body">
<table>
   <tr>
    <td colspan="2">
    <p style="padding:0px 5px;">
      <?= $_details_pppoe_client ?>
    </p>
    </td>
  </tr>
</table>
</div>
</div>
</div>
</div>
<?php include("./ppp/pppjs.php"); ?>
<script>
$(document).ready(function(){ GetVPP('<?= $session; ?>'); });
</script>
