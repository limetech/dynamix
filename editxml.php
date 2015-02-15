<?php
$domName = $lv->domain_get_name_by_uuid($uuid);
$xml = $lv->domain_get_xml($domName);
if ($_GET['readonly']) {
	$readonly = 'readonly';
	$method = "View";
	$type = "hidden";
	$return = "Back";
}
else {
	$method = "Edit";
	$type = "submit";
	$return = "Cancel";
}
?>
<link rel="stylesheet" href="/plugins/dynamix.kvm.manager/scripts/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="/plugins/dynamix.kvm.manager/scripts/codemirror/addon/hint/show-hint.css">
<style type="text/css">
	.CodeMirror { border: 1px solid #eee; }
</style>
<div>
	<form method="POST" id="editXML" action="?subaction=domain-create&uuid=<?=$uuid;?>" >
		<table class="tablesorter" style="margin-top:-33px">
			<thead><th><b><?=$method;?> Domain <?=$domName;?> XML Description</b></th></thead>
			<tr>
				<td style="padding: 4px 0;">
					<textarea autofocus spellcheck="false" <?=$readonly?> id="code" name="xmldesc" rows="15" cols="100%"><?=$xml;?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<input type="<?=$type;?>" value="Save">
						<button type="button" onclick="javascript:done()" ><?=$return?></button>
					</div>
				</td>
			</tr>
		</table>
	</form>
</div>
<script src="/plugins/dynamix.kvm.manager/scripts/codemirror/lib/codemirror.js"></script>
<script src="/plugins/dynamix.kvm.manager/scripts/codemirror/addon/hint/show-hint.js"></script>
<script src="/plugins/dynamix.kvm.manager/scripts/codemirror/addon/hint/xml-hint.js"></script>
<script src="/plugins/dynamix.kvm.manager/scripts/codemirror/addon/hint/libvirt-schema.js"></script>
<script src="/plugins/dynamix.kvm.manager/scripts/codemirror/mode/xml/xml.js"></script>
<script>
	$(function() {
		function completeAfter(cm, pred) {
			var cur = cm.getCursor();
			if (!pred || pred()) setTimeout(function() {
				if (!cm.state.completionActive)
					cm.showHint({completeSingle: false});
			}, 100);
			return CodeMirror.Pass;
		}

		function completeIfAfterLt(cm) {
			return completeAfter(cm, function() {
				var cur = cm.getCursor();
				return cm.getRange(CodeMirror.Pos(cur.line, cur.ch - 1), cur) == "<";
			});
		}

		function completeIfInTag(cm) {
			return completeAfter(cm, function() {
				var tok = cm.getTokenAt(cm.getCursor());
				if (tok.type == "string" && (!/['"]/.test(tok.string.charAt(tok.string.length - 1)) || tok.string.length == 1)) return false;
				var inner = CodeMirror.innerMode(cm.getMode(), tok.state).state;
				return inner.tagName;
			});
		}

		var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
			mode: "xml",
			lineNumbers: true,
			extraKeys: {
				"'<'": completeAfter,
				"'/'": completeIfAfterLt,
				"' '": completeIfInTag,
				"'='": completeIfInTag,
				"Ctrl-Space": "autocomplete"
			},
			hintOptions: {schemaInfo: getLibvirtSchema()}
		});
	});
</script>