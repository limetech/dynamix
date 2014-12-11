<?PHP
	$logfile = "/var/log/apcupsd";
	$configfile_old = "/boot/config/plugins/apcupsd/apcupsd.cfg";
	$configfile = "/boot/config/apcupsd.cfg";
	$apcupsd_running = file_exists("/var/run/apcupsd.pid") ? "yes" : "no";

	// All valid settings and their defaults
	$settings = array(
		"SERVICE" => "disable",
		"UPSCABLE" => "usb",
		"CUSTOMUPSCABLE" => "",
		"UPSTYPE" => "usb",
		"DEVICE" => "",
		"BATTERYLEVEL" => "10",
		"MINUTES" => "10",
		"TIMEOUT" => "0",
		"KILLUPS" => "no"
	);

	if (!file_exists($configfile)) {
		if (file_exists($configfile_old)) {
			// Copy old apcupsd.cfg from the plugin to /boot/config/ (we dont overwrite the existing)
			copy($configfile_old, $configfile);
		} else {
			// Create a seed config file
			saveconfig();
		}
	}

	readfile("/usr/local/emhttp/update.htm");

	$cur_dt = date("F j\, Y h:i:s A");
	write_log("\nStart: $cur_dt");

	if ($argv[1] == "autostart") {
		$CMD = $argv[1];
		$_POST = parse_ini_file($configfile);
	} else {
		$CMD = $_POST['cmd'];
	}

	foreach ($_POST as $key => $value) {
		if (array_key_exists($key, $settings)) {
			$settings[$key] = $_POST[$key];
		}
	}

	switch ($CMD) {
		case 'apply':
			saveconfig();
			// No break on purpose

		case 'autostart';
			if ($settings['SERVICE'] == "enable") {
				applyconfig();
				stopapcupsd();
				startapcupsd();
			} else {
				stopapcupsd();
			}
			break;

		default:
			break;
	}

	echo("<html>");
	echo("<head><script>var goback=parent.location;</script></head>");
	echo("<body onLoad=\"parent.location=goback;\"></body>");
	echo("</html>");

	$cur_dt = date("F j\, Y h:i:s A");
	write_log("\nEnd: $cur_dt");

	function startapcupsd() {
		echo("Starting apcupsd...");
		exec_log("/etc/rc.d/rc.apcupsd start");
		sleep(3);
		echo("Completed...");
		sleep(1);
	}

	function stopapcupsd() {
		global $apcupsd_running;

		if ($apcupsd_running == "yes") {
			echo("Stopping apcupsd...");
			exec_log("/etc/rc.d/rc.apcupsd stop");
			sleep(0.5);
			exec_log("killall -9 apcupsd");
			echo("Completed...");
			sleep(1);
		}
	}

	function applyconfig() {
		global $settings;

		$UPSCABLE = $settings['UPSCABLE'];
		$CUSTOMUPSCABLE = $settings['CUSTOMUPSCABLE'];
		$UPSTYPE = $settings['UPSTYPE'];
		$DEVICE = $settings['DEVICE'];
		$BATTERYLEVEL = $settings['BATTERYLEVEL'];
		$MINUTES = $settings['MINUTES'];
		$TIMEOUT = $settings['TIMEOUT'];
		$KILLUPS = $settings['KILLUPS'];

		echo("Applying Settings...");

		if ($UPSCABLE == "custom") {
			exec_log("sed -i -e '/^UPSCABLE/c\\UPSCABLE '$CUSTOMUPSCABLE'' /etc/apcupsd/apcupsd.conf");
		} else {
			exec_log("sed -i -e '/^UPSCABLE/c\\UPSCABLE '$UPSCABLE'' /etc/apcupsd/apcupsd.conf");
		}

		exec_log("sed -i -e '/^UPSTYPE/c\\UPSTYPE '$UPSTYPE'' /etc/apcupsd/apcupsd.conf");

		exec_log("sed -i -e '/^DEVICE/c\\DEVICE '$DEVICE'' /etc/apcupsd/apcupsd.conf");

		exec_log("sed -i -e '/^BATTERYLEVEL/c\\BATTERYLEVEL '$BATTERYLEVEL'' /etc/apcupsd/apcupsd.conf");
		exec_log("sed -i -e '/^MINUTES/c\\MINUTES '$MINUTES'' /etc/apcupsd/apcupsd.conf");
		exec_log("sed -i -e '/^TIMEOUT/c\\TIMEOUT '$TIMEOUT'' /etc/apcupsd/apcupsd.conf");

		if ($KILLUPS == "yes") {
			exec_log("! grep -q apccontrol /etc/rc.d/rc.6 && sed -i -e 's/\\/sbin\\/poweroff/\\/etc\\/apcupsd\\/apccontrol killpower; \\/sbin\\/poweroff/' /etc/rc.d/rc.6");
		} else {
			exec_log("grep -q apccontrol /etc/rc.d/rc.6 && sed -i -e 's/\\/etc\\/apcupsd\\/apccontrol killpower; \\/sbin\\/poweroff/\\/sbin\\/poweroff/' /etc/rc.d/rc.6");
		}

		echo("Completed...");
		sleep(1);
	}

	function saveconfig() {
		global $configfile, $settings;

		echo("Saving Settings...");
		$string = "#apcupsd configuration\n";

		foreach ($settings as $key => $value) {
			$string .= "$key=\"$value\"\n";
		}

		write_string($configfile, $string, TRUE);

		echo("Completed...");
		sleep(1);
	}

	function exec_log($cmd) {
		$results = exec("$cmd 2>/dev/null");

		$results = "\nCMD: $cmd \nResults: $results";
		write_log($results);
	}

	function write_log($contents) {
		global $logfile;

		write_string($logfile, "$contents\n", FALSE);
	}

	function write_string($file, $contents, $overwrite) {
		if (file_exists($file)) {
			if ($overwrite) {
				unlink($file);
			}
		} else {
			touch($file);
		}

		$fp = @fopen($file, 'a');
		@flock($fp, LOCK_EX);
		@fwrite($fp, $contents);
		@flock($fp, LOCK_UN);
		@fclose($fp);
	}
?>
