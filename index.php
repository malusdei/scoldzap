<?php
$conn=mysqli_connect("localhost","dzc","dropzone","scoldzap");
$optFactions = "<option value=\"0\" selected=\"selected\">[Select Faction]</option>";
$optSizes = "<option value=\"0\" selected=\"selected\">[Select Size]</option>";
$sql = "select size_id, size_name from tblSizes";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  $optSizes .= "<option value=\"".$row['size_id']."\">".$row['size_name']."</option>\n";
  }
$sql = "select faction_id, faction_name from tblFactions";
$res = $conn->query($sql);
while ($row = mysqli_fetch_array($res)) {
  $optFactions .= "<option value=\"".$row['faction_id']."\">".$row['faction_name']."</option>\n";
  }


//if ($_SERVER['REQUEST_METHOD'] == 'POST')
if ($_POST["Submit"] == "Login") {
  $uname = $conn->real_escape_string($_POST["uname"]);
  $pword = md5($_POST["pword"]);
  $sql = "select user_id, username, password from tblUsers where username = '$uname' and password = '$pword' and active != 0;";
  $res = $conn->query($sql);
  if ($res->num_rows == 0) {
    $loginError .= "Sorry, incorrect user/password combination or account is inactive.<br>";
    $uname = "";
    unset($_COOKIE["uname"]);
    unset($_COOKIE["h"]);
    unset($_COOKIE["uid"]);
    }
  else {
    $row = mysqli_fetch_array($res);
    $uname = $row["username"];
    setcookie("uname", $uname);
    setcookie("h", $pword);
    setcookie("uid", $row["user_id"]);
    $loginError = "";

    // Get lists if you're in
    $sqlLists = "select list_id, list_name, list_date from tblLists where user_id = " . $row["user_id"];
    $resLists = $conn->query($sqlLists);
    }
  }
else {
  // Still verify cookie combo is correct.
  $uname = $_COOKIE["uname"];
  $pword = $_COOKIE["h"];
  $sql = "select user_id, username, password from tblUsers where username = '$uname' and password = '$pword' and active != 0;";
  $res = $conn->query($sql);
  //$loginError = $sql;
  if ($res->num_rows == 0) {
    $uname = "";
    unset($_COOKIE["uname"]);
    unset($_COOKIE["h"]);
    unset($_COOKIE["uid"]);
    }
  else {
    $sqlLists = "select list_id, list_name, list_date from tblLists where user_id = " . $_COOKIE["uid"];
    $resLists = $conn->query($sqlLists);
    }
  }

?>
<html>
<head>
<!-- old version js/jquery-1.11.0.min.js -->
<script src="http://code.jquery.com/jquery-1.11.0.min.js">
</script>
<style>
table.tblHQ th {
  background-color: #CC99FF
  }

table.tblArmor th{
  background-color: #FFCC00
  }

table.tblInfantry th {
  background-color: #669966
  }

table.tblHeavy th{
  background-color: #FFFF99
  }

table.tblScout th {
  background-color: #FFFF99
  }

table.tblFleet th {
  background-color: #99CCFF
  }

table.tblDrills th {
  background-color: #BB9090
  }

table.tblGates th {
  background-color: #FF4040
  }

table.tblUnit td {
  white-space: nowrap;
  valign: top;
  }

select.selTop {
  width: 170px;
  }

select.selWalkerUnit {
  width: 210px;
  }

button.btnSquadAdd {
  display:inline-block;
  width: 70px;
  }

button.btnBGAdd {
  display:inline-block;
  width: 240px;
  }


</style>
<title>
Solomon Chang's OnLine DropZone Army Planner
</title>
<script>
var faction;
var arrFactions = [];
var arrSizes = [];
var arrBGTypes = [];
var arrRosterTypes = [];
var arrRosterLimits = [];
var arrBGLimits = [];
// Squad type by cat_id
var objSquadCats = new Object;
// Squads by unit_id
var objSquadUnits = new Object();
var objPHRWalkerUnits = new Object(); //exception for PHR walker squads
// Squad units by cat_id
var objSquadOpts = new Object;
var objPHRWalkerOpts = new Object; //exception for PHR walker squads
var objBGLimits = new Object;
var objTransNum = new Object;  // transports by number
var objCommanders = new Object;
var objFamousCommanders = new Object;
var arrWalkerSquadronIds = ["143","147","172","176","197","397","402","590","591","592"];
var objLoad = new Object;
var glUnitId = 0;
var glButtonId = 0;
var maxdivs = 3;
var faction_id;
var size_id;
var fc_id = "1";
var flagFamCom = 0;
var kpTotal = 0;
var printMode = 0;
var uname = "<?=$uname?>";
var getList = "<?=(isset($_GET['list']) && is_numeric($_GET['list']))?$_GET['list']:0?>";
if (uname == "") $("#frmSaveList :input").prop("disabled", true);

</script>

<script src="js/initialize.php">
</script>

<script>
var updateTotalPoints = function() {
  //Certain Ajkax object must be loaded, or else abort
  //if ($.isEmptyObject(objCommanders) == true) return;
  var t = 0;

  $(".thPoints").each(function() {
    t += parseInt($(this).html());
    });
  //t += parseInt(objFamousCommanders[$("#selFamComm").val()].points);
  $(".selCommander").each(function() {
    if ($(this).val() != "0") t += parseInt(objCommanders[$(this).val()].points);
    });
  if (kpTotal > 0) {
    t = kpTotal - t;
    }
  $("#divPointTotal").html(String(t) + " Points");
  $("#divPointTotal").data("points", t);
  errorCheck();
  }

var updateBGPoints = function(divBattleGroup) {
  var t = 0;
  divBattleGroup.find(".thSquad").each(function() {
    t += parseInt($(this).data("points"));
    });
  divBattleGroup.find(".thPoints").html(t);
  updateTotalPoints();
  }

var updateSpecialTransportPoints = function(tblUnit) {
  // An unfortunate exception to Drills and Gates
  // All we're going to do is count the multiples.
  var base_cost = parseInt(tblUnit.find(".selSpecTrans").val());
  var q = parseInt(tblUnit.find(".selSpecTransNumber").val());
  var thSquad = tblUnit.find(".thSquad");
  var sumTotal = base_cost * q;

  //thSquad.data("points", sumTotal);
  //thSquad.html(String(sumTotal) + " pts");
  //alert(thSquad.data("points"));
  //alert($(this).closest(".divBattleGroup").attr("id"));
  updateBGPoints(tblUnit.closest(".divBattleGroup"));
  }

var updateSquadPoints = function(tblUnit) {
  var unit_id = tblUnit.find(".selUnit").val();
  var squad = objSquadUnits[unit_id];
  var thSquad = tblUnit.find(".thSquad");
  var transport;
  var p = parseInt(squad.points);
  var q = thSquad.data("count");
  var chkShare;
  var base_cost;
  var divUnitTransports = tblUnit.find(".divUnitTransports");
  var selUnitTrans = divUnitTransports.find(".selUnitTrans");

  var sumTotal = 0;

  if (tblUnit.find(".divUnitSel").is(":visible") == false || unit_id == "0") {
    // But if for some reason the unit is empty, consider this 0 points.
    // Set unit value to 0, clear the other divs, then return
    thSquad.data("points", 0);
    thSquad.html(String(0) + " pts");
    updateBGPoints(thSquad.closest(".divBattleGroup"));
    return;
    }
  if (tblUnit.find(".divUnitNumber").is(":visible") == false) {
      // Check to see if divUnitSel contains a unit that only 1 per squad.
      sumTotal = parseInt(squad.points);
      }
  else {
      // Use selUnitNumber multiple instead
      sumTotal = q*p;
      }

  $.each(tblUnit.find(".divUnitUpgrades").find(".chkUpgrade:checked"), function() {
    sumTotal += parseInt($(this).data("points"));
    });

  $.each(tblUnit.find(".radUpgrade:checked"), function() {
    sumTotal += parseInt($(this).val());
    });

  $.each(tblUnit.find(".divUnitTransportUpgrades").find(".chkUpgrade:checked"), function() {
    //sumTotal += parseInt($(this).data("points")) * parseInt($("option:selected", selUnitTrans).data("transcount"));
    sumTotal += parseInt($(this).data("points"));
    });

  $.each(tblUnit.find(".divUnitShareUpgrades").find(".chkShareUpgrade"), function() {
    if ($(this).prop("checked")) sumTotal += parseInt($(this).data("points"));
    });

  if (tblUnit.find(".divPHRSpecific").is(":visible") != false) {
    $.each(tblUnit.find(".selWalkerUnit"), function() {
      var walker_id = $(this).data("unit_id");
      sumTotal += parseInt(objSquadUnits[walker_id].points) * parseInt($(this).val());
      })
    }

  if (divUnitTransports.is(":visible") != false && divUnitTransports.html() != "") {
    transport = objSquadUnits[selUnitTrans.val()];
    if (selUnitTrans.val() != "0")
      sumTotal += parseInt(transport.points) * parseInt($("option:selected", selUnitTrans).data("transcount"));
    }

  if (tblUnit.find(".divUnitTransportUpgrades").is(":visible") != false) {
    }

  if (tblUnit.find(".divUnitShare").is(":visible") != false) {
    chkShare = tblUnit.find(".chkShare");
    if (chkShare.prop("checked")) sumTotal += parseInt(chkShare.data("points"));
    }

  if (tblUnit.find(".divUnitShareUpgrades").is(":visible") != false) {
    }

  thSquad.data("points", sumTotal);
  thSquad.html(String(sumTotal) + " pts");
  updateBGPoints(thSquad.closest(".divBattleGroup"));
  }

var errorCheck = function() {
  var visCount = 0;
  var squadMax = 0;
  var maxBGShare;
  var msg = "Errors: <br><ul>";
  var sumTotal = parseInt($("#divPointTotal").data("points"));
  var pointLimit = parseInt($("#txtPoints").val());
  var fract = "";
  var shaltMass = 0;
  var gateMass = 0;
  var gateVal = 0;
  var flagGunnar = 0;

  $.each($(".divBattleGroup"), function() {
    var divBattleGroup = $(this);
    visCount = 0;
    $.each(divBattleGroup.find(".tblUnit"), function() {
      if ($(this).is(":visible") == true) visCount += 1;
      });
    squadMax = parseInt(divBattleGroup.find(".thSquadMax").data("max"));
    if (visCount > squadMax) {
      divBattleGroup.find(".thSquadMax").css("color", "red");
      msg += "<li>One or more Battle Groups exceeds squad size.</li>";
      }
    else divBattleGroup.find(".thSquadMax").css("color", "black");
    });

  if (sumTotal > pointLimit) {
    msg += "<li>Army point total exceeded by " + (sumTotal - pointLimit) + " points.</li>";
    $("#divPointTotal").css("color", "red");
    }
    else $("#divPointTotal").css("color", "black");

  if (size_id == 1) {
    maxBGShare = pointLimit / 2;
    fract = " one half ";
    }
  else {
    maxBGShare = pointLimit / 3;
    fract = " one third ";
    }

  $.each($(".divBattleGroup"), function() {
    if ($(this).attr("id") == "divGates" || $(this).attr("id") == "divDrills") return;
    if (parseInt($(this).find($(".thPoints")).html()) > maxBGShare) {
      msg += "<li>" + $(this).find($(".thBGNameIdx")).html() + " may not exceed" + fract + "of the total points.</li>";
      $(this).find($(".thBGNameIdx")).css("color", "red");
      }
    else $(this).find($(".thBGNameIdx")).css("color", "black");
    })

  $.each($(".tblUnit:visible"), function() {
    if ($(this).find(".btnSquadRemove").is(":visible") == false) {
      if ($(this).find(".selUnit").val() == "0") {
        msg += "<li>" + $(this).closest(".divBattleGroup").find(".thBGNameIdx").html() + ": " + $(this).find(".thSquadName").html() + " is a mandatory selection.</li>";
        $(this).find(".thSquadName").css("color", "red");
        }
        else $(this).find(".thSquadName").css("color", "black");
      }
    })

  if (faction_id == 4) {
    $.each($(".selUnit"), function(){
      var q = 1;
      if ($(this).closest(".tblUnit").is(":visible") == false || $(this).val() == "0") return;
      if (typeof($(this).closest(".tblUnit").find(".selUnitNumber").val()) != "undefined") q = $(this).closest(".tblUnit").find(".selUnitNumber").val();
      shaltMass += parseInt(objSquadUnits[$(this).val()].mass) * q;
      })
    shaltMass += parseInt($("#tblUnit_Haven").find(".selSpecTransNumber").val()) * 1.5;
    $.each($("#trGates").find(".tblUnit"), function(){
      switch ($(this).attr("id").substring(8)) {
        case "Gaia":
          gateVal = 9;
          break;
        case "Eden":
          gateVal = 3;
          break;
        case "Spirit":
        case "Haven":
          gateVal = 2;
          break;
        }
      gateMass += parseInt($(this).find(".selSpecTransNumber").val()) * gateVal;
      })
    if (gateMass > shaltMass) {
      msg += "<li>Gate Capacity ("+gateMass+") shall not exceed total Shaltari mass ("+shaltMass+"). </li>";
      $("#thGates").css("color", "yellow");
      }
      else {
        $("#thGates").css("color", "black");
        }
    }

  if ($("#selFamComm").val() == "13") {
    for (var i = 1; i <= 3; i++) {
      if ($("#selCommander" + String(i)).val() != "0" && parseInt(objCommanders[$("#selCommander" + String(i)).val()].cv) > 2) {
        flagGunnar = 0;
        break;
        }
      else flagGunnar = 1;
      }
    if (flagGunnar == 1)
      msg += "<li>Famous Commander Gunnar must have higher level commander included.</li>";
    }

  msg += "</ul>";
  $("#dDebug").html(msg);
  }

var buildSquadSelect = function(cat_id) {
  var tagCount = "";
  var tagPoints;
  var intMin;
  var intPoints;
  var opts = "<option value=\"0\">[Select Unit]</option>";
  $.each(objSquadOpts[cat_id], function(unitid, val) {
    intMin = parseInt(val.min);
    intPoints = parseInt(val.points);
    tagPoints = val.points;
    tagCount = "";
    if (intMin > 1) {
      tagCount = " (x" + val.min + ")";
      tagPoints = intMin * intPoints;
      }
    if (val.show == "1")
      opts += "<option value=\"" + unitid + "\">" + val.name + "&nbsp;&nbsp;&nbsp;&nbsp;[" + tagPoints + " points minimum]</option>";
    });
  return "<select class=\"selUnit\">" + opts + "</select>";
  }

var resetBGBar = function(bgtypekey, idx, BGName) {
  // objBGL - for some reason is not global. Passing as param.
  var objBGLim = new Object();
  var ButtonHTML = "";
  var BGButtonLoc = "#thButton"+arrBGTypes[bgtypekey] + idx;
  var BGTitleLoc = "#th"+arrBGTypes[bgtypekey] + idx;
  var BGUnitLoc = "#trUnit"+arrBGTypes[bgtypekey] + idx;
  var BGMaxLoc = "#thMax"+arrBGTypes[bgtypekey] + idx;
  var txtClass = " class=\"btnSquadAdd\"";
  var txtDivs = "<div class=\"divPHRSpecific\" style=\"display: none;\"></div><div class=\"divUnitNumber\" style=\"display: none;\"></div><div class=\"divUnitUpgrades\" style=\"display: none;\"></div><div class=\"divUnitTransports\" data-count=\"0\" style=\"display: none;\"></div><div class=\"divUnitTransportUpgrades\" data-count=\"0\" style=\"display: none;\"></div><div class=\"divUnitShare\" style=\"display: none;\"></div><div class=\"divUnitShareUpgrades\" data-count=\"0\" style=\"display: none;\"></div>";
  var radCommand = "<input type=\"radio\" name=\"btnCommander1\" class=\"btnCommander1\" style=\"display:none\"><input type=\"radio\" name=\"btnCommander2\" class=\"btnCommander2\" style=\"display:none\"><input type=\"radio\" name=\"btnCommander3\" class=\"btnCommander3\" style=\"display:none\">";
  var txtCat;
  var txtIdx;
  var txtBG;
  var txtUnitRow;
  //var BGName = arrRosterButtons[key] + " " + idx;
  $(BGTitleLoc).html(BGName+" "+idx);
  $(BGButtonLoc).empty();
  $(BGUnitLoc).empty();

  $.each (objBGLimits[bgtypekey].limits, function(catkey, val) {
    if (parseInt(catkey) == 0) {
      $(BGMaxLoc).html("Maximum "+val.bgmax);
      $(BGMaxLoc).data("max", val.bgmax);
      $(BGMaxLoc).attr("class", "thSquadMax");
      return;
      }
    txtCat = " data-cat_id=\"" + catkey + "\"";
    txtIdx = " data-idx=\"" + idx + "\"";
    txtBG = " data-bg_id=\"" + bgtypekey + "\"";
    for (var s = 0; s < parseInt(val.bgmax); s++) {
      txtBtnId = " id=\"btnBG_" +glButtonId++ + "\"";
      ButtonHTML += "<button " + txtClass + txtCat + txtIdx + txtBG + txtBtnId + ">"
                  + objSquadCats[catkey] + "</button>";
      txtUnitRow = "<td valign=\"top\" data-cat_id=\"" + catkey + "\" style=\"display: none;\"><table class=\"tblUnit\" data-cat_id=\"" + catkey + "\" width=\"215\" data-shared_with=\"\" id=\"tblUnit_" + glUnitId++ + "\"><tr align=\"right\"><th class=\"thCommand\">"+radCommand+"</th><th class=\"thSquad\" data-points=\"0\" data-count=\"1\">0 pts</th><th class=\"thSquadName\">" + objSquadCats[catkey] + "</th><th><button class=\"btnSquadRemove\" " + txtCat + txtIdx + txtBG + "\">Remove</button></th></tr><tr><td valign=\"top\" colspan=\"3\" height=\"80\"><div class=\"divUnitSel\">" + buildSquadSelect(catkey) + "</div>" + txtDivs + "</td></tr></table></td>";
      $(BGUnitLoc).append(txtUnitRow);
      //glUnitId++;
      }
    $(BGButtonLoc).html(ButtonHTML);
    });
  $(BGButtonLoc).closest(".divBattleGroup").find(".thPoints").html("0");
  updateBGPoints($(BGButtonLoc).closest(".divBattleGroup"));
  }

var retrieveLimits = function(fid, sid, cid) {
  var objUpgrades = new Object;
  objSquadUnits = new Object;
  objSquadOpts = new Object;
  objPHRWalkerUnits = new Object;
  if (parseInt($("#selFaction").val()) == 0 || parseInt($("#selSize").val()) == 0) {
    return;
    }
  //initialize
  glUnitId = 0;
  glButtonId = 0;
  $.each($.find(".selSpecTransNumber"), function() {
    $(this).val("0");
    });
  $.each($.find(".thPoints"), function() {
    $(this).html("0");
    });
  $("#divCommander").html("");
  if (faction_id == 3)
    $.getJSON("data/phroptions.php?com="+$("#selFamComm").val(), function(data) {
      objPHRWalkerOpts = data;
      $.each(objPHRWalkerOpts, function(catkey, oSquad) {
        $.each(oSquad, function(unit_id, oUnit) {
          objPHRWalkerUnits[unit_id] = {name:oUnit.name, points:oUnit.points, mass:oUnit.mass, inc:oUnit.inc, min:oUnit.min, max:oUnit.max, show:oUnit.show, cat_id:catkey, type:oUnit.type, transports:oUnit.transports, share:oUnit.share, upgrades:oUnit.upgrades};
          objSquadUnits[unit_id] = objPHRWalkerUnits[unit_id];
          })
        });
      });
  $.getJSON("data/rlim.php?f="+fid+"&s="+sid+"&c="+cid+"&t="+$("#selRoster").val(), function(data) {
    arrRosterLimits = [];
    $.each (data, function(rtype_id, val) {
      arrRosterLimits.push(val);
      });
    $.getJSON("data/roptions.php?fac="+fid+"&com="+fc_id, function(data) {
      objSquadOpts = data;
      $.getJSON("data/bglim.php?f="+fid+"&s="+sid+"&c="+fc_id, function(data) {
        objBGLimits = data;
        populateLimits();
        errorCheck();
        loadAfterLimits();
        });
      // Build another object to reference by unit_id
      $.each(objSquadOpts, function(catkey, oSquad) {
        $.each(oSquad, function(unit_id, oUnit) {
          objSquadUnits[unit_id] = {name:oUnit.name, points:oUnit.points, mass:oUnit.mass, inc:oUnit.inc, min:oUnit.min, max:oUnit.max, faminc:oUnit.faminc, show:oUnit.show, cat_id:catkey, type:oUnit.type, transports:oUnit.transports, share:oUnit.share, upgrades:oUnit.upgrades};
          })
        });
      });
    });
  $.getJSON("data/commanders.php?f="+fid+"&s="+sid, function(commanders) {
    objCommanders = commanders;
    var i;
    for (i=1;i<=2;i++) {
      // Clear old commanders first
      $("#selCommander" + i).empty().append($("<option></option>").attr("value", "0").text("None"));
      $.each(commanders, function(key, cmd) {
        $("#selCommander" + i).append($("<option></option>").attr("value", key).text("[" + cmd.points + " points]   " + cmd.name));
        });
      }
    });
  }

var populateLimits = function() {
  //Maximum table cells for buttons
  var txtBody = "<table border=\"1\"><tr><th>Battlegroup Type</th><th>Min</th><th>Max</th><th colspan=\"2\">Add</th></tr>";
  var bgtypekey = 1;
  $("#divDrills").hide();
  $("#divGates").hide();
  $("#divHQ1").hide();
  $("#divHQ2").hide();
  $("#divHQ3").hide();
  $("#divArmor1").hide();
  $("#divArmor2").hide();
  $("#divArmor3").hide();
  $("#divInfantry1").hide();
  $("#divInfantry2").hide();
  $("#divInfantry3").hide();
  $("#divHeavy1").hide();
  $("#divHeavy2").hide();
  $("#divHeavy3").hide();
  $("#divScout1").hide();
  $("#divScout2").hide();
  $("#divScout3").hide();
  $("#divFleet1").hide();
  $("#divFleet2").hide();
  $("#divFleet3").hide();

  // Show drills BG if Resistance
  if (faction_id > 4) $("#divDrills").show();
  // Show gates BG if Shaltari
  if (faction_id == 4) $("#divGates").show();

  for (var rtype_id in arrRosterLimits) {
    var i = 2;
    txtBody += "<tr class=\"trRoster\" bgcolor='" + arrRosterTypes[rtype_id].color + "'>";
    txtBody += "<td class=\"tdRosterName\">" + arrRosterTypes[rtype_id].name + "</td>"
        + "<td class=\"tdRosterMin\">" + arrRosterLimits[rtype_id].allmin + "</td>"
        + "<td class=\"tdRosterMax\">"+ arrRosterLimits[rtype_id].allmax + "</td>";
    if (rtype_id > 0) {
      var arrRosterButtons = arrRosterLimits[rtype_id].bg_name.split(",");
      for (var key in arrRosterButtons) {
        txtBody += "<td><button class=\"btnBGAdd\" value=\"" + bgtypekey
                + "\" data-rtype_id=\"" + rtype_id + "\">"
                + arrRosterButtons[key] + "</button></td>";
        for (var m = 1; m <= maxdivs; m++) {
          resetBGBar(bgtypekey, m, arrRosterButtons[key]);
          }
        bgtypekey += 1;
        i = i - 1;
        }
      }
    //Fill table cells
    while (i > 0) {
      txtBody = txtBody + "<td>&nbsp;</td>";
      i = i - 1;
      }
    txtBody = txtBody + "</tr>";
    };
  txtBody = txtBody + "</table>";
  $("#dSummary").html(txtBody);

  // Make mandatory choices appear
  $.each($(".trRoster"), function() {
    var rostMin = parseInt($(this).find(".tdRosterMin").html());
    // If Skirmish and famous commander is set, force command roster.
    if (parseInt($("#selFamComm").val()) > 1 && size_id == "1" && $(this).find(".tdRosterName").html() == "HQ") rostMin = 1;
    var i = 0;
    while (i < rostMin) {
      $(this).find(".btnBGAdd").trigger("click");
      i += 1;
      }
    flagFamCom = 0;
    });
  // Of those, hide the Remove button.
  $(".divBattleGroup").each(function(){
    if ($(this).is(":visible") == true) {
      $(this).find(".btnBGRemove").hide();
      }
    });
  }

var loadBeforeLimits = function() {
  $.getJSON("data/listmeta.php?l="+getList, function(data) {
    objLoadMeta = data;
    $("#divListMeta").html(objLoadMeta["list_name"] + "<br>" + objLoadMeta["username"]);
    });

  $.getJSON("data/listload.php?l="+getList, function(data) {
    objLoad = data;
    //populateLimits();
    //errorCheck();
    fc_id = objLoad['famm'];
    $("#selRoster").val(objLoad['rost']);
    $("#selFaction").val(objLoad['fact']).trigger("change");
    $("#selSize").val(objLoad['size']).trigger("change");
    $("#selPoints").val(objLoad['pts']);
    });
  }

var loadAfterLimits = function() {
  if (getList == "0") return;
    $("#selCommander1").val(objLoad['comm1']).trigger("change");
    $("#selCommander2").val(objLoad['comm2']).trigger("change");
  $.each(objLoad["bgs"], function(bgDivName, bgDivVal) {
    // Iterate each Battlegroup, and set.
    $("#"+bgDivName).show();
    if (bgDivName == "divDrills") {
      }
    else if (bgDivName == "divGates") {
      }
    else {
      if (bgDivVal.mandatory == "1") 
        $("#"+bgDivName).find(".btnBGRemove").hide();
      else
        $("#"+bgDivName).find(".btnBGRemove").show();
      $.each(bgDivVal.units, function(unitKey, unitVal) {
        var thisUnitTbl = "#" + unitVal.unitID;
        $(thisUnitTbl).parent().show();
        if (unitVal.mandatory == "1") 
          $(thisUnitTbl).find(".btnBSquadRemove").hide();
        else
          $(thisUnitTbl).find(".btnSquadRemove").show();
        $(thisUnitTbl).find(".selUnit").val(unitVal.selUnit).trigger("change");
        if ('divPHRSpecific' in unitVal) {
          $(thisUnitTbl).find("#divPHRSpecific").show();
          $.each(unitVal.divPHRSpecific, function(walkerKey, walkerVal) {
            $(thisUnitTbl).find("."+walkerKey).val(walkerVal).trigger("change");
            });
          }
        if ('divUnitNumber' in unitVal) {
          $(thisUnitTbl).find("#divUnitNumber").show();
          $(thisUnitTbl).find(".selUnitNumber").val(unitVal.divUnitNumber.selUnitNumber).trigger("change");
          }
        if ('divUnitUpgrades' in unitVal) {
          $(thisUnitTbl).find("#divUnitUpgrades").show();
          $.each(unitVal.divUnitUpgrades.radios, function(radKey) {
            $("#" + radKey).prop("checked", true).trigger("change");
            });
          $.each(unitVal.divUnitUpgrades.checks, function(chkKey) {
            $("#" + chkKey).prop("checked", true).trigger("change");
            });
          }
        if ('divUnitTransports' in unitVal) {
          $(thisUnitTbl).find("#divUnitTransports").show();
          $(thisUnitTbl).find(".selUnitTrans").val(unitVal.divUnitTransports.selUnitTrans).trigger("change");
          }
        if ('divUnitTransportUpgrades' in unitVal) {
          $(thisUnitTbl).find("#divUnitTransportUpgrades").show();
          $.each(unitVal.divUnitTransportUpgrades.radios, function(radKey) {
            $("#" + radKey).prop("checked", true).trigger("change");
            });
          $.each(unitVal.divUnitTransportUpgrades.checks, function(chkKey) {
            $("#" + chkKey).prop("checked", true).trigger("change");
            });
          }
        //if (divUnitShare in $(this)) {}
        if ('divUnitShareUpgrades' in unitVal) {
          $(thisUnitTbl).find("#divUnitShareUpgrades").show();
          $.each(unitVal.divUnitShareUpgrades.radios, function(radKey) {
            $("#" + radKey).prop("checked", true).trigger("change");
            });
          $.each(unitVal.divUnitShareUpgrades.checks, function(chkKey) {
            $("#" + chkKey).prop("checked", true).trigger("change");
            });
          }

        });
      }
    });
  }

var addShare = function(topTransId, arrShare) {
  // Make divUnitShare visible
  arrShare.sort();
  //arrShare.reverse();
  var topId = arrShare.pop();
  var tblUnit = $("#tblUnit_" + topId);
  var divUnitShare = tblUnit.find(".divUnitShare")
  var arrNames = [];
  var txtUnitShare = "<input type=\"checkbox\" class=\"chkShare\" ";

  $.each(arrShare, function(shareKey, shareVal) {
    arrNames.push(objSquadUnits[$("#tblUnit_" + shareVal).find(".selUnit").val()].name + " squad");
    });
  divUnitShare.show();
  txtUnitShare += "data-unit_id=\"" + topTransId + "\" ";
  txtUnitShare += " data-share_with=\"" + arrShare.join() + "\" ";
  txtUnitShare += "data-points=\"" + objSquadUnits[topTransId].points + "\"> ";
  txtUnitShare += "Share " + objSquadUnits[topTransId].name + " with " + arrNames.join(" and ") + " for " + objSquadUnits[topTransId].points + " points";
  divUnitShare.html(txtUnitShare);
  }

var removeShares = function(element) {
  var tblUnit = element.closest(".tblUnit");
  var txtShares = String(tblUnit.data("shared_with"));
  var arrShares = txtShares.split(",");

  $.each(arrShares, function(shareKey, shareVal) {
    var iUnit = $("#tblUnit_" + shareVal);
    var iDivShare = iUnit.find(".divUnitShare");
    iDivShare.html("");
    iDivShare.hide();
    });
  tblUnit.data("shared_with", "")
  }

var listUpgrades = function(quant, unit_id, divSection) {
  var tblUnit = divSection.closest(".tblUnit");
  var squad = objSquadUnits[unit_id];
  var objUpgrades = new Object;
  var tbl_id =parseInt(tblUnit.attr("id").substring(8));
  var txtCheckUp = "";
  var msg = "";
  var tmpID = 1;

  if (!($.isEmptyObject(squad.upgrades))) {
    divSection.show();
    // re-sort upgrades by alt
    $.each(squad.upgrades, function (up_id, u) {
      if ($.isEmptyObject(objUpgrades[u.alt])) objUpgrades[u.alt] = [];
      objUpgrades[u.alt].push({'up_id':up_id, 'points':u.points, 'name':u.name, 'mw':u.replace_mw});
      });

    $.each(objUpgrades, function (alt, arrUpgrade) {
      //var q = tblUnit.find(".selUnitNumber").val();
      var strUpgrade = tbl_id + "_" + alt + "_";
      var i;
      if (alt == "0") {
        for (i=1;i<=parseInt(quant);i++) {
          txtCheckUp += "<hr>";
          $.each(arrUpgrade, function (k, v) {
            txtID = "chk" + tbl_id + "_" + tmpID;
            tmpID++;
            txtCheckUp += "<input type=\"checkbox\" id=\""+txtID+"\" class=\"chkUpgrade\" value=\""+v.up_id+"\" data-points=\""+v.points+"\">"+v.name+"  ["+v.points+" points]<br>";
            });
          }
        }
      else {
        for (i=1;i<=parseInt(quant);i++) {
          txtCheckUp += "<hr>";
          $.each(arrUpgrade, function(k, v) {
            //Radio button instead
            var upgrade_id = strUpgrade + String(i);
            var txtChecked="";
            txtID = "rad" + tbl_id + "_" + tmpID;
            tmpID++;
            if (v.points == "0") txtChecked = "checked=\"checked\"";
            txtCheckUp += "<input type=\"radio\" id=\""+txtID+"\" name=\"upgrade_" + upgrade_id + "\" value=\"" + v.points + "\" class=\"radUpgrade\"" + txtChecked + ">" + v.name + " [" + v.points + " pts]<br>";
            });
          }
        }
      });
    divSection.html(txtCheckUp);
    }
  }

var whoCanShare = function(unitDomId) {
  // Return a sorted list of compatible IDs in Battlegroup
  /*
  to be called when unit changes, number changes, or transport changes. Or checkboxes activated/deactivated.
  Check highest level of transportation (either 1 or none).
    If Transportation exists, get max_squads value of current transportation. As of current, this is either 2 or 3... call it mx.
    Build a sorted list/array of first mx compatible squads in battle group (including current one).
    (Compatible means: top level sharable, same transport, and not already shared by another squad)
  If not enough possible shares, do nothing.
  If Transportation does not exist, get max_squads value of current transportation. Still call it mx.
    Build sorted list/array of first mx compatible squads in battle group (including current one).
    (Compatible means: top level sharable, and not already shared by another squad)
  If not enough possible shares, do nothing.
  Offer to share
  */
  var tblUnit = $("#"+unitDomId)
  var my_id = parseInt(tblUnit.attr("id").substring(8));
  var divBattleGroup = tblUnit.closest(".divBattleGroup");
  var unit_id = parseInt(tblUnit.find(".selUnit").val());
  var squad = objSquadUnits[unit_id];
  var thisTransId = 0;
  var vehicleTotal = 0;
  var iTransId;       // For iterating through trans_ids
  var topTransId = 0; // The "outermost" transport for nested transports
  var maxSquads = 0;  // stop looking for shares after arrCandidates[] gets this big
  // Array of candidates in the same current battlegroup
  var arrCandidates = [parseInt(unitDomId.substring(8))];
  // Build an array of who can share, as per rulebook
  var arrCouldShare = [];
  $.each(squad.share, function(share_id) {
    arrCouldShare.push(parseInt(share_id));
    });
  arrCouldShare.sort();
  // Skip if there is nothing that can be shared
  if (squad.share.length == 0) return;

  // Keeping in mind selTransports or selNumber might not exist yet.
  if (tblUnit.find(".selUnitTrans").length
  && tblUnit.find(".selUnitTrans").val() != 0
  && squad.type == "Infantry") {
    thisTransId = parseInt(tblUnit.find(".selUnitTrans").val());
    // Best way to get first item in object: iterate, break immediately.
    // Actually, it's the only item.
    if ($.isEmptyObject(objSquadUnits[thisTransId].share) == false) {
      for (iShareId in objSquadUnits[thisTransId].share) break;
      maxSquads = objSquadUnits[thisTransId].share[iShareId].max_squads;
      }
    else maxSquads = 0;
    }
  else {
    thisTransId = 0; // There is no transportation. Most likely non-infantry.
    maxSquads = 2;
    //Okay, might not always be 2, but true for all vehicle squads for now.
    }

  $.each(divBattleGroup.find(".tblUnit"), function() {
    var iElId = parseInt($(this).attr("id").substring(8));
    var elUnit = $(this);
    var maxCarry = 0;
    var maxTransId = 0;
    // Don't accidentally share with yourself.
    if (my_id == iElId) return;
    // Skip if unit is already shared
    if ($(this).data("shared_with") != "") return;
    // Skip if tblUnit's parent td is not visible
    if ($(this).parent().is(":visible") == false) return;

    iTransId = 0;
    if ($.inArray(parseInt($(this).find(".selUnit").val()), arrCouldShare) != -1) {
      // potential matching unit_id found
      // Check for compatible transport.
      if ($(this).find(".selUnitTrans").length
      && $(this).find(".selUnitTrans").val() != 0)
        iTransId = parseInt($(this).find(".selUnitTrans").val());
      if (iTransId == thisTransId) {
        if (thisTransId != 0 && squad.type == "Infantry" && objSquadUnits[thisTransId].transports.length > 0) {
          arrCandidates.push(iElId);
          topTransId = objSquadUnits[thisTransId].transports[0].trans_id
          }
        if (thisTransId == 0 && squad.type == "Vehicle") {
          arrCandidates.push(iElId);
          // Top Transport is the one with the bigger carrying capacity.
          $.each(squad.transports, function(transKey, transVal) {
            if (parseInt(transVal.carry) > maxCarry) {
              maxCarry = parseInt(transVal.carry);
              maxTransId = transKey;
              }
            });
          topTransId = squad.transports[maxTransId].trans_id
          }
        }
      else return;
      }
    // And, at the end of each candidate iteration, break if arrCandidates has reached max.
    if(arrCandidates.length == maxSquads) return;
    });
  // Not out of the woods yet. Still have to see if vehicle numbers are correct.
  if (squad.type == "Vehicle") {
    // Count up candidates' vehicles. If falls short, then break/return/get out.
    if (arrWalkerSquadronIds.indexOf(String(unit_id)) > -1) {
      // PHR specific branch
      $.each(arrCandidates, function(k, v) {
        $.each($("#tblUnit_"+v).find(".selWalkerUnit"), function() {
          vehicleTotal += parseInt($(this).val());
          });
        });
      }
    else {
      $.each(arrCandidates, function(key, val) {
        vehicleTotal += parseInt($("#tblUnit_"+val).find(".selUnitNumber").val());
        });
      }
    for (iShareId in squad.share) break;
    // Too simple. Will need to revisit if rules ever allow dissimilarly numbered squads to share transport.
    if (parseInt(squad.share[iShareId].share_total) != vehicleTotal) return;
    }
  // Still possible that iterations completed without reaching maxSquad length
  if(arrCandidates.length == maxSquads) {
    arrCandidates.sort();
    //show checkbox
    addShare(topTransId, arrCandidates);
    //alert("Candidates " + JSON.stringify(arrCandidates) + " may share a " + objSquadUnits[topTransId].name + " for "  + objSquadUnits[topTransId].points + " points.");
    }

  // EOF
  }

var whichTransports = function(tblUnit) {
  var unit_id = tblUnit.find(".selUnit").val();
  var squad = objSquadUnits[unit_id];
  var objTrans = new Object;
  var phrTotal = 0;
  var q;

  if (arrWalkerSquadronIds.indexOf(unit_id) > -1) {
    // If PHR Walker...
    q = 0;
    $.each(tblUnit.find(".selWalkerUnit"), function() {
      q += parseInt($(this).val());
      });
    }
  else {
    if (tblUnit.find(".divUnitNumber").is(":visible") != false && tblUnit.find(".divUnitNumber").html() != "")
      q = parseInt(tblUnit.find(".selUnitNumber").val());
    else
      q = 1;
    }
  $.each(squad.transports, function(key, t) {
    var per = parseInt(t.carry);
    if (q % per > 0) {
      return;
      }
    var transCount = q/per;
    var txtPlural = "";
    var transName = objSquadUnits[t.trans_id].name;
    var transPoints = parseInt(objSquadUnits[t.trans_id].points)*transCount;
    if (transCount > 1) {
      txtPlural = " (x" + transCount + ")";
      }
    // There may be multiple trans_ids in set. Use Object to eliminate dupes.
    if (transCount > 0) {
      objTrans[t.trans_id] = {'transCount':transCount, 'transName':transName+txtPlural, 'transPoints':transPoints};
      }
    });
  return objTrans;
  }

var phrUnitChangeClone = function(selUnit) {
    // This is a clone of change event for selUnit, but customized for PHR.
    var divBattleGroup = selUnit.closest(".divBattleGroup");
    var unit_id = selUnit.val();
    var squad = objSquadUnits[unit_id];
    var base_cost = parseInt(objSquadUnits[unit_id].min) * parseInt(objSquadUnits[unit_id].points);
    var tblUnit = selUnit.closest(".tblUnit");
    //var divUnitSel = selUnit.closest(".tblUnit").find(".divUnitSel");
    var divPHRSpecific = selUnit.closest(".tblUnit").find(".divPHRSpecific");
    var divNumber = selUnit.closest(".tblUnit").find(".divUnitNumber");
    var divUpgrades = selUnit.closest(".tblUnit").find(".divUnitUpgrades");
    var divTransports = selUnit.closest(".tblUnit").find(".divUnitTransports");
    var divTransportUpgrades = selUnit.closest(".tblUnit").find(".divUnitTransportUpgrades");
    var divShareUpgrades = selUnit.closest(".tblUnit").find(".divUnitShareUpgrades");
    var thSquad = selUnit.closest(".tblUnit").find(".thSquad");
    var txtCheckUp = "";
    var txtNumSelect = "<select class=\"selUnitNumber\">";
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\" selected=\"selected\">No Transport   [0 points]</option>";
    //var mn = parseInt(squad.min);
    //var mx = parseInt(squad.max);
    //var inc = parseInt(squad.inc);
    var p = parseInt(squad.points);
    var q;
    var i;
    // Avoiding some exceptions for base cost
    if (unit_id == "0") base_cost = 0;

    divTransports.empty();
    divTransports.hide();
    divTransportUpgrades.empty();
    divTransportUpgrades.hide();
    divNumber.empty();
    divNumber.hide();
    divUpgrades.empty();
    divUpgrades.hide();
    thSquad.data("points", 0);
    thSquad.html("0 pts");
    divPHRSpecific.empty();
    divPHRSpecific.show();

    var tagCount = "";
    var tagPoints;
    var intMin;
    var intPoints;
    //var cat_id = selUnit.closest(".tblUnit").data("cat_id");
    var opts = "";

    $.each(objPHRWalkerOpts[unit_id], function(unitid, val) {
      intMin = 0;
      opts="";
      intPoints = parseInt(val.points);
      for (i = 0; i <= parseInt(val.max); i++)
        opts += "<option value=\""+i+"\">" + val.name + " x"+i+" ["+i*intPoints+" points]</option>";
      divPHRSpecific.append("<select class=\"selWalkerUnit sel" + val.name + "\" data-unit_id=\""+unitid+"\">" + opts + "</select><br>");
      });

    if (!($.isEmptyObject(squad.transports)) && faction_id != 4) {
      divTransports.show();
      divTransports.empty();
      objTrans = whichTransports(selUnit.closest(".tblUnit"));
      $.each(objTrans, function(trans_id,t) {
        txtTransSelect += "<option value=\"" + trans_id + "\" data-transcount=\"" + t.transCount + "\">" + t.transName + "   [" + t.transPoints + " points]</option>";
        });
      txtTransSelect += "</select>";
      //$(this).find(".divUnitTransports").data("count", transCount);
      divTransports.html(txtTransSelect);
      }
    removeShares(selUnit);
    updateSquadPoints(tblUnit);
    //EOF
    }

var updateDivCommander = function() {
  if ($("#selCommander1").val()=="0" && $("#selCommander2").val()=="0" && fc_id=="1")
    $("#divCommander").html("No Commander");
  else {
    txtCommander = "<table align='left' border='1' padding='1'><tr><th>Commander</th><th>CV</th><th>Radius</th></tr>";
    if (fc_id!="1" && $.isEmptyObject(objFamousCommanders) == false) {
      txtCommander += "<tr>";
      txtCommander += "<td>" + objFamousCommanders[fc_id].name + "</td>";
      txtCommander += "<td align='center'>" + objFamousCommanders[fc_id].cv + "</td>";
      txtCommander += "<td align='center'>" + objFamousCommanders[fc_id].radius + "</td>";
      txtCommander += "</tr>";
      }
    if ($("#selCommander1").val()!="0" && $.isEmptyObject(objCommanders) == false) {
      txtCommander += "<tr>";
      txtCommander += "<td>" + objCommanders[$("#selCommander1").val()].name + "</td>";
      txtCommander += "<td align='center'>" + objCommanders[$("#selCommander1").val()].cv + "</td>";
      txtCommander += "<td align='center'>" + objCommanders[$("#selCommander1").val()].radius + "</td>";
      txtCommander += "</tr>";
      }
    if ($("#selCommander2").val()!="0" && $.isEmptyObject(objCommanders) == false) {
      txtCommander += "<tr>";
      txtCommander += "<td>" + objCommanders[$("#selCommander1").val()].name + "</td>";
      txtCommander += "<td align='center'>" + objCommanders[$("#selCommander1").val()].cv + "</td>";
      txtCommander += "<td align='center'>" + objCommanders[$("#selCommander1").val()].radius + "</td>";
      txtCommander += "</tr>";
      }
    txtCommander += "</table><hr>";
    $("#divCommander").html(txtCommander);
    }
  }

var upgradeObjDiv = function(divTable, txtDiv) {
  //var upDiv = "." + txtDiv;
  //alert(upDiv);
  obj = new Object;
  obj.radios = new Object;
  obj.checks = new Object;
  $.each(divTable.find(txtDiv).find("input[type='radio']:checked"), function() {
    obj.radios[$(this).attr("id")] = $(this).val();
    });
  $.each(divTable.find(txtDiv).find("input[type='checkbox']:checked"), function() {
    obj.checks[$(this).attr("id")] = $(this).val();
    });
  return obj;
  }

var saveList = function () {
  var objSave = new Object;
  objSave.rost = $("#selRoster").val();
  objSave.fact = $("#selFaction").val();
  objSave.size = $("#selSize").val();
  objSave.pts = $("#txtPoints").val();
  objSave.famm = $("#selFamComm").val();
  objSave.comm1 = $("#selCommander1").val();
  objSave.comm2 = $("#selCommander2").val();
  objSave.comm3 = $("#selCommander3").val();
  objSave.bgs = new Object;
  objSave.squadbuttons = [];
  $.each($(this).find(".btnSquadAdd"), function() {
    if ($(this).is(":visible") == false) {
      objSave.squadbuttons.push($(this).attr("id"));
      }
    });
  if ($("#divGates").is(":visible") == true) {
    objSave.bgs.divGates = new Object;
    $.each($("#divGates").find(".tblUnit"), function() {
      objSave.bgs.divGates[$(this).attr("id")] = $(this).find(".selSpecTransNumber").val();
      });
    }
  if ($("#divDrills").is(":visible") == true) {
    //objSave.bgs["divDrills"] = new Object;
    if ($("#divDrills").find(".selSpecTrans").val() == "0") {
      objSave.bgs["divDrills"] = 0;
      }
    else {
      objSave.bgs["divDrills"] = [$("#divDrills").find(".selSpecTransNumber").val()];
      }
    }
  $.each($(".divBattleGroup"), function() {
    if ($(this).attr("id") == "divGates" || $(this).attr("id") == "divDrills") return;
    if ($(this).is(":visible") == false) return;
    var divID = $(this).attr("id");
    objSave.bgs[divID] = new Object;
    if ($(this).find(".btnBGRemove").is(":visible") == true) {
      objSave.bgs[divID].mandatory = "0";
      }
    else {
      objSave.bgs[divID].mandatory = "1";
      }
    objSave.bgs[divID].rtype = $(this).data("rtype_id");
    objSave.bgs[divID].bgtype = $(this).data("bgtype_id");
    objSave.bgs[divID].units = [];
    $.each($(this).find(".tblUnit"), function() {
      if ($(this).is(":visible") == false) return;
      var objTempUnit = new Object;
      objTempUnit.unitID = $(this).attr("id");
      //alert($(this).find(".selUnit").val());
      objTempUnit.selUnit = $(this).find(".selUnit").val();
      if ($(this).find(".btnSquadRemove").is(":visible") == true) {
        objTempUnit.mandatory = 0;
        }
      else objTempUnit.mandatory = 1;

      if ($(this).find(".divPHRSpecific").is(":visible") == true) {
        objTempUnit.divPHRSpecific = new Object;
        $.each($(this).find(".selWalkerUnit"), function() {
          //Remember that each selWalker has another class ID
          objTempUnit.divPHRSpecific[$(this).attr('class').split(' ')[1]] = $(this).val();
          });
        }

      if ($(this).find(".divUnitNumber").is(":visible") == true) {
        objTempUnit.divUnitNumber = new Object;
        objTempUnit.divUnitNumber.selUnitNumber = $(this).find(".selUnitNumber").val();
        }
      if ($(this).find(".divUnitUpgrades").is(":visible") == true)
        objTempUnit.divUnitUpgrades = upgradeObjDiv($(this), ".divUnitUpgrades");
      if ($(this).find(".divUnitTransports").is(":visible") == true) {
        objTempUnit.divUnitTransports = new Object;
        objTempUnit.divUnitTransports.selUnitTrans = $(this).find(".selUnitTrans").val();
        }
      if ($(this).find(".divUnitTransportUpgrades").is(":visible") == true)
        objTempUnit.divUnitTransportUpgrades = upgradeObjDiv($(this), ".divUnitTransportUpgrades");
      if ($(this).find(".divUnitShare").is(":visible") == true) {
        //Fix. Not Val, but whether it's checked.
        objTempUnit.divUnitShare = new Object;
        objTempUnit.divUnitShare.selUnitTrans = $(this).find(".chkShare").val();
        }
      if ($(this).find(".divUnitShareUpgrades").is(":visible") == true)
        objTempUnit.divUnitShareUpgrades = upgradeObjDiv($(this), ".divUnitShareUpgrades");
      objSave.bgs[divID].units.push(objTempUnit);
      });
    });
  msg = JSON.stringify(objSave);
  return msg;
  }

$(document).ready(function() {


  var section = [];
  //alert("init");

  $("#selRoster").change(function() {
    //So far only Cato changes the roster
    //if ($.isNumeric($("#selFamComm").val())) fc_id = $("#selFamComm").val();
    //else fc_id = "1";
    if ($("#selFamComm").val() == "3") {
      fc_id = $("#selFamComm").val();
      }
    retrieveLimits(faction_id, size_id, fc_id);
    });

  $("#selSize").change(function() {
    size_id = $("#selSize").val();
    //So far only Cato changes the roster
    //if ($.isNumeric($("#selFamComm").val())) fc_id = $("#selFamComm").val();
    //else fc_id = "1";
    if ($("#selFamComm").val() == "3") {
      fc_id = $("#selFamComm").val();
      }
    retrieveLimits(faction_id, size_id, fc_id);
    });

  $("#selFamComm").change(function() {
    //So far only Cato changes the roster
    //if ($.isNumeric($("#selFamComm").val())) fc_id = $("#selFamComm").val();
    //else fc_id = "1";
    fc_id = $("#selFamComm").val();
    retrieveLimits(faction_id, size_id, fc_id);
    updateDivCommander();
    updateTotalPoints();
    });

  $("#selFaction").change(function() {
    size_id = $("#selSize").val();
    faction_id = $("#selFaction").val();
    //So far only Cato changes the roster
    //if ($.isNumeric($("#selFamComm").val())) fc_id = $("#selFamComm").val();
    //else fc_id = 1;
    //if ($("#selFamComm").val() == 3) {
    //  fc_id = $("#selFamComm").val();
    //  }
    retrieveLimits(faction_id, size_id, fc_id);
    $("#selFamComm").empty();
    $.getJSON("data/famous.php?f="+faction_id, function(data) {
      objFamousCommanders = data;
      $.each(data, function(key, famcom) {
        $("#selFamComm").append($("<option></option>").attr("value", key).text("[" + famcom.points + " points]   " + famcom.name));
        });
      //Change FC if this is a load
      $("#selFamComm").val(fc_id);
      });
    retrieveLimits(faction_id, size_id, fc_id);
    });

  $(document).on('click', '.divUnitShare', function(){
    //alert($(this).closest(".tblUnit").data("shared_with"));
    });

  $(document).on('click', '.radUpgrade', function(){
    updateSquadPoints($(this).closest(".tblUnit"));
    });

  $(document).on('change', '.chkUpgrade', function(){
    // Exception: If Jocasta (checkbox value 92), and this is her retinue upgrade, change tranports to match.
    var tblUnit = $(this).closest(".tblUnit");
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\" selected=\"selected\">No Transport   [0 points]</option>";
    if ($(this).val() == "92") {
      if ($(this).prop("checked"))
        txtTransSelect += "<option value=\"192\" data-transcount=\"1\">Poseidon   [81 points]</option>";
      else
        txtTransSelect += "<option value=\"193\" data-transcount=\"1\">Neptune   [45 points]</option>";
      txtTransSelect += "</select>";
      tblUnit.find(".divUnitTransports").html(txtTransSelect);
      }
    updateSquadPoints(tblUnit);
    });

  $(document).on('change', '.selCommander', function(){
    if ($(this).val() == "0")
      $(".btnCommander" + $(this).data("idx")).hide();
    else
      $(".btnCommander" + $(this).data("idx")).show();
    updateDivCommander();
    updateTotalPoints();
    });

  $(document).on('change', '.chkShareUpgrade', function(){
    updateSquadPoints($(this).closest(".tblUnit"));
    // eof
    });

  $(document).on('change', '.chkShare', function(){
    var elCheck = $(this);
    var idx = $(this).data("idx");
    var unit_id = $(this).data("unit_id");
    var txtShare = String($(this).data("share_with"));
    var arrShare = txtShare.split(",");
    var points = $(this).data("points");
    var topUnitId = $(this).closest(".tblUnit").attr("id").substring(8);
    var divUnitShareUpgrades = $(this).closest(".tblUnit").find(".divUnitShareUpgrades");
    var txtCheckUp = "";

    //alert($(this).prop("checked"));
    if ($(this).prop("checked")) {
      $.each(arrShare, function(shareKey, shareVal) {
        var thisUnit = "#tblUnit_" + shareVal;
        var iDivUnitShare = $(thisUnit).find(".divUnitShare");
        var arrOtherShares = [];
        iDivUnitShare.show();
        iDivUnitShare.html("Shared ->");
        // But also need to insert list of other shared cells
        // Build array of shared unit IDs not this one.
        arrOtherShares.push(topUnitId);
        $.each(arrShare, function(sKey, sVal) {
          // Build array of shared unit IDs not this one.
          if ($(this) != String(shareVal)) arrOtherShares.push(sVal);
          });
        $(thisUnit).data("shared_with", arrOtherShares.join());
        });
      if (!($.isEmptyObject(objSquadUnits[unit_id].upgrades))) {
        divUnitShareUpgrades.show();
        $.each(objSquadUnits[unit_id].upgrades, function (up_id, u) {
          txtCheckUp += "<input type=\"checkbox\" class=\"chkShareUpgrade\" value=\""+up_id+"\" data-points=\""+u.points+"\">"+u.name+"  ["+u.points+" points]<br>";
          });
        divUnitShareUpgrades.show();
        divUnitShareUpgrades.empty();
        divUnitShareUpgrades.html(txtCheckUp);
        }
      }
    else {
      $.each(arrShare, function(shareKey, shareVal) {
        var thisUnit = "#tblUnit_" + shareVal;
        var iDivUnitShare = $(thisUnit).find(".divUnitShare");
        iDivUnitShare.empty();
        iDivUnitShare.hide();
        $(thisUnit).data("shared_with", "");
        });
      divUnitShareUpgrades.empty();
      divUnitShareUpgrades.hide();
      }
    //alert ($(this).closest(".tblUnit").data("shared_with"));
    updateSquadPoints($(this).closest(".tblUnit"));
    // eof
    });

  $(document).on('change', '.selUnitTrans', function(){
    var unit_id = $(this).closest(".tblUnit").find(".selUnit").val();
    var divUpgrades = $(this).closest(".tblUnit").find(".divUnitTransportUpgrades");
    var squad = objSquadUnits[unit_id];
    var transport = objSquadUnits[$(this).val()];
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\">No Transport</option>";
    var p = parseInt(squad.points);
    var q = thSquad.data("count");
    var txtCheckUp = "";
    var base_cost;

    if ($(this).val() == "0") {
      base_cost = q*p;
      }
    else {
      base_cost = transport.points * $("option:selected", this).data("transcount") + q*p;
      // Upgrades for transports
      if (kpTotal == 0) {
        divUpgrades.empty().hide();
        listUpgrades($("option:selected", this).data("transcount"), $(this).val(), divUpgrades);
        }
      }

    thSquad.data("points", base_cost);
    thSquad.html(base_cost + " pts");

    //(transPoints*transCount) + #selUnitNumber.val()*squad.min
    //updateBGPoints($(this).closest(".divBattleGroup"));
    updateSquadPoints($(this).closest(".tblUnit"));
    whoCanShare($(this).closest(".tblUnit").attr("id"));
    });

  $(document).on('change', '.selUnitNumber', function(){
    var unit_id = $(this).closest(".tblUnit").find(".selUnit").val();
    var squad = objSquadUnits[unit_id];
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var divTransports = $(this).closest(".tblUnit").find(".divUnitTransports");
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\">No Transport</option>";
    var objTrans = new Object;
    var txtPlural = "";
    var p = parseInt(squad.points);
    var q = parseInt($(this).val());

    if (kpTotal > 0) {
      thSquad.data("count", q);
      thSquad.data("points", q*p);
      thSquad.html(q*p + "pts");
      updateSquadPoints(tblUnit);
      return;
      }

    $(this).closest(".tblUnit").find(".divUnitTransportUpgrades").empty().hide();
    $(this).closest(".tblUnit").find(".divUnitShare").empty().hide();
    $(this).closest(".tblUnit").find(".divUnitShareUpgrades").empty().hide();
    thSquad.data("count", q);
    thSquad.data("points", q*p);
    thSquad.html(q*p + "pts");

    listUpgrades(q, unit_id, $(this).closest(".tblUnit").find(".divUnitUpgrades"));

    // Change transport options.  These will have changed with the number of units
    if (!($.isEmptyObject(squad.transports)) && faction_id != 4) {
      divTransports.empty();
      objTrans = whichTransports($(this).closest(".tblUnit"));
      $.each(objTrans, function(trans_id,t) {
        txtTransSelect += "<option value=\"" + trans_id + "\" data-transcount=\"" + t.transCount + "\">" + t.transName + "   [" + t.transPoints + " points]</option>";
        });
      txtTransSelect += "</select>";
      //$(this).find(".divUnitTransports").data("count", transCount);
      divTransports.html(txtTransSelect);
      }
    removeShares($(this));
    //updateBGPoints($(this).closest(".divBattleGroup"));
    updateSquadPoints($(this).closest(".tblUnit"));
    whoCanShare($(this).closest(".tblUnit").attr("id"));
    //EOF
    });

  $(document).on('change', '.selUnit', function(){
    // Avoiding some exceptions for base cost
    var unit_id = $(this).val();
    var tblUnit = $(this).closest(".tblUnit");
    tblUnit.find(".divUnitNumber").empty().hide();
    tblUnit.find(".divUnitUpgrades").empty().hide();
    tblUnit.find(".divUnitTransports").empty().hide();
    tblUnit.find(".divUnitTransportUpgrades").empty().hide();
    tblUnit.find(".divPHRSpecific").empty().hide();
    tblUnit.find(".thSquad").data("points", 0);
    tblUnit.find(".thSquad").html("0 pts");
    if (faction_id == 3 && arrWalkerSquadronIds.indexOf($(this).val()) > -1) {
      phrUnitChangeClone($(this));
      return;
      } //SpecTrans
    else {
      $(this).closest(".tblUnit").find(".divPHRSpecific").empty();
      $(this).closest(".tblUnit").find(".divPHRSpecific").hide();
      }
    if (unit_id == "0") {
      base_cost = 0;
      updateBGPoints($(this).closest(".divBattleGroup"));
      return;
      }
    var divBattleGroup = $(this).closest(".divBattleGroup");
    var squad = objSquadUnits[unit_id];
    var base_cost = parseInt(objSquadUnits[unit_id].min) * parseInt(objSquadUnits[unit_id].points);
    var divNumber = $(this).closest(".tblUnit").find(".divUnitNumber");
    var divUpgrades = $(this).closest(".tblUnit").find(".divUnitUpgrades");
    var divTransports = $(this).closest(".tblUnit").find(".divUnitTransports");
    var divTransportUpgrades = $(this).closest(".tblUnit").find(".divUnitTransportUpgrades");
    var divShareUpgrades = $(this).closest(".tblUnit").find(".divUnitShareUpgrades");
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var txtCheckUp = "";
    var txtNumSelect = "<select class=\"selUnitNumber\">";
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\" selected=\"selected\">No Transport   [0 points]</option>";
    var mn = parseInt(squad.min);
    var mx = parseInt(squad.max);
    var inc = parseInt(squad.inc);
    var p = parseInt(squad.points);
    var q;
    var objUpgrades = new Object;
    var tbl_id = parseInt(tblUnit.attr("id").substring(8));

    if (kpTotal > 0) {
      updateSquadPoints(tblUnit);
      return;
      }

    tblUnit.find(".divUnitTransportUpgrades").empty();
    tblUnit.find(".divUnitTransportUpgrades").hide();
    //alert($(this).data("total"));
    thSquad.data("points", base_cost);
    thSquad.data("count", mn);
    thSquad.html(base_cost + " pts");
    divNumber.empty();
    if (mx > 1) {
      divNumber.show();
      q = 0;
      selected = " selected=\"selected\"";
			while (q+mn <= mx) {
        txtNumSelect += "<option value=\""+(q+mn)+"\""+selected+">"+(q+mn)+" Units ["+((q+mn)*p)+" points]</option>";
        selected = "";
        q += inc;
        }
      txtNumSelect += "</select>";
      divNumber.html(txtNumSelect);
      }
    else {
      // Oops, single units still need a transport option!
      divNumber.html("<select class=\"selUnitNumber\" style=\"display:none;\"><option value=\"1\" selected=\"selected\"></option></select>");
      }
    if (!($.isEmptyObject(squad.transports)) && faction_id != 4) {
      divTransports.show();
      divTransports.empty();
      objTrans = whichTransports($(this).closest(".tblUnit"));
      $.each(objTrans, function(trans_id,t) {
        txtTransSelect += "<option value=\"" + trans_id + "\" data-transcount=\"" + t.transCount + "\">" + t.transName + "   [" + t.transPoints + " points]</option>";
        });
      txtTransSelect += "</select>";
      divTransports.html(txtTransSelect);
      }
    // Check for optional weapons
    // Check for optional attachments
    listUpgrades(mn, unit_id, divUpgrades);

    removeShares($(this));
    //updateBGPoints($(this).closest(".divBattleGroup"));
    updateSquadPoints(tblUnit);
    whoCanShare($(this).closest(".tblUnit").attr("id"));
    //EOF
    });

  $(document).on('change', '.selWalkerUnit', function(){
    var unit_id = $(this).closest(".tblUnit").find(".selUnit").val();
    var squad = objSquadUnits[unit_id];
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var divTransports = $(this).closest(".tblUnit").find(".divUnitTransports");
    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\">No Transport</option>";
    var objTrans = new Object;
    var txtPlural = "";
    var p = parseInt(squad.points);
    var q = parseInt($(this).val());

    var selWalkerUnit = $(this);
    var unitNumber = 0;
    var intMax = 6; // Might change someday with another faction.
    var tblUnit = $(this).closest(".tblUnit");
    var intRemaining;
    var i;

    if (kpTotal > 0) {
      updateSquadPoints(tblUnit);
      return;
      }

    $.each(tblUnit.find(".selWalkerUnit"), function() {
      unitNumber += parseInt($(this).val());
      });
    intRemaining = intMax - unitNumber;
    $.each(tblUnit.find(".selWalkerUnit"), function() {
      var lastVal = parseInt($(this).find('option:last-child').attr("value"));
      var unit_id = $(this).data("unit_id");
      //if ($(this) == selWalkerUnit) return;
      while (intRemaining + parseInt($(this).val()) != lastVal) {
        if (intRemaining + parseInt($(this).val()) < lastVal) {
          $(this).children('option:last').remove();
          }
        if (intRemaining + parseInt($(this).val()) > lastVal) {
          lastVal++;
          $(this).append($("<option></option>")
         .attr("value",lastVal)
         .text(objPHRWalkerUnits[unit_id].name + " x"+lastVal+" ["+lastVal*parseInt(objPHRWalkerUnits[unit_id].points)+" points]"));
          }
        lastVal = parseInt($(this).find('option:last-child').attr("value"));
        if (lastVal < 1 || lastVal > 5) break;
        }
      });

    $(this).closest(".tblUnit").find(".divUnitTransportUpgrades").empty().hide();
    $(this).closest(".tblUnit").find(".divUnitShare").empty().hide();
    $(this).closest(".tblUnit").find(".divUnitShareUpgrades").empty().hide();
    // Note: actually should call an "unshare" function at this point.
    thSquad.data("count", q);
    thSquad.data("points", q*p);
    thSquad.html(q*p + "pts");
    // Change transport options.  These will have changed with the number of units
    divTransports.empty();
    if (!($.isEmptyObject(squad.transports)) && faction_id != 4) {
      divTransports.empty();
      objTrans = whichTransports($(this).closest(".tblUnit"));
      $.each(objTrans, function(trans_id,t) {
        txtTransSelect += "<option value=\"" + trans_id + "\" data-transcount=\"" + t.transCount + "\">" + t.transName + "   [" + t.transPoints + " points]</option>";
        });
      txtTransSelect += "</select>";
      //$(this).find(".divUnitTransports").data("count", transCount);
      divTransports.html(txtTransSelect);
      }

    removeShares($(this));
    //updateBGPoints($(this).closest(".divBattleGroup"));
    updateSquadPoints($(this).closest(".tblUnit"));
    whoCanShare($(this).closest(".tblUnit").attr("id"));
    //EOF
    });

  $(document).on('change', '.selSpecTrans', function(){
    var base_cost = parseInt($(this).val());
    var q = parseInt($(this).closest(".tblUnit").find(".selSpecTransNumber").val());
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var sumTotal = base_cost * q;
    thSquad.data("points", sumTotal);
    thSquad.data("count", q);
    thSquad.html(String(sumTotal) + " pts");
    updateSpecialTransportPoints($(this).closest(".tblUnit"));
    });

  $(document).on('change', '.selSpecTransNumber', function(){
    var q = parseInt($(this).val());
    var base_cost = parseInt($(this).closest(".tblUnit").find(".selSpecTrans").val());
    var thSquad = $(this).closest(".tblUnit").find(".thSquad");
    var sumTotal = base_cost * q;
    thSquad.data("points", sumTotal);
    thSquad.data("count", q);
    thSquad.html(String(sumTotal) + " pts");
    updateSpecialTransportPoints($(this).closest(".tblUnit"));
    });

  $(".btnBGRemove").click(function(){
    var BGDiv = $(this).closest(".divBattleGroup");
    var idx = BGDiv.data("idx");
    var bgtypekey = BGDiv.data("bgtype_id");
    var bgname = BGDiv.find(".thBGNameIdx").html();
    //var BGUnitLoc = "#trUnit"+arrBGTypes[bgtypekey] + idx;
    bgname = bgname.substr(0, bgname.length - 2);
    //$(BGUnitLoc).empty();
    $(this).closest(".divBattleGroup").find(".thPoints").html(0);
    $(this).closest(".divBattleGroup").hide();
    resetBGBar(bgtypekey, idx, bgname);
    updateBGPoints($(this).closest(".divBattleGroup"));
    //alert($(this).closest(".divBattleGroup").id);
    });

  $(document).on('click', '.btnBGAdd', function(){
    // Different event syntax because
    var bgkey = $(this).attr("value");
    var rkey = $(this).data("rtype_id");
    var visicount = 0;
    //$("#divHQ1").show();
    // Count total visible
    $(".divBattleGroup").each(function(){
      if (rkey == $(this).data("rtype_id")) {
        if ($(this).is(":visible") == true) {
          visicount += 1;
          }
        }
      });

    // Find maximum allocation
    for (var rtype_id in arrRosterLimits) {
      if (arrRosterLimits[rtype_id].bgtype_id.indexOf(bgkey) > -1) {
        break;
        }
      }
    if (visicount < parseInt(arrRosterLimits[rtype_id].allmax)) {
      for (var i = 1; i <= 3; i++) {
        if ($("#div"+arrBGTypes[bgkey]+i).is(":visible") == false) {
          $("#div"+arrBGTypes[bgkey]+i).show();
          // In case Remove button was hidden by mandatory logic
          $("#div"+arrBGTypes[bgkey]+i).find(".btnBGRemove").show();
          // See if we need to activate each button to meet minimum
          //$.each($("#div"+arrBGTypes[bgkey]+i).find(".btnSquadAdd"), function() {
          $.each(objBGLimits[bgkey].limits, function(cat_key, lims) {
            var b = parseInt(lims.bgmin);
            if (cat_key == "0") return;
            $.each($("#div"+arrBGTypes[bgkey]+i).find(".btnSquadAdd"), function() {
              if (b == 0) return;
              if ($(this).data("cat_id") == cat_key) {
                $(this).trigger("click");
                b -= 1;
                }
              });
            //if (objBGLimits[bgkey].limits[$(this).data("cat_id")])
            });
          $("#div"+arrBGTypes[bgkey]+i).find(".tblUnit").each(function(){
            var idCommanderVehicle = "0";
            var tblUnit = $(this);
            if ($(this).is(":visible") == true)
              $(this).find(".btnSquadRemove").hide();
              // Force Command slot to FamCom's vehicle. (And all the checks that go with it.)
              if (parseInt($("#selFamComm").val()) > 1 && flagFamCom == 0 && $(this).data("cat_id") == "3") {
                $.each(objSquadUnits, function(comm_id, uval) {
                  if (uval.faminc == "1") {
                    var objTrans = new Object;
                    var txtTransSelect = "<select class=\"selUnitTrans\"><option value=\"0\" data-transcount=\"0\" selected=\"selected\">No Transport   [0 points]</option>";;
                    tblUnit.find(".selUnit").empty().append($("<option selected=\"selected\"></option>").attr("value", comm_id).text(uval.name + "   [Famous Commander]"));
                      if (!($.isEmptyObject(uval.transports)) && faction_id != 4) {
                        tblUnit.find(".divUnitTransports").show().empty;
                        objTrans = whichTransports(tblUnit);
                        $.each(objTrans, function(trans_id,t) {
                          txtTransSelect += "<option value=\"" + trans_id + "\" data-transcount=\"" + t.transCount + "\">" + t.transName + "   [" + t.transPoints + " points]</option>";
                          });
                        txtTransSelect += "</select>";
                        tblUnit.find(".divUnitTransports").html(txtTransSelect);
                        }
                      else tblUnit.find(".divUnitTransports").hide().empty;
                    flagFamCom = 1;
                    listUpgrades(1, comm_id, tblUnit.find(".divUnitUpgrades"));
                    updateSquadPoints(tblUnit);
                    return false;
                    }
                  })
                }
            });
          break;
          }
        }
      }
    errorCheck();
    });

  $(document).on('click', '.btnSquadRemove', function(){
    var trSquadCell = $(this).parent().parent().parent().parent().parent();
    var idx = $(this).data("idx");
    var cat_id = $(this).data("cat_id");
    var bg_id = $(this).data("bg_id");
    $.each($("#thButton" + arrBGTypes[bg_id] + idx).children(), function(el, key) {
      if ($(this).data("cat_id") == cat_id && $(this).is(":visible") == false) {
        $(this).show();
        //alert($(this).closest(".tblUnit").prop("tagName"));
        trSquadCell.hide();
        return false;
        }
      });
    $(this).closest(".tblUnit").find(".selUnit").val(0);
    $(this).closest(".tblUnit").find(".divUnitNumber").empty();
    $(this).closest(".tblUnit").find(".divUnitNumber").hide();
    $(this).closest(".tblUnit").find(".divUnitUpgrades").empty();
    $(this).closest(".tblUnit").find(".divUnitUpgrades").hide();
    $(this).closest(".tblUnit").find(".divUnitTransports").empty();
    $(this).closest(".tblUnit").find(".divUnitTransports").hide();
    $(this).closest(".tblUnit").find(".divUnitTransportUpgrades").empty();
    $(this).closest(".tblUnit").find(".divUnitTransportUpgrades").hide();
    $(this).closest(".tblUnit").find(".divPHRSpecific").empty();
    $(this).closest(".tblUnit").find(".divPHRSpecific").hide();
    $(this).closest(".tblUnit").find(".thSquad").data("points", 0);
    $(this).closest(".tblUnit").find(".thSquad").html("0 pts");
    updateBGPoints($(this).closest(".divBattleGroup"));
    });

  $(document).on('click', '.btnSquadAdd', function(){
    var elButton = $(this);
    var idx = $(this).data("idx");
    var cat_id = $(this).data("cat_id");
    var bg_id = $(this).data("bg_id");
    $.each($("#trUnit" + arrBGTypes[bg_id] + idx).children(), function(el, key) {
      if ($(this).data("cat_id") == cat_id && $(this).is(":visible") == false) {
        $(this).show();
        elButton.hide();
        return false;
        }
      });
    errorCheck();
    });

  $("#btnPrintMode").click(function() {
    if (printMode == 0) {
      $("#tblPicker").fadeOut();
      $("#dSummary").fadeOut();
      $("#divCommanderSelection").fadeOut();
      $("#divUser").fadeOut();
      printMode = 1;
      }
    else {
      $("#tblPicker").fadeIn();
      $("#dSummary").fadeIn();
      $("#divCommanderSelection").fadeIn();
      $("#divUser").fadeIn();
      printMode = 0;
      }
    });


  $("#btnClearActivations").click(function() {
    $(".chkActivated").removeAttr("checked");
    });

  $("#btnKillPointMode").click(function() {
    $("#btnKillPointMode").prop("disabled",true);
    $(".btnSquadAdd").prop("disabled",true);
    $(".btnSquadRemove").prop("disabled",true);
    $(".btnBGAdd").prop("disabled",true);
    $(".btnBGRemove").prop("disabled",true);
    $("#selFaction").prop("disabled",true);
    $("#selRoster").prop("disabled",true);
    $("#selSize").prop("disabled",true);
    //$("#selFamComm").prop("disabled",true);
    //$(".selCommander").prop("disabled",true);

    kpTotal = parseInt($("#divPointTotal").data("points"));
    $("#divPointTotal").data("points", 0);
    $("#divPointTotal").html("0 Points");
    $(".selUnit option:not(:selected)").remove();
    $(".selUnit").prepend("<option value='0'>They're dead, Jim</option>");
    $(".selUnitNumber option:not(:selected)").remove();
    $("#selFamComm option:not(:selected)").remove();
    $.each($(".selUnitNumber"), function () {
      var tblUnit = $(this).closest(".tblUnit");
      var unit_id = $(tblUnit).find(".selUnit").val();
      var squad = objSquadUnits[unit_id];
      var p = parseInt(squad.points);
      for (var intUnum = parseInt($(this).val()-1); intUnum >= 0; intUnum--) {
        $(this).append($("<option></option>").attr("value", intUnum).text(intUnum+" Units ["+((intUnum)*p)+" points]"));
        }
      });
    });

  $("#frmSaveList").submit(function(event) {
    $("#ljson").val(saveList());
    //$("#dDebug").html(saveList());
    if (uname == "") {
      alert ("Must be logged in as a registered user in order to save a list.");
      event.preventDefault();
      return false;
      }
    if ($("#dDebug").html().length > 30) {
      alert ("A list must be free of errors in order to save.");
      event.preventDefault();
      return false;
      }
    });

  $("#btnStatus").click(function() {
    var msg = "Faction ID is currently " + faction_id + "\n"
            + "Size ID is currently " + size_id + "\n";
    var i = 0;
    for (var rtype_id in arrRosterLimits) {
      //alert(arrRosterTypes[i]);
      msg = msg + rtype_id + ". " + arrRosterTypes[i]
          + " is between " + arrRosterLimits[rtype_id].allmin + " and " + arrRosterLimits[rtype_id].allmax;
          //$.each(arrBGLimits[i], function(qkey,qval) {
          //  msg = msg + qkey + ":" + " and ";
          //  })
      //alert(msg);
      msg = msg + "<br>";
      i = i + 1;
      };
    msg += JSON.stringify(objSquadOpts) + "<br><br>";
    msg += JSON.stringify(objSquadUnits) + "<br><br>";
    msg += JSON.stringify(objFamousCommanders) + "<br><br>";


    //msg += "Roster array: " + JSON.stringify(arrRosterTypes);
    //msg += "<br>";
    //msg += "BG Types array: " + JSON.stringify(arrBGTypes);
    $("#dDebug").html(msg);
    //alert ($("#dDebug").html().length);
    });

  //$("#selFaction").fadeOut().fadeIn();
  //$("#selSize").fadeOut().fadeIn();
  // Borrow div list for login error. Login will not be the same page as list recall.
  $("#divListMeta").html("<?=$loginError?>");

  if (getList != "0") {
    loadBeforeLimits();
    }

  });

</script>
</head>
<body>
<h3>SCOLDZAP</h3>
<div id="divUser" align="right">
<?
if ($uname == "") {
  if ($error == "") $error = "Welcome, Guest";
  ?>
  <?=$error?><br>
  <a href="register.php">Register</a><br>
  <form method="post" action="index.php">
    <table>
    <tr><td><input type="text" name="uname" width="8"></td><td>User Name</td></tr>
    <tr><td><input type="password" name="pword" width="8"></td><td>Password</td></tr>
    <tr><td>&nbsp;</td><td><input type="submit" name="Submit" value="Login"></td></tr>
    </table>
  </form>
  <?
  }
else {
  ?>
  Welcome, <?=$uname?><br>
  <a href="logout.php">Log out</a><br>
  <?
  }
?>
</div>
<div id="divListMeta"></div>
<table id="tblPicker" border="1" width="100%">
<tr>
<th>Roster</th>
<th>Faction</th>
<th>Game Size</th>
<th>Fam. Commander</th>
<th>Points</th>
</tr>
<tr>
<td>
<select id="selRoster" class="selTop" autocomplete="off">
<option value="Standard" selected="selected">Standard</option>
<option value="Defense">Defense</option>
<option value="Reinforcements">Reinforcements</option>
</select>
</td>
<td>
<select id="selFaction" class="selTop" autocomplete="off">
<?=$optFactions?>
</select>
</td>
<td>
<select id="selSize" class="selTop" autocomplete="off">
<?=$optSizes?>
</select>
</td>
<td>
<select id="selFamComm" class="selTop" autocomplete="off">
<option value="1">None</option>
</select>
</td>
<td>
<input type="text" id="txtPoints" class="selTop" value="1500">
</td>
</tr>
</table>

<div id="dSummary">
<table border="1">
<tbody>
<tr><th>Battlegroup Type</th><th>Min</th><th>Max</th><th colspan="2">&nbsp;</th></tr>
<tr bgcolor="CCCCCC"><td>Total Allowance</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr bgcolor="#CC99FF"><td>HQ</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr bgcolor="#FFCC00"><td>Armor</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr bgcolor="#669966"><td>Troops</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr bgcolor="#FFFF99"><td>Special</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr bgcolor="#99CCFF"><td>Air</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
</tbody>
</table>
</div>

<div id="divCommanderSelection">
	<select id="selCommander1" class="selTop selCommander" data-idx="1" autocomplete="off">
	<option value="0">None</option>
	</select> Commander 1 (optional)<br>
	<select id="selCommander2" class="selTop selCommander" data-idx="2" autocomplete="off">
	<option value="0">None</option>
	</select> Commander 2 (optional)<br>
	(Fielding more than 2 Commanders? Send me an email.)

</div>
<h2><div id="divPointTotal">0 Points</div></h2>
<h3><div id="divCommander">No Commander</div></h3>

<div id="divGates" class="divBattleGroup" data-rtype_id="0" data-bgtype_id="0" data-idx="0" style="display:none">

<table class="tblGates" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thGates" class="thBGNameIdx" align="left" width="240">Shaltari Gates</th>
  <th id="thButtonGates" class="thButtonGates" align="left">
  </th>
  <th id="thMaxGates" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trGates">
<td data-cat_id="0"><table width="215" id="tblUnit_Gaia" class="tblUnit"><tbody>
  <tr align="right"><th data-count="0" data-points="0" class="thSquad">0 pts</th><th class="thGate">Gaia</th></tr>
  <tr>
    <td valign="top" height="80" colspan="3">
    <div class="divSpecTransSel">
    <select class="selSpecTrans" autocomplete="off">
      <option value="80" selected="selected">Gaia&nbsp;&nbsp;&nbsp;&nbsp;[80 points minimum]</option>
    </select>
    </div>
    <div style="" class="divUnitNumber">
    <select class="selSpecTransNumber" autocomplete="off">
      <option selected="selected" value="0">(Select number of units)</option>
      <option value="1">1 Units [80 points]</option>
      <option value="2">2 Units [160 points]</option>
      <option value="3">3 Units [240 points]</option>
    </select>
    </div>
    </td>
  </tr>
</tbody></table></td>
<td data-cat_id="0"><table width="215" id="tblUnit_Eden" class="tblUnit"><tbody>
  <tr align="right"><th data-count="0" data-points="0" class="thSquad">0 pts</th><th class="thGate">Edens</th></tr>
  <tr>
    <td valign="top" height="80" colspan="3">
    <div class="divSpecTransSel">
    <select class="selSpecTrans" autocomplete="off">
      <option value="50" selected="selected">Eden&nbsp;&nbsp;&nbsp;&nbsp;[50 points minimum]</option>
    </select>
    </div>
    <div style="" class="divUnitNumber">
    <select class="selSpecTransNumber" autocomplete="off">
      <option selected="selected" value="0">(Select number of units)</option>
      <option value="1">1 Units [50 points]</option>
      <option value="2">2 Units [100 points]</option>
      <option value="3">3 Units [150 points]</option>
      <option value="4">4 Units [200 points]</option>
      <option value="5">5 Units [250 points]</option>
      <option value="6">6 Units [300 points]</option>
      <option value="7">7 Units [350 points]</option>
      <option value="8">8 Units [400 points]</option>
    </select>
    </div>
    </td>
  </tr>
</tbody></table></td>
<td data-cat_id="0"><table width="215" id="tblUnit_Spirit" class="tblUnit"><tbody>
  <tr align="right"><th data-count="0" data-points="0" class="thSquad">0 pts</th><th class="thGate">Spirits</th></tr>
  <tr>
    <td valign="top" height="80" colspan="3">
    <div class="divSpecTransSel">
    <select class="selSpecTrans">
      <option value="43" selected="selected">Spirit&nbsp;&nbsp;&nbsp;&nbsp;[43 points minimum]</option>
    </select>
    </div>
    <div style="" class="divUnitNumber">
    <select class="selSpecTransNumber">
      <option selected="selected" value="0">(Select number of units)</option>
      <option value="1">1 Units [43 points]</option>
      <option value="2">2 Units [86 points]</option>
      <option value="3">3 Units [129 points]</option>
      <option value="4">4 Units [172 points]</option>
      <option value="5">5 Units [215 points]</option>
      <option value="6">6 Units [258 points]</option>
      <option value="7">7 Units [301 points]</option>
      <option value="8">8 Units [344 points]</option>
    </select>
    </div>
    </td>
  </tr>
</tbody></table></td>
<td data-cat_id="0"><table width="215" id="tblUnit_Haven" class="tblUnit"><tbody>
  <tr align="right"><th data-count="0" data-points="0" class="thSquad">0 pts</th><th class="thGate">Havens</th></tr>
  <tr>
    <td valign="top" height="80" colspan="3">
    <div class="divSpecTransSel">
    <select class="selSpecTrans">
      <option value="20" selected="selected">Haven&nbsp;&nbsp;&nbsp;&nbsp;[20 points minimum]</option>
    </select>
    </div>
    <div style="" class="divUnitNumber">
    <select class="selSpecTransNumber">
      <option selected="selected" value="0">(Select number of units)</option>
      <option value="1">1 Units [20 points]</option>
      <option value="2">2 Units [40 points]</option>
      <option value="3">3 Units [60 points]</option>
      <option value="4">4 Units [80 points]</option>
      <option value="5">5 Units [100 points]</option>
      <option value="6">6 Units [120 points]</option>
      <option value="7">7 Units [140 points]</option>
      <option value="8">8 Units [160 points]</option>
    </select>
    </div>
    </td>
  </tr>
</tbody></table></td>
</tr>
</table>
</div>

<div id="divDrills" class="divBattleGroup" data-rtype_id="0" data-bgtype_id="0" data-idx="0" style="display:none">
<table class="tblDrills" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thDrills" class="thBGNameIdx" align="left" width="240">Drills</th>
  </th>
</tr>
</table>
<table border="1">
<tr id="trDrills">
<td data-cat_id="0">
  <table width="215" id="tblUnit_Drills" class="tblUnit"><tbody>
    <tr align="right"><th data-count="0" data-points="0" class="thSquad">0 pts</th><th>Drills</th></tr>
    <tr>
      <td valign="top" height="80" colspan="3">
      <div class="divSpecTransSel">
      <select class="selSpecTrans">
        <option value="0">[Select Unit]</option>
        <option value="50">Breaching Drill&nbsp;&nbsp;&nbsp;&nbsp;[50 points minimum]</option>
      </select>
      </div>
      <div style="" class="divUnitNumber">
      <select class="selSpecTransNumber">
        <option selected="selected" value="0">0 Units [0 points]</option>
        <option value="1">1 Units [50 points]</option>
        <option value="2">2 Units [100 points]</option>
        <option value="3">3 Units [150 points]</option>
      </select>
      </div>
      </td>
    </tr>
  </tbody></table>
</td>
</tr>
</table>
</div>

<div id="divHQ1" class="divBattleGroup" data-rtype_id="1" data-bgtype_id="1" data-idx="1" style="display:none">
<table class="tblHQ" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHQ1" class="thBGNameIdx" align="left" width="240">Command 1 (This title changes)</th>
  <th id="thButtonHQ1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHQ1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHQ1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divHQ2" class="divBattleGroup" data-rtype_id="1" data-bgtype_id="1" data-idx="2" style="display:none">
<table class="tblHQ" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHQ2" class="thBGNameIdx" align="left" width="240">Command 2 (This title changes)</th>
  <th id="thButtonHQ2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHQ2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHQ2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divHQ3" class="divBattleGroup" data-rtype_id="1" data-bgtype_id="1" data-idx="3" style="display:none">
<table class="tblHQ" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHQ3" class="thBGNameIdx" align="left" width="240">Command 3 (This title changes)</th>
  <th id="thButtonHQ3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHQ3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHQ3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divArmor1" class="divBattleGroup" data-rtype_id="2" data-bgtype_id="2" data-idx="1" style="display:none">
<table class="tblArmor" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thArmor1" class="thBGNameIdx" align="left" width="240">Armor 1 (This title changes)</th>
  <th id="thButtonArmor1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxArmor1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitArmor1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divArmor2" class="divBattleGroup" data-rtype_id="2" data-bgtype_id="2" data-idx="2" style="display:none">
<table class="tblArmor" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thArmor2" class="thBGNameIdx" align="left" width="240">Armor 2 (This title changes)</th>
  <th id="thButtonArmor2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxArmor2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitArmor2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divArmor3" class="divBattleGroup" data-rtype_id="2" data-bgtype_id="2" data-idx="3" style="display:none">
<table class="tblArmor" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thArmor3" class="thBGNameIdx" align="left" width="240">Armor 3 (This title changes)</th>
  <th id="thButtonArmor3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxArmor3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitArmor3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divInfantry1" class="divBattleGroup" data-rtype_id="3" data-bgtype_id="3" data-idx="1" style="display:none">
<table class="tblInfantry" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thInfantry1" class="thBGNameIdx" align="left" width="240">Troops 1 (This title changes)</th>
  <th id="thButtonInfantry1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxInfantry1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitInfantry1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divInfantry2" class="divBattleGroup" data-rtype_id="3" data-bgtype_id="3" data-idx="2" style="display:none">
<table class="tblInfantry" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thInfantry2" class="thBGNameIdx" align="left" width="240">Troops 2 (This title changes)</th>
  <th id="thButtonInfantry2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxInfantry2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitInfantry2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divInfantry3" class="divBattleGroup" data-rtype_id="3" data-bgtype_id="3" data-idx="3" style="display:none">
<table class="tblInfantry" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thInfantry3" class="thBGNameIdx" align="left" width="240">Troops 3 (This title changes)</th>
  <th id="thButtonInfantry3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxInfantry3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitInfantry3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divHeavy1" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="4" data-idx="1" style="display:none">
<table class="tblHeavy" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHeavy1" class="thBGNameIdx" align="left" width="240">Heavy 1 (This title changes)</th>
  <th id="thButtonHeavy1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHeavy1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHeavy1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divHeavy2" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="4" data-idx="2" style="display:none">
<table class="tblHeavy" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHeavy2" class="thBGNameIdx" align="left" width="240">Heavy 2 (This title changes)</th>
  <th id="thButtonHeavy2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHeavy2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHeavy2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divHeavy3" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="4" data-idx="3" style="display:none">
<table class="tblHeavy" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thHeavy3" class="thBGNameIdx" align="left" width="240">Heavy 3 (This title changes)</th>
  <th id="thButtonHeavy3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxHeavy3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitHeavy3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divScout1" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="5" data-idx="1" style="display:none">
<table class="tblScout" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thScout1" class="thBGNameIdx" align="left" width="240">Scout 1 (This title changes)</th>
  <th id="thButtonScout1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxScout1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitScout1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divScout2" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="5" data-idx="2" style="display:none">
<table class="tblScout" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thScout2" class="thBGNameIdx" align="left" width="240">Scout 2 (This title changes)</th>
  <th id="thButtonScout2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxScout2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitScout2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divScout3" class="divBattleGroup" data-rtype_id="4" data-bgtype_id="5" data-idx="3" style="display:none">
<table class="tblScout" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thScout3" class="thBGNameIdx" align="left" width="240">Scout 3 (This title changes)</th>
  <th id="thButtonScout3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxScout3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitScout3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divFleet1" class="divBattleGroup" data-rtype_id="5" data-bgtype_id="6" data-idx="1" style="display:none">
<table class="tblFleet" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thFleet1" class="thBGNameIdx" align="left" width="240">Air 1 (This title changes)</th>
  <th id="thButtonFleet1" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxFleet1" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitFleet1">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divFleet2" class="divBattleGroup" data-rtype_id="5" data-bgtype_id="6" data-idx="2" style="display:none">
<table class="tblFleet" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thFleet2" class="thBGNameIdx" align="left" width="240">Air 2 (This title changes)</th>
  <th id="thButtonFleet2" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxFleet2" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitFleet2">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>

<div id="divFleet3" class="divBattleGroup" data-rtype_id="5" data-bgtype_id="6" data-idx="3" style="display:none">
<table class="tblFleet" border="1" width="100%">
<tr>
  <th width="100" align="right">
    <table border="0">
      <tr>
        <th><input type="checkbox" class="chkActivated"></th>
        <th class="thPoints">0</th>
        <th width="50">Points</th>
      </tr>
    </table>
  </th>
  <th id="thFleet3" class="thBGNameIdx" align="left" width="240">Air 3 (This title changes)</th>
  <th id="thButtonFleet3" class="thButtonHQ" align="left">
  </th>
  <th id="thMaxFleet3" width="105"></th>
  <th width="50"><button class="btnBGRemove">Remove</button></th>
</tr>
</table>
<table border="1">
<tr id="trUnitFleet3">
<td>Command</td>
<td>Scout</td>
<td>Support</td>
</tr>
</table>
</div>
<hr>

<button id="btnClearActivations">Clear Activations</button>
<button id="btnKillPointMode">Kill Point Calculator Mode</button>
<button id="btnPrintMode">Print Mode</button>
<button id="btnStatus">Debug</button>
<br>
<form id="frmSaveList" method="post" action="savelist.php"<?=($uname==""?" disabled":"")?>>
<input type="text" name="lname"> Title of this list<br>
<input type="hidden" id="ljson" name="ljson">
<input type="submit" value="Save List">
</form>
<br>
<form id="frmLoad" method="get" action="index.php">
<input type="text" name="lnum">List Number to load<br>
<button id="btnLoadList">Load List</button>
</form>
<h3>My Lists</h3>

<table>
<?
while ($row = mysqli_fetch_array($resLists)) {
?>
<tr><td><?=$row['list_id']?></td><td><a href="index.php?list=<?=$row['list_id']?>"><?=$row['list_name']?></a></td><td><?=$row['list_date']?></td></tr>
<?
  }
?>
</table>
<div id="dDebug">
Bug list:
<ul>
<li>Strange sh*t happens when picking maxed out troop choices in single battlegroup with compatible transports.
</li>
</ul>
</div>


</body>
</html>

