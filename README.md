# image-uploader

A simple yet elegant PHP library for uploading and serving images. The aim of this project is to act as an interface to image uploading and serving on a media server.

## Prerequisites

1. Successfully tested on PHP >= 5.5
2. [GD](http://php.net/manual/en/book.image.php) is required (`sudo apt-get install php5-gd` will do)

## Installation using Composer

Add the following dependency to your `composer.json` file:

```json
{
  "require": {
    "fknussel/image-uploader": "dev-master"
  }
}
```

Fetch the dependecy by running:

```
php composer.phar install
```

Finally, import `image-uploader` into your script:

```php
require("vendor/autoload.php");
```

## Usage

### Serving images

```php
try {
  $imageUploader = new ImageUploader(UPLOAD_DIR, MD5_HASH_SALT);
  $res = $imageUploader->serve($_GET["identifier"]);
  var_dump($res);
} catch (Exception $e) {
  var_dump($e);
}
```

### Uploading images

```php
try {
  $imageUploader = new ImageUploader();
  $imageUploader->setPath(UPLOAD_DIR);
  $imageUploader->setSalt(MD5_HASH_SALT);
  $imageUploader->setMaxFileSize(MAX_FILE_SIZE);

  $uid = time() . rand();
  $success = $imageUploader->upload($_FILES[INPUT_FIELD_NAME], $uid);

  echo json_encode(array("sucess" => $success););
} catch (Exception $e) {
  die($e);
}
```

## License

image-uploader is [MIT licensed](https://opensource.org/licenses/MIT).

This project is a somewhat modified version of [Dhaval Kapil](https://github.com/DhavalKapil)'s [image-uploader](https://github.com/DhavalKapil/image-uploader/).
