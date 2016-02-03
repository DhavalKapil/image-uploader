<?php

require ("../src/ImageUpload.php");

try
{
  $imageUpload = new ImageUpload($_FILES, "../upload");

  $res = $imageUpload->upload("my_image");

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}