<?php
	require_once('/usr/local/emhttp/plugins/dynamix/include/Helpers.php');
	require_once('/usr/local/emhttp/plugins/dynamix.vm.manager/classes/libvirt.php');
	require_once('/usr/local/emhttp/plugins/dynamix.vm.manager/classes/libvirt_helpers.php');

	$arrValidMachineTypes = getValidMachineTypes();
	$arrValidPCIDevices = getValidPCIDevices();
	$arrValidUSBDevices = getValidUSBDevices();
	$arrValidDiskDrivers = getValidDiskDrivers();

	$arrValidGPUDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'vga'); });
	$arrValidAudioDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'audio'); });
	$arrValidOtherDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'other'); });

	$strCPUModel = getHostCPUModel();

	$arrConfigDefaults = [
		'domain' => [
			'persistent' => 1,
			'uuid' => $lv->domain_generate_uuid(),
			'clock' => 'localtime',
			'os' => 'windows',
			'arch' => 'x86_64',
			'machine' => 'pc',
			'mem' => 512 * 1024,
			'maxmem' => 512 * 1024,
			'password' => '',
			'cpumode' => 'host-passthrough',
			'vcpus' => 1,
			'vcpu' => [0],
			'hyperv' => 1,
			'ovmf' => 0
		],
		'media' => [
			'cdrom' => '',
			'drivers' => ''
		],
		'disk' => [
			[
				'new' => '',
				'size' => '',
				'driver' => 'raw',
				'dev' => 'hdc'
			]
		],
		'gpu' => [
			[
				'id' => 'vnc'
			]
		],
		'audio' => [
			[
				'id' => ''
			]
		],
		'pci' => [],
		'nic' => [
			[
				'network' => $domain_bridge,
				'mac' => $lv->generate_random_mac_addr()
			]
		],
		'usb' => [],
		'shares' => [
			[
				'source' => '',
				'target' => ''
			]
		]
	];

	// If we are editing a existing VM load it's existing configuration details
	$arrExistingConfig = (!empty($_GET['uuid']) ? domain_to_config($_GET['uuid']) : []);

	// Active config for this page
	$arrConfig = array_replace_recursive($arrConfigDefaults, $arrExistingConfig);


	$boolRunning = (!empty($arrConfig['domain']['state']) && $arrConfig['domain']['state'] == 'running');


	if (array_key_exists('createvm', $_POST)) {
		//DEBUG
		file_put_contents('/tmp/debug_libvirt_postparams.txt', print_r($_POST, true));
		file_put_contents('/tmp/debug_libvirt_newxml.xml', $lv->config_to_xml($_POST));

		$tmp = $lv->domain_new($_POST['domain'], $_POST['media'], $_POST['nic'], $_POST['disk'], $_POST['usb'], $_POST['shares'], $_POST['gpu'], $_POST['audio']);
		if (!$tmp){
			$arrResponse = ['error' => $lv->get_last_error()];
		} else {
			$arrResponse = ['success' => true];

			// Fire off the vnc popup if available
			$res = $lv->get_domain_by_name($_POST['domain']['name']);
			$vncport = $lv->domain_get_vnc_port($res);
			$wsport = $lv->domain_get_ws_port($res);

			if ($vncport >= 0) {
				$vnc = '/plugins/dynamix.vm.manager/vnc.html?autoconnect=true&host='.$var['IPADDR'].'&port='.$wsport;
				$arrResponse['vncurl'] = $vnc;
			}
		}

		echo json_encode($arrResponse);
		exit;
	}

	if (array_key_exists('updatevm', $_POST)) {
		//DEBUG
		file_put_contents('/tmp/debug_libvirt_postparams.txt', print_r($_POST, true));
		file_put_contents('/tmp/debug_libvirt_updatexml.xml', $lv->config_to_xml($_POST));

		// Backup xml for existing domain in ram
		$strOldXML = '';
		$boolOldAutoStart = false;
		$res = $lv->domain_get_name_by_uuid($_POST['domain']['uuid']);
		if ($res) {
			$strOldXML = $lv->domain_get_xml($res);
			$boolOldAutoStart = $lv->domain_get_autostart($res);

			//DEBUG
			file_put_contents('/tmp/debug_libvirt_oldxml.xml', $strOldXML);
		}

		// Remove existing domain
		$lv->domain_undefine($res);

		// Save new domain
		$tmp = $lv->domain_define($lv->config_to_xml($_POST));
		if (!$tmp){
			$strLastError = $lv->get_last_error();

			// Failure -- try to restore existing domain
			$tmp = $lv->domain_define($strOldXML);
			if ($tmp) $lv->domain_set_autostart($tmp, $boolOldAutoStart);

			$arrResponse = ['error' => $strLastError];
		} else {
			$lv->domain_set_autostart($tmp, $_POST['domain']['autostart'] == 1);

			$arrResponse = ['success' => true];
		}

		echo json_encode($arrResponse);
		exit;
	}
?>

<style type="text/css">
	.four label {
		float: left;
		display: table-cell;
		width: 25%;
	}
	.four label:nth-child(4n+4) {
		float: none;
		clear: both;
	}
</style>

<input type="hidden" name="domain[persistent]" value="<?=$arrConfig['domain']['persistent']?>">
<input type="hidden" name="domain[uuid]" value="<?=$arrConfig['domain']['uuid']?>">
<input type="hidden" name="domain[clock]" id="domain_clock" value="<?=$arrConfig['domain']['clock']?>">
<input type="hidden" name="domain[arch]" value="<?=$arrConfig['domain']['arch']?>">

<table>
	<tr>
		<td>Operating System:</td>
		<td>
			<select name="domain[os]" id="domain_os" title="define the base OS">
			<?php mk_dropdown_options(['windows' => 'Windows', 'other' => 'Other'], $arrConfig['domain']['os']); ?>
			</select>
		</td>
	</tr>
</table>

<table>
	<tr class="advanced">
		<td>CPU Mode:</td>
		<td>
			<select name="domain[cpumode]" title="define type of cpu presented to this vm">
			<?php mk_dropdown_options(['host-passthrough' => 'Host Passthrough (' . $strCPUModel . ')', 'emulated' => 'Emulated (QEMU64)'], $arrConfig['domain']['cpumode']); ?>
			</select>
		</td>
	</tr>

	<tr class="advanced">
		<td>CPU Pinning:</td>
		<td>
			<div class="textarea four">
			<?php
				for ($i = 0; $i < $maxcpu; $i++) {
					$extra = '';
					if (in_array($i, $arrConfig['domain']['vcpu'])) {
						$extra .= ' checked="checked"';
						if (count($arrConfig['domain']['vcpu']) == 1) {
							$extra .= ' disabled="disabled"';
						}
					}
				?>
				<label for="vcpu<?=$i?>"><input type="checkbox" name="domain[vcpu][]" class="domain_vcpu" id="vcpu<?=$i?>" value="<?=$i?>" <?=$extra;?>/> Core <?=$i?></label>
			<?php } ?>
			</div>
		</td>
	</tr>

	<tr class="basic">
		<td>CPUs:</td>
		<td>
			<select name="domain[vcpus]" id="domain_vcpus" title="define number of cpu cores used by this vm">
			<?php mk_dropdown_options(array_combine(range(1, $maxcpu), range(1, $maxcpu)), $arrConfig['domain']['vcpus']); ?>
			</select>
		</td>
	</tr>
</table>

<table>
	<tr>
		<td>Initial Memory:</td>
		<td>
			<select name="domain[mem]" id="domain_mem" title="define the amount memory">
			<?php
				for ($i = 1; $i <= ($maxmem*2); $i++) {
					$label = ($i * 512) . ' MB';
					$value = $i * 512 * 1024;
					echo mk_option($arrConfig['domain']['mem'], $value, $label);
				}
			?>
			</select>
		</td>

		<td class="advanced">Max Memory:</td>
		<td class="advanced">
			<select name="domain[maxmem]" id="domain_maxmem" title="define the maximum amount of memory">
			<?php
				for ($i = 1; $i <= ($maxmem*2); $i++) {
					$label = ($i * 512) . ' MB';
					$value = $i * 512 * 1024;
					echo mk_option($arrConfig['domain']['maxmem'], $value, $label);
				}
			?>
			</select>
		</td>
		<td></td>
	</tr>
</table>

<table>
	<tr class="advanced">
		<td>Machine:</td>
		<td>
			<select name="domain[machine]" id="domain_machine" title="Select the machine model.  i440fx will work for most.  Q35 for a newer machine model with PCIE">
			<?php mk_dropdown_options($arrValidMachineTypes, $arrConfig['domain']['machine']); ?>
			</select>
		</td>
	</tr>

	<tr class="advanced">
		<td>BIOS:</td>
		<td>
			<select name="domain[ovmf]" title="Select the BIOS.  SeaBIOS will work for most.  OVMF requires a UEFI-compatable OS (e.g. Windows 8/2012, newer Linux distros) and if using graphics device passthrough it too needs UEFI" <? if (!empty($arrConfig['domain']['state'])) echo 'disabled="disabled"'; ?>>
			<?php
				echo mk_option($arrConfig['domain']['ovmf'], '0', 'SeaBIOS');

				if (file_exists('/usr/share/qemu/ovmf-x64/OVMF-pure-efi.fd')) {
					echo mk_option($arrConfig['domain']['ovmf'], '1', 'OVMF');
				} else {
					echo mk_option('', '0', 'OVMF (Not Available)', 'disabled="disabled"');
				}
			?>
			</select>
		</td>
	</tr>
</table>

<table class="domain_os windows">
	<tr class="advanced">
		<td>Hyper-V:</td>
		<td>
			<select name="domain[hyperv]" id="hyperv" title="Hyperv tweaks for Windows.  Don't select if trying to passthrough Nvidia card">
			<?php mk_dropdown_options(['No', 'Yes'], $arrConfig['domain']['hyperv']); ?>
			</select>
		</td>
	</tr>
</table>

<table>
	<tr>
		<td>OS Install ISO:</td>
		<td>
			<input type="text" data-pickcloseonfile="true" data-pickfilter="iso" data-pickroot="<?=$domain_cfg['MEDIADIR']?>" name="media[cdrom]" value="<?=$arrConfig['media']['cdrom']?>" placeholder="Click and Select cdrom image to install operating system">
		</td>
	</tr>
</table>

<table class="domain_os windows">
	<tr>
		<td><a href="http://alt.fedoraproject.org/pub/alt/virtio-win/latest/images/" target="_blank">VirtIO Drivers ISO:</a></td>
		<td>
			<input type="text" data-pickcloseonfile="true" data-pickfilter="iso" data-pickroot="<?=$domain_cfg['MEDIADIR']?>" name="media[drivers]" value="<?=$arrConfig['media']['drivers']?>" placeholder="Download, Click and Select virtio drivers image">
		</td>
	</tr>
</table>


<? foreach ($arrConfig['disk'] as $i => $arrDisk) {
	$strLabel = ($i > 0) ? appendOrdinalSuffix($i + 1) : 'Primary';

	?>
	<table data-category="vDisk" data-multiple="true" data-minimum="1" data-maximum="24" data-index="<?=$i?>" data-prefix="<?=$strLabel?>">
		<tr>
			<td>vDisk Location:</td>
			<td>
				<input type="text" data-pickcloseonfile="true" data-pickfolders="true" data-pickfilter="img,qcow,qcow2" data-pickroot="/mnt/" name="disk[<?=$i?>][new]" class="disk" id="disk_<?=$i?>" value="<?=$arrDisk['new']?>" placeholder="Separate sub-folder and image will be created based on Name">
			</td>
		</tr>

		<tr class="disk_file_options">
			<td>vDisk Size:</td>
			<td>
				<input type="text" name="disk[<?=$i?>][size]" value="<?=$arrDisk['size']?>" placeholder="e.g. 10M, 1G, 10G...">
			</td>
		</tr>

		<tr class="advanced disk_file_options">
			<td>vDisk Type:</td>
			<td>
				<select name="disk[<?=$i?>][driver]" title="type of storage image">
				<?php mk_dropdown_options($arrValidDiskDrivers, $arrDisk['driver']); ?>
				</select>
			</td>
		</tr>

		<input type="hidden" name="disk[<?=$i?>][dev]" value="<?=$arrDisk['dev']?>">
	</table>
<? } ?>
<script type="text/html" id="tmplvDisk">
	<table>
		<tr>
			<td>vDisk Location:</td>
			<td>
				<input type="text" data-pickcloseonfile="true" data-pickfolders="true" data-pickfilter="img,qcow,qcow2" data-pickroot="/mnt/" name="disk[{{INDEX}}][new]" class="disk" id="disk_{{INDEX}}" value="" placeholder="Separate sub-folder and image will be created based on Name">
			</td>
		</tr>

		<tr class="disk_file_options">
			<td>vDisk Size:</td>
			<td>
				<input type="text" name="disk[{{INDEX}}][size]" value="" placeholder="e.g. 10M, 1G, 10G...">
			</td>
		</tr>

		<tr class="advanced disk_file_options">
			<td>vDisk Type:</td>
			<td>
				<select name="disk[{{INDEX}}][driver]" title="type of storage image">
				<?php mk_dropdown_options($arrValidDiskDrivers, ''); ?>
				</select>
			</td>
		</tr>

		<input type="hidden" name="disk[{{INDEX}}][dev]" value="">
	</table>
</script>


<? foreach ($arrConfig['shares'] as $i => $arrShare) {
	$strLabel = ($i > 0) ? appendOrdinalSuffix($i + 1) : '';

	?>
	<table class="domain_os other" data-category="Share" data-multiple="true" data-minimum="1" data-index="<?=$i?>" data-prefix="<?=$strLabel?>">
		<tr class="advanced">
			<td>unRAID Share:</td>
			<td>
				<input type="text" data-pickfolders="true" data-pickfilter="NO_FILES_FILTER" data-pickroot="/mnt/" value="<?=$arrShare['source']?>" name="shares[<?=$i?>][source]" placeholder="e.g. /mnt/user/..." title="path of unRAID share" />
			</td>
		</tr>

		<tr class="advanced">
			<td>unRAID Mount tag:</td>
			<td>
				<input type="text" value="<?=$arrShare['target']?>" name="shares[<?=$i?>][target]" placeholder="e.g. shares (name of mount tag inside vm)" title="mount tag inside vm" />
			</td>
		</tr>
	</table>
<? } ?>
<script type="text/html" id="tmplShare">
	<table class="domain_os other">
		<tr class="advanced">
			<td>unRAID Share:</td>
			<td>
				<input type="text" data-pickfolders="true" data-pickfilter="NO_FILES_FILTER" data-pickroot="/mnt/" value="" name="shares[{{INDEX}}][source]" placeholder="e.g. /mnt/user/..." title="path of unRAID share" />
			</td>
		</tr>

		<tr class="advanced">
			<td>unRAID Mount tag:</td>
			<td>
				<input type="text" value="" name="shares[{{INDEX}}][target]" placeholder="e.g. shares (name of mount tag inside vm)" title="mount tag inside vm" />
			</td>
		</tr>
	</table>
</script>


<? foreach ($arrConfig['gpu'] as $i => $arrGPU) {
	$strLabel = ($i > 0) ? appendOrdinalSuffix($i + 1) : '';

	?>
	<table data-category="Graphics_Card" data-multiple="true" data-minimum="1" data-maximum="<?=count($arrValidGPUDevices)+1?>" data-index="<?=$i?>" data-prefix="<?=$strLabel?>">
		<tr>
			<td>Graphics Card:</td>
			<td>
				<select name="gpu[<?=$i?>][id]" class="gpu">
				<?php
					if ($i == 0) {
						// Only the first video card can be VNC
						echo mk_option($arrGPU['id'], 'vnc', 'VNC');
					} else {
						echo mk_option($arrGPU['id'], '', 'None');
					}

					foreach($arrValidGPUDevices as $arrDev) {
						echo mk_option($arrGPU['id'], $arrDev['id'], trim($arrDev['name'] . ' | ' . $arrDev['id'], ' |'));
					}
				?>
				</select>
			</td>
		</tr>

		<? if ($i == 0) { ?>
		<tr class="vncpassword">
			<td>VNC Password:</td>
			<td><input type="password" name="domain[password]" title="password for VNC" placeholder="Password for VNC (optional)" /></td>
		</tr>
		<? } ?>
	</table>
<? } ?>
<script type="text/html" id="tmplGraphics_Card">
	<table>
		<tr>
			<td>Graphics Card:</td>
			<td>
				<select name="gpu[{{INDEX}}][id]" class="gpu">
				<?php
					echo mk_option('', '', 'None');

					foreach($arrValidGPUDevices as $arrDev) {
						echo mk_option('', $arrDev['id'], trim($arrDev['name'] . ' | ' . $arrDev['id'], ' |'));
					}
				?>
				</select>
			</td>
		</tr>
	</table>
</script>


<? foreach ($arrConfig['audio'] as $i => $arrAudio) {
	$strLabel = ($i > 0) ? appendOrdinalSuffix($i + 1) : '';

	?>
	<table data-category="Sound_Card" data-multiple="true" data-minimum="1" data-maximum="<?=count($arrValidAudioDevices)?>" data-index="<?=$i?>" data-prefix="<?=$strLabel?>">
		<tr>
			<td>Sound Card:</td>
			<td>
				<select name="audio[<?=$i?>][id]" class="audio">
				<?php
					echo mk_option($arrAudio['id'], '', 'None');

					foreach($arrValidAudioDevices as $arrDev) {
						echo mk_option($arrAudio['id'], $arrDev['id'], trim($arrDev['name'] . ' | ' . $arrDev['id'], ' |'));
					}
				?>
				</select>
			</td>
		</tr>
	</table>
<? } ?>
<script type="text/html" id="tmplSound_Card">
	<table>
		<tr>
			<td>Sound Card:</td>
			<td>
				<select name="audio[{{INDEX}}][id]" class="audio">
				<?php
					foreach($arrValidAudioDevices as $arrDev) {
						echo mk_option('', $arrDev['id'], trim($arrDev['name'] . ' | ' . $arrDev['id'], ' |'));
					}
				?>
				</select>
			</td>
		</tr>
	</table>
</script>


<? foreach ($arrConfig['nic'] as $i => $arrNic) {
	$strLabel = ($i > 0) ? appendOrdinalSuffix($i + 1) : '';

	?>
	<table data-category="Network" data-multiple="true" data-minimum="1" data-index="<?=$i?>" data-prefix="<?=$strLabel?>">
		<tr class="advanced">
			<td>Network MAC:</td>
			<td>
				<input type="text" name="nic[<?=$i?>][mac]" value="<?=$arrNic['mac']?>" title="random mac, you can supply your own" />
			</td>
		</tr>

		<tr class="advanced">
			<td>Network Bridge:</td>
			<td>
				<input type="text" name="nic[<?=$i?>][network]" value="<?=$arrNic['network']?>" placeholder="name of bridge in unRAID" title="name of bridge in unRAID automatically filled in" />
			</td>
		</tr>
	</table>
<? } ?>
<script type="text/html" id="tmplNetwork">
	<table>
		<tr class="advanced">
			<td>Network MAC:</td>
			<td>
				<input type="text" name="nic[{{INDEX}}][mac]" value="" title="random mac, you can supply your own" />
			</td>
		</tr>

		<tr class="advanced">
			<td>Network Bridge:</td>
			<td>
				<input type="text" name="nic[{{INDEX}}][network]" value="" placeholder="name of bridge in unRAID" title="name of bridge in unRAID automatically filled in" />
			</td>
		</tr>
	</table>
</script>


<table>
	<tr>
		<td>USB Devices:</td>
		<td>
			<div class="textarea">
			<?php
				if (!empty($arrValidUSBDevices)) {
					foreach($arrValidUSBDevices as $i => $arrDev) {
					?>
					<label for="usb<?=$i?>"><input type="checkbox" name="usb[]" id="usb<?=$i?>" value="<?=$arrDev['id']?>" <?php if (count(array_filter($arrConfig['usb'], function($arr) use ($arrDev) { return ($arr['id'] == $arrDev['id']); }))) echo 'checked="checked"'; ?>/> <?=$arrDev['name']?><span class="advanced"> | <?=$arrDev['id']?></span></label><br/>
					<?php
					}
				} else {
					echo "<i>None available</i>";
				}
			?>
			</div>
		</td>
	</tr>
</table>

<table>
	<tr>
		<td></td>
		<td>
		<? if (!$boolRunning) { ?>
			<? if (!empty($arrConfig['domain']['name'])) { ?>
				<input type="hidden" name="updatevm" value="1" />
				<input type="button" value="Update" busyvalue="Updating..." readyvalue="Update" id="btnSubmit" />
			<? } else { ?>
				<input type="hidden" name="createvm" value="1" />
				<input type="button" value="Create" busyvalue="Creating..." readyvalue="Create" id="btnSubmit" />
			<? } ?>
				<input type="button" value="Cancel" id="btnCancel" />
		<? } else { ?>
			<input type="button" value="Done" id="btnCancel" />
		<? } ?>
		</td>
	</tr>
</table>


<script type="text/javascript">
$(function() {
	$("#form_content #domain_mem").change(function changeMemEvent() {
		$("#domain_maxmem").val($(this).val());
	});

	$("#form_content .domain_vcpu").change(function changeVCPUEvent() {
		var $cores = $(".domain_vcpu:checked");
		$("#domain_vcpus").val($cores.length);

		if ($cores.length == 1) {
			$cores.prop("disabled", true);
		} else {
			$(".domain_vcpu").prop("disabled", false);
		}
	});

	$("#form_content #domain_vcpus").change(function changeVCPUsEvent() {
		var cores = $(this).val();

		$(".domain_vcpu")
			.prop('checked', false)
			.prop('disabled', false)
			.slice(0, cores)
			.prop('checked', true)
			.prop('disabled', (cores == 1));

	});

	$("#form_content #domain_maxmem").change(function changeMaxMemEvent() {
		if (parseFloat($(this).val()) < parseFloat($("#domain_mem").val())) {
			$("#domain_mem").val($(this).val());
		}
	});

	$("#form_content").on("change", ".disk", function changeDiskEvent() {
		var $input = $(this);
		var config = $input.data();

		if (config.hasOwnProperty('pickfilter')) {
			var isFile = false;

			//TODO - check server-side if file really exists or not
			$.each(config.pickfilter.split(","), function(index, item) {
				if ($input.val().substr((item.length+1) * -1) == "."+item) {
					isFile = true;
					return true;
				}
			});

			var $other_sections = $input.closest('table').find('.disk_file_options');

			if (isFile) {
				slideUpRows($other_sections);

				$other_sections.filter('.advanced').removeClass('advanced').addClass('wasadvanced');

				$input.attr('name', $input.attr('name').replace('new', 'image'));
			} else {
				$other_sections.filter('.wasadvanced').removeClass('wasadvanced').addClass('advanced');

				slideDownRows($other_sections.not(isVMAdvancedMode() ? '.basic' : '.advanced'));

				$input.attr('name', $input.attr('name').replace('image', 'new'));
			}
		}
	});

	$("#form_content").on("change", ".gpu", function changeGPUEvent() {
		var myvalue = $(this).val();
		var mylabel = $(this).children('option:selected').text();

		if ($(".gpu option[value='vnc']:selected").length) {
			slideDownRows($('.vncpassword'));
		} else {
			slideUpRows($('.vncpassword'));
		}

		if (mylabel.indexOf('NVIDIA ') > -1) {
			$("#hyperv").val(0).change();
		}

		$(".gpu").not(this).each(function () {
			if (myvalue == $(this).val()) {
				$(this).prop("selectedIndex", 0).change();
			}
		});
	});

	$("#form_content input[data-pickroot]").click(universalTreePicker);

	$("#form_content #btnSubmit").click(function frmSubmit() {
		var $button = $(this);

		//TODO: form validation

		var $form = $('#domain_template').closest('form');
		var postdata = $form.serialize().replace(/'/g,"%27");

		$form.find('input').prop('disabled', true);
		$button.val($button.attr('busyvalue'));

		$.post("<?=str_replace('/usr/local/emhttp', '', __FILE__)?>", postdata, function( data ) {
			if (data.success) {
				if (data.vncurl) {
					window.open(data.vncurl, '_blank', 'scrollbars=yes,resizable=yes');
				}
				done();
			}
			if (data.error) {
				alert("Error creating VM: " + data.error);
				$form.find('input').prop('disabled', false);
				$button.val($button.attr('readyvalue'));
			}
		}, "json");
	});

	$("#form_content #btnCancel").click(done);


	// Fire events below once upon showing page
	$("#form_content table[data-category]").each(function () {
		var category = $(this).data('category');

		updatePrefixLabels(category);
		bindSectionEvents(category);
	});

	$("#form_content #domain_os").change(function changeOSEvent() {
		slideUpRows($('.domain_os').not($('.' + $(this).val())));
		slideDownRows($('.domain_os.' + $(this).val()).not(isVMAdvancedMode() ? '.basic' : '.advanced'));

		if ($(this).val() == 'windows') {
			$('#domain_clock').val('localtime');
			$('#domain_machine').val('pc');
		} else {
			$('#domain_clock').val('utc');
			$('#domain_machine').val('q35');
		}
	});

	// Toggle OS-dependent fields now (we could fire the change event but we don't want to change the clock and machine)
	slideUpRows($('.domain_os').not($('.' + $("#form_content #domain_os").val())));
	slideDownRows($('.domain_os.' + $("#form_content #domain_os").val()).not(isVMAdvancedMode() ? '.basic' : '.advanced'));

	if ($(".gpu option[value='vnc']:selected").length) {
		$('.vncpassword').show();
	} else {
		$('.vncpassword').hide();
	}

	$("#form_content .disk").not("[value='']")
		.attr('name', function(){ return $(this).attr('name').replace('new', 'image'); })
		.closest('table').find('.disk_file_options').hide()
		.filter('.advanced').removeClass('advanced').addClass('wasadvanced');
});
</script>
