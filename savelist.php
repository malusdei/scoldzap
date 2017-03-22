<?php
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");

$uid = $_COOKIE["uid"];
$lname = $conn->real_escape_string($_POST["lname"]);
$lip = $_SERVER['REMOTE_ADDR'];
$ljson = $_POST["ljson"];
$lcs = md5($ljson);

$ljson = $conn->real_escape_string($ljson);

$sql = "insert into tblLists values (NULL, $uid, '$lname', now(), '$lip', '$lcs', '$ljson')";
$res = $conn->query($sql);

header("Location: index.php");
?>


