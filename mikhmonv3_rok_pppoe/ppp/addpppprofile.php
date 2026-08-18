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

  $getallqueue = $API->comm("/queue/simple/print", array(
    "?dynamic" => "false",
  ));
  $getpool = $API->comm("/ip/pool/print");

  if (isset($_POST['name'])) {
    $name = (preg_replace('/\s+/', '-', $_POST['name']));
    $localaddr = ($_POST['localaddr']);
    $remoteaddr = ($_POST['remoteaddr']);
    $ratelimit = ($_POST['ratelimit']);
    $dnsserver = ($_POST['dnsserver']);
    $onlyone = ($_POST['onlyone']);
    $expmode = ($_POST['expmode']);
    $validity = ($_POST['validity']);
    $getprice = ($_POST['price']);
    $getsprice = ($_POST['sprice']);
    $lockmode = ($_POST['lockunlock']);
    $parent = ($_POST['parent']);

    if ($getprice == "") {
      $price = "0";
    } else {
      $price = $getprice;
    }
    if ($getsprice == "") {
      $sprice = "0";
    } else {
      $sprice = $getsprice;
    }

    $onup = pppOnUpScript($name, $expmode, $price, $sprice, $validity, $lockmode);
    $monname = pppMonitorName($name);
    $randstarttime = "0" . rand(1, 5) . ":" . rand(10, 59) . ":" . rand(10, 59);
    $randinterval = "00:02:" . rand(10, 59);

    $API->comm("/ppp/profile/add", array(
      "name"           => "$name",
      "local-address"  => "$localaddr",
      "remote-address" => "$remoteaddr",
      "rate-limit"     => "$ratelimit",
      "dns-server"     => "$dnsserver",
      "only-one"       => "$onlyone",
      "on-up"          => "$onup",
      "parent-queue"   => "$parent",
    ));

    // Monitor of the expired clients of this profile.
    $getmon = $API->comm("/system/scheduler/print", array("?name" => "$monname"));
    $monid = $getmon[0]['.id'];
    if ($expmode != "0" && $expmode != "") {
      $bgservice = pppMonitorScript($name, $expmode);
      if (empty($monid)) {
        $API->comm("/system/scheduler/add", array(
          "name"       => "$monname",
          "start-time" => "$randstarttime",
          "interval"   => "$randinterval",
          "on-event"   => "$bgservice",
          "disabled"   => "no",
          "comment"    => "Monitor PPPoE Profile $name",
        ));
      } else {
        $API->comm("/system/scheduler/set", array(
          ".id"        => "$monid",
          "start-time" => "$randstarttime",
          "interval"   => "$randinterval",
          "on-event"   => "$bgservice",
          "disabled"   => "no",
          "comment"    => "Monitor PPPoE Profile $name",
        ));
      }
    } elseif (!empty($monid)) {
      $API->comm("/system/scheduler/remove", array(".id" => "$monid"));
    }

    $getprofile = $API->comm("/ppp/profile/print", array("?name" => "$name"));
    $pid = $getprofile[0]['.id'];
    echo "<script>window.location='./?ppp=edit-profile&pprofile=" . $pid . "&session=" . $session . "'</script>";
  }
}
?>
<div class="row">
<div class="col-8">
<div class="card box-bordered">
  <div class="card-header">
    <h3><i class="fa fa-plus"></i> <?= $_add . ' ' . $_pppoe_profile ?> <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small></h3>
  </div>
  <div class="card-body">
<form autocomplete="off" method="post" action="">
  <div>
    <a class="btn bg-warning" href="./?ppp=profiles&session=<?= $session; ?>"> <i class="fa fa-close btn-mrg"></i> <?= $_close ?></a>
    <button type="submit" name="save" onclick="loader()" class="btn bg-primary btn-mrg" ><i class="fa fa-save btn-mrg"></i> <?= $_save ?></button>
  </div>
<table class="table">
  <tr>
    <td class="align-middle"><?= $_name ?></td><td><input class="form-control" type="text" onchange="remSpace();" autocomplete="off" name="name" value="" required="1" autofocus></td>
  </tr>
  <tr>
    <td class="align-middle">Local Address</td><td><input class="form-control" type="text" autocomplete="off" name="localaddr" value="" placeholder="Example : 10.10.10.1"></td>
  </tr>
  <tr>
    <td class="align-middle">Remote Address</td>
    <td>
    <select class="form-control" name="remoteaddr">
      <option value="">none</option>
        <?php $TotalReg = count($getpool);
        for ($i = 0; $i < $TotalReg; $i++) {
          echo "<option>" . $getpool[$i]['name'] . "</option>";
        }
        ?>
    </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Rate limit [up/down]</td><td><input class="form-control" type="text" name="ratelimit" autocomplete="off" value="" placeholder="Example : 512k/1M" ></td>
  </tr>
  <tr>
    <td class="align-middle">DNS Server</td><td><input class="form-control" type="text" name="dnsserver" autocomplete="off" value="" placeholder="Example : 8.8.8.8,1.1.1.1" ></td>
  </tr>
  <tr>
    <td class="align-middle">Only One</td>
    <td>
      <select class="form-control" name="onlyone">
        <option value="yes">yes</option>
        <option value="no">no</option>
        <option value="default">default</option>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_expired_mode ?></td><td>
      <select class="form-control" onchange="RequiredVP();" id="expmode" name="expmode" required="1">
        <option value="">Select...</option>
        <option value="0">None</option>
        <option value="rem">Remove</option>
        <option value="dis">Disable</option>
        <option value="remc">Remove &amp; Record</option>
        <option value="disc">Disable &amp; Record</option>
      </select>
    </td>
  </tr>
  <tr id="validity" style="display:none;">
    <td class="align-middle"><?= $_validity ?></td><td><input class="form-control" type="text" id="validi" size="4" autocomplete="off" name="validity" value="" placeholder="Example : 30d"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_price . ' ' . $currency; ?></td><td><input class="form-control" type="text" size="10" min="0" name="price" value="" ></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_selling_price . ' ' . $currency; ?></td><td><input class="form-control" type="text" size="10" min="0" name="sprice" value="" ></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_lock_caller_id ?></td><td>
      <select class="form-control" id="lockunlock" name="lockunlock" required="1">
        <option value="Disable">Disable</option>
        <option value="Enable">Enable</option>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Parent Queue</td>
    <td>
    <select class="form-control" name="parent">
      <option>none</option>
        <?php $TotalReg = count($getallqueue);
        for ($i = 0; $i < $TotalReg; $i++) {
          echo "<option>" . $getallqueue[$i]['name'] . "</option>";
        }
        ?>
    </select>
  </td>
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
<table class="table">
    <tr>
    <td colspan="2">
      <p style="padding:0px 5px;">
        <?= $_details_pppoe_profile ?>
      </p>
      <p style="padding:0px 5px;">
        <?= $_format_validity ?>
      </p>
    </td>
  </tr>
</table>
</div>
</div>
</div>
</div>
<script type="text/javascript">
function remSpace() {
  var upName = document.getElementsByName("name")[0];
  upName.value = upName.value.replace(/\s/g, "-");
  upName.focus();
}
</script>
<?php include("./ppp/pppjs.php"); ?>
