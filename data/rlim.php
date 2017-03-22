<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$aRost = array();

$s = $_GET['s'];
$f = $_GET['f'];
$c = $_GET['c'];
$t = $_GET['t'];
// So far, only for Cato
if ($c != "3") $c = "1";

$sql = "select distinct rl.rtype_id, rl.allmin, rl.allmax, 
group_concat(case when bg.bgtype_id is null then 0 else bg.bgtype_id end) as bgtype_id,
group_concat(case when bg.bg_name is null then 'Total Allowance' else bg.bg_name end) as bg_name
from tblRosterLimits rl
left join tblBattleGroups bg
on rl.faction_id = bg.faction_id and rl.rtype_id = bg.rtype_id
where rl.faction_id = $f and rl.size_id = $s and rl.famous_id = $c and rl.army_roster = '$t'
group by rl.rtype_id
order by rl.rtype_id";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  $aRost[$row['rtype_id']] = array("allmin" => $row['allmin'], "allmax" =>$row['allmax'], "bgtype_id" => $row['bgtype_id'], "bg_name" => $row['bg_name']);
  }
$output = json_encode($aRost);
print($output);
?>
