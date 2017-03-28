<?php

class ImageUploader
{
  /**
   * The path to upload the images
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
   * The width to resize image
   *
   * @var number
   */
  private $newWidth;

  /**
   * The height to resize image
   *
   * @var number
   */
  private $newHeight;

  /**
   * List of valid mime types alongwith processing functions
   *
   * @var array
   */
  private static $MIME_TYPES_PROCESSORS = array(
    "image/gif"       => array("imagecreatefromgif", "imagegif"),
    "image/jpg"       => array("imagecreatefromjpeg", "imagejpeg"),
    "image/jpeg"      => array("imagecreatefromjpeg", "imagejpeg"),
    "image/png"       => array("imagecreatefrompng", "imagepng"),
    "image/bmp"       => array("imagecreatefromwbmp", "imagewbmp")
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
      $df = $this->max_file_size;
      throw new Exception("Size limit exceeded");
    }
  }



  /**
   * Checks if first 100 bytes contains any non ASCII char
   * Throws an exception on any error
   *
   * @var         $image        The $_FILE["image"] parameter
   */
  private function checkInitialBytes($image)
  {
    // Reading first 100 bytes
    $contents = file_get_contents($image['tmp_name'], null, null, 0, 100);

    if ($contents === false) {
      throw new Exception("Unable to read uploaded file");
    }

    $regex = "[\x01-\x08\x0c-\x1f]";
    if (preg_match($regex, $contents)) {
      throw new Exception("Unknown bytes found");
    }
  }

  /**
   * Makes a list of security checks before uploading
   * Throws an exception on any error
   *
   * @var         $image        The $_FILE["image"] parameter
   */
  private function securityChecks($image)
  {
    $this->checkParameters($image);
    $this->checkUploadError($image);
    $this->checkFileSize($image);
    $this->checkInitialBytes($image);
  }

  /**
   * Checks the mime type as well as uses the GD library to reprocess the image
   *
   * @var         $image        The $_FILE["image"] parameter
   * @var         $callback     The callback function for further image manipulations
   */
  private function reprocessImage($image, $callback)
  {
    // Extracting mime type using getimagesize
    $image_info = getimagesize($image["tmp_name"]);
    if ($image_info === null) {
      throw new Exception("Invalid image type");
    }

    $mime_type = $image_info["mime"];

    if (!array_key_exists($mime_type, self::$MIME_TYPES_PROCESSORS)) {
      throw new Exception("Invalid image MIME type");
    }

    $image_from_file = self::$MIME_TYPES_PROCESSORS[$mime_type][0];
    $image_to_file = self::$MIME_TYPES_PROCESSORS[$mime_type][1];

    $reprocessed_image = $image_from_file($image["tmp_name"]);

    if (!$reprocessed_image) {
      throw new Exception("Unable to create reprocessed image from file");
    }

    // Calling callback(if set) with path of image as a parameter
    if ($callback !== null) {
      $callback($reprocessed_image);
    }

    $image_to_file($reprocessed_image, $image["tmp_name"]);

    // Freeing up memory
    imagedestroy($reprocessed_image);
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
   * @var         $callback     The callback to be called after security checks
   *
   * @return      boolean       Whether the upload was successfull or not
   */
  public function upload($image, $identifier, $callback = null)
  {
    $this->securityChecks($image);

    $this->reprocessImage($image, $callback);

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
   * @var         $callback     The callback to be called before serving the image
   *
   * @return      bool          success or failure
   */
  public function serve($identifier, $callback = null)
  {
    if (!$this->exists($identifier)) {
      return false;
    }

    // Calculating the image path and the mime type
    $image_path = $this->getImagePath($identifier);
    $mime_type = getimagesize($image_path)["mime"];

    $image_from_file = self::$MIME_TYPES_PROCESSORS[$mime_type][0];
    $image_to_file = self::$MIME_TYPES_PROCESSORS[$mime_type][1];

    $image = $image_from_file($image_path);

    if (!$image) {
      throw new Exception("Unable to read image while serving");
    }

    if ($callback !== null) {
      $callback($image);
    }

    header("Content-Type: " . $mime_type);

    // Output buffering the image
    $result = $image_to_file($image, null);

    return $result;
  }

/**
   * Serves an image resized
   *
   * @var         $identifier   The image identifier
   * @var         $percent     The percentage that image will be resize
   * @var         $maxWidth     The max width that image will be resized
   * @var         $maxHeight    The max height that image will be resized
   *
   * @return      bool          success or failure
   */
  public function serveResize($identifier, $percent = null, $maxWidth = null, $maxHeight = null)
  {
    if (!$this->exists($identifier)) {
      return false;
    }

    // Calculating the image path and the mime type
    $image_path = $this->getImagePath($identifier);
    $mime_type = getimagesize($image_path)["mime"];

    $image_from_file = self::$MIME_TYPES_PROCESSORS[$mime_type][0];
    $image_to_file = self::$MIME_TYPES_PROCESSORS[$mime_type][1];

    $image = $image_from_file($image_path);

    if (!$image) {
      throw new Exception("Unable to read image while serving");
    }

    //applying resize on image proportionally according parameter of resize
    list($width, $height) = getimagesize($image_path);
    $ratio = $width/$height;  
    if($maxWidth !== null)
      $this->resizeWidthHeight($ratio, $maxWidth);
    if($maxHeight !== null)
      $this->resizeWidthHeight($ratio, $maxHeight);
    if($percent != null){
      $newwidth = $width * $percent;
      $newheight = $height * $percent;  
    }

    
    $resized = imagecreatetruecolor($this->newwidth, $this->newheight);
    $source = $image_from_file($image_path);

    imagecopyresized( 
                $resized, 
                $source, 
                0, 
                0, 
                0, 
                0, 
                $this->newwidth, 
                $this->newheight, 
                $width, 
                $height);

    $image = $resized;

    header("Content-Type: " . $mime_type);

    // Output buffering the image
    $result = $image_to_file($image, null);

    return $result;
  }

  /**
   * Resize image
   *
   * @var         $ratio   The ratio of image
   * @var         $maxValue     The max value to width or height resize
   *
   * @return      void          
   */
  public function resizeWidthHeight($ratio, $maxValue){

    if( $ratio > 1) {
          $this->newwidth = $maxValue;
          $this->newheight = $maxValue/$ratio;
      }
      else {
          $this->newwidth = $maxValue*$ratio;
          $this->newheight = $maxValue;
      }  
  }

}
