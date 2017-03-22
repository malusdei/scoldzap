<?php
// Get vars
//   s size
//   f faction
header('Content-type: application/json');
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");

$l = $_GET['l'];

$sql = "select u.username, l.list_name, l.list_json from tblLists l inner join tblUsers u on l.user_id = u.user_id where l.list_id = $l";
$res = $conn->query($sql);
$row = mysqli_fetch_array($res);
$output = $row['list_json'];
print($output);
?>

