<?php

## BETA 11
	$dockerManPaths      = array(
	'plugin'            => '/usr/local/emhttp/plugins/dynamix.docker.manager',
	'autostart-file'    => '/var/lib/docker/unraid-autostart',
	'template-repos'    => '/boot/config/plugins/dockerMan/template-repos',
	'templates-user'    => '/boot/config/plugins/dockerMan/templates-user',
	'templates-storage' => '/boot/config/plugins/dockerMan/templates',
	'images-ram'        => '/usr/local/emhttp/state/plugins/dynamix.docker.manager/images',
	'images-storage'    => '/boot/config/plugins/dockerMan/images',
	'webui-info'        => '/usr/local/emhttp/state/plugins/dynamix.docker.manager/docker.json',
	'update-status'     => '/var/lib/docker/unraid-update-status.json',
	);

//## BETA 9
//	$dockerManPaths      = array(
//	'plugin'            => '/usr/local/emhttp/plugins/dockerMan',
//	'autostart-file'    => '/var/lib/docker/unraid-autostart',
//	'template-repos'    => '/boot/config/plugins/dockerMan/template-repos',
//	'templates-user'    => '/boot/config/plugins/dockerMan/templates-user',
//	'templates-storage' => '/boot/config/plugins/dockerMan/templates',
//	'images-ram'        => '/usr/local/emhttp/state/plugins/dockerMan/images',
//	'images-storage'    => '/boot/config/plugins/dockerMan/images',
//	'webui-info'        => '/usr/local/emhttp/state/plugins/dockerMan/docker.json',
//	);

//## BETA 8
//	$dockerManPaths     = array(
//	'plugin'            => '/usr/local/emhttp/plugins/dockerMan',
//	'autostart-file'    => '/var/lib/docker/unraid-autostart',
//	'template-repos'    => '/boot/config/plugins/dockerMan/template-repos',
//	'templates-user'    => '/var/lib/docker/unraid-templates',
//	'templates-storage' => '/boot/config/plugins/dockerMan/templates',
//	'images-ram'        => '/usr/local/emhttp/state/plugins/dockerMan/images',
//	'images-storage'    => '/boot/config/plugins/dockerMan/images',
//	'webui-info'        => '/usr/local/emhttp/state/plugins/dockerMan/docker.json',
//	);

#load emhttp variables if needed.
$var = (isset($var)) ? $var : parse_ini_file("/usr/local/emhttp/state/var.ini");


######################################
##   	  DOCKERTEMPLATES CLASS       ##
######################################

class DockerTemplates {

	public function download_url($url, $path = ""){
		if ($path) $path = " -o '$path' ";
		return shell_exec("curl --connect-timeout 15 --max-time 60 -s -k -L $path $url 2>/dev/null" );
	}


	public function listDir($dir, $ext = FALSE, $prefix = '') {
		$dir = rtrim($dir, '\\/');
		$result = array();
		$dir_result = array();
		if (! $dir) return $result;
		$Files = scandir($dir);
		if (! $Files) return $result;
		natcasesort($Files);
		foreach ($Files as $f) {
			if ($f !== '.' and $f !== '..') {
				if (is_dir("$dir/$f")) {
					$dir_result = array_merge($dir_result, $this->listDir("$dir/$f", $ext, $f));
				} else {
					$dirfile = $dir."/".$f;
					$fext = new SplFileInfo($dirfile);
					$fext = $fext->getExtension();
					if ($ext){
						if ( $ext != $fext ) continue;
					}
					$result[] = array(
						'path' => $dirfile,
						'prefix' => $prefix,
						'name' => preg_replace("/\.{$ext}/", '', $f)
						);
				}
			}
		}
		return array_merge($result, $dir_result);
	}


	public function getTemplates($type) {
		global $dockerManPaths;
		$tmpls = array();
		$dirs = array();
		if ($type == "all"){
			$dirs[] = $dockerManPaths['templates-storage'];
			$dirs[] = $dockerManPaths['templates-user'];

		} else if ($type == "user"){
			$dirs[] = $dockerManPaths['templates-user'];

		} else if ($type == "default"){
			$dirs[] = $dockerManPaths['templates-storage'];
		} else {
			$dirs[] = $type;
		}
		foreach ($dirs as $dir) {
			if (! is_dir( $dir)) @mkdir( $dir, 0770, true);
			$tmpls = array_merge($tmpls, $this->listDir($dir, "xml"));
		}
		return $tmpls;
	}


	private function removeDir($path){
		if (is_dir($path) === true) {
			$files = array_diff(scandir($path), array('.', '..'));
			foreach ($files as $file) {
				$this->removeDir(realpath($path) . '/' . $file);
			}
			return rmdir($path);
		} else if (is_file($path) === true) {
			return unlink($path);
		}
		return false;
	}


	public function downloadTemplates(){
		global $dockerManPaths;
		$repotemplates = array();
		$msg = array('');
		$urls = @file($dockerManPaths['template-repos'], FILE_IGNORE_NEW_LINES);
		if ( ! is_array($urls)) return false;
		$msg[] = " Updating templates\n URLs:\n   " . implode("\n   ", $urls) . "\n Files:";

		foreach ($urls as $url) {
			$tmp_dir = "/tmp/tmp-".mt_rand();
			@mkdir($tmp_dir, 0777, TRUE);

			$api_regexes = array(
				0 => '%/.*github.com/([^/]*)/([^/]*)/tree/([^/]*)/(.*)$%i',
				1 => '%/.*github.com/([^/]*)/([^/]*)/tree/([^/]*)$%i',
				2 => '%/.*github.com/([^/]*)/(.*).git%i',
				3 => '%/.*github.com/([^/]*)/(.*)%i',
				);

			for ($i=0; $i <= count($api_regexes); $i++) {
				if ( preg_match($api_regexes[$i], $url, $matches) ){
					$github_api['user']   = ( isset( $matches[1] )) ? $matches[1] : "";
					$github_api['repo']   = ( isset( $matches[2] )) ? $matches[2] : "";
					$github_api['branch'] = ( isset( $matches[3] )) ? $matches[3] : "master";
					$github_api['path']   = ( isset( $matches[4] )) ? $matches[4] : "";
					$github_api['url']    = sprintf("https://github.com/%s/%s/archive/%s.tar.gz", $github_api['user'], $github_api['repo'], $github_api['branch']);
					break;
				}
			}

			$this->download_url($github_api['url'], "$tmp_dir.tar.gz");
			if (is_file( "$tmp_dir.tar.gz")) {
				shell_exec("tar -zxf $tmp_dir.tar.gz --strip=1 -C $tmp_dir/ 2>&1");
				unlink("$tmp_dir.tar.gz");
			} else {
				$msg[] = "   Downloading ". $github_api['url'] ." has failed.";
				continue;
			}


			$templates = $this->getTemplates($tmp_dir);
			foreach ($templates as $template) {
				$storPath = sprintf("%s/%s", $dockerManPaths['templates-storage'], str_replace($tmp_dir."/", "", $template['path']) );
				$repotemplates[] = $storPath;
				if (! is_dir( dirname( $storPath ))) @mkdir( dirname( $storPath ), 0777, true);
				if ( is_file($storPath) ){
					if ( sha1_file( $template['path'] )  ===  sha1_file( $storPath )) {
						$msg[] = sprintf("   Skipped: %s", $template['prefix'] . '/' . $template['name']);
						continue;
					} else {
						@copy($template['path'], $storPath);
						$msg[] = sprintf("   Updated: %s", $template['prefix'] . '/' . $template['name']);
					}
				} else {
					@copy($template['path'], $storPath);
					$msg[] = sprintf("   Added: %s", $template['prefix'] . '/' . $template['name']);
				}
			}
			$this->removeDir($tmp_dir);
		}
		// Delete any templates not in the repos
		foreach ($this->listDir($dockerManPaths['templates-storage'], "xml") as $arrLocalTemplate) {
			if (!in_array($arrLocalTemplate['path'], $repotemplates)) {
				unlink($arrLocalTemplate['path']);
				$msg[] = sprintf("   Removed: %s\n", $arrLocalTemplate['prefix'] . '/' . $arrLocalTemplate['name']);

				// Any other files left in this template folder? if not delete the folder too
				$files = array_diff(scandir(dirname($arrLocalTemplate['path'])), array('.', '..'));
				if (empty($files)) {
					rmdir(dirname($arrLocalTemplate['path']));
					$msg[] = sprintf("   Removed: %s\n", $arrLocalTemplate['prefix']);
				}
			}
		}
		return $msg;
	}


	public function getTemplateValue($Repository, $field, $scope = "all"){
		$tmpls = array();
		if ($scope == "default" || $scope == "all") {
			$tmpls = array_merge($tmpls, $this->getTemplates("default"));
		}
		if ($scope == "user" || $scope == "all") {
			$tmpls = array_merge($tmpls, $this->getTemplates("user"));
		}

		foreach ($tmpls as $file) {
			$doc = new DOMDocument();
			$doc->load($file['path']);
			$TemplateRepository = $doc->getElementsByTagName( "Repository" )->item(0)->nodeValue;
			if (! preg_match("/:[\w]*$/i", $TemplateRepository)) {
				$Repo = preg_replace("/:[\w]*$/i", "", $Repository);
			}else{
				$Repo = $Repository;
			}

			if ( $Repo == $TemplateRepository ) {
				$TemplateField = $doc->getElementsByTagName( $field )->item(0)->nodeValue;
				return trim($TemplateField);
				break;
			}
		}
		return NULL;
	}


	public function getUserTemplate($Container){
		foreach ($this->getTemplates("user") as $file) {
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->load( $file['path'] );
			$Name = $doc->getElementsByTagName( "Name" )->item(0)->nodeValue;
			if ($Name == $Container){
				return $file['path'];
			}
		}
		return FALSE;
	}


	public function getControlURL($name){
		global $var;
		$DockerClient = new DockerClient();
		$IP         = $var["IPADDR"];
		$Repository = preg_replace("/:[\w]*$/i", "", $Repository);

		foreach ($DockerClient->getDockerContainers() as $ct) {
			if ($ct['Name'] == $name) {
				$Repository = preg_replace("/:[\w]*$/i", "", $ct['Image']);
				$Ports = $ct["Ports"];
				break;
			}
		}

		$WebUI      = $this->getTemplateValue($Repository, "WebUI");
		$isEditable = preg_match("%\[IP\]%", $WebUI);

		if ($isEditable) {
			$WebUI = preg_replace("%\[IP\]%", $IP, $WebUI);
			preg_match("%\[PORT:(\d+)\]%", $WebUI, $matches);
			$ConfigPort = $matches[1];
			if ($ct["NetworkMode"] == "bridge"){
				foreach ($Ports as $key){
					if ($key["PrivatePort"] == $ConfigPort){
						$ConfigPort = $key["PublicPort"];
					}
				}
			}
			$WebUI = preg_replace("%\[PORT:\d+\]%", $ConfigPort, $WebUI);
		}
		return $WebUI;
	}


	public function removeInfo($container){
		global $dockerManPaths;
		$dockerIni = $dockerManPaths['webui-info'];
		if (! is_dir( dirname( $dockerIni ))) @mkdir( dirname( $dockerIni ), 0770, true);
		$info = (is_file($dockerIni)) ? json_decode(file_get_contents($dockerIni), TRUE) : array();
		if (! count($info) ) $info = array();

		if (isset($info[$container])) unset($info[$container]);
		file_put_contents($dockerIni, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

		$update_file = $dockerManPaths['update-status'];
		$updateStatus = (is_file($update_file)) ? json_decode(file_get_contents($update_file), TRUE) : array();
		if (isset($updateStatus[$container])) unset($updateStatus[$container]);
		file_put_contents($update_file, json_encode($updateStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}


	public function getAllInfo($reload = FALSE){
		global $dockerManPaths;
		$DockerClient = new DockerClient();
		$DockerUpdate = new DockerUpdate();
		$new_info     = array();

		$dockerIni = $dockerManPaths['webui-info'];
		if (! is_dir( dirname( $dockerIni ))) @mkdir( dirname( $dockerIni ), 0770, true);
		$info = (is_file($dockerIni)) ? json_decode(file_get_contents($dockerIni), TRUE) : array();
		if (! count($info) ) $info = array();

		$containers   = $DockerClient->getDockerContainers();
		if (! count($containers) ) $containers = array();

		$autostart_file = $dockerManPaths['autostart-file'];
		$allAutoStart = @file($autostart_file, FILE_IGNORE_NEW_LINES);
		if ($allAutoStart===FALSE) $allAutoStart = array();

		$update_file = $dockerManPaths['update-status'];
		$updateStatus = (is_file($update_file)) ? json_decode(file_get_contents($update_file), TRUE) : array();

		foreach ($containers as $ct) {
			$name           = $ct['Name'];
			$image          = $ct['Image'];
			$tmp            = ( count($info[$name]) ) ?  $info[$name] : array() ;

			$tmp['running'] = $ct['Running'];
			$tmp['autostart'] = in_array($name, $allAutoStart);

			if (!array_key_exists('icon', $tmp) || !array_key_exists('banner', $tmp) || $reload) {
				$img = $this->getBannerIcon( $image );
				$tmp['banner'] = ( $img['banner'] ) ? $img['banner'] : "#";
				$tmp['icon']   = ( $img['icon'] )   ? $img['icon'] : "#";
			}

			if (!array_key_exists('url', $tmp) || $reload) {
				$WebUI      = $this->getControlURL($name);
				$tmp['url'] = ($WebUI) ? $WebUI : "#";
			}

			if (!array_key_exists('registry', $tmp) || $reload ){
				$Registry = $this->getTemplateValue($image, "Registry");
				$tmp['registry'] = ( $Registry ) ? $Registry : "#";
			}

			if (!array_key_exists('updated', $tmp) || $reload) {
				if ($reload) $updateStatus[$name] = $DockerUpdate->getUpdateStatus($name, $image);
				$tmp['updated'] = (array_key_exists($name, $updateStatus)) ? $updateStatus[$name] : 'undef';
			}

			if (!array_key_exists('template', $tmp) || $reload){
				$tmp['template'] = $this->getUserTemplate($name);
			}

			$new_info[$name] = $tmp;
		}
		file_put_contents($dockerIni, json_encode($new_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		if($reload) {
			foreach ($updateStatus as $ct => $update)	if (!isset($new_info[$ct])) unset($updateStatus[$ct]);
			file_put_contents($update_file, json_encode($updateStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}
		return $new_info;
	}


	public function getBannerIcon($Repository){
		global $dockerManPaths;
		$out = array();
		$Images = array();

		$Images    		 = array('banner' => $this->getTemplateValue($Repository, "Banner"),
												'icon' => $this->getTemplateValue($Repository, "Icon") );

		$defaultImages = array('banner' => '/plugins/dynamix.docker.manager/assets/images/spacer.png',
													 'icon'   => '/plugins/dynamix.docker.manager/assets/images/question.png');

		foreach ($Images as $type => $imgUrl) {
			preg_match_all("/(.*?):([\w]*$)/i", $Repository, $matches);
			$tempPath    = sprintf("%s/%s-%s-%s.png", $dockerManPaths[ 'images-ram' ], preg_replace('%\/|\\\%', '-', $matches[1][0]), $matches[2][0], $type);
			$storagePath = sprintf("%s/%s-%s-%s.png", $dockerManPaths[ 'images-storage' ], preg_replace('%\/|\\\%', '-', $matches[1][0]), $matches[2][0], $type);
			if (! is_dir( dirname( $tempPath ))) @mkdir( dirname( $tempPath ), 0770, true);
			if (! is_dir( dirname( $storagePath ))) @mkdir( dirname( $storagePath ), 0770, true);
			if (! is_file( $tempPath )) {
				if ( is_file( $storagePath )){
					@copy($storagePath, $tempPath);
				} else {
					$this->download_url($imgUrl, $storagePath);
					@copy($storagePath, $tempPath);
				}
			}
			$out[ $type ] = ( is_file( $tempPath ) ) ? str_replace('/usr/local/emhttp', '', $tempPath) : "";
		}
		return $out;
	}
}


######################################
##   	  DOCKERUPDATE CLASS        ##
######################################
class DockerUpdate{

	public function download_url($url){
		return shell_exec("curl --connect-timeout 5 --max-time 30 -s -k -L $url 2>/dev/null" );
	}


	public function getRemoteVersion($RegistryUrl, $image){
		preg_match_all("/:([\w]*$)/i", $image, $matches);
		$tag        = isset($matches[1][0]) ? $matches[1][0] : "latest";
		preg_match("#/u/([^/]*)/([^/]*)#", $RegistryUrl, $matches);
		$apiUrl     = sprintf("http://index.docker.io/v1/repositories/%s/%s/tags/%s", $matches[1], $matches[2], $tag);
		$apiContent = $this->download_url($apiUrl);
		$json       = json_decode($apiContent, TRUE);
		$currentId  = substr($json[0]['id'],0,16);
		return $currentId;
	}


	public function getLocalVersion($file){
		if(is_file($file)){
			$doc = new DOMDocument();
			$doc->load($file);
			if ( ! $doc->getElementsByTagName( "Version" )->length == 0 ) {
				return $doc->getElementsByTagName( "Version" )->item(0)->nodeValue;
			} else {
				return NULL;
			}
		}
	}


	public function getUpdateStatus($container, $image) {
		$DockerTemplates = new DockerTemplates();
		$RegistryUrl     = $DockerTemplates->getTemplateValue($image, "Registry");
		$userFile        = $DockerTemplates->getUserTemplate($container);
		$localVersion    = $this->getLocalVersion($userFile);
		$remoteVersion   = $this->getRemoteVersion($RegistryUrl, $image);
		if ($localVersion && $remoteVersion) {
			if ($remoteVersion == $localVersion){
				$update = "true";
			} else {
				$update = "false";
			}
		} else {
			$update = "undef";
		}
		return $update;
	}

	public function syncVersions($container) {
		global $dockerManPaths;
		$update_file              = $dockerManPaths['update-status'];
		$updateStatus             = (is_file($update_file)) ? json_decode(file_get_contents($update_file), TRUE) : array();
		$updateStatus[$container] = 'true';
		file_put_contents($update_file, json_encode($updateStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}


######################################
##   	  DOCKERCLIENT CLASS        ##
######################################
class DockerClient {

	private function build_sorter($key) {
		return function ($a, $b) use ($key) {
			return strnatcmp(strtolower($a[$key]), strtolower($b[$key]));
		};
	}


	private function humanTiming ($time){
		$time = time() - $time; // to get the time since that moment
		$tokens = array (
			31536000 => 'year',
			2592000 => 'month',
			604800 => 'week',
			86400 => 'day',
			3600 => 'hour',
			60 => 'minute',
			1 => 'second'
		);
		foreach ($tokens as $unit => $text) {
			if ($time < $unit) continue;
			$numberOfUnits = floor($time / $unit);
			return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'')." ago";
		}
	}


	private function unchunk($result) {
		return preg_replace_callback(
			'/(?:(?:\r\n|\n)|^)([0-9A-F]+)(?:\r\n|\n){1,2}(.*?)'
			.'((?:\r\n|\n)(?:[0-9A-F]+(?:\r\n|\n))|$)/si',
			create_function('$matches','return hexdec($matches[1]) == strlen($matches[2]) ?
			$matches[2] :$matches[0];'),
			$result
		);
	}


	private function formatBytes($size){
		if ($size == 0){ return "0 B";}
		$base = log($size) / log(1024);
		$suffix = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		return round(pow(1024, $base - floor($base)), 1) ." ". $suffix[floor($base)];
	}


	private function getDockerJSON($url, $method = "GET"){
		$fp = stream_socket_client('unix:///var/run/docker.sock', $errno, $errstr);

		if ($fp === false) {
			echo "Couldn't create socket: [$errno] $errstr";
			return NULL;
		}
		$out="$method {$url} HTTP/1.1\r\nConnection: Close\r\n\r\n";
		fwrite($fp, $out);
		while (!feof($fp)) {
			$data .= fgets($fp, 5000);
		}
		fclose($fp);
		$data = $this->unchunk($data);
		preg_match_all('/[^\{]*(\{.*\})/',$data, $matches);
		$json = array();
		foreach($matches[1] as $x){
			$json[] = json_decode( $x, true );
		}
		return $json;
	}


	public function getInfo(){
		$info = $this->getDockerJSON("/info");
		$version = $this->getDockerJSON("/version");
		return array_merge($info[0], $version[0]);
	}


	private function getContainerDetails($id){
		$json = $this->getDockerJSON("/containers/{$id}/json");
		return $json;
	}


	public function startContainer($id){
		$json = $this->getDockerJSON("/containers/${id}/start", "POST");
		return $json;
	}


	public function removeImage($id){
		$json = $this->getDockerJSON("/images/{$id}", "DELETE");
		return $json;
	}


	public function stopContainer($id){
		$json = $this->getDockerJSON("/containers/${id}/stop", "POST");
		return $json;
	}


	private function getImageDetails($id){
		$json = $this->getDockerJSON("/images/$id/json");
		return $json;
	}


	public function getDockerContainers(){
		$containers = array();
		$json = $this->getDockerJSON("/containers/json?all=1");

		if (! $json ){ return $containers; }

		foreach($json as $obj){
			$c = array();
			$status  = $obj['Status'] ? $obj['Status'] : "None";
			preg_match("/\b^Up\b/", $status, $matches);
			$running = $matches ? TRUE : FALSE;
			$details = $this->getContainerDetails($obj['Id']);

			// echo "<pre>".print_r($details,TRUE)."</pre>";

			$c["Image"]       = $obj['Image'];
			$c["ImageId"]     = substr($details[0]["Image"],0,12);
			$c["Name"]        = substr($obj['Names'][0], 1);
			$c["Status"]      = $status;
			$c["Running"]     = $running;
			$c["Cmd"]         = $obj['Command'];
			$c["Id"]          = substr($obj['Id'],0,12);
			$c['Volumes']     = $details[0]["HostConfig"]['Binds'];
			$c["Created"]     = $this->humanTiming($obj['Created']);
			$c["NetworkMode"] = $details[0]['HostConfig']['NetworkMode'];

			$Ports = $details[0]['HostConfig']['PortBindings'];
			$Ports = (count ( $Ports )) ? $Ports : array();
			$c["Ports"] = array();
			foreach ($Ports as $port => $value) {
				list($PrivatePort, $Type) = explode("/", $port);
				$PublicPort   = $value[0]['HostPort'];
				$c["Ports"][] = array(
							'PrivatePort' => $PrivatePort,
							'PublicPort'  => $PublicPort,
							'Type'        => $Type );
			}

			$containers[] = $c;
		}
		usort($containers, $this->build_sorter('Name'));
		return $containers;
	}


	public function getImageID($Image){
		$allImages = $this->getDockerImages();
		foreach ($allImages as $img) {
			preg_match("%" . preg_quote($Image, "%") ."%", $img["Tags"][0], $matches);
			if( $matches){
				return $img["Id"];
			}
		}
		return NULL;
	}


	private function usedBy($imageId){
		$out = array();
		$Containers = $this->getDockerContainers();
		$Containers = ( count( $Containers )) ? $Containers : array();
		foreach ($Containers as $ct) {
			if ($ct["ImageId"] == $imageId){
				$out[] = $ct["Name"];
			}
		}
		return $out;
	}


	public function getDockerImages(){

		$images = array();
		$c = array();
		$json = $this->getDockerJSON("/images/json?all=0");

		if (! $json){ return $images; }

		foreach($json as $obj){
			$c = array();
			$tags = array();
			foreach($obj['RepoTags'] as $t){
				$tags[] = htmlentities($t);
			}

			$c["Created"]      = $this->humanTiming($obj['Created']);//date('Y-m-d H:i:s', $obj['Created']);
			$c["Id"]           = substr($obj['Id'],0,12);
			$c["ParentId"]     = substr($obj['ParentId'],0,12);
			$c["Size"]         = $this->formatBytes($obj['Size']);
			$c["VirtualSize"]  = $this->formatBytes($obj['VirtualSize']);
			$c["Tags"]         = $tags;
			$c["usedBy"]       = $this->usedBy($c["Id"]);

			$imgDetails = $this->getImageDetails($obj['Id']);
			$a = $imgDetails[0]['Config']['Volumes'];
			$b = $imgDetails[0]['Config']['ExposedPorts'];
			$c['ImageType'] = (! count($a) && ! count($b)) ? 'base' : 'user';

			$images[]          = $c;

		}
		return $images;
	}
}
?>
