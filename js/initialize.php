<?php
header('Content-Type: application/javascript');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$optFactions = "";
$optSizes = "";
$sql = "select size_id, size_name from tblSizes";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  ?>
  arrSizes[<?=$row['size_id']?>] = "<?=$row['size_name']?>";
  <?
  }
$sql = "select faction_id, faction_name from tblFactions";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  ?>
  arrFactions[<?=$row['faction_id']?>] = "<?=$row['faction_name']?>";
  <?
  }
$sql = "select rtype_id, rtype_name, rtype_color from tblRosterTypes";
$res = $conn->query($sql);
$num = mysqli_num_rows($res);
echo "arrRosterTypes[0] = new Object();\n";
echo "arrRosterTypes[0].name = \"Total Allowance\";\n";
echo "arrRosterTypes[0].color = \"CCCCCC\";\n";

while ($row = mysqli_fetch_array($res)) {
  ?>
  arrRosterTypes[<?=$row['rtype_id']?>] = new Object();
  arrRosterTypes[<?=$row['rtype_id']?>].name = "<?=$row['rtype_name']?>";
  arrRosterTypes[<?=$row['rtype_id']?>].color = "<?=$row['rtype_color']?>";
  <?
  }
$sql = "select bgtype_id, bgtype_name from tblBGTypes";
$res = $conn->query($sql);
$num = mysqli_num_rows($res);
while ($row = mysqli_fetch_array($res)) {
  ?>
  arrBGTypes[<?=$row['bgtype_id']?>] = "<?=$row['bgtype_name']?>";
  <?
  }

$sql = "select cat_id, cat_name from tblCategories";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  ?>
  objSquadCats[<?=$row['cat_id']?>] = "<?=$row['cat_name']?>";
  <?
  }
  ?>

