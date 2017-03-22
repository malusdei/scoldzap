<?php
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$arBG = array();

$error = "";

$sql = "select * from tblUsers where username = \"" . $_POST["uname"] . "\";";

$res = $conn->query($sql);
if ($res->num_rows > 0) $error .= "Sorry, that user name already exists.<br>";
if ($_POST["Submit"] == "Submit") {
  if ($_POST["pword1"] != $_POST["pword2"]) $error .= "Passwords do not match.<br>";
  else $pword = $conn->real_escape_string($_POST["pword1"]);
  if ($_POST["fname"] == "") $error .= "First Name is mandatory.<br>";
  else $fname = $conn->real_escape_string($_POST["fname"]);
  if ($_POST["lname"] == "") $error .= "Last Name is mandatory.<br>";
  else $lname = $conn->real_escape_string($_POST["lname"]);
  if ($_POST["uname"] == "") $error .= "You must pick a user name.<br>";
  else $uname = $conn->real_escape_string($_POST["uname"]);
  if ($_POST["email"] == "") $error .= "Email is mandatory.<br>";
  else $email = $conn->real_escape_string($_POST["email"]);
  if ($_POST["country"] == "") $error .= "Country Name is mandatory.<br>";
  else $country = $conn->real_escape_string($_POST["country"]);
  if ($_POST["state"] == "") $error .= "State is mandatory.<br>";
  else $state = $conn->real_escape_string($_POST["state"]);
  $hawkid = $conn->real_escape_string($_POST["hid"]);
  if ($error == "") {
    // Insert new user
    $sqlUser = "insert into tblUsers (username, password, fname, lname, email, hawkid, country, state, joined, ip)
                values ('$uname', md5('$pword'), '$fname', '$lname', '$email', '$hawkid', '$country', '$state', now(), '".$_SERVER['REMOTE_ADDR']."');";
    $conn->query($sqlUser);
    setcookie("uname", $uname);
    setcookie("h", md5($pword));
    //and redirect
    header("Location: index.php");
    }
  }
?>
<html>
<head>
</head>
<body>

<?php
echo $error;
?>
<br><br>
<form method="POST" action="#">

<table border="0" padding="1">
<tr>
<td>First Name:</td>
<td><input type="text" name="fname" value="<?=$_POST['fname']?>"></td>
</tr>

<tr>
<td>Last Name:</td>
<td><input type="text" name="lname" value="<?=$_POST['lname']?>"></td>
</tr>

<tr>
<td>User Name:</td>
<td><input type="text" name="uname" value="<?=$_POST['uname']?>"></td>
</tr>

<tr>
<td>Password:</td>
<td><input type="password" name="pword1" value="<?=$_POST['pword1']?>"></td>
</tr>

<tr>
<td>Password (repeat):</td>
<td><input type="password" name="pword2" value="<?=$_POST['pword2']?>"></td>
</tr>

<tr>
<td>Email:</td>
<td><input type="text" name="email" value="<?=$_POST['email']?>"></td>
</tr>

<tr>
<td>Country:</td>
<td><input type="text" name="country" value="<?=$_POST['country']?>"></td>
</tr>

<tr>
<td>State/Province:</td>
<td><input type="text" name="state" value="<?=$_POST['state']?>"></td>
</tr>

<tr>
<td>Hawk Forums ID (optional):</td>
<td><input type="text" name="hid" value="<?=$_POST['hid']?>"></td>
</tr>

<tr>
<td>&nbsp;</td>
<td><input type="submit" name="Submit" value="Submit"></td>
</tr>


</table>
</form>

<h2>Terms:</h2>
Why register? So you can save army lists!<br>
(Keep in mind this feature is *very* beta)<br>
However, by saving army lists, you are using more of my server's resources. If you somehow abuse this system, perhaps by creating really long titles like "Make Money At Home", I will deactivate your account. I will prevent you from creating any other accounts with what ever identifying information (IP address, email, etc.) I have about you. I'm glad we're clear on this point.<br>
I reserve the right to deactivate your account at any time, for any reason. <br>
You do not have to be an active registered user to build your own lists, or look at other people's army lists.  You only need to be active and logged in if you want to save your own lists.<br>
I have to approve your registration, which I'll do after I verify your email is legitimate. This is not an automated process, and I'll be checking periodically for new registrations.  If you think a registration is taking a long time to approve, you can send me a private message - I'm Tenebris on the Hawk Forums.<br>
One account per an email, please.  If I start noticing too many pending registrations coming from the same email, or too many from the same IP, I might decide to never approve you.  There's no hard number, but I'd say 2000 registrations from the same location in 5 seconds is a good example.<br>
Hawk's rules are always changing, thus the format of how I save is always changing. I reserve the right to destroy any or all lists at any given point without warning.  I will still endeavor to make saved lists compatible between one version and the next, but if I cannot reconcile the different formats, I will blow everything away and start all over.  I will also try to give fair warning, within a week in advance, so you can copy lists out to another medium, but I'm just covering my ass when I say I can do it without notification.<br>
Also, keep in mind this machine is a free-tier Amazon instance, so it can die at any time, and I might lose all data on it between now and the last backup. And I take backups probably every other expansion book release.<br>



</body>
