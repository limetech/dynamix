<?php
	require_once('/usr/local/emhttp/plugins/dynamix/include/Helpers.php');
	require_once('/usr/local/emhttp/plugins/dynamix.kvm.manager/classes/libvirt.php');
	require_once('/usr/local/emhttp/plugins/dynamix.kvm.manager/classes/libvirt_helpers.php');

	$arrValidMachineTypes = getValidMachineTypes();
	$arrValidPCIDevices = getValidPCIDevices();
	$arrValidUSBDevices = getValidUSBDevices();
	$arrValidDiskDrivers = getValidDiskDrivers();

	$arrValidGPUDevices = array_filter($arrValidPCIDevices, function($arr) { return ($arr['dev_class'] == 'vga'); });
	$arrValidAudioDevices = array_filter($arrValidPCIDevices, function($arr) { return ($arr['dev_class'] == 'audio'); });
	$arrValidOtherDevices = array_filter($arrValidPCIDevices, function($arr) { return ($arr['dev_class'] == 'other'); });

	$arrConfig = [
		'domain' => [
			'persistent' => 1,
			'clock' => 'localtime',
			'os' => 1,
			'arch' => 'x86_64',
			'machine' => 'pc',
			'mem' => 512,
			'maxmem' => 512,
			'password' => '',
			'vcpus' => 1,
			'hyperv' => 1
		],
		'media' => [
			'cdrom' => '',
			'drivers' => ''
		],
		'disk' => [
			0 => [
				'new' => '',
				'size' => '',
				'driver' => 'raw',
				'dev' => 'hdc'
			]
		],
		'gpu' => [
			0 => [
				'id' => 'vnc'
			],
			1 => [
				'id' => ''
			]
		],
		'audio' => [
			0 => [
				'id' => ''
			]
		],
		'pci' => [],
		'nic' => [
			0 => [
				'net' => $domain_bridge,
				'mac' => $lv->generate_random_mac_addr()
			]
		],
		'usb' => []
	];
?>

<input type="hidden" name="domain[persistent]" value="<?=$arrConfig['domain']['persistent']?>">
<input type="hidden" name="domain[clock]" value="<?=$arrConfig['domain']['clock']?>">
<input type="hidden" name="domain[os]" value="<?=$arrConfig['domain']['os']?>">
<input type="hidden" name="domain[arch]" value="<?=$arrConfig['domain']['arch']?>">
<input type="hidden" name="domain[maxmem]" id="domain_maxmem" value="<?=$arrConfig['domain']['maxmem']?>">
<input type="hidden" name="domain[password]" value="<?=$arrConfig['domain']['password']?>">


<table>

	<tr>
		<td>CPUs:</td>
		<td>
			<select name="domain[vcpus]" title="define number of vpus for domain">
			<?php mk_dropdown_options(array_combine(range(1, $maxcpu), range(1, $maxcpu)), $arrConfig['domain']['vcpus']); ?>
			</select>
		</td>

		<td>Machine:</td>
		<td>
			<select name="domain[machine]" title="Select the machine model.  i440fx will work for most.  Q35 for a newer machine model with PCIE">
			<?php mk_dropdown_options($arrValidMachineTypes, $arrConfig['domain']['machine']); ?>
			</select>
		</td>
	</tr>

	<tr>
		<td>Memory (MiB):</td>
		<td>
			<select name="domain[mem]" id="domain_mem" title="define the amount memory">
			<?php
				for ($i = 1; $i <= ($maxmem*2); $i++) {
					$mem = ($i*512);
					echo mk_option($arrConfig['domain']['mem'], $mem, $mem);
				}
			?>
			</select>
		</td>

		<td>HyperV:</td>
		<td>
			<select name="domain[hyperv]" id="hyperv" title="Hyperv tweaks for Windows.  Don't select if trying to passthrough Nvidia card">
			<?php mk_dropdown_options(['No', 'Yes'], $arrConfig['domain']['hyperv']); ?>
			</select>
		</td>
	</tr>

	<!--tr>
		<td>Persistence:</td>
		<td>
			<select name="domain[persistent]" title="Select domain to be permanent or temporary">
				<option value="1">Permanent</option>
				<option value="0">Temporary</option>
			</select>
		  <label style="margin-top:0px;margin-left:47px">Clock offset:&nbsp;</label>
			<select name="domain[clock]" title="how the guest clock is synchronized to the host">
				<option value="localtime">localtime</option>
				<option value="utc">UTC</option>
			</select>
		</td>
	</tr>

	<tr>
		<td>Autostart:</td>
		<td>
			<select name="domain[autostart]" title="Select domain autostart on boot">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select>
			   <span class="windows">
			   <label style="margin-top:0px;margin-left:100px">HyperV: &nbsp;</label>
				<select name="domain[hyperv]" title="Hyperv tweaks for Windows.  Don't select if trying to passthrough Nvidia card">
					<option value="0">No</option>
					<option value="1">Yes</option>
				</select>
				</span>
	</tr-->

</table>

<table>

	<tr>
		<td>OS Install Media (iso):</td>
		<td>
			<input type="text" data-pickcloseonfile="true" data-pickfilter="iso" data-pickroot="<?=$domain_cfg['MEDIADIR']?>" name="media[cdrom]" value="<?=$arrConfig['media']['cdrom']?>" placeholder="Click and Select cdrom image to install operating system">
		</td>
	</tr>

	<tr>
		<td>
			<a href="http://alt.fedoraproject.org/pub/alt/virtio-win/latest/images/" target="_blank">Virtio drivers (iso):</a>
			<div style="font-size:10px; margin-top:-8px;">(latest drivers)&nbsp;</div>
		</td>
		<td>
			<input type="text" data-pickcloseonfile="true" data-pickfilter="iso" data-pickroot="<?=$domain_cfg['MEDIADIR']?>" name="media[drivers]" value="<?=$arrConfig['media']['drivers']?>" placeholder="Download, Click and Select virtio drivers image">
		</td>
	</tr>

	<? foreach ($arrConfig['disk'] as $i => $arrDisk) {
		$strLabel = 'Primary';
		if ($i > 0) {
			$strLabel = appendOrdinalSuffix($i + 1);
		}

		?>
		<tr>
			<td><?=$strLabel?> vDisk Location:</td>
			<td>
				<input type="text" data-pickcloseonfile="true" data-pickfolders="true" data-pickfilter="img,qcow,qcow2" data-pickroot="/mnt/" name="disk[<?=$i?>][new]" class="disk" id="disk_<?=$i?>" value="<?=$arrDisk['new']?>" placeholder="Separate sub-folder and image will be created based on Name">
			</td>
		</tr>

		<tr class="disk_<?=$i?>_new">
			<td><?=$strLabel?> vDisk Size:</td>
			<td>
				<input type="text" name="disk[<?=$i?>][size]" value="<?=$arrDisk['size']?>" placeholder="e.g. 10M, 1G, 10G...">
			</td>
		</tr>

		<tr class="disk_<?=$i?>_new">
			<td><?=$strLabel?> vDisk Type:</td>
			<td>
				<select name="disk[<?=$i?>][driver]" title="type of storage image">
				<?php mk_dropdown_options($arrValidDiskDrivers, $arrDisk['driver']); ?>
				</select>
			</td>
		</tr>

		<input type="hidden" name="disk[<?=$i?>][dev]" value="<?=$arrDisk['dev']?>">
	<? } ?>

	<? foreach ($arrConfig['gpu'] as $i => $arrGPU) {
		$strLabel = 'Primary';
		if ($i > 0) {
			$strLabel = appendOrdinalSuffix($i + 1);
		}

		?>
		<tr>
			<td><?=$strLabel?> Graphics Card:</td>
			<td>
				<select name="gpu[<?=$i?>][id]" class="gpu" id="gpu_<?=$i?>">
				<?php
					if ($i == 0) {
						// Only the first video card can be VNC
						echo mk_option($arrGPU['id'], 'vnc', 'VNC');
					} else {
						echo mk_option($arrGPU['id'], '', 'None');
					}

					foreach($arrValidGPUDevices as $x) {
						echo mk_option($arrGPU['id'], $x['dev_id'], trim($x['dev_id'] . ' | ' . $x['dev_name'], ' |'));
					}
				?>
				</select>
			</td>
		</tr>
	<? } ?>

	<tr>
		<td>Sound Card:</td>
		<td>
			<select name="audio[0][id]">
			<?php
				echo mk_option($arrConfig['audio'][0]['id'], '', 'None');

				foreach($arrValidAudioDevices as $x) {
					echo mk_option($arrConfig['audio'][0]['id'], $x['dev_id'], trim($x['dev_id'] . ' | ' . $x['dev_name'], ' |'));
				}
			?>
			</select>
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
		<td>MAC:</td>
		<td>
			<input type="text" name="nic[0][mac]" value="<?=$arrConfig['nic'][0]['mac']?>" title="random mac, you can supply your own" />
		</td>
	</tr>

	<tr class="network" hidden>
		<td>Bridge:</td>
		<td>
			<input type="text" name="nic[0][net]" value="<?=$arrConfig['nic'][0]['net']?>" placeholder="name of bridge in unRAID" title="name of bridge in unRAID automatically filled in" />
		</td>
	</tr>

	<tr>
		<td>USB Devices:</td>
		<td>
		<?php
			if (!empty($arrValidUSBDevices)) {
				foreach($arrValidUSBDevices as $i=>$x) {
				?>
				<label for="usb<?=$i?>"><input type="checkbox" name="usb[]" id="usb<?=$i?>" value="<?=$x['dev_id']?>" <?php if (in_array($x['dev_id'], $arrConfig['usb'])) echo 'checked="checked"'; ?>/> <?=$x['dev_id']?> | <?=$x['dev_name']?></label><br/>
				<?php
				}
			} else {
				echo "<i>None available</i>";
			}
		?>
		</td>
	</tr>


	<tr>
		<td></td>
		<td>
			<input type="submit" value="Create VM" />
			<input type="button" value="Reset Form" onClick="this.form.reset();location.reload()" />
		</td>
	</tr>

</table>

<script type="text/javascript">
$(function() {
	$("#domain_mem").change(function () {
		$("#domain_maxmem").val($(this).val());
	});

	$("#form_content").on("change", ".disk", function () {
		var input = $(this);
		var config = input.data();

		if (config.pickfilter) {
			var isFile = false;

			//TODO - check server-side if file really exists or not
			$.each(config.pickfilter.split(","), function(index, item) {
				if (input.val().substr((item.length+1) * -1) == "."+item) {
					isFile = true;
				}
			});

			var disk_new = $("." + input.attr('id') + "_new");

			if (isFile) {
				slideUpRows(disk_new);

				input.attr('name', input.attr('name').replace('new', 'image'));
			} else {
				slideDownRows(disk_new);

				input.attr('name', input.attr('name').replace('image', 'new'));
			}
		}
	});

	$("#form_content").on("change", ".gpu", function () {
		var myvalue = $(this).val();
		var myid = $(this).attr('id');
		var mylabel = $(this).children('option:selected').text();

		if (mylabel.indexOf('NVIDIA ') > -1) {
			$("#hyperv").val(0);
		}

		$(".gpu").each(function () {
			if (myid != $(this).attr('id')) {
				if (myvalue == $(this).val()) {
					$(this).prop("selectedIndex", 0);
				}
			}
		});
	});

	$("#form_content").on("click", "input[data-pickroot]", universalTreePicker);
});
</script>
