<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$aRost = array();

$f = $_GET['f'];

$sql = "select distinct famous_id, famous_name, pts, cv, radius from tblFamousCommanders where faction_id = $f or famous_id = 1 order by famous_id";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  $aRost[$row['famous_id']] = array(name=>$row['famous_name'],points=>$row['pts'],cv=>$row['cv'],radius=>$row['radius']);
  }
$output = json_encode($aRost);
print($output);
?>

