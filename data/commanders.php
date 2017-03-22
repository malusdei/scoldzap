<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$aRost = array();

$f = $_GET['f'];
$s = $_GET['s'];

$sql = "select distinct cm_id, cm_name, points, cv, radius from tblCommanders where faction_id = $f and size_id = $s order by cm_id";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  $aRost[$row['cm_id']] = array('name'=>$row['cm_name'],'points'=>$row['points'],'cv'=>$row['cv'],'radius'=>$row['radius']);
  }
$output = json_encode($aRost);
print($output);
?>

