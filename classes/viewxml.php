<div id="title" <span class='left'>View Node XML Description</span></div>
<div>
	<form method="POST" id="viewXML">
		<table>
			<tr>
				<td>
					<textarea autofocus readonly name="xmldesc" rows="16" cols="100%"><?php echo htmlentities($lv->get_node_device_xml($name, false));?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<button type="button" onclick="javascript:history.go(-1)" >Back</button>
					</div>
				</td>
			</tr>
		</table>
	</form>
</div>
