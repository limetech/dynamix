<?PHP
	require_once "/usr/local/emhttp/webGui/include/Wrappers.php";

	$logfile = "/var/log/apcupsd";
	$configfile = "/boot/config/plugins/apcupsd/apcupsd.cfg";
	$apcupsd_running = file_exists( "/var/run/apcupsd.pid") ? "yes" : "no";

	$settings = array(
		"SERVICE",
		"UPSCABLE",
		"CUSTOMUPSCABLE",
		"UPSTYPE",
		"DEVICE",
		"BATTERYLEVEL",
		"MINUTES",
		"TIMEOUT",
		"KILLUPS"
	);

	if ($argv[1] == "autostart") {
                // only do this once
		if ($apcupsd_running == "yes") return 1;
		$CMD = $argv[1];
                $apcupsd_installing = "yes";
	} else {
		$CMD = $_POST['cmd'];
                $apcupsd_installing = "no";
	}

	if ($CMD  == "autostart") {
		$_POST = parse_plugin_cfg( "apcupsd" );
	}

	$cur_dt = date("F j\, Y h:i:s A");
	write_log("\nStart: $cur_dt");

	if ($apcupsd_installing == "no") {
		readfile("/usr/local/emhttp/update.htm");
		$newline = "";
	} else {
		$newline = "\n";
	}

	$SERVICE=$_POST['SERVICE'];
	$UPSCABLE=$_POST['UPSCABLE'];
	$CUSTOMUPSCABLE=$_POST['CUSTOMUPSCABLE'];
	$UPSTYPE=$_POST['UPSTYPE'];
	$DEVICE=$_POST['DEVICE'];
	$BATTERYLEVEL=$_POST['BATTERYLEVEL'];
	$MINUTES=$_POST['MINUTES'];
	$TIMEOUT=$_POST['TIMEOUT'];
	$KILLUPS=$_POST['KILLUPS'];

	switch ($CMD) {
		case 'apply':
			saveconfig();

		case 'autostart';
			if ($SERVICE == "enable") {
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

	/* Only refresh the webpage if apcupsd is not installing. */
	if ($apcupsd_installing == "no") {
		echo("<html>");
		echo("<head><script>var goback=parent.location;</script></head>");
		echo("<body onLoad=\"parent.location=goback;\"></body>");
		echo("</html>");
	}

	$cur_dt = date("F j\, Y h:i:s A");
	write_log("\nEnd: $cur_dt");

	function startapcupsd() {
		global $newline;

		echo("Starting apcupsd...$newline");
		exec_log("/etc/rc.d/rc.apcupsd start");
		echo("Completed...$newline");
	}

	function stopapcupsd() {
		global $newline, $apcupsd_running;

		if ($apcupsd_running == "yes") {
			echo("Stopping apcupsd...$newline");
			exec_log("/etc/rc.d/rc.apcupsd stop");
			sleep(0.5);
			exec_log("killall -9 apcupsd");
			echo("Completed...$newline");
			sleep(1);
		}
	}

	function applyconfig() {
		global $SERVICE, $UPSCABLE, $CUSTOMUPSCABLE, $UPSTYPE, $DEVICE, $BATTERYLEVEL, $MINUTES, $TIMEOUT, $KILLUPS, $newline;

		echo("Applying Settings...$newline");

		if ($UPSCABLE == "custom") {
			exec_log("sed -i -e '/^UPSCABLE/c\\UPSCABLE '$CUSTOMUPSCABLE'' /etc/apcupsd/apcupsd.conf");
		} else {
			exec_log("sed -i -e '/^UPSCABLE/c\\UPSCABLE '$UPSCABLE'' /etc/apcupsd/apcupsd.conf");
		}

		exec_log("sed -i -e '/^NISIP/c\\NISIP 0.0.0.0' /etc/apcupsd/apcupsd.conf");

		exec_log("sed -i -e '/^UPSTYPE/c\\UPSTYPE '$UPSTYPE'' /etc/apcupsd/apcupsd.conf");

		exec_log("sed -i -e '/^DEVICE/c\\DEVICE '$DEVICE'' /etc/apcupsd/apcupsd.conf");

		exec_log("! grep -q apccontrol /etc/rc.d/rc.6 && sed -i -e 's/\\/sbin\\/poweroff/\\/etc\\/apcupsd\\/apccontrol killpower; \\/sbin\\/poweroff/' /etc/rc.d/rc.6");

		exec_log("sed -i -e '/^BATTERYLEVEL/c\\BATTERYLEVEL '$BATTERYLEVEL'' /etc/apcupsd/apcupsd.conf");
		exec_log("sed -i -e '/^MINUTES/c\\MINUTES '$MINUTES'' /etc/apcupsd/apcupsd.conf");
		exec_log("sed -i -e '/^TIMEOUT/c\\TIMEOUT '$TIMEOUT'' /etc/apcupsd/apcupsd.conf");

		if ($KILLUPS == "yes") {
			exec_log("! grep -q apccontrol /etc/rc.d/rc.6 && sed -i -e 's/\\/sbin\\/poweroff/\\/etc\\/apcupsd\\/apccontrol killpower; \\/sbin\\/poweroff/' /etc/rc.d/rc.6");
		}
		else
		{
			exec_log("grep -q apccontrol /etc/rc.d/rc.6 && sed -i -e 's/\\/etc\\/apcupsd\\/apccontrol killpower; \\/sbin\\/poweroff/\\/sbin\\/poweroff/' /etc/rc.d/rc.6");
		} 
		echo("Completed...$newline");  
		sleep(1);
	}

	function saveconfig () {
		global $_POST, $configfile, $settings, $newline;

		echo("Saving Settings...$newline");
		sleep(2);
		$string .= "#apcupsd configuration\n";

		foreach ($_POST as $key=>$value) {
			if (in_array($key, $settings)) {
				$string .= "$key=\"$value\"\n";
			}
		}

		write_string($configfile, $string, TRUE);

		echo("Completed...$newline");
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

	function write_string ($file, $contents, $overwrite) {
		if (file_exists($file)) {
			if ($overwrite) {
				unlink($file);
			}
		} else {
                        @mkdir(dirname($file), 0755, TRUE);
			touch($file);
		}

		$fp = @fopen($file, 'a');
		@flock($fp, LOCK_EX);
		@fwrite($fp, $contents);
		@flock($fp, LOCK_UN);
		@fclose($fp);
	}
?>
