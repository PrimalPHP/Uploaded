<?php 

namespace Primal\Uploaded;

class UploadException extends \Exception {
	
	public $upload_error;
	
	function __construct($error, $message) {
		$this->upload_error = $error;
		
		parent::__construct($message);
	}
	
}

