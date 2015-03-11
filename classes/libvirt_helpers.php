<?php

	if (!isset($var)) {
		$var = parse_ini_file('state/var.ini');
	}


	// Check if program is running and get config info
	$libvirt_running = trim(shell_exec( "[ -f /proc/`cat /var/run/libvirt/libvirtd.pid 2> /dev/null`/exe ] && echo 'yes' || echo 'no' 2> /dev/null" ));
	$domain_cfgfile = "/boot/config/domain.cfg";
	if(!file_exists($domain_cfgfile)){
		$fp = fopen("$domain_cfgfile", 'w');
		fwrite($fp, 'DEBUG="no"'."\n".'MEDIADIR="/mnt/"'."\n".'DISKDIR="/mnt/"'."\n");
		fclose($fp);
	}

	// This will clean any ^M characters caused by windows from the config file
	if (file_exists("$domain_cfgfile"))
		shell_exec("sed -i 's!\r!!g' '$domain_cfgfile'");
	$domain_cfg = parse_ini_file( "$domain_cfgfile" );
	$domain_debug = isset($domain_cfg['DEBUG']) ? $domain_cfg['DEBUG'] : "no";
	if($domain_debug != "yes")
		error_reporting(0);
	$domain_bridge = (!($domain_cfg['BRNAME'])) ? $var['BRNAME'] : $domain_cfg['BRNAME'];
	$msg = (empty($domain_bridge)) ? "Error: Setup Bridge in Settings/Network Settings" : false;
	$libvirt_service = isset($domain_cfg['SERVICE']) ?	$domain_cfg['SERVICE'] : "enable";

	if ($libvirt_running == "yes"){
		$uri = is_dir('/proc/xen') ? 'xen:///system' : 'qemu:///system';
		$lv = new Libvirt($uri, null, null, false);
		$info = $lv->host_get_node_info();
		$maxcpu = (int)$info['cores']*(int)$info['threads'];
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

	function getValidPCIDevices() {
		$arrWhitelistGPUNames = array('VGA compatible controller');
		$arrWhitelistAudioNames = array('Audio device');

		$arrValidPCIDevices = array();

		exec("lspci", $arrAllPCIDevices);

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
					'dev_id' => $arrMatch['id'],
					'dev_type' => $arrMatch['type'],
					'dev_class' => $strClass,
					'dev_name' => $arrMatch['name']
				);
			}
		}

		return $arrValidPCIDevices;
	}


	function getValidUSBDevices() {
		$arrValidUSBDevices = array();

		exec("lsusb", $arrAllUSBDevices);

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

				if (trim(shell_exec('lsusb -d ' . $arrMatch['id'] . ' -v | grep \'bDeviceClass            9 Hub\'')) != '') {
					// Device class is a Hub, skip device
					continue;
				}

				$arrValidUSBDevices[] = array(
					'dev_id' => $arrMatch['id'],
					'dev_name' => $arrMatch['name'],
				);
			}
		}

		return $arrValidUSBDevices;
	}


	function getValidMachineTypes() {
		$arrValidMachineTypes = [
			'q35' => 'Q35',
			'pc' => 'i440fx'
		];

		//TODO: add support for OVMF types

		return $arrValidMachineTypes;
	}


	function getValidDiskDrivers() {
		$arrValidDiskDrivers = [
			'raw' => 'raw',
			'qcow2' => 'qcow2'
		];

		return $arrValidDiskDrivers;
	}

?>