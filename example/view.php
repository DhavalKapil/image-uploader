<?php

require ("../src/ImageUploader.php");

try
{
  $imageUploader = new ImageUploader("../upload", "random_salt");

  // optional
  $custom_callback = function(&$image) {
    $image = imagecrop($image, array(
                          "x" => 0,
                          "y" => 0,
                          "width" => 50,
                          "height" => 50
                      ));
  };

  $res = $imageUploader->serve("my_id", $custom_callback);

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}