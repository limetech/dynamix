<div class="wrap">
	<div class="list">
			<table class="tablesorter" style="margin-top:-21px"><thead><th colspan="2"><b>Create a new storage pool</b></th></thead> 
				<form method="POST" action="?psubaction=pool-start">
						<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
						<tr align="left">
							<td align="right">Storage name:&nbsp;</td>
							<td align="left"><input type="text" autofocus name="pool[name]" placeholder="Name of storage pool"></td>
						</tr>
						<tr align="left">
							<td align="right">Location:&nbsp;</td>
							<td align="left"><input type="text" name="pool[path]" placeholder="Will be created if doesn't exist e.g. /mnt/cache/images "></td>
						</tr>
						<tr align="right">
							<td align="left"></td>
							<td align="left">
									<input type="submit" class="btn btn-sm btn-default" value="Save">
									<button type="button" class="btn btn-sm btn-default" onclick="javascript:history.go(-1)" >Cancel</button>
							</td>
						</tr>
						<input type="hidden" name="sent" value="1" />
					</table>
				</form>
	</div>
</div>