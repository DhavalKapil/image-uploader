<?php

require ("../src/ImageUpload.php");

try
{
  $imageUpload = new ImageUpload("../upload", "random_salt");

  $res = $imageUpload->serve("my_id");

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}