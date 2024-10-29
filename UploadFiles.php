<?php
require_once('include/Builder.php');
class UploadFiles {

    private $folder = 'uploads/';
    private $filenames = array();
    private $supportFileExtensions = array();
    private $errorMessage;
    private $isUpdate = true;
    private $uploaded = false;

    private $permission = '0777';
    private $MAPPING_ERROR_TO_MESSAGE = array
    (
        UPLOAD_ERR_OK         => null,
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the maximum size allowed by the server.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the maximum size allowed by the page.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    );

    private $NOT_ALLOWED_FILES_EXTENSION = array(
        'BAT','BIN','CMD','COM','CPL','EXE','GADGET',
        'INF1','INS','INX','ISU','JOB','JSE','LNK',
        'MSC','MSI','MSP','MST','PAF','PIF','PS1',
        'REG','RGS','SCR','SCT','SHB','SHS','U3P',
        'VB','VBE','VBS','VBSCRIPT','WS','WSF','WSH'
    );

    private $NOT_ALLOWED_FILES_MIME = array(
        'bat',
        'x-msdos-program',
        'x-bat',
        'x-msdownload',
        'x-dosexec',
        'vnd.microsoft.portable-executable',
        'x-msi',
        'x-sh'
    );

    private $allowedExts = [
        'avi', 'doc', 'docx', 'gif', 'jpeg', 'jpg', 'mp4',
        'mpeg', 'mpg', 'msg', 'png', 'wmv', 'xls', 'xlsx', 'pdf'
    ];

    private $mimeType = array(
        'avi'  => array('application/x-troff-msvideo', 'video/avi', 'video/msvideo', 'video/x-msvideo'),
        'doc'  => array('application/msword'),
        'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'gif'  => array('image/gif'),
        'jpeg' => array('image/jpeg', 'image/pjpeg'),
        'jpg'  => array('image/jpeg', 'image/pjpeg'),
        'mp3'  => array('audio/mpeg3', 'audio/x-mpeg-3', 'video/mpeg', 'video/x-mpeg'),
        'mp4'  => array('video/mp4'),
        'mpeg' => array('video/mpeg'),
        'mpg'  => array('audio/mpeg', 'video/mpeg'),
        'msg'  => array('application/vnd.ms-outlook'),
        'pdf'  => array('application/pdf'),
        'png'  => array('image/png'),
        'wmv'  => array('video/x-ms-wmv'),
        'xls'  => array('application/excel', 'application/vnd.ms-excel', 'application/x-excel', 'application/x-msexcel'),
        'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    );
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setSupportedFileExt(explode(',', HmConfig::get('FileSupportMovementList')));
    }

    ##################----Accessors----##################

    /**
     * Gets the upload folder
     *
     * @return string the upload folder
     */
    public function getFolder() {
        return $this->folder;
    }

    /**
     * Gets the output filenames.
     *
     * @return array the output filenames.
     */
    public function getFilenames() {
        if (count($this->filenames) > 1) {
            return $this->filenames;
        } else {
            return $this->filenames[0];
        }

    }

    /**
     * Gets the supported file extensions.
     *
     * @return string[] the supported extensions.
     */
    public function getFileSupportedExt() {
        return $this->supportFileExtensions;
    }

    /**
     * Gets the error message if an error occurred.
     *
     * @return string|null the error message or null if one did not occur.
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Get is update
     * @return mixed
     */
    public function getIsUpdate() {
        return $this->isUpdate;
    }

    /**
     * Get uploaded
     * @return bool
     */
    public function getUploaded() {
        return $this->uploaded;
    }

    ##################----Mutators----##################

    /**
     * Sets the directory to upload files to.
     *
     * @param $folder string the directory name.
     * @return $this UploadFiles this object.
     */
    public function setFolder($folder) {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Sets the fileNames
     * @param $fileNames the file names
     * @return $this UploadFiles this object.
     */
    public function setFileNames(array $fileNames) {
        $this->filenames = $fileNames;

        return $this;
    }

    /**
     * Sets the supported file extensions.
     *
     * @param $extension_list array the list of file extensions.
     * @return $this UploadFiles this object.
     */
    public function setSupportedFileExt(array $extension_list)
    {
        foreach ($extension_list as &$extension) {
            $extension = strtolower($extension);
        }
        $this->supportFileExtensions = $extension_list;

        return $this;
    }

    /**
     * Set the error message
     *
     * @params $errorMessage string the error message
     */
    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Set is update
     * @param $isUpdate
     * @return $this
     */
    public function setIsUpdate($isUpdate) {
        $this->isUpdate = $isUpdate;

        return $this;
    }

    /**
     * Set is uploaded
     * @param $uploaded
     * @return $this
     */
    public function setUploaded ($uploaded) {
        $this->uploaded = $uploaded;

        return $this;
    }
    ##################----Methods----##################

    /**
     * @param $id mix file new name
     * @param $path string file path
     * @param $files array the files
     * @param $rename boolean is the file need rename
     * @param $table string database table name
     * @param $allowedFileExt array allowed file extension
     * @param bool $isUpdate update file or add new file.
     * @param mix filename prefix
     * @return boolean
     * @throws ErrorException
     */
    public function upload($id, $path, $files, $table, $allowedFileExt, $isUpdate = true, $prefix = null) {
        try {
            if ($this->uploaded) {
                return true;
            }
            if (isset($allowedFileExt)) {
                $this->setSupportedFileExt($allowedFileExt);
            }
            if ($this->validateUploadedFiles($files)) {
                $this->setIsUpdate($isUpdate);
                foreach( $files as $idx => $fi ) {
                    $files[$idx]['name'] = sanitiseFilenameString(($prefix ?? time()).'-'.pathinfo($fi['name'], PATHINFO_FILENAME).'.'.pathinfo($fi['name'], PATHINFO_EXTENSION));
                }
                if ( isset($path) ) {
                    if ( strpos($path,'uploads') !== false ) {
                        $path = ltrim($path, 'uploads');
                    }

                    $path = rtrim($path, '/');
                    $path = $this->folder.$path.'/';
                    if ( !is_dir($path) ) {
                        mkdir($path, $this->permission,true);
                    }
                    $this->setFolder($path);
                }
                if ( $isUpdate ) {
                    $this->setIsUpdate($isUpdate);
                    foreach ($files as $f) {
                        if (file_exists($this->getFilePath($f['name']))) {
                            $this->delete($this->getFilePath($f['name']));
                        }
                    }
                }
                if ( $this->moveFile($files) && $this->getErrorMessage() == null ) {
                    foreach ( $this->getNewFilenames() as $file ) {
                        if ( isset($table) || $table != '' ) {
                            $this->saveInDB($table, $id, $file);
                        }
                    }
                } else {
                    return false;
                }
            } else {
                $this->errorMessage = 'Invalid upload files. File type does not match extension.';
                return false;
            }
        }
        catch (Exception $e) {
            handleError($e->getMessage(), false);

            return false;
        }

        return true;
    }

    /**
     * Delete the file by file name
     * @param $filename
     * @return bool
     */
    public function delete($filename) {
        if ( unlink($filename) ) {
            return true;
        }
        $this->errorMessage = 'No such file or directory.';
        return false;
    }


    public function getFilePath($filename) {
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        if (!$basename) {
            $this->errorMessage = 'The filename is invalid.';
            return null;
        }

        $filename = $this->folder . $filename;
        $path_info = pathinfo($filename);
        $extension = &$path_info['extension'];

        if ( !in_array(strtolower($extension), $this->supportFileExtensions) ) {
            $this->errorMessage = 'The file extension is not supported.';

            return null;
        }

        $dirname = &$path_info['dirname'];
        $basename = $path_info['filename'];

        for ( $n = 0; file_exists($filename) && !$this->isUpdate; $n++ ) {
            $filename = $dirname . DIRECTORY_SEPARATOR . $basename . '.' . $n . '.' . $extension;
        }

        return $filename;
    }

    public function moveFile($files) {
        if (!$files) {
            $this->errorMessage = 'No files supplied.';

            return false;
        }
        foreach ($files as $file) {
            $filename = $file['name'];
            $fileError = $file['error'];
            $tempName = $file['tmp_name'];
            if (!is_array($filename)) {
                $errorMessage = $this->getErrorCode($fileError);
                if ($errorMessage !== null) {
                    $this->errorMessage = $errorMessage;
                    return false;
                }

                if ( !$this->move($tempName, $filename) ) {
                    return false;
                }
            } else {
                for ( $i = 0; $i < count($filename); $i++ ) {
                    $errorMessage = $this->getErrorCode($fileError[$i]);
                    if ($errorMessage !== null) {
                        $this->errorMessage = $errorMessage;
                        return false;
                    }
                    if ( !$this->move($tempName[$i], $filename[$i]) ) {
                        return false;
                    }
                }
            }
        }
        return true;
    }


    /**
     * Check is the file extension name match MIME file type.
     * Drop the upload file if NOT match.
     * @param $file
     * @return bool
     */
    public function validateUploadedFiles($file): bool {
        if (isset($file) && !empty($file)) {
            $self = new self;
            $file = $self->normalizeFiles($file);
            foreach ($file as $f ) {
                preg_match('/([^\/]+$)/', mime_content_type($f['tmp_name']), $mime);
                $mime = $mime[1];
                preg_match('/([^\/]+$)/', $f['type'], $fileType);
                $fileType = $fileType[1];
                if ( $mime == 'octet-stream' || $fileType == 'octet-stream' ) {
                    $handler = fopen($f['tmp_name'], "rb");
                    $contents = fread($handler, 1000);
                    foreach ($this->supportFileExtensions as $ft) {
                        if ( strpos(strtoupper($contents), strtoupper($ft)) ) {
                            fclose($handler);
                            return true;
                        }
                    }
                    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                    if ( in_array(mime_content_type($f['tmp_name']), $this->mimeType[$ext]) ) {
                        return true;
                    }
                }
                if ( strpos($mime , 'video') === 0 && strpos($fileType, 'video') === 0 ) {
                    return true;
                }
                if ( $mime !== $fileType && ( in_array(strtoupper($mime), $self->NOT_ALLOWED_FILES_EXTENSION) || in_array($mime, $this->mimeType[$fileType]) || in_array($mime, $self->NOT_ALLOWED_FILES_MIME) ) ) {
                    $this->delete($f['tmp_name']); //remove the temp file

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Normalize the file if there are multiple file uploaded
     * @param array $files
     * @return array
     */
    function normalizeFiles(array $files = []): array {
        $normalized_file = [];

        foreach($files as $index => $file) {
            if (!is_array($file['name'])) {
                $normalized_file[$index] = $file;
                continue;
            }

            foreach($file['name'] as $idx => $name) {
                $normalized_file[$index][$idx] = [
                    'name' => $name,
                    'type' => $file['type'][$idx],
                    'tmp_name' => $file['tmp_name'][$idx],
                    'error' => $file['error'][$idx],
                    'size' => $file['size'][$idx]
                ];
            }
        }

        return $normalized_file;
    }

    public static function failedCheckMessage() {
        return 'Invalid upload files.';
    }

    public function saveInDB($table, $id, $filePath) {
        $db = Builder::buildDb();
        $sql = "UPDATE ".$table." SET ProfilePath = @path WHERE ID = @id";

        $params = [
            'path' => [$filePath, EnumDbType::STRING],
            'id' => [$id, EnumDbType::INT]
        ];

        $db->queryRsP($sql,$params);
    }

    public function getNewFilenames() {
        return $this->filenames;
    }

    public function rename($file, $name) {

    }


    private function move($tempFile, $filename) {
        $filename = $this->getFilePath($filename);

        if ( $filename === null ) {

            return false;
        }

        if ( !move_uploaded_file($tempFile, $filename) ) {
            $this->errorMessage = 'Error on moving file';

            return false;
        }
        $this->filenames[] = $filename;

        return true;
    }

    private function getErrorCode($error_code) {
        if ( array_key_exists($error_code, $this->MAPPING_ERROR_TO_MESSAGE) ) {
            return $this->MAPPING_ERROR_TO_MESSAGE[$error_code];
        }

        return 'An unknown error occurred (' . $error_code . ').';
    }
}

