<?php
	require_once('/usr/local/emhttp/plugins/dynamix.kvm.manager/classes/libvirt.php');
	require_once('/usr/local/emhttp/plugins/dynamix.kvm.manager/classes/libvirt_helpers.php');
?>

<input type="hidden" name="domain[os]" value="0"/>

<table>

	<tr>
		<td>Architecture:</td>
		<td>
			<select name="domain[arch]" title="Select domain Architecture 32bit or 64bit">
				<option value="x86_64">64bit</option>
				<option value="i686">32bit</option>
			</select>
		</td>

		<td class="windows">HyperV:</td>
		<td class="windows">
			<select name="domain[hyperv]" title="Hyperv tweaks for Windows.  Don't select if trying to passthrough Nvidia card">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select>
		</td>
	</tr>

	<tr>
		<td>CPUs:</td>
		<td>
			<select name="domain[vcpus]" title="define number of vpus for domain">
			<?php
				for ($i = 1; $i <= $maxcpu; $i++)
					echo '<option value='.$i.'>'.$i.'</option>';
			?>
			</select>
		</td>

		<td>Machine:</td>
		<td>
			<select name="domain[machine]" title="Select the machine model.  pc-i440fx will work for most.  Q35 for a newer machine model with PCIE">
				<option value="pc-i440fx-2.1">pc</option>
				<option value="pc-q35-2.1">q35</option>
			</select>
		</td>
	</tr>

	<tr>
		<td>Memory (MiB):</td>
		<td>
			<select name="domain[mem]" title="define the amount memory">
			<?php memOption($maxmem);?>
			</select>
		</td>

		<td>Max. Mem (MiB):</td>
		<td>
			<select name="domain[maxmem]" title="define the maximun amount of memory">
			<?php memOption($maxmem);?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Persistence:</td>
		<td>
			<select name="domain[persistent]" title="Select domain to be permanent or temporary">
				<option value="1">Permanent</option>
				<option value="0">Temporary</option>
			</select>
		</td>

		<td>Clock offset:</td>
		<td>
			<select name="domain[clock]" title="how the guest clock is synchronized to the host">
				<option value="localtime">localtime</option>
				<option value="utc">UTC</option>
			</select>
		</td>
	</tr>

</table>

<table>

	<tr>
		<td>Password:</td>
		<td><input type="password" name="domain[password]" title="password for VNC" placeholder="Password for VNC (optional)" /></td>
	</tr>

	<tr>
		<td>OS Install Media (iso):</td>
		<td>
			<input type="text" onclick='mediaTree(media_tree, media_file, "<?php echo $domain_cfg['MEDIADIR'];?>");' id="media_file" name="media[cdrom]" placeholder="Click and Select cdrom image to install operating system">
			<div id="media_tree" hidden>
		</td>
	</tr>

	<tr class="windows">
		<td>
			<a href="http://alt.fedoraproject.org/pub/alt/virtio-win/latest/images/" target="_blank">Virtio drivers (iso):</a>
			<div style="font-size:10px; margin-top:-8px;">(latest drivers)&nbsp;</div>
		</td>
		<td>
			<input type="text" onclick='mediaTree(drivers_tree, drivers_file, "<?php echo $domain_cfg['MEDIADIR'];?>");' id="drivers_file" name="media[drivers]" placeholder="Download, Click and Select virtio drivers image">
			<div id="drivers_tree" hidden></div>
		</td>
	</tr>

	<tr>
		<td>Disk Settings:</td>
		<td>
			<select name="disk[settings]" onchange="toggleRows('newdisk', this.value, 'existing');">
				<option value="1">Create New Disk</option>
				<option value="0">Use Existing Disk</option>
			</select>
		</td>
	</tr>

	<tr class="newdisk">
		<td>Primary vDisk Location:</td>
		<td>
			<input type="text" onclick='dirTree(new_tree, new_file, "/mnt/");' id="new_file" name="disk[new]" placeholder="Separate sub-folder and image will be created based on Name">
			<div id="new_tree" hidden></div>
		</td>
	</tr>

	<tr class="newdisk">
		<td>Primary vDisk Size:</td>
		<td>
			<input type="text" name="disk[size]" placeholder="e.g. 10M, 1G, 10G...">
		</td>
	</tr>

	<tr class="newdisk">
		<td>Primary vDisk Type:</td>
		<td>
			<select name="disk[driver]" title="type of storage image">
				<option value="raw">raw</option>
				<option value="qcow2">qcow2</option>
			</select>
		</td>
	</tr>

	<tr class="existing" hidden>
		<td>Existing Disk:</td>
		<td>
			<input type="text" onclick='diskTree(existing_tree, existing_file, "/mnt/");' id="existing_file" name="disk[image]" placeholder="Select existing image to use for virtual machine">
			<div id="existing_tree" hidden></div>
		</td>
	</tr>

	<tr>
		<td>Disk dev name:</td>
		<td>
			<input type="text" value="hda" name="disk[dev]" placeholder="name of disk inside vm" title="name of disk inside vm" />
		</td>
	</tr>

	<tr>
		<td>Network Settings:</td>
		<td>
			<select onchange="toggleRows('network', this.value)">
				<option value="0">Auto</option>
				<option value="1">Manual</option>
			</select>
		</td>
	</tr>

	<tr class="network" hidden>
		<td></td>
		<td>
			<table style="margin-top:0px;margin-left:-105px">
				<tr>
					<td>MAC:</td>
					<td>
						<input type="text" name="nic[mac]" title="random mac, you can supply your own" value="<?php echo $lv->generate_random_mac_addr() ?>" id="nic_mac_addr" />
					</td>
				</tr>

				<tr>
					<td>Bridge:</td>
				   <td>
						<input type="text" value="<?=$domain_bridge?>" name="nic[net]" placeholder="name of bridge in unRAID" title="name of bridge in unRAID automatically filled in" />
				   </td>
				</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td>USB Devices:</td>
		<td>
			<select onchange="toggleRows('usb', this.value)">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select>
		</td>
	</tr>

	<tr class="usb" hidden>
		<td></td>
		<td>
			<table style="margin-top:0px;margin-left:-50px;width:auto; max-width:382px; word-wrap: break-word;" >
				<tr>
					<td align="left">
						<?php
							$usb = trim(shell_exec('lsusb'));
							$usb = explode("\n", $usb);
							array_walk($usb, 'usb_checkbox');
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr class="other" hidden>
		<td>unRAID Shares:</td>
		<td>
			<select onchange="toggleRows('shares', this.value)">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select>
		</td>
	</tr>

	<tr class="shares" hidden>
		<td></td>
		<td>
			<table style="margin-top:0px;margin-left:-129px">
				<tr>
					<td>Share:</td>
					<td>
						<input type="text" value="" name="shares[source]" placeholder="e.g. /mnt...(won't work with windows)" title="path of unRAID share" />
					</td>
				</tr>

				<tr>
					<td>Mount tag:</td>
					<td>
						<input type="text" value="" name="shares[target]" placeholder="e.g. shares (name of mount tag inside vm)" title="mount tag inside vm" />
					</td>
				</tr>
			</table>
		</td>
	</tr>


	<tr align="left">
		<td></td>
		<td>
			<input type="submit" value="Create VM" />
			<input type="button" value="Reset Form" onClick="this.form.reset();location.reload()" />
		</td>
	</tr>

</table>
