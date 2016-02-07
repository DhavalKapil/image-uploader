<?php

require ("../src/ImageUploader.php");

try
{
  $imageUpload = new ImageUploader("../upload", "random_salt");

  $res = $imageUpload->serve("my_id");

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}