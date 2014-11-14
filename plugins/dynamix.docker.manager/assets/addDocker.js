
var pathNum = 2;
var portNum = 0;
var varNum = 0;
var currentPath = "/mnt/";

if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}

$(document).mouseup(function (e) {
	var container = $(".fileTree");
	if (!container.is(e.target) && container.has(e.target).length === 0) {
		container.hide();
	}
});

$(document).ready(function() {
	if ($("#NetworkType").val() != 'bridge') {
		$("#titlePort").css({'display': "none"});
	};
	$("#NetworkType").change(function() {
		if ($(this).val() != "bridge" ){
			$("#titlePort").css({'display': "none"});
		}else{
			$("#titlePort").css({'display': "block"});
			
		}
	});
	$("#TemplateSelect").change(function() {
		if ($(this).val() != "" ){
		document.getElementById("#xmlTemplate").value = $(this).val();
		document.forms["formTemplate"].submit();
	}
	});
	$("#toggleMode").addClass("fa-toggle-off");
	$("#toggleMode").removeClass("fa-toggle-on");
});
function rmTemplate(tmpl){
	var name = tmpl.split(/[\/]+/).pop();
	r = confirm("Removing template:  " + name + "\n\nAre you sure?");
	if (r == false){return;}
	document.getElementById("#rmTemplate").value = tmpl;
	document.forms["formTemplate"].submit();
}

function toggleBrowser(N) {
	var el = $('#fileTree' + N);
	$( el ).fileTree({
		root: currentPath,
		script: '/plugins/dynamix.docker.manager/assets/jsFileTree/jqueryFileTree.php',
		folderEvent: 'click',
		expandSpeed: 750,
		collapseSpeed: 750,
		multiFolder: false,
	}, function(file) {
		document.getElementById("hostPath" + N).value = file;
	});
	$( el ).show();
}

function hideBrowser(N) {
	$("#fileTree" + N).css({
		'display': "none"
	});
	$("#fileTree" + N).html("");
	brOpen[N] = false;
}

function addPort(frm) {
	portNum++;
	var hostPort = $("#hostPort1");
	var containerPort = $("#containerPort1");
	var portProtocol = $("#portProtocol1");

	if (portProtocol.val() == "udp"){
		var select = "selected";
	} else {
		var select = "";
	}

	var row = [
	'<tr id="portNum{0}">',
	'<td>',
	'<input type="text" name="containerPort[]" value="{2}" class="textPort" title="Set the port your app uses inside the container.">',
	'</td>',
	'<td>',
	'<input type="text" name="hostPort[]" value="{1}" class="textPort" title="Set the port you use to interact with the app.">',
	'</td>',
	'<td>',
	'<select name="portProtocol[]">',
	'<option value="tcp">tcp</option>',
	'<option value="udp" {3}>udp</option>',
	'</select>',
	'</td>',
	'<td>',
	'<input type="button" value="Remove" onclick="removePort({0});">',
	'</td>',
	'</tr>',
	].join('');

	$('#portRows').append(row.format(portNum, hostPort.val(), containerPort.val(), select));
	hostPort.val('');
	containerPort.val('');
	portProtocol.val('tcp');
}

function removePort(rnum) {
	jQuery('#portNum' + rnum).remove();
}

function addPath(frm) {
	pathNum++;
	
	var hostPath = $("#hostPath1");
	var containerPath = $("#containerPath1");
	var hostWritable = $("#hostWritable1");

	if (hostWritable.val() == "ro"){
		var select = "selected";
	} else {
		var select = "";
	}

	var row = [
	'<tr id="pathNum{0}">',
	'<td>',
	'<input type="text" name="containerPath[]" value="{2}" class="textPath" onclick="hideBrowser({0});" title="The directory your app uses inside the container. Ex: /config">',
	'</td>',
	'<td>',
	'<input type="text" id="hostPath{0}" name="hostPath[]" value="{1}" class="textPath"  onclick="toggleBrowser({0});" title="The directory in your array the app have access to. Ex: /mnt/user/Movies"/>',
	'<div id="fileTree{0}" class="fileTree"></div>',
	'</td>',
	'<td>',
	'<select name="hostWritable[]">',
	'<option value="rw">Read/Write</option>',
	'<option value="ro" {3}>Read Only</option>',
	'</select>',
	'</td>',
	'<td>',
	'<input type="button" value="Remove" onclick="removePath({0});"></td></tr>',
	'</td>',
	'</tr>',
	].join('');
	
	$('#pathRows tbody').append(row.format(pathNum, hostPath.val(), containerPath.val(), select));
	hostPath.val('');
	containerPath.val('');
	hostWritable.val('rw');
}

function removePath(rnum) {
	jQuery('#pathNum' + rnum).remove();
}

function addEnv(frm) {
	varNum++;
	var VariableName = $("#VariableName1");
	var VariableValue = $("#VariableValue1");

	var row = [
	'<tr id="varNum{0}">',
	'<td>',
	'<input type="text" name="VariableName[]" value="{1}" class="textEnv">',
	'</td>',
	'<td>',
	'<input type="text" name="VariableValue[]" value="{2}" class="textEnv">',
	'<input type="button" value="Remove" onclick="removeEnv({0});">',
	'</td>',
	'</tr>',
	].join('');

	$('#envRows tbody').append(row.format(varNum, VariableName.val(), VariableValue.val()));
	VariableName.val('');
	VariableValue.val('');

}

function removeEnv(rnum) {
	jQuery('#varNum' + rnum).remove();
}

function toggleMode(){
	var field = $("#additionalFields");
	state = $( field ).css( "display" );
	if (state == "none") {
		$( field ).css( "display", "block" );
		$("#toggleMode").removeClass("fa-toggle-off");
		$("#toggleMode").addClass("fa-toggle-on");
	} else {
		$( field ).css( "display", "none" );
		$("#toggleMode").removeClass("fa-toggle-on");
		$("#toggleMode").addClass("fa-toggle-off");
	}
}
