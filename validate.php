<?php
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$arBG = array();

$error = "";
$uname = $_GET['uname'];
$mpw = $_GET['mkey'];
$fragpword = substr($mpw, 0, 32);
$fraguid = substr($mpw, 32)==""?0:substr($mpw, 32);


$sql = "select * from tblUsers where username = \"$uname\" and password = \"$fragpword\" and user_id = $fraguid;";

$res = $conn->query($sql);
$row = mysqli_fetch_array($res);
if ($res->num_rows == 0) $error = "Sorry, that registration link is invalid.<br> Remember my disclaimer about banning you for abusing the system? I'll just be checking for more shenanigans from ".$_SERVER['REMOTE_ADDR']." in the near future.  Thanks.";
elseif ($row['active'] == "1") $error = "This account is already active. Please return to the main page log in.";
else {
  $sql = "update tblUsers set active = 1 where username = \"$uname\" and password = \"$fragpword\" and user_id = $fraguid;";
  $conn->query($sql);
  $error = "Thank you for your validation. You can now return to the main page an log in.";
  }
//and redirect
//header("Location: index.php");
?>
<html>
<head>
</head>
<body>

<?=$error?><br>
<br>
<input type="button" onclick="window.location='http://solomonder.com/scoldzap'" value="Return to Main Page">
</body>
</html>
