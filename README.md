#Primal.Uploaded

Created and Copyright 2012 by Jarvis Badgley, chiper at chipersoft dot com.

Primal.Uploaded is a sanity wrapper for the PHP `$_FILES` super-global designed to make form uploads with nested array fields (ie, file input form elements named with the `name[subname][]` syntax) easier to work with.  It converts the array soup that PHP generates into a sanitized iterable collection of file objects that can be addressed just like normal $_POST contents.

See the included example.php file for usage.

##Files Object

This is the `$_FILES` wrapper class.  It is a singleton implementation and should only ever be instantiated via a call to `Primal\Uploaded\Files::GetInstance()`.  The returned result will be an iterable collection of files.

Individual file fields can be accessed via their array structure (ex: `uploads[]` becomes `$files['uploads'][0]`) or as an overall iterable collection (ex: `foreach ($files as $file) {}`);

The collection will only include records for inputs which received file data (empty fields are ignored), and will include incomplete or oversized uploads.  It is important to test the `valid` property on a file before working with it.

If the object encounters a fatal upload issue such as an unwritable uploads directory (`UPLOAD_ERR_CANT_WRITE`) it will throw an `UploadException`.

##File Object

The File object that the wrapper returns contains the following properties:

- `field`: The form element name that the file was uploaded as
- `index`: If the name was in array syntax, index will contain the specific array index of this file
- `valid`: Boolean, identifies if the file uploaded correctly
- `path`: String, the current temporary location of the file
- `basename`: The original file name
- `filename`: The original file name, minus the extension.
- `extension`: The extension portion of the original file name
- `error`: Integer identifying the state of the file. Note that this does not match the PHP file upload constants.
- `type`: The file's mime-type, automatically determined based on the file's contents or extension.
- `size`: Integer, the total size of the file in bytes.

The following functions are provided:

- `moveTo($path)`: Moves the uploaded file to a new destination.
- `open($mode)`: Opens the file for read and/or writing and returns an SplFileObject.
- `getRawFileRecord()`: Returns the original `$_FILES` array for this upload

##License

Primal.Uploaded is released under an MIT license.  No attribution is required.  For details see the enclosed LICENSE file.




