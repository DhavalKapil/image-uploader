<?php

require ("../src/ImageUploader.php");

try
{
  $imageUploader = new ImageUploader("../upload", "random_salt");

  $res = $imageUploader->serve("my_id");

  var_dump($res);
}
catch (Exception $e)
{
  var_dump($e);
}