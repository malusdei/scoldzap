<html>
<head>
</head>

<body>
<script src="js/jquery-1.11.0.min.js">
</script>

<script>
bgroup = [];
bgroup[13] = "HQ";
bgroup[15] = "Infantry";

msg = "";
//$.each(bgroup, function(bg_id, a) {
for (var keys in bgroup) {
  msg += "Key " + keys + ": " + bgroup[keys] + "\n";
  alert(msg);
  }
//  });


//alert(bg[14]);
</script>

</body>

</html>
