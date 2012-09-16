<?php 

namespace Primal\Uploaded;

/**
 * Primal UploadedFiles class
 * Parses the contents of the $_FILES superglobal and generates an iterable collection of sanitized UploadedFile objects 
 *
 * @package Primal.Upload
 * @author Jarvis Badgley
 */
class Files implements \IteratorAggregate, \ArrayAccess, \Countable  {
	
	private $files = array();
	private $files_by_name = array();
	
	/**
	 * Singleton variable.
	 *
	 * @var UploadedFiles
	 * @static
	 * @access private
	 */
	static private $singleton;
	
	/**
	 * undocumented function
	 *
	 * @return UploadedFiles
	 */
	static public function GetInstance() {
		//Using self instead of static so that we're only accessing the local private variable
		//allowing subclasses to override with their own singleton implementation.
		return self::$singleton ?: self::$singleton = new self();
	}
	
	/**
	 * Private constructor ensures singleton
	 *
	 */
	private function __construct() {

		$this->processBranch($_FILES, $this->files_by_name);

	}

	private function processBranch($branch, &$collection, $branch_name = '') {
		
		foreach ($branch as $field => $leaves) {
			
			$branch_subname = $branch_name . ($branch_name ? "[$field]" : $field);
			
			if (is_array($leaves['error'])) {
				//multiple files were uploaded under this key, so flop the array to produce a sane collection
				$leaves = self::array_flop($leaves);
				
				if (!isset($leaves['tmp_name']) || !isset($leaves['error']) || !isset($leaves['name']) || !isset($leaves['type'])) {
					//if we don't see the typical file item format, then this is a nested form element array and we need to parse as a new root
					if (!isset($collection[$field])) $collection[$field] = array();
					$this->processBranch($leaves, $collection[$field], $branch_subname);
					continue;
				}
				
			} else {
				//only a single file was uploaded under this name, but we want to work in a collection, so wrap it.
				$leaves = array($leaves);
			}
			
			foreach ($leaves as $leaf) {
				//check if the file was actually uploaded.  if not, then it was just an empty upload field and we can skip it
				if ($leaf['error'] != UPLOAD_ERR_NO_FILE) {
					$file = new File($leaf, $branch_subname, $field);
					$this->files[] = $file;
					$collection[$field] = $file;
				}
				
			}
			
		}
		
		
	}

/**
	arrayaccess
*/

	public function offsetGet($key){
		return $this->files_by_name[$key];
	}

	public function offsetExists($key) {
		return isset($this->files_by_name[$key]);
	}

	public function offsetSet($key, $value){
		//ignore
	}

	public function offsetUnset($key){
		//ignore
	}

/**
	Countable and IteratorAggrigate
*/

	function count() {
		return count($this->files);
	}

	public function getIterator() {
		return new \ArrayIterator($this->files);
	}

/**
	Private Utility Functions
*/
	
	
	/**
	 * Takes a named array of indexed arrays and returns an indexed array of named arrays.
	 * Useful for converting a collection of indexed form fields (ie: name="field[]") and getting individual records
	 *
	 * @param array $input The array to be flopped
	 * @return array
	 */
	static public function array_flop($input) {
		$output = array();
		foreach ($input as $key=>$collection) {
			foreach ($collection as $i=>$value) {
				$output[$i][$key] = $value;
			}
		}
		return $output;
	}
	
}

