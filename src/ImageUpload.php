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
   * List of valid mime types
   *
   * @var array
   */
  private static $ALLOWED_MIME_TYPES = array(
    "image/gif",
    "image/jpg",
    "image/png",
    "image/bmp",
  );

  /**
   * Constructor function
   */
  public function __construct($_files = null, $path = null)
  {
    $this->_files = $_files;
    $this->path = $path;
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
   * @return      @array        The $_FILES array
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
   * Checks the files and path parameters
   *
   * @var         string         The name of the input file element
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
   * @var         string        The name of the input file element
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
   * @var         string        The name of the input file element
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
   * @var         string        The name of the input file element
   */
  private function securityCheck($image)
  {
    $this->checkParameters($image);
    $this->checkFilesError($image);
    $this->checkMimeType($image);
  }

  /**
   * Uploads a particular image
   *
   * @var         $image        The name of the input file element
   *
   * @return      boolean       Whether the upload was successfull or not
   */
  public function upload($image)
  {
    $this->securityCheck($image);

    $destination_path = $this->path . DIRECTORY_SEPARATOR . $this->_files[$image]["name"];
    $result = move_uploaded_file($this->_files[$image]["tmp_name"], $destination_path);

    return $result;
  }
}