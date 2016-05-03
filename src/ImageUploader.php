<?php

class ImageUploader {
  /**
   * Path where images get uploaded to
   * @var string
   */
  private $path;

  /**
   * Salt used by the application to hash images names
   * @var string
   */
  private $salt;

  /**
   * Maximum size allowed (in bytes)
   * @var number
   */
  private $max_size;

  /**
   * Valid mime types and processing functions
   * @var array
   */
  private static $MIME_TYPES_PROCESSORS = array(
    "image/gif" => array("imagecreatefromgif", "imagegif"),
    "image/jpg" => array("imagecreatefromjpeg", "imagejpeg"),
    "image/jpeg" => array("imagecreatefromjpeg", "imagejpeg"),
    "image/png" => array("imagecreatefrompng", "imagepng"),
    "image/bmp" => array("imagecreatefromwbmp", "imagewbmp")
  );

  /**
   * Constructor method
   */
  public function __construct($path = null, $salt = null, $max_file_size = null) {
    $this->path = $path;
    $this->salt = $salt;
    $this->max_file_size = $max_file_size;
  }

  /**
   * Set $path
   * @param string $path Path where images get uploaded to
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * Get $path
   * @return string Path where images get uploaded to
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set $salt
   * @param string $salt Salt used to hash images names
   */
  public function setSalt($salt) {
    $this->salt = $salt;
  }

  /**
   * Get $salt
   * @return string Salt used to hash images names
   */
  public function getSalt() {
    return $this->salt;
  }

  /**
   * Set $max_file_size
   * @param number $max_file_size Maximum file size allowed (in bytes)
   */
  public function setMaxFileSize($max_file_size) {
    $this->max_file_size = $max_file_size;
  }

  /**
   * Get $max_file_size
   * @return number Maximum file size allowed (in bytes)
   */
  public function getMaxFileSize() {
    return $this->max_file_size;
  }

  /**
   * Get hashed image name
   * @param string $identifier Image identifier
   * @return string MD5 hash
   */
  private function getHash($identifier) {
    if ($this->salt === null) {
      $image_name = md5($identifier);
    } else {
      $image_name = md5($identifier . $this->salt);
    }

    return $image_name;
  }

  /**
   * Get path to a given filename
   * @param string $identifier Image identifier
   * @return string Relative path to the image
   */
  private function getImagePath($identifier) {
    return $this->path . DIRECTORY_SEPARATOR . $identifier;
  }

  /**
   * Check whether an image with this identifier exists
   * @param string $identifier Image identifier
   * @return boolean Indicates whether a file with the provided name exists
   */
  public function exists($identifier) {
    $image_path = $this->getImagePath($identifier);

    return file_exists($image_path);
  }

  /**
   * Make sure we get the correct input
   * @param array $image This is the $_FILES["image"] object
   */
  private function checkParameters($image) {
    if (!is_array($image)) {
      throw new Exception("No image matching the name provided was uploaded");
    }

    if (!file_exists($this->path)) {
      throw new Exception("Invalid path, make sure the provided route exists");
    }
  }

  /**
   * Check for upload errors
   * @param array $image The $_FILES["image"] param
   */
  private function checkUploadError($image) {
    if (!isset($image["error"]) || is_array($image["error"])) {
      throw new Exception("Invalid params");
    }

    switch ($image["error"]) {
      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_NO_FILE:
        throw new Exception("No file sent");
        break;

      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new Exception("Exceeded maximum filesize limit");
        break;

      default:
        throw new Exception("Oops, something went wrong when trying to upload your image!");
    }
  }

  /**
   * Check whether uploaded file size is within max filesize limit
   * @param array $image The $_FILES["image"] param
   */
  private function checkFileSize($image) {
    if ($this->max_file_size !== null && $image["size"] > $this->max_file_size) {
      throw new Exception("Exceeded maximum filesize limit");
    }
  }

  /**
   * Check whether first 100 bytes contain any non ASCII character
   * @param array $image The $_FILES["image"] param
   */
  private function checkInitialBytes($image) // @TODO
  {
    // Read first 100 bytes
    $content = file_get_contents($image["tmp_name"], null, null, 0, 100);

    if ($content === false) {
      throw new Exception("Unable to read uploaded file");
    }

    $regex = "[\x01-\x08\x0c-\x1f]";

    if (preg_match($regex, $content)) {
      throw new Exception("Invalid image content found");
    }
  }

  /**
   * Run a handful of safety checks before uploading the image
   * @param array $image The $FILES["image"] param
   */
  private function securityChecks($image) {
    $this->checkParameters($image);
    $this->checkUploadError($image);
    $this->checkFileSize($image);
    $this->checkInitialBytes($image);
  }

  /**
   * Check the mime type as well as uses the GD library to reprocess the image
   * @param array $image The $_FILES["image"] param
   * @param function $callback Callback to allow for extra image manipulation
   */
  private function reprocessImage($image, $callback) {
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

    // Executing callback (if any), need to pass in image path as param
    if ($callback !== null) {
      $callback($reprocessed_image);
    }

    $image_to_file($reprocessed_image, $image["tmp_name"]);

    // Free up memory
    imagedestroy($reprocessed_image);
  }

  /**
   * Upload an image
   * @param array $image The $_FILES["image"] param
   * @param string $identifier Image identifier
   * @param function $callback Optional callback, allows for extra image manipulation
   * @return boolean Indicates whether the image upload was successful
   */
  public function upload($image, $identifier, $callback = null) {
    $this->securityChecks($image);
    $this->reprocessImage($image, $callback);

    $new_name = $this->getHash($identifier);
    $destination_path = $this->getImagePath($new_name);
    $result = move_uploaded_file($image["tmp_name"], $destination_path);

    return $result;
  }

  /**
   * Serve an image
   * @param string $identifier Image identifier
   * @param function $callback Optional callback, allows for extra image manipulation
   * @return boolean Indicates whether the image has been served successfully
   */
  public function serve($filename, $callback = null) {
    if (!$this->exists($filename)) {
      return false;
    }

    $image_path = $this->getImagePath($filename);
    $mime_type = getimagesize($image_path)["mime"];

    $image_from_file = self::$MIME_TYPES_PROCESSORS[$mime_type][0];
    $image_to_file = self::$MIME_TYPES_PROCESSORS[$mime_type][1];

    $image = $image_from_file($image_path);

    if (!$image) {
      throw new Exception("Unable to read image");
    }

    if ($callback !== null) {
      $callback($image);
    }

    header("Content-Type: " . $mime_type);

    $result = $image_to_file($image, null);

    return $result;
  }
}
