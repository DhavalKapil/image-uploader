<?php

class ImageUpload
{
  /**
   * The instance of the $_FILES array
   *
   * @var array
   */
  private $_files;

  /**
   * The path to uplaod the images
   *
   * @var string
   */
  private $path;

  /**
   * The salt used by the application to encrypt image path
   *
   * @var string
   */
  private $salt;

  /**
   * @var array     The min and max image size allowed for upload (in bytes)
   */
    protected $size;

  /**
   * List of valid mime types
   *
   * @var array
   */
  private static $ALLOWED_MIME_TYPES = array(
    "image/gif",
    "image/jpg",
    "image/jpeg",
    "image/png",
    "image/bmp",
  );

  /**
   * Constructor function
   */
  public function __construct($_files = null, $path = null, $salt = null, $min = null, $max = null)
  {
    $this->_files = $_files;
    $this->path = $path;
    $this->salt = $salt;
    $this->setSize($min, $max);
  }

  /**
   * Set $_files
   *
   * @param       $_files       The $_FILES array
   */
  public function setFiles($_files)
  {
    $this->_files = $_files;
  }

  /**
   * Get $_files
   *
   * @return      array         The $_FILES array
   */
  public function getFiles()
  {
    return $this->_files;
  }

  /**
   * Set $path
   *
   * @param       $path         The path to upload images
   */
  public function setPath($path)
  {
    $this->path = $path;
  }

  /**
   * Get $path
   *
   * @return      string        The path to upload images
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Set $salt
   *
   * @param       $salt         The salt
   */
  public function setSalt($salt)
  {
    $this->salt = $salt;
  }

  /**
   * Get $salt
   *
   * @return      string        The salt
   */
  public function getSalt()
  {
    return $this->salt;
  }
  /**
    * Sets the min and max size limit
    *
    * @param  $min   int minimum value in bytes
    * @param  $max   int maximum value in bytes
    *
    */
   public function setSize($min = null, $max = null)
   {
       $this->size = array($min, $max);
   }

  /**
   * Checks the files and path parameters
   *
   * @var         $image         The name of the input file element
   */
  private function checkParameters($image)
  {
    // Checking if both _file and path are valid
    if (!is_array($this->_files)) {
      throw new Exception("Invalid FILES parameter");
    }
    if (!is_array($this->_files[$image])) {
      throw new Exception("No image with given name uploaded");
    }
    if (!file_exists($this->path)) {
      throw new Exception("Given path does not exists");
    }
  }

  /**
   * Checks $_FILES[$image]['error']
   *
   * @var         $image        The name of the input file element
   */
  private function checkFilesError($image)
  {
    if ( !isset($this->_files[$image]['error']) || is_array($this->_files[$image]['error']) ) {
      throw new Exception("Invalid parameters");
    }

    switch ($this->_files[$image]['error']) {

      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_NO_FILE:
        throw new Exception('No file sent.');

      case UPLOAD_ERR_INI_SIZE:

      case UPLOAD_ERR_FORM_SIZE:
        throw new Exception('Exceeded filesize limit.');

      default:
        throw new Exception('Unknown errors.');
    }
  }

  /**
   * Checks the mime type of the image
   *
   * @var         $image        The name of the input file element
   */
  private function checkMimeType($image)
  {
    // Extracting mime type using getimagesize
    $image_info = getimagesize($this->_files[$image]["tmp_name"]);
    if ($image_info === null) {
      throw new  Exception("Invalid image type");
    }

    $mime_type = $image_info["mime"];

    if (!in_array($mime_type, self::$ALLOWED_MIME_TYPES)) {
      throw new Exception("Invalid image MIME type");
    }
  }

  /**
   * Makes a list of security checks before uploading
   * Throws an exception on any error
   *
   * @var         $image        The name of the input file element
   */
  private function securityCheck($image)
  {
    $this->checkParameters($image);
    $this->checkFilesError($image);
    $this->checkMimeType($image);
    $this->checkFileSize($image);
  }

  /**
   * Returns the path of an image depending on identifier
   *
   * @var         $identifier   The image identifier
   *
   * @return      string        The path of the image
   */
  private function getImagePath($identifier)
  {
    $image_name = "";
    if ($this->salt === null) {
      $image_name = md5($identifier);
    }
    else {
      $image_name = md5($identifier . $this->salt);
    }

    $image_path = $this->path . DIRECTORY_SEPARATOR . $image_name;

    return $image_path;
  }

  /**
   * Uploads a particular image
   *
   * @var         $image        The name of the input file element
   * @var         $identifier   The image identifier
   *
   * @return      boolean       Whether the upload was successfull or not
   */
  public function upload($image, $identifier)
  {
    $this->securityCheck($image);

    $destination_path = $this->getImagePath($identifier);
    $result = move_uploaded_file($this->_files[$image]["tmp_name"], $destination_path);

    return $result;
  }

  /**
   * Checks whether an image with this identifier exists or not
   *
   * @var         $identifier   The image identifier
   *
   * @return      bool          whether an image exists or not
   */
  public function exists($identifier)
  {
    $image_path = $this->getImagePath($identifier);

    return file_exists($image_path);
  }

  /**
   * Serves an image
   *
   * @var         $identifier   The image identifier
   *
   * @return      bool          success or failure
   */
  public function serve($identifier)
  {
    if (!$this->exists($identifier)) {
      return false;
    }

    // Calculating the image path and the mime type
    $image_path = $this->getImagePath($identifier);
    $mime_type = getimagesize($image_path)["mime"];

    header("Content-Type: " . $mime_type);
    readfile($image_path);

    return true;
  }

  /**
   * Checks if uploaded file size is within upload limit
   * Throws an exception on any error
   *
   * @var         string        The name of the input file element
   */
   public function checkFileSize($allowed_size, $image)
   {
     //No need to check image size, if unspecified by user
     if(empty($this->size)) return;

     list($min_size, $max_size) = $this->size;
     if($this->_files[$image]['size'] > $max_size || $this->_file['size'] < $min_size) {
       throw new  Exception("Size limit exceeded");
     }
   }
}
