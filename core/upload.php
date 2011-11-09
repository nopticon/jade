<?php
/*
<Jade, Email Server.>
Copyright (C) <2011>  <NPT>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('XFS')) exit;

class upload
{
	public $error = array();
	
	public function array_merge($files)
	{
		$file_ary = array();
		if (!is_array($files)) return $file_ary;
		
		$a_keys = array_keys($files);
		for ($i = 0, $end = count($files['name']); $i < $end; $i++)
		{
			foreach ($a_keys as $k)
			{
				$file_ary[$i][$k] = $files[$k][$i];
			}
		}
		
		$check = array('name' => '', 'name' => 'none', 'size' => 0, 'error' => 4);
		foreach ($file_ary as $i => $row)
		{
			foreach ($check as $k => $v)
			{
				if ($row[$k] === $v)
				{
					unset($file_ary[$i]);
				}
			}
		}
		
		return array_values($file_ary);
	}
	
	public function get_extension($file)
	{
		return strtolower(str_replace('.', '', substr($file, strrpos($file, '.'))));
	}
	
	public function rename($a, $b)
	{
		$filename = str_replace($a['random_name'], $b, $a['filepath']);
		@rename($a['filepath'], $filename);
		@chmod($filename, 0644);
		
		return $filename;
	}
	
	public function _row($filepath, $filename)
	{
		$row = array(
			'extension' => $this->get_extension($filename),
			'name' => strtolower($filename),
			'random_name' => time() . '_' . substr(md5(unique_id()), 6)
		);
		
		$row['filename'] = $row['random_name'] . '.' . $row['extension'];
		$row['filepath'] = $filepath . $row['filename'];
		
		return $row;
	}
	
	public function process($filepath, $files, $extension, $filesize, $safe = true)
	{
		global $user;
		
		$umask = umask(0);
		$files = $this->array_merge($files);
		if (!count($files))
		{
			$this->error[] = 'UPLOAD_NO_FILES';
			return false;
		}
		
		_pre($files, true);
		
		foreach ($files as $i => $row)
		{
			$row['extension'] = $this->get_extension($row['name']);
			$row['name'] = strtolower($row['name']);
			
			if (!in_array($row['extension'], $extension))
			{
				$this->error[] = sprintf(_lang('UPLOAD_INVALID_EXT'), $row['name']);
				$row['error'] = 1;
			}
			elseif ($safe)
			{
				if (preg_match('/\.(cgi|pl|js|asp|php|html|htm|jsp|jar|exe|dll|bat)/', $row['name']))
				{
					$row['extension'] = 'txt';
				}
			}
			elseif ($row['size'] > $filesize)
			{
				$this->error[] = sprintf(_lang('UPLOAD_TOO_BIG'), $row['name'], ($filesize / 1048576));
				$row['error'] = 1;
			}
			
			if (isset($row['error']) && $row['error'] === 1)
			{
				unset($files[$i]);
				continue;
			}
			$files[$i] = $row;
		}
		
		foreach ($files as $i => $row)
		{
			$row['random_name'] = time() . '_' . substr(md5(unique_id()), 6);
			$row['filename'] = $row['random_name'] . '.' . $row['extension'];
			$row['filepath'] = $filepath . $row['filename'];
			
			if (@move_uploaded_file($row['tmp_name'], $row['filepath']))
			{
				@chmod($row['filepath'], 0644);
			}
			else
			{
				$this->error[] = sprintf(_lang('UPLOAD_FAILED'), $row['name']);
				$row['error'] = 1;
			}
			
			if (@filesize($row['filepath']) > $filesize)
			{
				$this->error[] = sprintf(_lang('UPLOAD_TOO_BIG'), $row['name'], ($filesize / 1048576));
				$row['error'] = 1;
			}
			
			if (isset($row['error']) && $row['error'] === 1)
			{
				@unlink($row['filepath']);
				unset($files[$i]);
				continue;
			}
			$files[$i] = $row;
		}
		
		@umask($umask);
		return (count($files)) ? $files : false;
	}
	
	public function resize(&$row, $folder_a, $folder_b, $filename, $measure, $mscale = true, $watermark = true, $remove = false)
	{
		$a_filename = $filename . '.' . $row['extension'];
		$source = $folder_a . $row['filename'];
		$destination = $folder_b . $a_filename;
		
		// Get source image data
		list($width, $height, $type, $void) = @getimagesize($source);
		if ($width < 1 && $height < 1)
		{
			return false;
		}
		
		if ($width < $measure[0] && $height < $measure[1])
		{
			$measure[0] = $width;
			$measure[1] = $height;
		}
		
		$scale_mode = ($mscale === true) ? 'c' : 'v';
		$row = array_merge($row, array('width' => $width, 'height' => $height, 'mwidth' => $measure[0], 'mheight' => $measure[1]));
		$row = array_merge($row, $this->scale($scale_mode, $row));
		
		switch ($type)
		{
			case IMG_JPG:
				$image_f = 'imagecreatefromjpeg';
				$image_g = 'imagejpeg';
				$image_t = 'jpg';
				break;
			case IMG_GIF:
				$image_f = 'imagecreatefromgif';
				$image_g = 'imagegif';
				$image_t = 'gif';
				break;
			case IMG_PNG:
				$image_f = 'imagecreatefrompng';
				$image_g = 'imagepng';
				$image_t = 'png';
				break;
		}
		
		if (!$image = @$image_f($source))
		{
			return false;
		}
		
		@imagealphablending($image, true);
		$thumb = @imagecreatetruecolor($row['width'], $row['height']);
		@imagecopyresampled($thumb, $image, 0, 0, 0, 0, $row['width'], $row['height'], $width, $height);
		
		// Watermark
		if ($watermark)
		{
			$wm = imagecreatefrompng('../home/style/images/w.png');
			$wm_w = imagesx($wm);
			$wm_h = imagesy($wm);
			$dest_x = $row['width'] - $wm_w - 5;
			$dest_y = $row['height'] - $wm_h - 5;
			
			imagecopymerge($thumb, $wm, $dest_x, $dest_y, 0, 0, $wm_w, $wm_h, 100);
			imagedestroy($wm);
		}
		
		eval('$created = @' . $image_g . '($thumb, $destination' . (($type == IMG_JPG) ? ', 85' : '') . ');');
		if (!$created || !@file_exists($destination))
		{
			return false;
		}
		
		@chmod($destination, 0644);
		@imagedestroy($thumb);
		@imagedestroy($image);
		
		if ($remove && @file_exists($source))
		{
			@unlink($source);
		}
		
		$row['filename'] = $a_filename;
		return $row;
	}
	
	public function scale($mode, $a)
	{
		switch ($mode)
		{
			case 'c':
				$width = $a['mwidth'];
				$height = round(($a['height'] * $a['mwidth']) / $a['width']);
				break;
			case 'v':
				if ($a['width'] > $a['height'])
				{
					$width = round($a['width'] * ($a['mwidth'] / $a['width']));
					$height = round($a['height'] * ($a['mwidth'] / $a['width']));
				} 
				else 
				{
					$width = round($a['width'] * ($a['mwidth'] / $a['height']));
					$height = round($a['height'] * ($a['mwidth'] / $a['height']));
				}
				break;
		}
		return array('width' => $width, 'height' => $height);
	}
	
	public function picnik_import()
	{
		global $config;
	}
	
	public function picnik_export()
	{
		global $config;
	}
}

/*

// 
// index.php
//
// This is the source code for the KingOfTheHill application.  
// All the application logic is on this page.
//
// This code can be freely copied, modified, and distributed.  
//    

// You can get your own API key at http://www.picnik.com/info/api
$apikey = "a77f917f0058eb066a87af4d8a540960";

// If someone wants to view the source, then dump it out.
if( isset( $_GET["source"] ) ) {
	echo "<pre>";
	echo htmlentities( file_get_contents( "index.php") );								
	echo "</pre>";
	exit;
}

// $strRoot will be the URL to this page
$strPath = str_replace( "\\", "/", $_SERVER['REQUEST_URI'] );
$aPath = explode( "?", $strPath);
$strRoot = "http://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $aPath[0];
if ($strRoot[strlen($strRoot)-1] != "/")
	$strRoot .= "/";

// $strPicnikUrl is the URL that we use to launch Picnik.  Give it an API key
$strPicnikUrl = "http://www.picnik.com/service";
	
// $aPicnikParams collects together all the params we'll give Picnik.  Start with an API key
$aPicnikParams['_apikey'] = $apikey;
	
// tell Picnik where to send the exported image
$aPicnikParams['_export'] = $strRoot;
	
// give the export button a title
$aPicnikParams['_export_title'] = "King Me!";
	
// turn on the close button, and tell it to come back here
$aPicnikParams['_close_target'] = $strRoot;
	
// send in the previous "king" image in case the user feels like decorating it
$aPicnikParams['_import'] = $strRoot . "img/king.jpg";
	
// tell Picnik to redirect the user to the following URL after the HTTP POST instead of just redirecting to _export
$aPicnikParams['_redirect'] = $strRoot . "?coronation";
	
// tell Picnik our name.  It'll use it in a few places as appropriate
$aPicnikParams['_host_name'] = "King Of The Hill";
	
// turn off the "Save & Share" tab so users don't get confused
$aPicnikParams['_exclude'] = "&_exclude=out";
	
// See if we've been given a new picture to use as the king. 
// Note that the when Picnik is exporting from its servers, this page will be hit TWICE.  
// Once will be the POST with the image data contained in $_FILES.  The second will be
// a GET of the _redirect URL we passed in above.
if (isset($_FILES['file'])) {
	// retrieve the image's attributes from the $_FILES array
	$image_tmp_filename = $_FILES['file']['tmp_name'];	
	$image_data = file_get_contents( $image_tmp_filename );
	file_put_contents( "img/king.jpg", $image_data );	
}
if (isset($_POST['address'])) {
	$address = htmlentities( $_POST['address'] );
	file_put_contents( "img/address.txt", $address );
} else {
	// read in the "address.txt" file so that we know what to call the King.
	// This data was posted to us from the Picnik server on a previous call.
	$address = @file_get_contents( "img/address.txt" );
}

// When you're debugging this kind of application, keep in mind that Picnik will
// invoke your script twice: once with the POST'd data, and then again with
// a GET to the value of the _redirect parameter.  To see what happens
// on the first (POST) call, you can use something like the below to dump 
// variables to a debug file.
//$debug = "";
//$debug .= "\n\nFILES: " . print_r($_FILES, true);
//$debug .= "\n\nPOST: " . print_r($_POST, true);
//$debug .= "\n\nGET: " . print_r($_GET, true);
//file_put_contents( "img/debug." . time() . ".txt", $debug);

?>
<html>
<head>
	<title>Picnik Sample: King Of The Hill</title>
</head>
<body>
<h2>Welcome to King Of The Hill!</h2>
<p>King Of The Hill is an easy game to play.  Just use Picnik to upload a photo, and <b>you win!</b></p>
<p><font size="-1">(King Of The Hill is a Picnik API sample that demonstrates how to receive an image from Picnik's servers via HTTP POST.  You can <a href="index.php?source">view the source code</a> for this page.</font>)</p>
<?php 
	if( isset( $_POST["coronation"] ) ) {
		echo "<hr/>";
		
		echo "<h3>You're the new King!  Congratulations, " . $_POST["coronation"] . "!<h3>";
	} else {
		// echo a form so that new Kings can be crowned
		echo "<hr/>";
		echo "<h3>Become the new King!</h3>";
		echo	"<p>Just tell us how you would like to be addressed at your coronation:</p>\n";
		echo "<form method='POST' action='$strPicnikUrl'>\n";
		
		// put all the API parameters into the form as hidden inputs
		foreach( $aPicnikParams as $key => $value ) {
			echo "<input type='hidden' name='$key' value='$value'/>\n";
		}
		
		// anything that doesn't start with an underscore will be sent back to us.  
		// We'll use the "coronation" value to determine when we've got a new King.
		echo "<input type='text' name='address' value='Your Majesty'/>\n";
		echo "<input type='submit' value='Crown Me!'/>\n";
		echo "</form>";
	}
?>
<hr/>
<h2>Long Live The King!</h2>
<img src="img/king.jpg?<?php echo microtime(true); ?>" border="0">
<p>The King would like to be addressed as <b>"<?php echo $address ?>"</b>.</p>
</body>
</html>

*/

?>