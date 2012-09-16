<?php 

error_reporting(E_ALL & ~E_NOTICE); //disabling notices for the sake of the demo

include 'classes/Primal/Uploaded/File.php';
include 'classes/Primal/Uploaded/Files.php';

$files = Primal\Uploaded\Files::GetInstance();

?><!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>File Example</title>
</head>
<body>
	<form method="post" accept-charset="utf-8" enctype="multipart/form-data">
		<p>standalone</p>
		<input type="file" name="standalone">
		<pre><?php var_dump($files['standalone']); ?></pre>
		<hr>
		<p>nested[]</p>
		<input type="file" name="nested[]"><br>
		<input type="file" name="nested[]"><br>
		<pre><?php var_dump($files['nested']); ?></pre>
		<hr>
		<p>deep[nested][collection][]</p>
		<input type="file" name="deep[nested][collection][]"><br>
		<pre><?php var_dump($files['deep']['nested']['collection']); ?></pre>
		<hr>
		<p>deep[nested][property]</p>
		<input type="file" name="deep[nested][property]"><br>
		<pre><?php var_dump($files['deep']['nested']['property']); ?></pre>
		<hr>
		<input type="submit" value="Upload Files">
		
<?php if ($files->count()): ?>
		<hr>
		<h2>All Uploads:</h2>
		<pre>
<?php

		foreach ($files as $file) {
			var_dump($file);
		}

?>
		</pre>

<?php endif; ?>

	</form>
</body>
</html>