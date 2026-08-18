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

  $getprofile = $API->comm("/ppp/profile/print");
  $TotalReg = count($getprofile);
  $countprofile = $API->comm("/ppp/profile/print", array(
    "count-only" => "",
  ));
}
?>
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header align-middle">
    <h3><i class="fa fa-pie-chart"></i> <?= $_pppoe_profile ?>
    &nbsp; | &nbsp; <a href="./?ppp=add-profile&session=<?= $session; ?>" title="<?= $_add ?>"><i class="fa fa-plus-square"></i> <?= $_add ?></a>
    </h3>
</div>
<div class="card-body">
<div class="row">
  <div class="col-6 pd-t-5 pd-b-5">
    <input id="filterTable" type="text" style="padding:5.8px;" class="form-control" placeholder="<?= $_search ?>">
  </div>
</div>
<div class="overflow mr-t-10 box-bordered" style="max-height: 75vh">
<table id="dataTable" class="table table-bordered table-hover text-nowrap">
  <thead>
  <tr>
    <th style="min-width:50px;" class="text-center"><?= $countprofile; ?></th>
    <th class="align-middle pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_name ?></th>
    <th class="align-middle">Local<br>Address</th>
    <th class="align-middle">Remote<br>Address</th>
    <th class="align-middle">Rate<br>Limit</th>
    <th class="align-middle"><?= $_expired_mode ?></th>
    <th class="align-middle"><?= $_validity ?></th>
    <th class="text-right align-middle"><?= $_price . " " . $currency; ?></th>
    <th class="text-right align-middle"><?= $_selling_price . " " . $currency; ?></th>
    <th class="align-middle"><?= $_lock_caller_id ?></th>
    </tr>
  </thead>
  <tbody>
<?php
for ($i = 0; $i < $TotalReg; $i++) {

  $profiledetails = $getprofile[$i];
  $pid = $profiledetails['.id'];
  $pname = $profiledetails['name'];
  $plocal = $profiledetails['local-address'];
  $premote = $profiledetails['remote-address'];
  $pratelimit = $profiledetails['rate-limit'];
  $ponup = $profiledetails['on-up'];
  $meta = pppMeta($ponup);

  $monname = pppMonitorName($pname);
  $getmon = $API->comm("/system/scheduler/print", array(
    "?name" => "$monname",
  ));
  $monexpired = $getmon[0];
  $pmon = $monexpired['name'];
  $chkpmon = $monexpired['disabled'];
  if (empty($pmon) || $chkpmon == "true") {
    $moncolor = "text-orange";
  } else {
    $moncolor = "text-green";
  }

  // Number of secrets using this profile.
  $countsecret = $API->comm("/ppp/secret/print", array(
    "count-only" => "",
    "?profile"   => "$pname",
  ));

  echo "<tr>";
  ?>
  <td style='text-align:center;'><i class='fa fa-minus-square text-danger pointer' onclick="if(confirm('<?= $_confirm_remove_profile ?> (<?= $pname; ?>) ?')){loadpage('./?remove-pprofile=<?= $pid; ?>&pname=<?= $pname ?>&session=<?= $session; ?>')}else{}" title='<?= $_remove ?> <?= $pname; ?>'></i>&nbsp;&nbsp;&nbsp;&nbsp;
  <?php
  echo "<a title='" . $_pppoe_clients . " " . $pname . "' href='./?ppp=secrets&pprofile=" . $pname . "&session=" . $session . "'><i class='fa fa-users'></i></a></td>";
  echo "<td><a title='" . $_edit . " " . $pname . "' href='./?ppp=edit-profile&pprofile=" . $pid . "&session=" . $session . "'><i class='fa fa-edit'></i> <i class='fa fa-ci fa-circle " . $moncolor . "'></i> " . $pname . " [" . (int) $countsecret . "]</a></td>";
  echo "<td>" . $plocal . "</td>";
  echo "<td>" . $premote . "</td>";
  echo "<td>" . $pratelimit . "</td>";
  echo "<td>" . pppExpModeName($meta['expmode']) . "</td>";
  // A validity with no unit was saved before the normalisation existed : the
  // router reads it as seconds. Flag it, re-saving the profile repairs it.
  if ($meta['validity'] != "" && preg_match('/^\d+$/', $meta['validity'])) {
    echo "<td><span class='text-danger' title='" . $_validity_no_unit . "'><i class='fa fa-exclamation-triangle'></i> " . $meta['validity'] . "</span></td>";
  } else {
    echo "<td>" . $meta['validity'] . "</td>";
  }
  echo "<td style='text-align:right;'>" . pppPrice($meta['price'], $currency, $cekindo) . "</td>";
  echo "<td style='text-align:right;'>" . pppPrice($meta['sprice'], $currency, $cekindo) . "</td>";
  echo "<td>" . $meta['lock'] . "</td>";
  echo "</tr>";
}
?>
  </tbody>
</table>
</div>
</div>
</div>
</div>
</div>
