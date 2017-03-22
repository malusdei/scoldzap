<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$arBG = array();

$s = $_GET['s'];
$f = $_GET['f'];

$sql = "select distinct bgl.bg_id, bg.rtype_id, bg.bgtype_id, bg.bg_name, bgl.cat_id, bgmin, bgmax
from tblBattleGroupLimits bgl
inner join tblBattleGroups bg
using (bg_id)
where bgl.faction_id = $f and bgl.size_id = $s
order by bgtype_id, cat_id;
";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  if ($row['cat_id'] == 0) {
    $arBG[$row['bgtype_id']] = array();
    // print $row['cat_id']." is cat_id.\n";
    }
  //$arBG[$row['bg_id']]['limits'] = array();
  $arBG[$row['bgtype_id']]['limits'][$row['cat_id']] = array("bgmin" => $row['bgmin'], "bgmax" => $row['bgmax']);
  $arBG[$row['bgtype_id']]['rtype_id'] = $row['rtype_id'];
  $arBG[$row['bgtype_id']]['bg_name'] = $row['bg_name'];
  $arBG[$row['bgtype_id']]['bg_id'] = $row['bg_id'];
  // print $row['bg_id'].", ".$row['cat_id'].", ".$row['bg_min'].", ".$row['bg_max']."\n";
  }
/*
foreach ($arBG as $a => $i) {
  print "\t$a\n";
  for ($i as $b => $j) {
    print "\t\t$b\n";
    for ($j as $c => $k) {
      print "\t\t\t$c\n";
      }
    }
  }
*/
$output = json_encode($arBG);
print($output);
?>

