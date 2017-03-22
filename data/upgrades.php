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
$sqlUpgrades = "SELECT distinct up.attach_id, up.upgrade_name, up.upgrade_points, up.upgrade_alt, replace_main_weapon
FROM tblUpgrades up
INNER JOIN tblUnits un
ON up.attach_id = un.unit_id
WHERE un.faction_id = $fac and commander_id = $com
order by attach_id;";
$resUpgrades = $conn->query($sqlUpgrades);
while ($rowUpgrades = mysqli_fetch_array($resUpgrades)) {
  $objRost[$rowUpgrades['attach_id']] = array("name" => $rowUpgrades['upgrade_name'], "points" =>$rowUpgrades['upgrade_points'], "exclusive" => $rowUpgrades['upgrade_alt'], "replace_mw" => $rowUpgrades['replace_main_weapon']);
  }

$output = json_encode($objRost);
print($output);
?>
