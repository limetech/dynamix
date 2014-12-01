<div>
	<form method="POST" action="?subaction=volume-save&amp;pool=<?=$pool?>">
	 <table class="tablesorter" style="margin-top:-21px"><thead><th colspan="2"><b>Create a new volume in pool <?=$pool?></b></th></thead>
		<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		<tr align="left">
			<td align="right">Volume name:&nbsp;</td>
			<td align="left"><input type="text" autofocus name="disk[name]" title="name of volume" placeholder="Name of volume without extension"></td>
		</tr>
		<tr align="left">
			<td align="right">Capacity:&nbsp;</td>
			<td align="left"><input type="text" name="disk[capacity]" placeholder="e.g. 10M or 1G"></td>
		</tr>
		<tr align="left">
			<td align="right">Allocation:&nbsp;</td>
			<td align="left"><input type="text" name="disk[allocation]" placeholder="e.g. 10M or 1G"></td>
		</tr>
		<tr align="left">
			<td align="right">Disk type:&nbsp;</td>
			<td align="left">
				<select name="disk[driver]">
					<option value="qcow2">qcow2</option>
					<option value="raw">raw</option>
					<option value="qcow">qcow</option>
				</select>
			</td>
		</tr>
		<tr align="right">
			<td align="left"></td>
			<td align="left">
				<input type="submit" value="Save">
				<button type="button" onclick="javascript:history.go(-1)" >Cancel</button>
			</td>
		</tr>
			<input type="hidden" name="sent" value="1" />
 </table>
</form>
</div>
