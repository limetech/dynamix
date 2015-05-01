<?PHP
/* Copyright 2015, Lime Technology
 * Copyright 2015, Derek Macias, Eric Schultz, Jon Panozzo.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?

	if (!isset($var)) {
		$var = parse_ini_file('state/var.ini');
	}


	// Check if program is running and
	$libvirt_running = trim(shell_exec( "[ -f /proc/`cat /var/run/libvirt/libvirtd.pid 2> /dev/null`/exe ] && echo 'yes' || echo 'no' 2> /dev/null" ));

	// Create domain config if needed
	$domain_cfgfile = "/boot/config/domain.cfg";
	if (!file_exists($domain_cfgfile)) {
		file_put_contents($domain_cfgfile, 'SERVICE="disable"'."\n".'DEBUG="no"'."\n".'MEDIADIR="/mnt/"'."\n".'DISKDIR="/mnt/"'."\n".'BRNAME=""'."\n");
	} else {
		// This will clean any ^M characters (\r) caused by windows from the config file
		shell_exec("sed -i 's!\r!!g' '$domain_cfgfile'");
	}

	$domain_cfg = parse_ini_file($domain_cfgfile);

	$domain_debug = isset($domain_cfg['DEBUG']) ? $domain_cfg['DEBUG'] : "no";
	if ($domain_debug != "yes") {
		error_reporting(0);
	}

	$domain_bridge = (!($domain_cfg['BRNAME'])) ? 'virbr0' : $domain_cfg['BRNAME'];
	$msg = (empty($domain_bridge)) ? "Error: Setup Bridge in Settings/Network Settings" : false;
	$libvirt_service = isset($domain_cfg['SERVICE']) ?	$domain_cfg['SERVICE'] : "disable";

	if ($libvirt_running == "yes"){
		$uri = is_dir('/proc/xen') ? 'xen:///system' : 'qemu:///system';
		$lv = new Libvirt($uri, null, null, false);
		$info = $lv->host_get_node_info();
		$maxcpu = (int)$info['cpus'];
		$maxmem = number_format(($info['memory'] / 1048576), 1, '.', ' ');
	}

	$theme = $display['theme'];
	//set color on even rows for white or black theme
	function bcolor($row, $color) {
		if ($color == "white")
			$color = ($row % 2 == 0) ? "transparent" : "#F8F8F8";
		else
			$color = ($row % 2 == 0) ? "transparent" : "#0C0C0C";
		return $color;
	}

	//create checkboxes for usb devices
	function usb_checkbox($usb, $key) {
		$deviceid = substr(strstr($usb, 'ID'),3,9);
		echo '<input class="checkbox" type="checkbox" value="'.$deviceid.'" name="usb['.$key.']" />';
		echo "<label>$usb</label><br />";
	}

	//create memory drop down option based on max memory
	function memOption($maxmem) {
		for ($i = 1; $i <= ($maxmem*2); $i++) {
			$mem = ($i*512);
			echo "<option value='$mem'>$mem</option>";
		}
	}

	//create drop down options from arrays
	function arrayOptions($ValueArray, $DisplayArray, $value) {
		for ($i = 0; $i < sizeof($ValueArray); $i++) {
			echo "<option value='$ValueArray[$i]'";
			if ($ValueArray[$i] == $value)
				echo " selected='selected'>$DisplayArray[$i] *</option>";
			else
				echo ">$DisplayArray[$i]</option>";
		}
	}

	//create memory drop down options
	function memOptions($maxmem, $mem) {
		for ($i = 1; $i <= ($maxmem*2); $i++) {
			$mem2 = ($i*512);
			echo "<option value=".$mem2*1024;
			if ((int)$mem == $mem2*1024)
				echo " selected='selected'>$mem2 *</option>";
			else
				echo ">$mem2</option>";
		}
	}


	function mk_dropdown_options($arrOptions, $strSelected) {
		foreach ($arrOptions as $key => $label) {
			echo mk_option($strSelected, $key, $label);
		}
	}

	function appendOrdinalSuffix($number) {
		$ends = array('th','st','nd','rd','th','th','th','th','th','th');

		if (($number % 100) >= 11 && ($number % 100) <= 13) {
			$abbreviation = $number . 'th';
		} else {
			$abbreviation = $number . $ends[$number % 10];
		}

		return $abbreviation;
	}


	$cacheValidPCIDevices = null;
	function getValidPCIDevices() {
		global $cacheValidPCIDevices;

		if (!is_null($cacheValidPCIDevices)) {
			return $cacheValidPCIDevices;
		}

		$arrWhitelistGPUNames = array('VGA compatible controller', 'Video Device');
		$arrWhitelistAudioNames = array('Audio device');

		$arrValidPCIDevices = array();

		exec("lspci 2>/dev/null", $arrAllPCIDevices);

		foreach ($arrAllPCIDevices as $strPCIDevice) {
			if (preg_match('/^(?P<id>\S+) (?P<type>.+): (?P<name>.+)$/', $strPCIDevice, $arrMatch)) {
				$strClass = 'other';
				if (in_array($arrMatch['type'], $arrWhitelistGPUNames)) {
					$strClass = 'vga';
				} else if (in_array($arrMatch['type'], $arrWhitelistAudioNames)) {
					$strClass = 'audio';
				}

				if (!file_exists('/sys/bus/pci/devices/0000:' . $arrMatch['id'] . '/iommu_group/')) {
					// No IOMMU support for device, skip device
					continue;
				}

				$arrValidPCIDevices[] = array(
					'id' => $arrMatch['id'],
					'type' => $arrMatch['type'],
					'class' => $strClass,
					'name' => $arrMatch['name']
				);
			}
		}

		$cacheValidPCIDevices = $arrValidPCIDevices;

		return $arrValidPCIDevices;
	}


	$cacheValidUSBDevices = null;
	function getValidUSBDevices() {
		global $cacheValidUSBDevices;

		if (!is_null($cacheValidUSBDevices)) {
			return $cacheValidUSBDevices;
		}

		$arrValidUSBDevices = array();

		exec("lsusb 2>/dev/null", $arrAllUSBDevices);

		foreach ($arrAllUSBDevices as $strUSBDevice) {
			if (preg_match('/^.+ID (?P<id>\S+) (?P<name>.+)$/', $strUSBDevice, $arrMatch)) {
				$arrMatch['name'] = trim($arrMatch['name']);

				if (empty($arrMatch['name'])) {
					// Device name is blank, skip device
					continue;
				}

				if (stripos($GLOBALS['var']['flashGUID'], str_replace(':', '-', $arrMatch['id'])) === 0) {
					// Device id matches the unraid boot device, skip device
					continue;
				}

				if (trim(shell_exec('lsusb -d ' . $arrMatch['id'] . ' -v 2>/dev/null | grep \'bDeviceClass            9 Hub\'')) != '') {
					// Device class is a Hub, skip device
					continue;
				}

				$arrValidUSBDevices[] = array(
					'id' => $arrMatch['id'],
					'name' => $arrMatch['name'],
				);
			}
		}

		$cacheValidUSBDevices = $arrValidUSBDevices;

		return $arrValidUSBDevices;
	}


	function getValidMachineTypes() {
		global $lv;

		$arrValidMachineTypes = [];

		$arrQEMUInfo = $lv->get_connect_information();
		$arrMachineTypes = $lv->get_machine_types('x86_64');

		$strQEMUVersion = $arrQEMUInfo['hypervisor_major'] . '.' .  $arrQEMUInfo['hypervisor_minor'];

		foreach ($arrMachineTypes as $arrMachine) {
			if ($arrMachine['name'] == 'q35') {
				// Latest Q35
				$arrValidMachineTypes[$arrMachine['name']] = 'Q35-' . $strQEMUVersion;
			}
			if (strpos($arrMachine['name'], 'q35-') !== false) {
				// Prior releases of Q35
				$arrValidMachineTypes[$arrMachine['name']] = str_replace(['q35', 'pc-'], ['Q35', ''], $arrMachine['name']);
			}
			if ($arrMachine['name'] == 'pc') {
				// Latest i440fx
				$arrValidMachineTypes[$arrMachine['name']] = 'i440fx-' . $strQEMUVersion;
			}
			if (strpos($arrMachine['name'], 'i440fx-') !== false) {
				// Prior releases of i440fx
				$arrValidMachineTypes[$arrMachine['name']] = str_replace('pc-', '', $arrMachine['name']);
			}
		}

		arsort($arrValidMachineTypes);

		return $arrValidMachineTypes;
	}


	function getValidDiskDrivers() {
		$arrValidDiskDrivers = [
			'raw' => 'raw',
			'qcow2' => 'qcow2'
		];

		return $arrValidDiskDrivers;
	}


	function getValidKeyMaps() {
		$arrValidKeyMaps = [
			'ar' => 'Arabic (ar)',
			'hr' => 'Croatian (hr)',
			'cz' => 'Czech (cz)',
			'da' => 'Danish (da)',
			'nl' => 'Dutch (nl)',
			'nl-be' => 'Dutch-Belgium (nl-be)',
			'en-gb' => 'English-United Kingdom (en-gb)',
			'en-us' => 'English-United States (en-us)',
			'es' => 'Español (es)',
			'et' => 'Estonian (et)',
			'fo' => 'Faroese (fo)',
			'fi' => 'Finnish (fi)',
			'fr' => 'French (fr)',
			'bepo' => 'French-Bépo (bepo)',
			'fr-be' => 'French-Belgium (fr-be)',
			'fr-ca' => 'French-Canadian (fr-ca)',
			'fr-ch' => 'French-Switzerland (fr-ch)',
			'de-ch' => 'German-Switzerland (de-ch)',
			'hu' => 'Hungarian (hu)',
			'is' => 'Icelandic (is)',
			'it' => 'Italian (it)',
			'ja' => 'Japanese (ja)',
			'lv' => 'Latvian (lv)',
			'lt' => 'Lithuanian (lt)',
			'mk' => 'Macedonian (mk)',
			'no' => 'Norwegian (no)',
			'pl' => 'Polish (pl)',
			'pt-br' => 'Portuguese-Brazil (pt-br)',
			'ru' => 'Russian (ru)',
			'sl' => 'Slovene (sl)',
			'sv' => 'Swedish (sv)',
			'th' => 'Thailand (th)',
			'tr' => 'Turkish (tr)'
		];

		return $arrValidKeyMaps;
	}


	function getHostCPUModel() {
		$cpu = explode('#', exec("dmidecode -q -t 4|awk -F: '{if(/Version:/) v=$2; else if(/Current Speed:/) s=$2} END{print v\"#\"s}'"));
		list($strCPUModel) = explode('@', str_replace(array("Processor","CPU","(C)","(R)","(TM)"), array("","","&#169;","&#174;","&#8482;"), $cpu[0]) . '@');
		return trim($strCPUModel);
	}


	function domain_to_config($uuid) {
		global $lv;

		$arrValidPCIDevices = getValidPCIDevices();
		$arrValidUSBDevices = getValidUSBDevices();
		$arrValidDiskDrivers = getValidDiskDrivers();

		$arrValidGPUDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'vga'); });
		$arrValidAudioDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'audio'); });
		$arrValidOtherDevices = array_filter($arrValidPCIDevices, function($arrDev) { return ($arrDev['class'] == 'other'); });

		$res = $lv->domain_get_domain_by_uuid($uuid);
		$dom = $lv->domain_get_info($res);
		$medias = $lv->get_cdrom_stats($res);
		$disks = $lv->get_disk_stats($res, false);
		$arrNICs = $lv->get_nic_info($res);
		$arrHostDevs = $lv->domain_get_host_devices_pci($res);
		$arrUSBDevs = $lv->domain_get_host_devices_usb($res);

		$arrGPUDevices = [];
		$arrAudioDevices = [];
		$arrOtherDevices = [];

		// check for vnc; add to arrGPUDevices
		$intVNCPort = $lv->domain_get_vnc_port($res);
		if (!empty($intVNCPort)) {
			$arrGPUDevices[] = [
				'id' => 'vnc',
				'keymap' => $lv->domain_get_vnc_keymap($res)
			];
		}

		foreach ($arrHostDevs as $arrHostDev) {
			$arrFoundGPUDevices = array_filter($arrValidGPUDevices, function($arrDev) use ($arrHostDev) { return ($arrDev['id'] == $arrHostDev['id']); });
			if (!empty($arrFoundGPUDevices)) {
				$arrGPUDevices[] = ['id' => $arrHostDev['id']];
				continue;
			}

			$arrFoundAudioDevices = array_filter($arrValidAudioDevices, function($arrDev) use ($arrHostDev) { return ($arrDev['id'] == $arrHostDev['id']); });
			if (!empty($arrFoundAudioDevices)) {
				$arrAudioDevices[] = ['id' => $arrHostDev['id']];
				continue;
			}

			$arrFoundOtherDevices = array_filter($arrValidOtherDevices, function($arrDev) use ($arrHostDev) { return ($arrDev['id'] == $arrHostDev['id']); });
			if (!empty($arrFoundOtherDevices)) {
				$arrOtherDevices[] = ['id' => $arrHostDev['id']];
				continue;
			}
		}

		// Add claimed USB devices by this VM to the available USB devices
		/*
		foreach($arrUSBDevs as $arrUSB) {
			$arrValidUSBDevices[] = array(
				'id' => $arrUSB['id'],
				'name' => $arrUSB['product'],
			);
		}
		*/

		$arrDisks = [];
		foreach ($disks as $disk) {
			$arrDisks[] = [
				'new' => (empty($disk['file']) ? $disk['partition'] : $disk['file']),
				'size' => '',
				'driver' => 'raw',
				'dev' => $disk['device'],
				'bus' => $disk['bus']
			];
		}

		return [
			'domain' => [
				'name' => $lv->domain_get_name($res),
				'desc' => $lv->domain_get_description($res),
				'persistent' => 1,
				'uuid' => $lv->domain_get_uuid($res),
				'clock' => $lv->domain_get_clock_offset($res),
				'os' => ($lv->domain_get_clock_offset($res) == 'localtime' ? 'windows' : 'other'),
				'arch' => $lv->domain_get_arch($res),
				'machine' => $lv->domain_get_machine($res),
				'mem' => $lv->domain_get_current_memory($res),
				'maxmem' => $lv->domain_get_memory($res),
				'password' => '', //TODO?
				'cpumode' => $lv->domain_get_cpu_type($res),
				'vcpus' => $dom['nrVirtCpu'],
				'vcpu' => $lv->domain_get_vcpu_pins($res),
				'hyperv' => ($lv->domain_get_feature($res, 'hyperv') ? 1 : 0),
				'autostart' => ($lv->domain_get_autostart($res) ? 1 : 0),
				'state' => $lv->domain_state_translate($dom['state']),
				'ovmf' => ($lv->domain_get_ovmf($res) ? 1 : 0)
			],
			'media' => [
				'cdrom' => (!empty($medias) && !empty($medias[0]) && array_key_exists('file', $medias[0])) ? $medias[0]['file'] : '',
				'drivers' => (!empty($medias) && !empty($medias[1]) && array_key_exists('file', $medias[1])) ? $medias[1]['file'] : ''
			],
			'disk' => $arrDisks,
			'gpu' => $arrGPUDevices,
			'audio' => $arrAudioDevices,
			'pci' => $arrOtherDevices,
			'nic' => $arrNICs,
			'usb' => $arrUSBDevs,
			'shares' => $lv->domain_get_mount_filesystems($res)
		];
	}

?>