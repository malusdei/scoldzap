<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$objRost = array();
$arrCat = array();
$strWalkerSquadronIds = "143,147,172,176,197,397,402,590,591,592";

//$cat = preg_replace("/[^0-9,.]/", "", $_GET['cat']);
//$fac = preg_replace("/[^0-9,.]/", "", $_GET['fac']);
$com = preg_replace("/[^0-9,.]/", "", $_GET['com']);
//$pts = preg_replace("/[^0-9,.]/", "", $_GET['pts']);
//$pid = preg_replace("/[^0-9,.]/", "", $_GET['pid']);

// Note that 0 points might be an auto-include (Commander Vehicle), or alt-mode (Transition), or dependent (Starsprite)
$sqlSquads = "select unit_id from tblUnits where unit_id in ($strWalkerSquadronIds) and commander_id = $com";
$resSquads = $conn->query($sqlSquads);
while ($rowSquads = mysqli_fetch_array($resSquads)) {
//foreach (array(3,4,6) as $c) {
  $c = $rowSquads['unit_id'];
	$sqlUnits = "SELECT distinct unit_id, unit_name, pts, type, mass, squad_inc, squad_min, squad_max, famous_incl, default_show
	FROM tblUnits
	WHERE parent_id = $c
	and pts > 0
	order by unit_name desc;";
  $objRost = array();
	$res = $conn->query($sqlUnits);
	while ($row = mysqli_fetch_array($res)) {
    $objRost[$row['unit_id']] = array("name" => $row['unit_name'], "points" =>$row['pts'], "type" => $row['type'], "mass" => $row['mass'], "inc" => $row['squad_inc'], "min" => $row['squad_min'], "max" => $row['squad_max'], "faminc" => $row['famous_incl'], "show" => $row['default_show'], "transports" => array());
    }
  $arrCat[$c] = $objRost;
  }

$output = json_encode($arrCat);
print($output);
?>
