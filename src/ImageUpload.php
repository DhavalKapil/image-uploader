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
   * Uploads a particular image
   *
   * @var         $image        The name of the input file element
   *
   * @return      boolean       Whether the upload was successfull or not
   */
  public function upload($image)
  {
    $destination_path = $this->path . DIRECTORY_SEPARATOR . $this->_files[$image]["name"];
    $result = move_uploaded_file($this->_files[$image]["tmp_name"], $destination_path);

    return $result;
  }
}