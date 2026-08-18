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
ini_set('max_execution_time', 300);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  include_once('./ppp/pppmisc.php');
  include_once('./ppp/pppscript.php');

  $pprofile = $_GET['pprofile'];
  $pstatus = $_GET['pstatus'];

  if ($pprofile != "" && $pprofile != "all") {
    $getsecret = $API->comm("/ppp/secret/print", array(
      "?profile" => "$pprofile",
    ));
  } else {
    $getsecret = $API->comm("/ppp/secret/print");
  }
  $TotalReg = count($getsecret);

  $getprofile = $API->comm("/ppp/profile/print");
  $TotalReg2 = count($getprofile);

  // Active connections, indexed by user name.
  $getactive = $API->comm("/ppp/active/print");
  $onlineusers = array();
  for ($i = 0; $i < count($getactive); $i++) {
    $onlineusers[$getactive[$i]['name']] = $getactive[$i]['address'];
  }

  // Rows kept after the status filter, plus the counters of the summary.
  $rows = array();
  $cntwaiting = 0;
  $cntactive = 0;
  $cntexpired = 0;
  for ($i = 0; $i < $TotalReg; $i++) {
    $secret = $getsecret[$i];
    $status = pppStatus($secret['comment'], $secret['disabled']);
    if ($status == "waiting") {
      $cntwaiting++;
    } elseif ($status == "expired") {
      $cntexpired++;
    } else {
      $cntactive++;
    }
    if ($pstatus == "" || $pstatus == "all" || $pstatus == $status) {
      $secret['mikhmon-status'] = $status;
      $rows[] = $secret;
    }
  }
  $countsecret = count($rows);
}
?>
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
    <h3><i class="fa fa-users"></i> <?= $_pppoe_clients ?>
      <span style="font-size: 14px">
        &nbsp; | &nbsp; <a href="./?ppp=addsecret&session=<?= $session; ?>" title="<?= $_add ?>"><i class="fa fa-user-plus"></i> <?= $_add ?></a>
        &nbsp; | &nbsp; <a href="./?ppp=profiles&session=<?= $session; ?>" title="<?= $_pppoe_profile ?>"><i class="fa fa-pie-chart"></i> <?= $_pppoe_profile ?></a>
        &nbsp; | &nbsp; <a href="./?ppp=active&session=<?= $session; ?>" title="<?= $_ppp_active ?>"><i class="fa fa-plug"></i> <?= $_ppp_active ?></a>
      </span> &nbsp;
      <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small>
    </h3>
</div>
<div class="card-body">
  <div class="row">
   <div class="col-6 pd-t-5 pd-b-5">
  <div class="input-group">
    <div class="input-group-4 col-box-4">
      <input id="filterTable" type="text" style="padding:5.8px;" class="group-item group-item-l" placeholder="<?= $_search ?>">
    </div>
    <div class="input-group-4 col-box-4">
      <select style="padding:5px;" class="group-item group-item-m" onchange="location = this.value; loader()" title="<?= $_profile ?>">
        <option><?= $_profile ?></option>
        <option value="./?ppp=secrets&pprofile=all&session=<?= $session; ?>"><?= $_show_all ?></option>
      <?php
      for ($i = 0; $i < $TotalReg2; $i++) {
        $profile = $getprofile[$i];
        echo "<option value='./?ppp=secrets&pprofile=" . $profile['name'] . "&session=" . $session . "'>" . $profile['name'] . "</option>";
      }
      ?>
    </select>
  </div>
  <div class="input-group-4 col-box-4">
    <select style="padding:5px;" class="group-item group-item-r" onchange="location = this.value; loader()" title="<?= $_status ?>">
      <option><?= $_status ?></option>
      <option value="./?ppp=secrets&pprofile=<?= ($pprofile == "" ? "all" : $pprofile); ?>&pstatus=all&session=<?= $session; ?>"><?= $_show_all ?></option>
      <option value="./?ppp=secrets&pprofile=<?= ($pprofile == "" ? "all" : $pprofile); ?>&pstatus=active&session=<?= $session; ?>"><?= $_st_active ?> [<?= $cntactive; ?>]</option>
      <option value="./?ppp=secrets&pprofile=<?= ($pprofile == "" ? "all" : $pprofile); ?>&pstatus=waiting&session=<?= $session; ?>"><?= $_st_waiting ?> [<?= $cntwaiting; ?>]</option>
      <option value="./?ppp=secrets&pprofile=<?= ($pprofile == "" ? "all" : $pprofile); ?>&pstatus=expired&session=<?= $session; ?>"><?= $_st_expired ?> [<?= $cntexpired; ?>]</option>
    </select>
  </div>
  </div>
  </div>
  <div class="col-6 text-right pd-t-5">
    <?php if ($pstatus == "expired" && $countsecret > 0) { ?>
    <button class="btn bg-red" onclick="if(confirm('<?= $_confirm_remove_expired ?>')){loadpage('./?remove-pppsecret-expired=1&pprofile=<?= $pprofile; ?>&session=<?= $session; ?>');loader();}else{}" title="<?= $_confirm_remove_expired ?>"><i class="fa fa-trash"></i> <?= $_st_expired ?></button>
    <?php } ?>
    <span class="pd-5"><i class="fa fa-circle text-green"></i> <?= $_st_active ?> : <b><?= $cntactive; ?></b></span>
    <span class="pd-5"><i class="fa fa-circle text-orange"></i> <?= $_st_waiting ?> : <b><?= $cntwaiting; ?></b></span>
    <span class="pd-5"><i class="fa fa-circle text-danger"></i> <?= $_st_expired ?> : <b><?= $cntexpired; ?></b></span>
  </div>
</div>
<div class="overflow mr-t-10 box-bordered" style="max-height: 75vh">
<table id="dataTable" class="table table-bordered table-hover text-nowrap">
  <thead>
  <tr>
    <th style="min-width:70px;" class="align-middle text-center" id="cuser"><?= $countsecret; ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_name ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_profile ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Service</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Caller ID</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Remote Address</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_status ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_expired ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_comment ?></th>
    </tr>
  </thead>
  <tbody id="tbody">
<?php
for ($i = 0; $i < $countsecret; $i++) {
  $secret = $rows[$i];
  $sid = $secret['.id'];
  $sname = $secret['name'];
  $sprofile = $secret['profile'];
  $sservice = $secret['service'];
  $scallerid = $secret['caller-id'];
  $sremote = $secret['remote-address'];
  $scomment = $secret['comment'];
  $sdisabled = $secret['disabled'];
  $status = $secret['mikhmon-status'];

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

  if ($status == "expired") {
    $badge = "<span class='text-danger'><i class='fa fa-circle'></i> " . $_st_expired . "</span>";
  } elseif ($status == "waiting") {
    $badge = "<span class='text-orange'><i class='fa fa-circle'></i> " . $_st_waiting . "</span>";
  } elseif ($status == "disabled") {
    $badge = "<span class='text-orange'><i class='fa fa-lock'></i> " . $_st_disabled . "</span>";
  } else {
    $badge = "<span class='text-green'><i class='fa fa-circle'></i> " . $_st_active . "</span>";
  }
  if (isset($onlineusers[$sname])) {
    $badge = $badge . " <span class='text-green' title='Online " . $onlineusers[$sname] . "'><i class='fa fa-plug'></i></span>";
  }

  echo "<tr>";
  ?>
  <td style='text-align:center;'><i class='fa fa-minus-square text-danger pointer' onclick="if(confirm('<?= $_confirm_remove_client ?> (<?= $sname; ?>) ?')){loadpage('./?remove-pppsecret=<?= $sid; ?>&session=<?= $session; ?>')}else{}" title='<?= $_remove ?> <?= $sname; ?>'></i>&nbsp;&nbsp;
  <?php
  if ($sdisabled == "true") {
    $uriprocess = "'./?enable-pppsecret=" . $sid . "&session=" . $session . "'";
    echo '<span class="text-warning pointer" title="' . $_enable . ' ' . $sname . '" onclick="loadpage(' . $uriprocess . ')"><i class="fa fa-lock"></i></span>&nbsp;&nbsp;';
  } else {
    $uriprocess = "'./?disable-pppsecret=" . $sid . "&session=" . $session . "'";
    echo '<span class="pointer" title="' . $_disable . ' ' . $sname . '" onclick="loadpage(' . $uriprocess . ')"><i class="fa fa-unlock"></i></span>&nbsp;&nbsp;';
  }
  $urirenew = "'./?renew-pppsecret=" . $sid . "&session=" . $session . "'";
  echo '<span class="text-primary pointer" title="' . $_renew . ' ' . $sname . '" onclick="if(confirm(\'' . $_confirm_renew . ' (' . $sname . ') ?\')){loadpage(' . $urirenew . ')}else{}"><i class="fa fa-refresh"></i></span>';
  echo "</td>";
  echo "<td><a title='" . $_edit . " " . $sname . "' href='./?secret=" . $sid . "&session=" . $session . "'><i class='fa fa-edit'></i> " . $sname . "</a></td>";
  echo "<td><a title='" . $_profile . " " . $sprofile . "' href='./?ppp=secrets&pprofile=" . $sprofile . "&session=" . $session . "'>" . $sprofile . "</a></td>";
  echo "<td>" . $sservice . "</td>";
  echo "<td>" . $scallerid . "</td>";
  echo "<td>" . $sremote . "</td>";
  echo "<td>" . $badge . "</td>";
  echo "<td>" . $sexpired . "</td>";
  echo "<td>" . $snote . "</td>";
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
