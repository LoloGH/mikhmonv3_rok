<script type="text/javascript">
// Show / hide the validity field depending on the selected expired mode.
function RequiredVP() {
  var mode = document.getElementById("expmode").value;
  var row = document.getElementById("validity").style;
  var input = document.getElementById("validi");
  if (mode === "rem" || mode === "remc" || mode === "dis" || mode === "disc") {
    row.display = "table-row";
    input.type = "text";
    input.required = true;
    input.focus();
  } else {
    row.display = "none";
    input.type = "hidden";
    input.required = false;
  }
}
$(document).ready(function () {
  if (document.getElementById("expmode")) {
    RequiredVP();
  }
});
// Price and validity of the selected PPPoE profile.
function GetVPP(session) {
  var prof = document.getElementById("pprof").value;
  $("#GetValidPrice").load("./process/getvalidpricep.php?name=" + prof + "&session=" + session + " #getdata");
}
</script>
