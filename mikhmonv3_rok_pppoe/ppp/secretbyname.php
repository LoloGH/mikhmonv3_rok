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

// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  include_once('./ppp/pppmisc.php');
  include_once('./ppp/pppscript.php');

  date_default_timezone_set($_SESSION['timezone']);

  $getprofile = $API->comm("/ppp/profile/print");

  if (substr($secretbyname, 0, 1) == "*") {
    $getsecret = $API->comm("/ppp/secret/print", array("?.id" => "$secretbyname"));
  } else {
    $getsecret = $API->comm("/ppp/secret/print", array("?name" => "$secretbyname"));
  }

  $secret = $getsecret[0];
  $sid = $secret['.id'];
  $sname = $secret['name'];
  $spass = $secret['password'];
  $sprofile = $secret['profile'];
  $sservice = $secret['service'];
  $slocal = $secret['local-address'];
  $sremote = $secret['remote-address'];
  $scallerid = $secret['caller-id'];
  $sdisabled = $secret['disabled'];
  $scomment = $secret['comment'];

  if ($sname == "") {
    echo "<b>" . $_pppoe_clients . " not found, redirect to the list...</b>";
    echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
  }

  // Split the comment into the expiration date and the free note.
  if (pppIsExpDate($scomment)) {
    $sexpired = substr($scomment, 0, 19);
    $snote = trim(substr($scomment, 19));
  } else {
    $sexpired = "";
    $snote = $scomment;
    if (substr($snote, 0, 3) == "pp-") {
      $snote = substr($snote, 3);
    }
  }
  $status = pppStatus($scomment, $sdisabled);

  // Commercial data of the profile of this client.
  $getp = $API->comm("/ppp/profile/print", array("?name" => "$sprofile"));
  $meta = pppMeta($getp[0]['on-up']);
  if ($meta['sprice'] != "" && $meta['sprice'] != "0") {
    $billed = $meta['sprice'];
  } else {
    $billed = $meta['price'];
  }

  // Last connection seen on the router.
  $getactive = $API->comm("/ppp/active/print", array("?name" => "$sname"));
  $sactiveaddr = $getactive[0]['address'];
  $sactiveup = $getactive[0]['uptime'];

  if (isset($_POST['name'])) {
    $name = (preg_replace('/\s+/', '', $_POST['name']));
    $password = ($_POST['pass']);
    $profile = ($_POST['profile']);
    $service = ($_POST['service']);
    $localaddr = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $callerid = ($_POST['callerid']);
    $disabled = ($_POST['disabled']);
    $note = ($_POST['comment']);
    $expired = trim($_POST['expired']);

    if ($expired != "") {
      $fullcomment = trim(str_replace("T", " ", $expired) . " " . $note);
    } elseif ($note != "") {
      $fullcomment = "pp-" . $note;
    } else {
      $fullcomment = "";
    }

    $API->comm("/ppp/secret/set", array(
      ".id"            => "$sid",
      "name"           => "$name",
      "password"       => "$password",
      "profile"        => "$profile",
      "service"        => "$service",
      "local-address"  => "$localaddr",
      "remote-address" => "$remoteaddr",
      "caller-id"      => "$callerid",
      "disabled"       => "$disabled",
      "comment"        => "$fullcomment",
    ));
    echo "<script>window.location='./?secret=" . $sid . "&session=" . $session . "'</script>";
  }

  // Share the client credentials on WhatsApp, same idea as the hotspot voucher.
  if ($meta['validity'] != "") {
    $wavalid = $_validity . " : *" . $meta['validity'] . "* %0A";
  } else {
    $wavalid = "";
  }
  if ($billed != "" && $billed != "0") {
    if ($currency == in_array($currency, $cekindo['indo'])) {
      $waprice = $_price . " : *" . $currency . " " . number_format((float) $billed, 0, ",", ".") . "* %0A";
    } else {
      $waprice = $_price . " : *" . $currency . " " . number_format((float) $billed) . "* %0A";
    }
  } else {
    $waprice = "";
  }
  if ($sexpired != "") {
    $waexp = $_expired . " : *" . $sexpired . "* %0A";
  } else {
    $waexp = "";
  }
  $shareWA = "
%0A---------%0A
*" . $hotspotname . "*
%0A%0A
PPPoE Username : *" . $sname . "* %0A
PPPoE Password : *" . $spass . "* %0A
" . $_profile . " : *" . $sprofile . "* %0A
" . $wavalid . "
" . $waexp . "
" . $waprice . "
---------
";
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
    <h3><i class="fa fa-edit"></i> <?= $_edit . ' ' . $_pppoe_clients . ' ' . $sname ?>
    <?php
    if ($status == "expired") {
      echo "<span class='text-danger'> [" . $_st_expired . "]</span>";
    } elseif ($status == "waiting") {
      echo "<span class='text-orange'> [" . $_st_waiting . "]</span>";
    } elseif ($status == "disabled") {
      echo "<span class='text-orange'> [" . $_st_disabled . "]</span>";
    } else {
      echo "<span class='text-green'> [" . $_st_active . "]</span>";
    }
    ?>
    <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small></h3>
  </div>
  <div class="card-body">
<form autocomplete="new-password" method="post" action="">
  <div>
    <a class="btn bg-warning" href="./?ppp=secrets&session=<?= $session; ?>"> <i class="fa fa-close"></i> <?= $_close ?></a>
    <button type="submit" onclick="loader()" class="btn bg-primary" name="save"><i class="fa fa-save"></i> <?= $_save ?></button>
    <a class="btn bg-primary" href="javascript:void(0)" onclick="if(confirm('<?= $_confirm_renew ?> (<?= $sname; ?>) ?')){loadpage('./?renew-pppsecret=<?= $sid; ?>&session=<?= $session; ?>');loader();}else{}"><i class="fa fa-refresh"></i> <?= $_renew ?></a>
    <a class="btn bg-green" target="_blank" href="https://api.whatsapp.com/send?text=<?= $shareWA; ?>"><i class="fa fa-share-alt"></i> <?= $_share ?></a>
  </div>

<table class="table">
  <tr>
    <td class="align-middle"><?= $_name ?></td><td><input class="form-control" type="text" autocomplete="off" name="name" value="<?= $sname; ?>" required="1"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_password ?></td><td>
        <div class="input-group">
          <div class="input-group-11 col-box-10">
            <input class="group-item group-item-l" id="passUser" type="password" name="pass" autocomplete="new-password" value="<?= $spass; ?>" required="1">
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
        <option><?= $sprofile; ?></option>
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
        <option value="<?= $sservice; ?>"><?= ($sservice == "" ? "any" : $sservice); ?></option>
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
    <td class="align-middle">Local Address</td><td><input class="form-control" type="text" autocomplete="off" name="localaddr" value="<?= $slocal; ?>"></td>
  </tr>
  <tr>
    <td class="align-middle">Remote Address</td><td><input class="form-control" type="text" autocomplete="off" name="remoteaddr" value="<?= $sremote; ?>"></td>
  </tr>
  <tr>
    <td class="align-middle">Caller ID</td><td><input class="form-control" type="text" autocomplete="off" name="callerid" value="<?= $scallerid; ?>"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_expired ?></td><td><input class="form-control" type="text" autocomplete="off" name="expired" value="<?= $sexpired; ?>" placeholder="YYYY-MM-DD HH:MM:SS"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_comment ?></td><td><input class="form-control" type="text" id="comment" autocomplete="off" name="comment" value="<?= $snote; ?>" placeholder="<?= $_client_note ?>"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_status ?></td><td>
      <select class="form-control" name="disabled">
        <option value="<?= ($sdisabled == "true" ? "yes" : "no"); ?>"><?= ($sdisabled == "true" ? $_st_disabled : $_enable); ?></option>
        <option value="no"><?= $_enable ?></option>
        <option value="yes"><?= $_disable ?></option>
      </select>
    </td>
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
      <h3><i class="fa fa-info-circle"></i> <?= $_pppoe_clients ?></h3>
    </div>
    <div class="card-body">
<table class="table">
  <tr><td><?= $_profile ?></td><td><?= $sprofile; ?></td></tr>
  <tr><td><?= $_validity ?></td><td><?= $meta['validity']; ?></td></tr>
  <tr><td><?= $_expired_mode ?></td><td><?= pppExpModeName($meta['expmode']); ?></td></tr>
  <tr><td><?= $_price . ' ' . $currency ?></td><td><?= pppPrice($meta['price'], $currency, $cekindo); ?></td></tr>
  <tr><td><?= $_selling_price . ' ' . $currency ?></td><td><?= pppPrice($meta['sprice'], $currency, $cekindo); ?></td></tr>
  <tr><td><?= $_expired ?></td><td><?= ($sexpired == "" ? $_st_waiting : $sexpired); ?></td></tr>
  <tr><td><?= $_ppp_active ?></td><td><?= ($sactiveaddr == "" ? "-" : $sactiveaddr . " (" . $sactiveup . ")"); ?></td></tr>
</table>
<p style="padding:0px 5px;"><?= $_details_pppoe_client ?></p>
</div>
</div>
</div>
</div>
<?php include("./ppp/pppjs.php"); ?>
