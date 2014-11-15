<script>
	function change_divs(what, val) {
		if (val == 1){
			style = 'table-row';
			style2 = 'none';}
		else {
			style = 'none';
			style2 = 'table-row';}
		name = 'setup_'+what;
		name2 = 'setup2_'+what;
		d = document.getElementById(name);
		if (d != null)
			d.style.display = style;
		d = document.getElementById(name2);
		if (d != null)
			d.style.display = style2;
	}
</script>
<?php
$uuid = $_GET['uuid'];
$domName = $lv->domain_get_name_by_uuid($uuid);
?>
<div class="wrap">
	<div class="list">
			<h3>add a new volume to domain <?=$domName?></h3>
				<form method="POST" action="?subaction=disk-save&amp;uuid=<?=$uuid?>&amp;disk=<?=$disk?>">
					<table>
					<tr>
				   	<td align="right">Select:&nbsp;</td>
    					<td>
      					<select name="disk[select]" onchange="change_divs('disk', this.value)">
								<option value="0">Use Existing Disk</option>
								<option value="1">Create New Disk</option>
					      </select>
    					</td>
					</tr>

						<tr align="left" id="setup2_disk" style="display: table-row">
							<td align="right">Disk devices:&nbsp;</td>
							<td align="left">
			<select name="disk[img]" title="select domain image to use for virtual machine">
				<option value="" selected>none selected</option>
<?php
	$pools = $lv->get_storagepools();
	if($pools) {
		for ($i = 0; $i < sizeof($pools); $i++) {
			$pname = $pools[$i];
			$info = $lv->get_storagepool_info($pname);
			if ($info['volume_count'] > 0) {
				$tmp = $lv->storagepool_get_volume_information($pools[$i]);
				$tmp_keys = array_keys($tmp);
				for ($ii = 0; $ii < sizeof($tmp); $ii++) {
					$vname = $tmp_keys[$ii];
					$vpath = $tmp[$vname]['path'];
					echo '<option value="'.base64_encode($vpath).'">'.$vname.'</option>';
				}
			}
		}	
	}
?>
								</select>
							</td>
						</tr>

				<tr id="setup_disk" style="display: none">
				    <td>&nbsp;</td>
    				<td>
        			<table>
						<tr>
							<td align="right">Select Pool:&nbsp;</td>
							<td align="left">
			<select name="disk[pool]" title="select pool">
<?php
	$pools = $lv->get_storagepools();
	if($pools) {
		for ($i = 0; $i < sizeof($pools); $i++) {
			$pname = $pools[$i];
					echo '<option value="'.$pname.'">'.$pname.'</option>';
		}	
	}else {				
			echo '<option value="" selected>none selected</option>';
			}
?>
								</select>
							</td>
						</tr>

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
							<td align="left"><select name="disk[driver]">
														<option value="qcow2">qcow2</option>
														<option value="raw">raw</option>
														<option value="qcow">qcow</option>
		    										</select>
							</td>
	    				</tr>
	    					</table>
    				</td>
				</tr>
	    				<tr>
							<td align="right">Disk name:&nbsp;</td>
							<td>
								<input type="text" value="hdb" name="disk[dev]" placeholder="name of disk inside vm" title="name of disk inside vm" />
							</td>
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