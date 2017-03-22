<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
//$aMeta = Array();

$l = $_GET['l'];

$sql = "select u.username, l.list_name from tblLists l inner join tblUsers u on l.user_id = u.user_id where l.list_id = $l";
$res = $conn->query($sql);
//while ($row = mysqli_fetch_array($res)) {
//  $aRost[$row['famous_id']] = array(name=>$row['famous_name'],points=>$row['pts'],cv=>$row['cv'],radius=>$row['radius']);
//  }

$row = mysqli_fetch_array($res);
$output = json_encode($row);;
print($output);
?>

