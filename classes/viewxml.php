<div>
 <form method="POST" id="viewXML">
  <table class="tablesorter" style="margin-top:-21px"><thead><th colspan="2"><b>View Node XML Description</b></th></thead> 
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