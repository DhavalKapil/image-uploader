<?php

class ImageUpload
{
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
   * The min size allowed for upload (in bytes)
   *
   * @var number     
   */
  private $min_size;

  /**
   * The max size allowed for upload (in bytes)
   *
   * @var number     
   */
  private $max_size;

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
  public function __construct($path = null,
                              $salt = null,
                              $min_file_size = null,
                              $max_file_size = null)
  {
    $this->path = $path;
    $this->salt = $salt;
    $this->min_file_size = $min_file_size;
    $this->max_file_size = $max_file_size;
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
   * Set $min_file_size
   *
   * @param       $min_file_size          The minimum file size
   */
  public function setMinFileSize($min_file_size)
  {
    $this->min_file_size = $min_file_size;
  }

  /**
   * Get $min_file_size
   *
   * @return      number                  The minimum file size
   */
  public function getMinFileSize()
  {
    return $this->min_file_size;
  }

  /**
   * Set $max_file_size
   *
   * @param       $max_file_size           The maximum file size
   */
  public function setMaxFileSize($max_file_size)
  {
    $this->max_file_size = $max_file_size;
  }

  /**
   * Get $max_file_size
   *
   * @return      number                  The maximum file size
   */
  public function getMaxFileSize()
  {
    return $this->max_file_size;
  }

  /**
   * Checks the files and path parameters
   *
   * @var         $image         The $_FILE["image"] parameter
   */
  private function checkParameters($image)
  {
    if (!is_array($image)) {
      throw new Exception("No image with given name uploaded");
    }
    if (!file_exists($this->path)) {
      throw new Exception("Given path does not exists");
    }
    if ($this->min_file_size !== null
      && $this->max_file_size !== null
      && $this->min_file_size > $this->max_file_size) {
      throw new Exception("Invalid file size parameters");
    }
  }

  /**
   * Checks upload error
   *
   * @var         $image        The $_FILE["image"] parameter
   */
  private function checkUploadError($image)
  {
    if ( !isset($image['error']) || is_array($image['error']) ) {
      throw new Exception("Invalid parameters");
    }

    switch ($image['error']) {

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
   * @var         $image        The $_FILE["image"] parameter
   */
  private function checkMimeType($image)
  {
    // Extracting mime type using getimagesize
    $image_info = getimagesize($image["tmp_name"]);
    if ($image_info === null) {
      throw new Exception("Invalid image type");
    }

    $mime_type = $image_info["mime"];

    if (!in_array($mime_type, self::$ALLOWED_MIME_TYPES)) {
      throw new Exception("Invalid image MIME type");
    }
  }

  /**
   * Checks if uploaded file size is within upload limit
   *
   * @var         $image        The $_FILE["image"] parameter
   */
  private function checkFileSize($image)
  {
    if ($this->min_file_size !== null && $image['size'] < $this->min_file_size) {
      throw new Exception("Size too small");
    }
    if ($this->max_file_size !== null && $image['size'] > $this->max_file_size) {
      throw new Exception("Size limit exceeded");
    }
  }

  /**
   * Makes a list of security checks before uploading
   * Throws an exception on any error
   *
   * @var         $image        The $_FILE["image"] parameter
   */
  private function securityCheck($image)
  {
    $this->checkParameters($image);
    $this->checkUploadError($image);
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
   * @var         $image        The $_FILE["image"] parameter
   * @var         $identifier   The image identifier
   *
   * @return      boolean       Whether the upload was successfull or not
   */
  public function upload($image, $identifier)
  {
    $this->securityCheck($image);

    $destination_path = $this->getImagePath($identifier);
    $result = move_uploaded_file($image["tmp_name"], $destination_path);

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
}
