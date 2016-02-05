<?php

require ("../src/ImageUpload.php");

try
{
  $imageUpload = new ImageUpload($_FILES, "../upload", "random_salt");

  $res = $imageUpload->upload("my_image", "my_id");

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}
