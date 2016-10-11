<?php
class SMTH
{
	//-----------------------------------------
	// smthplayer 0.12 - smth class (rel: 10/12/2012)
	// Powered by Matteo Seclì <secli.matteo@gmail.com>
	// Released under GPLv2 licence or higher.
	//----------------------------------------- 
	// This class is free software: you can redistribute it and/or modify
	// it under the terms of the GNU General Public License as published by
	// the Free Software Foundation, either version 3 of the License, or
	// (at your option) any later version.
	// Rai.php is distributed in the hope that it will be useful,
	// but WITHOUT ANY WARRANTY; without even the implied warranty of
	// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	// GNU General Public License for more details.
	// -----------------------------------------
	// You should have received a copy of the GNU General Public License
	// along with rai.php.  If not, see <http://www.gnu.org/licenses/>.
	//-----------------------------------------
	// See smthplayer.php header for general infos and conditions.
	//-----------------------------------------

	//-- Parser
	//-----------------------------------------
	public $conta;
	public $failed = 0;
	public $StreamIndex = 0;
	public $first;
	public $tipo = "video";
	public $v_start = array();
	public $a_start = array();
	public $base_url = array();

	public $v_chunks = array( array() );
	public $a_chunks = array( array() );

	public $v_next;
	public $a_next;
	//-----------------------------------------

	//-- Downloader
	//-----------------------------------------
	public $AudioTS;
	public $VideoTS;
	public $AudioRaw;
	public $AudioChunk;
	public $VideoTemp;
	public $VideoChunk;
	public $v_size;
	public $a_size;
	public $v_payload;
	public $a_payload;
	public $ASRate;
	public $suffix;
	//-----------------------------------------

	//-- Initialize
	//-----------------------------------------
	function get_temp_dir() {
		$temp = sys_get_temp_dir();
		strpos($temp, "/") == 0 ? $sep = '/' : $sep = "";
		return $temp . $sep;
	}

	function initialize ($abs_path, $v_method, $a_method, $VCPData, $AWaveFormatEx, $ASamplingRate) {
		$temp = $this->get_temp_dir();
		$this->suffix = uniqid();
		strpos($temp, "/") == 0 ? $sep = '/' : $sep = "";
		switch ($v_method) {
			case 'grab_wvc1':
				$this->VideoTS = $temp . $sep . "Video".$this->suffix.".vc1";
				/* Video Header */
				$VideoHdr = pack("H*" , "$VCPData");
				file_put_contents($this->VideoTS, $VideoHdr);
				break;
			case 'grab_avc1':
				$this->VideoTS = $temp . $sep . "Video".$this->suffix.".264";
				$this->VideoTemp = $temp . $sep . "Temp".$this->suffix.".264";
				$this->VideoChunk = $temp . $sep . "Chunk".$this->suffix.".264";
				/* Video Header */
				$VideoHdr = pack("H*" , "$VCPData");
				file_put_contents($this->VideoTS, $VideoHdr);
				break;
		}
		switch ($a_method) {
			case 'grab_wmav2':
				$this->AudioTS = $temp . $sep . "Audio".$this->suffix.".wav";
				$AudioHdr  = "\x52\x49\x46\x46"; // 'RIFF'
				$AudioHdr .= "\x30\xFF\xFF\xFF"; // chunk size
				$AudioHdr .= "\x57\x41\x56\x45\x66\x6D\x74\x20\x1C\x00\x00\x00"; // 'WAVE' // 'fmt ' // sub chunk size
				$AudioHdr .= pack("H*" , "$AWaveFormatEx");
				$AudioHdr .= "\x64\x61\x74\x61";
				$AudioHdr .= "\x00\xFF\xFF\xFF";
				file_put_contents($this->AudioTS, $AudioHdr);
				break;
			case 'grab_wmapro':
				$this->AudioTS = $temp . $sep . "Audio".$this->suffix.".wav";
				$AudioHdr  = "\x52\x49\x46\x46"; // 'RIFF'
				$AudioHdr .= "\x00\x00\x00\x00"; // chunk size
				$AudioHdr .= "\x57\x41\x56\x45\x66\x6D\x74\x20\x24\x00\x00\x00"; // 'WAVE' // 'fmt ' // sub chunk size = 40
				$AudioHdr .= pack("H*" , "$AWaveFormatEx");
				$AudioHdr .= "\x66\x61\x63\x74";  // 'fact'
				$AudioHdr .= "\x04\x00\x00\x00"; // size of fact chunk
				$AudioHdr .= "\x00\x00\x00\x00"; // dwSampleLength
				$AudioHdr .= "\x64\x61\x74\x61\x30\xFF\xFF\xFF"; // 'data' // subchunk size		//Fake chunk size to prevent mplayer stopping! ;)
				file_put_contents($this->AudioTS, $AudioHdr);
				break;
			case 'grab_aac':
				$this->AudioTS = $temp . $sep . "Audio".$this->suffix.".raw";
				$this->ASRate = $ASamplingRate;
				break;
		}
	}
	//-----------------------------------------


//-----------------BEGINNING MkVid.264.php-----------------//
// This file was originally an independent file called by the program.
// Since version 0.6, it's included in smth.php itself for same reasons:
// - This makes smthplayer faster:
// - There were some problems with certain versions of php.
//-----------------------------------------
// MkVid264.php is powered under the GPLv2 or higher licence.
// It's the php porting by mitm of the k0wal5ky's bash function, with some fixes by k0wal5ky himself.
// Thanks to both mitm and k0wal5ky for this file.
//-----------------------------------------
        function PrintHexBytes($string, $hex=true, $spaces=true, $htmlencoding='UTF-8') {
                $returnstring = '';
                for ($i = 0; $i < strlen($string); $i++) {
                        if ($hex) {
                                $returnstring .= str_pad(dechex(ord($string{$i})), 2, '0', STR_PAD_LEFT);
                        } else {
                                $returnstring .= ' '.(preg_match("#[\x20-\x7E]#", $string{$i}) ? $string{$i} : '�');
                        }
                        if ($spaces) {
                                $returnstring .= ' ';
                        }
                }
                if (!empty($htmlsafe)) {
                        if ($htmlencoding === true) {
                                $htmlencoding = 'UTF-8';
                        }
                        $returnstring = htmlentities($returnstring, ENT_QUOTES, $htmlencoding);
                }
                return $returnstring;
        }
        function BigEndian2Int($byteword, $synchsafe=false, $signed=false) {
                $intvalue = 0;
                $bytewordlen = strlen($byteword);
                if ($bytewordlen == 0) {
                        return false;
                }
                for ($i = 0; $i < $bytewordlen; $i++) {
                        if ($synchsafe) {
                                $intvalue += (ord($byteword{$i}) & 0x7F) * pow(2, ($bytewordlen - 1 - $i) * 7);
                        } else {
                                $intvalue += ord($byteword{$i}) * pow(256, ($bytewordlen - 1 - $i));
                        }
                }
                if ($signed && !$synchsafe) {
                        if ($bytewordlen <= PHP_INT_SIZE) {
                                $signMaskBit = 0x80 << (8 * ($bytewordlen - 1));
                                if ($intvalue & $signMaskBit) {
                                        $intvalue = 0 - ($intvalue & ($signMaskBit - 1));
                                }
                        } else {
                                throw new Exception('ERROR: Cannot have signed integers larger than '.(8 * PHP_INT_SIZE).'-bits ('.strlen($byteword).') in getid3_lib::BigEndian2Int()');
                                break;
                        }
                }
                return $intvalue;
        }
	function MkVid264() {
		//Fixes by Matteo Seclì
		$string = file_get_contents($this->VideoTemp, null, null);
		for($offset = 0, $unit = 0; $offset < filesize($this->VideoTemp); $offset+=$size+4, $unit++) {
			$size = $this->BigEndian2Int(file_get_contents($this->VideoTemp, null, null, $offset, 4));
			$data = file_get_contents($this->VideoTemp, null, null, $offset + 4, $size);
			$slice = file_get_contents($this->VideoTemp, null, null, $offset, $size + 4);
			$type = file_get_contents($this->VideoTemp, null, null, $offset + 4, 1);
			file_put_contents($this->VideoChunk, "\x00\x00\x00\x01", FILE_APPEND);
			file_put_contents($this->VideoChunk, $data, FILE_APPEND);
		}
        }
//-----------------END MkVid.264.php-----------------//


	//-- Downloader
	function download($v_chunk, $a_chunk, $v_method, $a_method) {
		//--FIX BY k0wal5ky
		$sync = false;
		// (strval($this->a_next) - strval($this->v_next)) < 0 ? $sign = -1 : $sign = 1;
		$shift = (strval($this->a_next) - strval($this->v_next)) / 10000000; // * $sign;
		if($shift < 2 || $shift > 100) {	// If $shift it's a huge number, there is something wrong in the extracted timestamps. So, the class does not skip the chunk and let try smthplayer to use timestamps from manifest.
			$err = $this->$a_method($a_chunk);
			sleep(0.5);
		} else {
			echo "Syncing A/V...Audio chunk skipped\n";
			$sync = true;
			$err = false;
		}
		//--
		if (!($err)) $err = $this->$v_method($v_chunk);
		if (!($err)) {
			$this->v_size = file_put_contents($this->VideoTS, $this->v_payload, FILE_APPEND | LOCK_EX);
			if (!($sync)) $this->a_size = file_put_contents($this->AudioTS, $this->a_payload, FILE_APPEND | LOCK_EX);
			$this->conta++;
		} else {
			echo "Error downloading chunks - sleeping 2 seconds...\n";
			sleep(2);
			if(5 > $this->failed++) {
				echo "Retrying ($this->failed/5)...\n";
				$this->download($v_chunk, $a_chunk);
			} else {
				echo "Server or network error. Quit.\n";
				exit;
			}
		}
		if ($err) $shift = $err;
		return $shift;
	}

	function grab_wvc1($v_chunk) {
		$data = file_get_contents($v_chunk);
		if (file_exists($this->VideoTemp)) unlink($this->VideoTemp);
		if (file_exists($this->VideoChunk)) unlink($this->VideoChunk);
		$Upos = strrpos($data, "uuid");
		$temp = substr($data, $Upos+25, 8);
		$temp = bin2hex($temp);
		$this->v_next = hexdec($temp);
		$TsPos = strrpos($data, "mdat");
		$this->v_payload = substr($data, $TsPos+4);
		return false; 
	}

	function grab_avc1($v_chunk) {
		$data = file_get_contents($v_chunk);
		if (file_exists($this->VideoTemp)) unlink($this->VideoTemp);
		if (file_exists($this->VideoChunk)) unlink($this->VideoChunk);
		$Upos = strrpos($data, "uuid");
		$temp = substr($data, $Upos+25, 8);
		$temp = bin2hex($temp);
		$this->v_next = hexdec($temp);
		$TsPos = strrpos($data, "mdat");
		$this->v_payload = substr($data, $TsPos+4);
		file_put_contents($this->VideoTemp, $this->v_payload, LOCK_EX);
		$this->MkVid264();
		$this->v_payload = file_get_contents($this->VideoChunk);
		return false; 
	}

	function grab_wmav2($a_chunk) {
		$data = file_get_contents($a_chunk);
		$Upos = strrpos($data, "uuid");
		$temp = substr($data, $Upos+25, 8);
		$temp = bin2hex($temp);
		$this->a_next = hexdec($temp);
		$TsPos = strrpos($data, "mdat");
		$this->a_payload = substr($data, $TsPos+4);
		return false;
	}

	function grab_wmapro($a_chunk) {
		$data = file_get_contents($a_chunk);
		$Upos = strrpos($data, "uuid");
		$temp = substr($data, $Upos+25, 8);
		$temp = bin2hex($temp);
		$this->a_next = hexdec($temp);
		$TsPos = strrpos($data, "mdat");
		$this->a_payload = substr($data, $TsPos+4);
		return false;
	}

	function grab_aac($a_chunk) {
		$data = file_get_contents($a_chunk);
		$Upos = strrpos($data, "uuid");
		$temp = substr($data, $Upos+25, 8);
		$temp = bin2hex($temp);
		$this->a_next = hexdec($temp);
		$TsPos = strrpos($data, "mdat");
		$this->a_payload = substr($data, $TsPos+4);
		return false;
	}
	//-----------------------------------------
	//-- EOF Downloader
}
?>
