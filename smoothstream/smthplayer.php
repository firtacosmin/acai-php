#!/usr/bin/php
<?php
//-----------------------------------------
// smthplayer 0.12 (rel: 10/12/2012)
// Powered by Matteo Seclì <secli.matteo@gmail.com>
// Released under GPLv2 licence or higher.
//
// Using smth.php class and MkVid264.php (by mitm and k0wal5ky)
//
// smthplayer is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// By using this program, you accept that I HAVE NO RESPONSIBILITY
// for any use you make of it. Therefore, make sure you can play with a standalone player
// the stream you'd like to watch.
//
// Although this program is an evolution of old raibot.php 0.0.2a <http://acab.servebeer.com/php/src/>
// by mitm, it has no more relationship with rai.tv. Its aim is to be a universal cross-platform smooth
// streaming player, not a bot for rai.tv site. Here is what is changed:
//
// [See the README file to read the Changelog]
//
// I want also to say clearly that this program is a player, not a downloader! And it will NEVER be
// a downloader. In fact, audio and video temporary files are always kept separated and never joined in a
// audio/video cointainer. This is for legal reasons, so please DO NOT ask me to make a recording function.
//-----------------------------------------

$mplayer = "mplayer";
$vc1_codec = "ffvc1";
$vc1_adaptive_codec = "wmvvc1dmo";
$h264_codec = "ffh264";
//$h264_codec = "ffh264 -fps 25";
$wmav2_codec = "ffwmav2";
$wmapro_codec = "ffwmapro";
$aac_codec = "faad";
$playfile = "PlayMe.sh";
$automatic_play = "yes";
$buffer_time = "10";
$init_delay = "5";
$mplayer_options = "-idle -mc 0 -autosync 30";

$longopts  = array(
	"ism:",
	"vidx:",
	"manifest:",
	"clip:",
	"csm:",
	"buffer:",
	"delay:",
	"fs",
	"nosync",
	"vc:",
	"ac:",
);
//$options = getopt("", $longopts);
$options = $_GET;
if (isset($options["buffer"])) $buffer_time = $options["buffer"];
if (isset($options["delay"])) $init_delay = $options["delay"];
if (isset($options["fs"])) $mplayer_options = "-fs ".$mplayer_options;
if (isset($options["csm"])) {
	$options["ism"] = $options["csm"];
	$options["manifest"] = "csm";
}
if (isset($options["vc"])) {
	$vc1_codec = $options["vc"];
	$vc1_adaptive_codec = $options["vc"];
	$h264_codec = $options["vc"];
}
if (isset($options["ac"])) {
	$wmav2_codec = $options["ac"];
	$wmapro_codec = $options["ac"];
	$aac_codec = $options["ac"];
}

echo "SMTHPlayer - a universal cross-platform player for the smooth streaming format.\n";
echo "v0.12 - Powered by Matteo Seclì <secli.matteo@gmail.com>\n";
if ($buffer_time % 2 != 0) {
	exit("Error! \$buffer_time must be an even number!\nQuitting...\n");
}
if (!empty($options["ism"]) && (is_numeric($options["vidx"]) || $options["vidx"] == 'ask' || $options["vidx"] == 'adaptive'))
{
	include('smth.php');
	$tempdir = sys_get_temp_dir();
	strpos($tempdir, "/") == 0 ? $sep = '/' : $sep = '';
	$abs_path = $options["ism"];
	if (!empty($options["manifest"]) && $options["manifest"] != "csm") {
		$manifest = $options["manifest"];
	} elseif (!empty($options["manifest"]) && $options["manifest"] == "csm") {
		if (empty($options["clip"])) exit("Error! Clip number missing!\nSee the guide for details...\n");
		$manifest = $options["ism"];
		if (empty($options["delay"])) $init_delay = "0";
	} else {
		$subfix = "Manifest";
		$manifest = $abs_path . $subfix;
	}
	$bot = new SMTH(); 
	$v_url = array();
	$a_url = array();

	function formatBytes($size, $precision = 2) {
		$units = array(' byte', ' kB', ' MB', ' GB', ' TB'); 
		$unit_pointer = 0;
		while ($size >= 1024) {
			$size = $size/1024;
			$unit_pointer++;
		}
		return round($size, $precision).$units[$unit_pointer];
	}

///////////////////////-----------START ASKING FOR BITRATE------------//////////////////////
	$xml = new SimpleXMLElement($manifest, NULL, TRUE);
	$Video_StreamIndex_XPath = '//StreamIndex[@Type="video"]';	//Useless by now
	$Video_QualityLevel_XPath = '//StreamIndex[@Type="video"]/QualityLevel';
	$Video_Chunk_XPath = '//StreamIndex[@Type="video"]/c';
	$Audio_StreamIndex_XPath = '//StreamIndex[@Type="audio"]';
	$Audio_QualityLevel_XPath = '//StreamIndex[@Type="audio"]/QualityLevel';
	$Audio_Chunk_XPath = '//StreamIndex[@Type="audio"]/c';
	if (!empty($xml['IsLive'])) {
		if (eregi("true",$xml['IsLive'])) {
			$Live = "yes";
			echo "\nLIVE stream detected!\n";
		} else {
			$Live = "no";
		}
	} else {
		$Live = "no";
	}
	if (!empty($options["manifest"]) && $options["manifest"] == "csm") {
		echo "\nAvailable clips:\n";
		$clip_count = 0;
		$ClipManifest_array = array();
		$ClipUrl_array = array();
		$ClipBegin_array = array();
		foreach ($xml->xpath('//Clip') as $Clip) {
			$ClipManifest_array[] = (string) $Clip['Url'];
			$ClipUrl_array[] = substr($ClipManifest_array[$clip_count], 0, -8);
			$ClipBegin_array[] =  (string) $Clip['ClipBegin'];
			echo $clip_count, "\n";
			$clip_count++;
		}
		if ($options["clip"] == 'ask') {
			echo "Type the index of the desired clip:\n";
			$clip_count = chop(fgets(STDIN));
		} else {
			$clip_count = $options["clip"];
		}
		$ClipManifest = $ClipManifest_array[$clip_count];
		$ClipUrl = $ClipUrl_array[$clip_count];
		$ClipBegin = $ClipBegin_array[$clip_count];
		$abs_path = $ClipUrl;
		$Video_StreamIndex_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="video"]';
		$Video_QualityLevel_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="video"]/QualityLevel';
		$Video_Chunk_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="video"]/c';
		$Audio_StreamIndex_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="audio"]';
		$Audio_QualityLevel_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="audio"]/QualityLevel';
		$Audio_Chunk_XPath = '//Clip[@Url="' . $ClipManifest . '" and @ClipBegin="' . $ClipBegin . '"]/StreamIndex[@Type="audio"]/c';
	}

	echo "\nAvailable bitrates:\n";
	echo "Index   Bitrate\n";
	$index_count = 0;
	$VBitrate_array = array();
	$VCPData_array = array();
	$VFourCC_array = array();
	$VMaxWidth_array = array();
	$VMaxHeight_array = array();
	$VCustomAttributes_array = array();
	foreach ($xml->xpath($Video_StreamIndex_XPath) as $VStreamIndex) {
		if (isset($VStreamIndex['Name'])) {
			$VName = (string) $VStreamIndex['Name'];
		} else {
			$VName = "video";
		}
		$VUrlPath = (string) $VStreamIndex['Url'];
	}

/////////////Trying to sort in the correct way the quality levels in a strange-ordered manifest.////////////
	$QualityLevel_array = array();
	foreach ($xml->xpath($Video_QualityLevel_XPath) as $QualityLevel) {
		$QualityLevel_array["Bitrate"][] = (string) $QualityLevel['Bitrate'];
		$QualityLevel_array["CodecPrivateData"][] = (string) $QualityLevel['CodecPrivateData'];
		$QualityLevel_array["FourCC"][] = (string) $QualityLevel['FourCC'];
		$QualityLevel_array["MaxWidth"][] = (string) $QualityLevel['MaxWidth'];
		$QualityLevel_array["MaxHeight"][] = (string) $QualityLevel['MaxHeight'];
		if ($QualityLevel->children()->getName() ==  'CustomAttributes') {
			$QualityLevel_array["CustomAttributes"][] =  ',' . (string) $QualityLevel->CustomAttributes->Attribute['Name'] . '=' . (string) $QualityLevel->CustomAttributes->Attribute['Value'];
		} else {
			$QualityLevel_array["CustomAttributes"][] = '';
		}
	}
	array_multisort($QualityLevel_array["Bitrate"], SORT_DESC, $QualityLevel_array["CodecPrivateData"], $QualityLevel_array["FourCC"], $QualityLevel_array["MaxWidth"], $QualityLevel_array["MaxHeight"], $QualityLevel_array["CustomAttributes"]);
	foreach ($xml->xpath($Video_QualityLevel_XPath) as $QualityLevel) {
		$VBitrate_array[] = $QualityLevel_array["Bitrate"][$index_count];
		$VCPData_array[] = $QualityLevel_array["CodecPrivateData"][$index_count];
		$VFourCC_array[] = $QualityLevel_array["FourCC"][$index_count];
		$VMaxWidth_array[] = $QualityLevel_array["MaxWidth"][$index_count];
		$VMaxHeight_array[] = $QualityLevel_array["MaxHeight"][$index_count];
		if ($QualityLevel->children()->getName() ==  'CustomAttributes') {
			$VCustomAttributes_array[] =  $QualityLevel_array["CustomAttributes"][$index_count];
		} else {
		$VCustomAttributes_array[] = '';
		}
		echo $index_count, "       ", $VBitrate_array[$index_count], " \n";
		$index_count++;
	}
//////////End sorting./////////

	if ($options["vidx"] == 'ask') {
		echo "Type the index of the desired bitrate:\n";
		$index_count = chop(fgets(STDIN));
	} elseif ($options["vidx"] == 'adaptive') {
		$index_count = 0;
	} else {
		$index_count = $options["vidx"];
	}
	$VBitrate = $VBitrate_array[$index_count];
	$VCPData = $VCPData_array[$index_count];
	$VFourCC = $VFourCC_array[$index_count];
	$VMaxWidth = $VMaxWidth_array[0];
	$VMaxHeight = $VMaxHeight_array[0];
	$VCustomAttributes = $VCustomAttributes_array[$index_count];
	echo "\nSelected video bitrate: ", $VBitrate;
	echo "\nDetected video type: ", $VFourCC,"\n";


	$index_count = 0;
	$ABitrate = array();	//Useless by now
	$ASamplingRate = array();	//Useless by now
	$AWaveFormatEx = array();	//Useless by now
	$ACPData = array();	//Useless by now
	$AFourCC = array();	//Useless by now
	$ASubtype = array();	//Useless by now
	$ACustomAttributes = array();	//Useless by now
	foreach ($xml->xpath($Audio_StreamIndex_XPath) as $AStreamIndex) {
		$ASubtype = (string) $AStreamIndex['Subtype'];
		if (isset($AStreamIndex['Name'])) {
			$AName = (string) $AStreamIndex['Name'];
		} else {
			$AName = "audio";
		}
		$AUrlPath = (string) $AStreamIndex['Url'];
	}
	foreach ($xml->xpath($Audio_QualityLevel_XPath) as $QualityLevel) {
		$ABitrate = (string) $QualityLevel['Bitrate'];
		$ASamplingRate = (string) $QualityLevel['SamplingRate'];
		$AWaveFormatEx = (string) $QualityLevel['WaveFormatEx'];
		$AWaveFormatEx_attribs = array((string) $QualityLevel['AudioTag'], (string) $QualityLevel['Channels'], (string) $QualityLevel['SamplingRate'], (string) $QualityLevel['PacketSize'], (string) $QualityLevel['BitsPerSample']);	//TRY TO RREMOVE THIS ARRAY AND PARSE SINGLE ATTRIBUTES, TO AVOID PARSING TWICE "SAMPLINGRATE" AND MAKE MORE SIMPLE THE UNDERSTANDING OF THE SCRIPT!!!
		$ACPData = (string) $QualityLevel['CodecPrivateData'];
		$AFourCC = (string) $QualityLevel['FourCC'];  
		if ($QualityLevel->children()->getName() ==  'CustomAttributes') {
			$ACustomAttributes =  ',' . (string) $QualityLevel->CustomAttributes->Attribute['Name'] . '=' . (string) $QualityLevel->CustomAttributes->Attribute['Value'];
		} else {
			$ACustomAttributes = "";
		}
		$index_count++;
	}
	if ($ASubtype != "") $AFourCC = $ASubtype;
	if (empty($AWaveFormatEx)) :
		if (!empty($AWaveFormatEx_attribs) && eregi("wma",$AFourCC)) :
			$AWaveFormatEx = implode('', array_reverse(str_split(sprintf("%04s",dechex($AWaveFormatEx_attribs[0])), 2)));	//wFormatTag
			$AWaveFormatEx .= implode('', array_reverse(str_split(sprintf("%04s",dechex($AWaveFormatEx_attribs[1])), 2)));	//nChannels
			$AWaveFormatEx .= implode('', array_reverse(str_split(sprintf("%08s",dechex($AWaveFormatEx_attribs[2])), 2)));	//nSamplesPerSec
			$AWaveFormatEx .= implode('', array_reverse(str_split(sprintf("%08s",dechex($AWaveFormatEx_attribs[2]*$AWaveFormatEx_attribs[1]*$AWaveFormatEx_attribs[4]/8)), 2)));	//nAvgBytesPerSec
			$AWaveFormatEx .= implode('', array_reverse(str_split(sprintf("%04s",dechex($AWaveFormatEx_attribs[3])), 2)));	//nBlockAlign
			$AWaveFormatEx .= implode('', array_reverse(str_split(sprintf("%04s",dechex($AWaveFormatEx_attribs[4])), 2)));	//wBitsPerSample
			if ($AWaveFormatEx_attribs[0] == "354") :
				$AWaveFormatEx .= "1200";	//cbSize = 18
			elseif ($AWaveFormatEx_attribs[0] == "353") :
				$AWaveFormatEx .= "0000";	//cbSize = 0
			else:
				exit("Unknown audio format! Quitting...\n");
			endif;
			$AWaveFormatEx .= $ACPData;
		else:
			$AWaveFormatEx = "no";
		endif;
	elseif (eregi("wma",substr($AFourCC, -3)) || $AFourCC == "") :
		if ((substr($AWaveFormatEx, 0, 4) == "6101") || $AWaveFormatEx_attribs[0] == "353") :
			$AFourCC = "Wma2";
		elseif ((substr($AWaveFormatEx, 0, 4) == "6201") || $AWaveFormatEx_attribs[0] == "354") :
			$AFourCC = "WmaPro";
		else:
			exit("Unknown audio format! Quitting...\n");
		endif;
	endif;
	echo "\nSelected audio bitrate: ", $ABitrate;
	echo "\nDetected audio type: ", $AFourCC,"\n";
	echo "\n";
///////////////////////-----------END ASKING FOR BITRATE------------//////////////////////

///////////////////////-----------CHOICING A/V METHODS------------//////////////////////
	$audiofile_opts = "";		//	v0.9
	if (eregi("wvc1",$VFourCC)) :
		$v_method = "grab_wvc1";
		$v_codec = $vc1_codec;
		if ($options["vidx"] == 'adaptive') $v_codec = $vc1_adaptive_codec;
		$v_ext = ".vc1";
	elseif (eregi("avc1",$VFourCC) || eregi("h264",$VFourCC) || eregi("x264",$VFourCC) || eregi("davc",$VFourCC)) :
		$v_method = "grab_avc1";
		$v_codec = $h264_codec;
		$v_ext = ".264";
	else:
		exit("Unknown video format! Quitting...\n");
	endif;
	if (eregi("wma2",$AFourCC)) :
		$a_method = "grab_wmav2";
		$a_codec = $wmav2_codec;
		$a_ext = ".wav";
	elseif (eregi("wmap",$AFourCC)) :
		$a_method = "grab_wmapro";
		$a_codec = $wmapro_codec;
		$a_ext = ".wav";
	elseif (eregi("aac",$AFourCC)) :
		$a_method = "grab_aac";
		$a_codec = $aac_codec;
		$a_ext = ".raw";
		$audiofile_opts = " -audio-demuxer rawaudio -rawaudio rate=".$ASamplingRate.":bitrate=".$ABitrate.":format=0xff";
	else:
		exit("Unknown audio format! Quitting...\n");
	endif;
///////////////////////-----------END CHOICING A/V METHODS------------//////////////////////
	
	if ($options["vidx"] == 'ask' && $Live == "yes") {
		echo "Downloading the manifest again, please wait...\n\n";
		$xml = new SimpleXMLElement($manifest, NULL, TRUE);
	}
///////////////////////-----------CREATING TIMESTAMPS ARRAYS------------//////////////////////
	$v_start = array();
	$v_start = array();
	$v_end = count($xml->xpath($Video_Chunk_XPath));
	$a_end = count($xml->xpath($Audio_Chunk_XPath));
	$timestamp_count = "0";	
	foreach ($xml->xpath($Video_Chunk_XPath) as $v_timestamps) {
		if ((string) $v_timestamps['t'] != "") {
			$v_start[] = (string) $v_timestamps['t'];
			if (!isset($v_start[1]) && (string) $v_timestamps['d'] != "") $v_start[1] = (string) $v_timestamps['d'] + $v_start[0];
		} else {
			if (!isset($v_start[0])) {
				$v_start[0] = 0;
			} else {
				if ($timestamp_count+1 != $v_end) $v_start[$timestamp_count+1] = ($v_start[$timestamp_count] + (string) $v_timestamps['d']);
			}
			if (!isset($v_start[1])) $v_start[1] = (string) $v_timestamps['d'] + $v_start[0];
		$d_chunks = "yes";
		}
	$timestamp_count++;
	}
	$timestamp_count = "0";
	foreach ($xml->xpath($Audio_Chunk_XPath) as $a_timestamps) {
		if ((string) $a_timestamps['t'] != "") {
			$a_start[] = (string) $a_timestamps['t'];
			if (!isset($a_start[1]) && (string) $a_timestamps['d'] != "") $a_start[1] = (string) $a_timestamps['d'] + $a_start[0];
		} else {
			if (!isset($a_start[0])) {
				$a_start[0] = 0;
			}else {
				if ($timestamp_count+1 != $a_end) $a_start[$timestamp_count+1] = ($a_start[$timestamp_count] + (string) $a_timestamps['d']);
			}
			if (!isset($a_start[1])) $a_start[1] = (string) $a_timestamps['d'] + $a_start[0];
		}
	$timestamp_count++;
	}
///////////////////////-----------END CREATING TIMESTAMPS ARRAYS------------//////////////////////

	if ($Live == "no" && empty($options["delay"])) $init_delay = "0";

////////A/V pre-syncing////////
	$init_shift = 0;
	while (($a_start[$init_delay]-$v_start[$init_delay+$init_shift])/10000000 >= 2) $init_shift++;
////////End A/V pre-syncing////////
	
	if ($Live == "no") {
		$chunks = (min((count($v_start)-$init_delay-$init_shift-1), (count($a_start)-$init_delay-1)));
		$duration = gmdate("H:i:s", $chunks*2);
	} else {
		$chunks = "∞";
		$duration = "∞";
	}
	
	if (preg_match("/customattributes/i", $VUrlPath)) {	
		$v_base_url = $abs_path . "QualityLevels($VBitrate" . $VCustomAttributes . ")/Fragments(" . $VName . "=";
	} else {
		$v_base_url = $abs_path . "QualityLevels($VBitrate)/Fragments(" . $VName . "=";
	}
	if (preg_match("/customattributes/i", $AUrlPath)) {	
		$a_base_url = $abs_path . "QualityLevels($ABitrate" . $ACustomAttributes . ")/Fragments(" . $AName . "=";
	} else {
		$a_base_url = $abs_path . "QualityLevels($ABitrate)/Fragments(" . $AName . "=";
	}
	$vurl = $v_base_url . $v_start[$init_delay+$init_shift] . ")";
	$aurl = $a_base_url . $a_start[$init_delay] . ")";
	
	$index_count = 0;
	echo "Video start: $vurl\nAudio start: $aurl\n\n";

	$bot->initialize($abs_path, $v_method, $a_method, $VCPData, $AWaveFormatEx, $ASamplingRate);

	$bot->download($vurl, $aurl, $v_method, $a_method);
	
	if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
		declare(ticks = 1);
		function signal_handler($signal) {
			global $bot;
			global $tempdir;
			global $sep;
			switch($signal) {
				case SIGINT:
					if (file_exists($bot->VideoTS)) unlink($bot->VideoTS);
					if (file_exists($bot->VideoTemp)) unlink($bot->VideoTemp);
					if (file_exists($bot->VideoChunk)) unlink($bot->VideoChunk);
					if (file_exists($bot->AudioTS)) unlink($bot->AudioTS);
					if (file_exists($bot->AudioRaw)) unlink($bot->AudioRaw);
					if (file_exists($bot->AudioChunk)) unlink($bot->AudioChunk);
					if (file_exists($tempdir.$sep."Shift".$bot->suffix)) unlink($tempdir.$sep."Shift".$bot->suffix);
					$return_close = "No MPlayer process opened...";
					if (isset($GLOBALS['mplayer_process'])) {
						global $pipes;
						fclose($pipes[0]);
						$return_close = "MPlayer process closed with exit code ".proc_close($GLOBALS['mplayer_process'])."!";
					}
					exit("\nCtrl+C pressed!\n".$return_close."\nDeleting temp files and quitting...ok!\n");
			}
		}
		pcntl_signal(SIGINT, "signal_handler");
	}

	$dump_start = time();
	$start = date("Y-m-d H:i:s", $dump_start);
	$conta = 0;
	$count = 0; //D
	$skip = 0;
	$paused = "no";
	$played = 0;
	$player_cache = 0;
	$start_chunk_time = 0;
	$end_chunk_time = 0;
	while (($Live == "yes") || ($Live == "no" && (isset($v_start[($bot->conta+$init_delay+$init_shift)]) && isset($a_start[($bot->conta+$init_delay-$skip)])))) {
		$conta++;
		$Vtime = $bot->conta;
		$Vtime = $Vtime * 2;
		$time = time();
		echo "Chunk $bot->conta/$chunks - Start time: $start - Now: ".date("Y-m-d H:i:s", $time)."\n";
		$elapsed = $time-$dump_start;
		echo "Starting timestamp: $dump_start - Now: $time\nElapsed: ".gmdate("H:i:s", $elapsed)." - Vtime: ".gmdate("H:i:s", $Vtime)."/".$duration."\n";
			
		if ($Vtime > $buffer_time) {
			$player_cache = $player_cache+2;
			if ($paused == "no") {
				$played = $played+$end_chunk_time-$start_chunk_time;
				echo $played, "\n";
			}
		}
		if ($played > $player_cache && $paused == "no") {
			fwrite($pipes[0], "osd_show_text Buffering... 3\npause\n");
			$paused = "yes";
		}
		if ($paused == "yes") {
			if ($played <= $player_cache) {
				fwrite($pipes[0], "pause\n");
				$paused = "no";
			}
		}
		$start_chunk_time = $time;

		if ($options["vidx"] == 'adaptive') {
			if ($elapsed > $Vtime) {
				if (isset($VBitrate_array[$index_count+1])) {
					$index_count = $index_count+1;
					$VBitrate = $VBitrate_array[$index_count];
					$VCPData = $VCPData_array[$index_count];
					$VideoHdr = pack("H*" , "$VCPData");
					if ($v_method == "grab_avc1") file_put_contents($bot->VideoTS, $VideoHdr, FILE_APPEND | LOCK_EX);
					echo "Switching to LOWER quality!!! :(\n";
					echo "Video bitrate:     " . $VBitrate_array[$index_count-1] . "   =>   " . $VBitrate . "\n";
				} else {
					echo "**************************************************************\n";
					echo "Your system or your connection is TOO SLOW to play this!!! :(\n";
					echo "**************************************************************\n";
				}
			} elseif (($elapsed < $Vtime) && isset($VBitrate_array[$index_count-1])) {
				$index_count = $index_count-1;
				$VBitrate = $VBitrate_array[$index_count];
				$VCPData = $VCPData_array[$index_count];
				$VideoHdr = pack("H*" , "$VCPData");
				if ($v_method == "grab_avc1") file_put_contents($bot->VideoTS, $VideoHdr, FILE_APPEND | LOCK_EX);
				echo "Switching to BETTER quality!!! :)\n";
				echo "Video bitrate:     " . $VBitrate_array[$index_count+1] . "   =>   " . $VBitrate . "\n";
			}
		}


		if ($Live == "yes") {
			$vurl = $v_base_url . $bot->v_next . ")";
		} else {
			$vurl = $v_base_url . $v_start[$bot->conta+$init_delay+$init_shift] . ")";
		}
		if ($Live == "yes") {
			$aurl = $a_base_url . $bot->a_next . ")";
		} else {
			$aurl = $a_base_url . $a_start[$bot->conta+$init_delay-$skip] . ")";
		}
		

		$shift = str_replace(",", ".", $bot->download($vurl, $aurl, $v_method, $a_method));
		if ($Live != "yes") $shift = str_replace(",", ".", ($a_start[$bot->conta-1+$init_delay-$skip]-$v_start[$bot->conta-1+$init_delay+$init_shift])/10000000);
		if ($shift >= 2 && $shift <= 100) $skip++;
		$video_dl = file_put_contents($tempdir . $sep . "last_video", $vurl);
		$audio_dl = file_put_contents($tempdir . $sep . "last_audio", $aurl);

		if ($Vtime == 2) {
			if ($shift < -100 || $shift > 100) $shift = 0;	
			file_put_contents($tempdir . $sep . "Shift".$bot->suffix, $shift);
		}
		if ($Vtime == $buffer_time && !isset($playfile_on)) {
			file_put_contents($playfile, $mplayer." -ac ".$a_codec." -vc ".$v_codec." ".$mplayer_options." -vf scale=".$VMaxWidth. ":".$VMaxHeight." -delay ".file_get_contents($tempdir.$sep."Shift".$bot->suffix)/-1 ." -audiofile ".$bot->AudioTS." ".$audiofile_opts." ".$bot->VideoTS);
			$playfile_on = "yes";
		}
		if ($Vtime == $buffer_time && $automatic_play == "yes" && !isset($player_on)) {
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("file", $tempdir.$sep."mpout", "a"), 
			2 => array("file", $tempdir.$sep."mperr", "a")
		);
		$mplayer_process = proc_open($mplayer." -ac ".$a_codec." -vc ".$v_codec." -slave ".$mplayer_options." -vf scale=".$VMaxWidth. ":".$VMaxHeight." -delay ".file_get_contents($tempdir.$sep."Shift".$bot->suffix)/-1 ." -audiofile ".$bot->AudioTS." ".$audiofile_opts." ".$bot->VideoTS, $descriptorspec, $pipes);
		$start_play = $time;
		$player_cache = $Vtime - $elapsed;
			$player_on="yes";
		}
		if ($Vtime == ($buffer_time+120)) {
			$data = "\xFF\x01\xC3\xD1\x07\xA2";
			$handle = fopen($bot->VideoTS, "c");
			fwrite($handle, $data);
			fclose($handle);
			file_put_contents($playfile, "");
		}
		echo "Video chunk size: ".formatBytes($bot->v_size)." - Audio chunk size: ".formatBytes($bot->a_size)."\n";
		if ($conta == 1) {
			echo "A/V Shift: " . $shift . "\n";
			$sync = $Vtime-$elapsed;
			if($sync < 0 || $Vtime < $buffer_time) $sync = 0;
			$conta = 0;
			if (($options["vidx"] != 'adaptive' || ($options["vidx"] == 'adaptive' && $VBitrate == $VBitrate_array[0])) && (!isset($options["nosync"]))) {
				echo "Syncing server timestamps. Sleeping $sync second(s)...\n";
				sleep($sync);
			}
		echo "\n";
		}
				$end_chunk_time = time();
	}
	$return_close = "No MPlayer process opened...";
	if (isset($GLOBALS['mplayer_process'])) {
		global $pipes;
		fclose($pipes[0]);
		$return_close = "MPlayer process closed with exit code ".proc_close($GLOBALS['mplayer_process'])."!";
	}
	exit($return_close."\nEnd of stream! Quitting...\n");
} else {
	echo "\nUsage for ism - isml manifests:\n	php smthplayer.php --ism <ISM> --vidx <INDEX_NUMBER|'ask'|'adaptive'> --manifest <Manifest forced url>\n";
	echo "\n'--ism' and '--vidx' arguments are necessary, while the '--manifest' one is optional.\n";
	echo "'--ism' argument is the url of the video with the '/' at the end, while '--vidx' argument is a number (from 0 that indicates highest quality to a maximum number depending on the manifest), or the word 'ask', that makes the program asking you for index number after showing you the available indexes, or the word 'adaptive' that makes the program trying to play an adaptive streaming (still experimental!!!). Use 'ask' option if you are unsure about index number. Options can be given in any order in one of these forms:\n--option=\"value\"	--option \"value\"	--option value\nExamples:\n";
	echo "	php smthplayer.php --ism http://mediadl.microsoft.com/mediadl/iisnet/smoothmedia/Experience/BigBuckBunny_720p.ism/ --vidx ask\n";
	echo "	php smthplayer.php --vidx 2 --ism http://mediadl.microsoft.com/mediadl/iisnet/smoothmedia/Experience/BigBuckBunny_720p.ism/\n";
	echo "\n'--manifest' option forces the manifest url. If the program cannot obtain manifest url simply adding 'Manifest' to the url you gave as first argument, you can specify it manually. Example:\n";
	echo "	php smthplayer.php --ism http://smooth.server.com/smoothmedia/Funny_Video.ism/ --vidx ask --manifest http://manifest.smooth.com/smoothmanifests/Funny_Video.ism/My_strange_manifest\n";
	echo "\nUsage for csm manifests:\n	php smthplayer.php --csm <CSM> --vidx <INDEX_NUMBER|'ask'|'adaptive'> --clip <CLIP_NUMBER|'ask'>\n";
	echo "\nThis syntax is similar to that used for ism manifests. The '--csm' option replaces the '--ism' one, and the argument for this option is the url of the manifest with the '.csm' at the end. You have also to specify the index of the clip by using the '--clip' option. The argument for this option must be a number (from 0 that indicates the first clip to a maximum number depending on the manifest), or the word 'ask', that makes the program asking you for clip number after showing you the available clips. Examples:\n";
	echo "	php smthplayer.php --csm http://localhost/mycustommanifest.csm --vidx ask --clip ask    (Ask for clip and bitrate)\n";
	echo "	php smthplayer.php --csm http://localhost/mycustommanifest.csm --vidx 2 --clip ask    (Ask for clip and use index 2 bitrate)\n";
	echo "	php smthplayer.php --csm http://localhost/mycustommanifest.csm --clip 2 --vidx 0    (Play 3rd clip and use index 0 bitrate)\n";
	echo "	php smthplayer.php --csm http://localhost/mycustommanifest.csm --clip ask --vidx 0    (Ask for clip and use index 0 bitrate)\n";
	echo "\nYou can also pass extra options to the program:\n";
	echo "	--buffer <BUFFER>	Starts SMTHPlayer with the value you give as\n";
	echo "				\$buffer_time (See README);\n\n";
	echo "	--delay <DELAY>		Starts SMTHPlayer with the value you give as\n";
	echo "				\$init_delay (See README).\n";
	echo "				For non-live streams, this starts the video\n";
	echo "				from a time (in seconds) equal to 2*<DELAY>;\n\n";
	echo "	--fs			Starts MPlayer in fullscreen mode if not set\n";
	echo "				by default;\n\n";
	echo "	--nosync		Doesn't sync timestamps with the server;\n\n";
	echo "				Useful for csm manifests;\n\n";
	echo "	--vc <V_CODEC>		Forces MPlayer video codec with that you give;\n\n";
	echo "	--ac <A_CODEC>		Forces MPlayer audio codec with that you give.\n";
}
?>
