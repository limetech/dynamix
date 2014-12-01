<div>
	<form method="POST" action="?subaction=pool-start">
	 	<table class="tablesorter" style="margin-top:-21px"><thead><th colspan="2"><b>Create a new storage pool</b></th></thead>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
			<tr align="left">
				<td align="right">Storage name:&nbsp;</td>
				<td align="left"><input type="text" autofocus name="pool[name]" placeholder="Name of storage pool"></td>
			</tr>
			<tr align="left">
				<td align="right">Location:&nbsp;</td>
				<td align="left"><input type="text" id="storage_dir" name="pool[path]" placeholder="Will be created if doesn't exist e.g. /mnt/cache/images "></td>
			</tr>
			<tr align="right">
				<td align="left"></td>
				<td align="left">
						<input type="submit" value="Save">
						<button type="button" onclick="javascript:history.go(-1)" >Cancel</button>
				</td>
			</tr>
			<tr align="left">
				<td align="right">&nbsp;</td>
				<td align="left"><div id="storage_tree"></div></td>
			</tr>
			<input type="hidden" name="sent" value="1" />
		</table>
	</form>
</div>