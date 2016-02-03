<?php

require ("../src/ImageUpload.php");
var_dump($_FILES);
$imageUpload = new ImageUpload($_FILES, "../upload");

$res = $imageUpload->upload("my_image");

var_dump($res);