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

  $getactive = $API->comm("/ppp/active/print");
  $TotalReg = count($getactive);
  $countactive = $API->comm("/ppp/active/print", array(
    "count-only" => "",
  ));
}
?>
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
    <h3><i class="fa fa-plug"></i> <?= $_ppp_active ?>
      <span style="font-size: 14px">
        &nbsp; | &nbsp; <a href="./?ppp=secrets&session=<?= $session; ?>" title="<?= $_pppoe_clients ?>"><i class="fa fa-users"></i> <?= $_pppoe_clients ?></a>
      </span> &nbsp;
      <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small>
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
    <th style="min-width:50px;" class="align-middle text-center"><?= $countactive; ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_name ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Service</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Caller ID</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Address</th>
    <th class="text-right align-middle pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_uptime ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Encoding</th>
    </tr>
  </thead>
  <tbody>
<?php
for ($i = 0; $i < $TotalReg; $i++) {
  $active = $getactive[$i];
  $aid = $active['.id'];
  $aname = $active['name'];
  $aservice = $active['service'];
  $acallerid = $active['caller-id'];
  $aaddress = $active['address'];
  $auptime = $active['uptime'];
  $aencoding = $active['encoding'];

  echo "<tr>";
  ?>
  <td style='text-align:center;'><i class='fa fa-minus-square text-danger pointer' onclick="if(confirm('<?= $_confirm_remove_active ?> (<?= $aname; ?>) ?')){loadpage('./?remove-pactive=<?= $aid; ?>&session=<?= $session; ?>')}else{}" title='<?= $_remove ?> <?= $aname; ?>'></i></td>
  <?php
  echo "<td><a title='" . $_edit . " " . $aname . "' href='./?secret=" . $aname . "&session=" . $session . "'><i class='fa fa-edit'></i> " . $aname . "</a></td>";
  echo "<td>" . $aservice . "</td>";
  echo "<td>" . $acallerid . "</td>";
  echo "<td>" . $aaddress . "</td>";
  echo "<td style='text-align:right'>" . $auptime . "</td>";
  echo "<td>" . $aencoding . "</td>";
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
