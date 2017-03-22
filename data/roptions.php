<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$objRost = array();
$arrCat = array();

//$cat = preg_replace("/[^0-9,.]/", "", $_GET['cat']);
$fac = preg_replace("/[^0-9,.]/", "", $_GET['fac']);
$com = preg_replace("/[^0-9,.]/", "", $_GET['com']);
//$pts = preg_replace("/[^0-9,.]/", "", $_GET['pts']);
//$pid = preg_replace("/[^0-9,.]/", "", $_GET['pid']);

// Note that 0 points might be an auto-include (Commander Vehicle), or alt-mode (Transition), or dependent (Starsprite)
for ($c = 1; $c <= 9; $c++) {
	$sqlUnits = "SELECT distinct unit_id, unit_name, pts, type, mass, squad_inc, squad_min, squad_max, parent_id, famous_incl, default_show
	FROM tblUnits
	WHERE cat_id = $c and faction_id = $fac and commander_id = $com
	and parent_id = 0
	order by unit_id;";
  $objRost = array();
	$res = $conn->query($sqlUnits);
	while ($row = mysqli_fetch_array($res)) {
      $objRost[$row['unit_id']] = array("name" => $row['unit_name'], "points" =>$row['pts'], "type" => $row['type'], "mass" => $row['mass'], "inc" => $row['squad_inc'], "min" => $row['squad_min'], "max" => $row['squad_max'], "faminc" => $row['famous_incl'], "show" => $row['default_show'], "transports" => array());
      //Transports
      $sqlTrans = "select trans_id, per_carry, trans_type from tblTransports where unit_id = " . $row['unit_id'] . " order by trans_id, per_carry";
      $resTrans = $conn->query($sqlTrans);
      $objRost[$row['unit_id']]["transports"] = array();
      if ($resTrans->num_rows > 0) {
        while ($rowTrans = mysqli_fetch_array($resTrans)) {
          $objRost[$row['unit_id']]["transports"][] = array('carry' => $rowTrans["per_carry"], 'trans_id' => $rowTrans["trans_id"], 'trans_type' => $rowTrans["trans_type"]);
          }
        }
      // Shares
      $sqlShare = "select share_id, share_total, max_squads from tblShare where unit_id = " . $row['unit_id'] . " order by share_id, share_total";
      $resShare = $conn->query($sqlShare);
      $objRost[$row['unit_id']]["share"] = array();
      if ($resShare->num_rows > 0) {
        while ($rowShare = mysqli_fetch_array($resShare)) {
          $objRost[$row['unit_id']]["share"][$rowShare["share_id"]] = array('share_total' => $rowShare["share_total"], 'max_squads' => $rowShare["max_squads"]);
          }
        }
      // Upgrades
      $sqlUpgrades = "SELECT distinct up.upgrade_id, up.attach_id, up.upgrade_name, up.upgrade_points, up.upgrade_alt, replace_main_weapon
                      FROM tblUpgrades up
                      INNER JOIN tblUnits un
                      ON up.attach_id = un.unit_id
                      WHERE un.faction_id = $fac and commander_id = $com and un.unit_id = ".$row['unit_id']."
                      order by upgrade_points;";
      $resUpgrades = $conn->query($sqlUpgrades);
      $objRost[$row['unit_id']]["upgrades"] = array();
      if ($resUpgrades->num_rows > 0) {
        while ($rowUpgrades = mysqli_fetch_array($resUpgrades)) {
          $objRost[$row['unit_id']]["upgrades"][$rowUpgrades["upgrade_id"]] = array('name' => $rowUpgrades["upgrade_name"], 'points' => $rowUpgrades["upgrade_points"], 'alt' => $rowUpgrades["upgrade_alt"], 'replace_mw' => $rowUpgrades["replace_main_weapon"]);
          }
        }
      }
  $arrCat[$c] = $objRost;
  }

$output = json_encode($arrCat);
print($output);
?>
