<?php 

namespace Primal\Uploaded;

class File {
	
	const ERR_OK = 0;
	const ERR_TOO_LARGE = 1;
	const ERR_INCOMPLETE = 2;
	
	public $field;
	public $index = 0;

	public $valid = false;
	
	public $path = false;
	public $type = false;
	public $size = false;
	public $extension = false;
	public $basename = false;
	public $filename = false;
	
	public $error = 0;
	protected $raw;
	
	public function __construct($unit, $field = '', $index = 0) {
		$this->field = $field;
		$this->index = $index;
		$this->raw = $unit;
		
		$this->valid = ($unit['error'] == UPLOAD_ERR_OK);
		if (!$this->valid) {
			switch ($unit['error']) {
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new UploadException($unit['error'], "PHP Runtime Error: Upload temporary folder is either missing or undefined.");
				
			case UPLOAD_ERR_CANT_WRITE:
				throw new UploadException($unit['error'], "PHP Runtime Error: Upload temporary folder is not writable.");
				
			case UPLOAD_ERR_EXTENSION:
				throw new UploadException($unit['error'], "PHP Runtime Error: An unknown extension has blocked file uploads.");
				
			case UPLOAD_ERR_PARTIAL:
				$this->error = self::ERR_INCOMPLETE;
				break;
				
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$this->error = self::ERR_TOO_LARGE;
				break;
			}
			
		}
		
		$this->basename = $unit['name'] ?: false;
		if ($this->basename) {
			$info = pathinfo($this->basename);
			$this->filename = $info['filename'];
			$this->extension = $info['extension'];
		}
		
		$this->size = $unit['size'];
		
		//only set the path if the upload was valid and has a temporary file which exists
		$this->path = $this->valid && $unit['tmp_name'] && file_exists($unit['tmp_name']) ? $unit['tmp_name'] : false;

		if ($this->path) {
			//if temp file exists, let's identify the file type
			
			//first try detecting known filetypes using the contents. grab 4k from the file for checking
			$h = fopen($this->path, 'r');
			$chunk = fread($h, 4096);
			fclose($h);
			if ($chunk) {
				$this->type = self::mimetype_by_content($chunk, $this->extension);
			}
			
			
			if (!$this->type && $this->extension) {
				//couldn't identify by contents. If an extension is provided, try to identify from that
				$this->type = self::mimetype_by_extension($this->extension);
			}
			
			if (!$this->type && $unit['type']) {
				//still couldn't identify. Did the browser provide a type?
				$this->type = $unit['type'];
			}
			
		}
		
	}
	
	/**
	 * Moves the uploaded file to a new destination, if the file can be moved.
	 *
	 * @param string $new_path 
	 * @return boolean
	 */
	public function moveTo($new_path) {
		if (!$this->path) return false;
		
		return move_uploaded_file($this->path, $new_path);
	}
	
	/**
	 * Opens the file for reading or writing and returns an SplFileObject
	 * See http://www.php.net/manual/en/class.splfileobject.php for more details
	 *
	 * @param string $mode 
	 * @return SplFileObject | false
	 */
	public function open($mode='r') {
		if (!$this->path) return false;

		return new SplFileObject($this->path, $mode);
	}
	
	/**
	 * Returns the original file record
	 *
	 * @return void
	 * @author Jarvis Badgley
	 */
	public function getRawFileRecord() {
		return $this->raw;
	}
	
/**
	Private Utility Functions
*/	
	
	/**
	 * Looks for specific bytes in a file to determine the mime type of the file
	 * 
	 * @author     Will Bond [wb] <will@flourishlib.com>
	 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  string $content    The first 4 bytes of the file content to use for byte checking
	 * @param  string $extension  The extension of the filetype, only used for difficult files such as Microsoft office documents
	 * @return string  The mime type of the file
	 */
	static private function mimetype_by_content($content, $extension) {
		$length = strlen($content);
		$_0_8   = substr($content, 0, 8);
		$_0_6   = substr($content, 0, 6);
		$_0_5   = substr($content, 0, 5);
		$_0_4   = substr($content, 0, 4);
		$_0_3   = substr($content, 0, 3);
		$_0_2   = substr($content, 0, 2);
		$_8_4   = substr($content, 8, 4);
		
		// Images
		if ($_0_4 == "MM\x00\x2A" || $_0_4 == "II\x2A\x00") {
			return 'image/tiff';	
		}
		
		if ($_0_8 == "\x89PNG\x0D\x0A\x1A\x0A") {
			return 'image/png';	
		}
		
		if ($_0_4 == 'GIF8') {
			return 'image/gif';	
		}
		
		if ($_0_2 == 'BM' && $length > 14 && in_array($content[14], array("\x0C", "\x28", "\x40", "\x80"))) {
			return 'image/x-ms-bmp';	
		}
		
		$normal_jpeg    = $length > 10 && in_array(substr($content, 6, 4), array('JFIF', 'Exif'));
		$photoshop_jpeg = $length > 24 && $_0_4 == "\xFF\xD8\xFF\xED" && substr($content, 20, 4) == '8BIM';
		if ($normal_jpeg || $photoshop_jpeg) {
			return 'image/jpeg';	
		}
		
		if (preg_match('#^[^\n\r]*\%\!PS-Adobe-3#', $content)) {
			return 'application/postscript';			
		}
		
		if ($_0_4 == "\x00\x00\x01\x00") {
			return 'application/vnd.microsoft.icon';	
		}
		
		
		// Audio/Video
		if ($_0_4 == 'MOVI') {
			if (in_array($_4_4, array('moov', 'mdat'))) {
				return 'video/quicktime';
			}	
		}
		
		if ($length > 8 && substr($content, 4, 4) == 'ftyp') {
			
			$_8_3 = substr($content, 8, 3);
			$_8_2 = substr($content, 8, 2);
			
			if (in_array($_8_4, array('isom', 'iso2', 'mp41', 'mp42'))) {
				return 'video/mp4';
			}	
			
			if ($_8_3 == 'M4A') {
				return 'audio/mp4';
			}
			
			if ($_8_3 == 'M4V') {
				return 'video/mp4';
			}
			
			if ($_8_3 == 'M4P' || $_8_3 == 'M4B' || $_8_2 == 'qt') {
				return 'video/quicktime';	
			}
		}
		
		// MP3
		if (($_0_2 & "\xFF\xF6") == "\xFF\xF2") {
			if (($content[2] & "\xF0") != "\xF0" && ($content[2] & "\x0C") != "\x0C") {
				return 'audio/mpeg';
			}	
		}
		if ($_0_3 == 'ID3') {
			return 'audio/mpeg';	
		}
		
		if ($_0_8 == "\x30\x26\xB2\x75\x8E\x66\xCF\x11") {
			if ($content[24] == "\x07") {
				return 'audio/x-ms-wma';
			}
			if ($content[24] == "\x08") {
				return 'video/x-ms-wmv';
			}
			return 'video/x-ms-asf';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'AVI ') {
			return 'video/x-msvideo';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'WAVE') {
			return 'audio/x-wav';	
		}
		
		if ($_0_4 == 'OggS') {
			$_28_5 = substr($content, 28, 5);
			if ($_28_5 == "\x01\x76\x6F\x72\x62") {
				return 'audio/vorbis';	
			}
			if ($_28_5 == "\x07\x46\x4C\x41\x43") {
				return 'audio/x-flac';	
			}
			// Theora and OGM	
			if ($_28_5 == "\x80\x74\x68\x65\x6F" || $_28_5 == "\x76\x69\x64\x65") {
				return 'video/ogg';		
			}
		}
		
		if ($_0_3 == 'FWS' || $_0_3 == 'CWS') {
			return 'application/x-shockwave-flash';	
		}
		
		if ($_0_3 == 'FLV') {
			return 'video/x-flv';	
		}
		
		
		// Documents
		if ($_0_5 == '%PDF-') {
			return 'application/pdf'; 	
		}
		
		if ($_0_5 == '{\rtf') {
			return 'text/rtf';	
		}
		
		// Office '97-2003 or Office 2007 formats
		if ($_0_8 == "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" || $_0_8 == "PK\x03\x04\x14\x00\x06\x00") {
			if (in_array($extension, array('xlsx', 'xls', 'csv', 'tab'))) {
				return 'application/vnd.ms-excel';	
			}
			if (in_array($extension, array('pptx', 'ppt'))) {	
				return 'application/vnd.ms-powerpoint';
			}
			// We default to word since we need something if the extension isn't recognized
			return 'application/msword';
		}
		
		if ($_0_8 == "\x09\x04\x06\x00\x00\x00\x10\x00") {
			return 'application/vnd.ms-excel';	
		}
		
		if ($_0_6 == "\xDB\xA5\x2D\x00\x00\x00" || $_0_5 == "\x50\x4F\x5E\x51\x60" || $_0_4 == "\xFE\x37\x0\x23" || $_0_3 == "\x94\xA6\x2E") {
			return 'application/msword';	
		}
		
		
		// Archives
		if ($_0_4 == "PK\x03\x04") {
			return 'application/zip';	
		}
		
		if ($length > 257) {
			if (substr($content, 257, 6) == "ustar\x00") {
				return 'application/x-tar';	
			}
			if (substr($content, 257, 8) == "ustar\x40\x40\x00") {
				return 'application/x-tar';	
			}
		}
		
		if ($_0_4 == 'Rar!') {
			return 'application/x-rar-compressed';	
		}
		
		if ($_0_2 == "\x1F\x9D") {
			return 'application/x-compress';	
		}
		
		if ($_0_2 == "\x1F\x8B") {
			return 'application/x-gzip';	
		}
		
		if ($_0_3 == 'BZh') {
			return 'application/x-bzip2';	
		}
		
		if ($_0_4 == "SIT!" || $_0_4 == "SITD" || substr($content, 0, 7) == 'StuffIt') {
			return 'application/x-stuffit';	
		}	
		
		
		// Text files
		if (strpos($content, '<?xml') !== FALSE) {
			if (stripos($content, '<!DOCTYPE') !== FALSE) {
				return 'application/xhtml+xml';
			}
			if (strpos($content, '<svg') !== FALSE) {
				return 'image/svg+xml';
			}
			if (strpos($content, '<rss') !== FALSE) {
				return 'application/rss+xml';
			}
			return 'application/xml';	
		}   
		
		if (strpos($content, '<?php') !== FALSE || strpos($content, '<?=') !== FALSE) {
			return 'application/x-httpd-php';	
		}
		
		if (preg_match('#^\#\![/a-z0-9]+(python|perl|php|ruby)$#mi', $content, $matches)) {
			switch (strtolower($matches[1])) {
			case 'php':
				return 'application/x-httpd-php';
			case 'python':
				return 'application/x-python';
			case 'perl':
				return 'application/x-perl';
			case 'ruby':
				return 'application/x-ruby';
			}	
		}
		
		
		// Default
		return false;
	}
	
	
	/**
	 * Uses the extension of the all-text file to determine the mime type
	 * 
	 * @author     Will Bond [wb] <will@flourishlib.com>
	 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  string $extension  The file extension
	 * @return string  The mime type of the file
	 */
	static private function mimetype_by_extension($extension) {
		switch ($extension) {
		case 'css':
			return 'text/css';
		
		case 'csv':
			return 'text/csv';
		
		case 'htm':
		case 'html':
		case 'xhtml':
			return 'text/html';
			
		case 'ics':
			return 'text/calendar';
		
		case 'js':
			return 'application/javascript';
		
		case 'php':
		case 'php3':
		case 'php4':
		case 'php5':
		case 'inc':
			return 'application/x-httpd-php';
			
		case 'pl':
		case 'cgi':
			return 'application/x-perl';
		
		case 'py':
			return 'application/x-python';
		
		case 'rb':
		case 'rhtml':
			return 'application/x-ruby';
		
		case 'rss':
			return 'application/rss+xml';
			
		case 'tab':
			return 'text/tab-separated-values';
		
		case 'vcf':
			return 'text/x-vcard';
		
		case 'xml':
			return 'application/xml';
		}
		return false;
	}
}