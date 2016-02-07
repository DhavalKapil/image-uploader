<?php

require ("../src/ImageUploader.php");

try
{
  $imageUploader = new ImageUploader("../upload", "random_salt");

  // An optional custom callback to process the uploaded image using GD library
  $img_filter = IMG_FILTER_GRAYSCALE;

  $custom_callback = function($image) use($img_filter) {
    imagefilter($image, $img_filter);
  };

  $res = $imageUploader->upload($_FILES["my_image"], "my_id", $custom_callback);

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}
