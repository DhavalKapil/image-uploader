# image-uploader

> A simple and elegant PHP library for securely uploading images

The aim of this project is to provide users with a **simple** interface to upload images in their applications in a **highly secure** manner while also being **highly customizable**.

The uploaded images go through a lot of security checks before they are uploaded.

## Features

1. A **clean** interface - **easy to use**.

2. Uploaded images are referenced by a unique identifier - **removes the hassles of storage from the user**.

3. **Customizable** - users of this library can operate on the uploaded images(i.e. crop, add filters, etc.) without even worrying about the uploading process.

4. **Highly secure** - a lot of security checks were compiled from many sources into this one library.

## Security Checklist

1. Checks `$_FILES` parameter for correctness.

2. Checks uploaded image file size, where `min` and `max` sizes are given by the user.

3. Checks upload errors by analyzing the variable `$_FILES["image-name"]["error"]`

4. Checks for unwanted bytes in the first 100 bytes of the uploaded image.

5. Checks image's mime type by using the `getimagesize` function. Does not rely on `$_FILES["image-name"]["type"]`.

6. Reprocesses the image using the [GD](http://php.net/manual/en/book.image.php) library to remove any malicious code.

7. Renames the uploaded image, uses `md5` hash(with a user given salt) as the new image name.

8. Uses [move\_uploaded\_file](http://php.net/manual/en/function.move-uploaded-file.php) to properly move the uploaded file to the target destination as well as setting appropriate permissions.

## Prerequisites

1. Successfully tested on PHP >= 5.5


2. [GD](http://php.net/manual/en/book.image.php) library

 ```
 sudo apt-get install php5-gd

 sudo service apache2 restart
 ```

## Installation

### Composer

Add the following in your composer.json:

```js
{
  "require": {
    "dhaval/image-uploader": "dev-master"
  }
}
```

And then run

```
php composer.phar install
```

Include `image-uploader` in your php code

```php
require("vendor/autoload.php");
```

### Manually

Clone this repository in your project's home directory

```
git clone https://github.com/dhavalkapil/image-uploader
```

Include `ImageUploader.php` in your php code.

```php
require("image-uploader/src/ImageUploader.php");
```

## Usage

### Frontend

First of all create an HTML form to allow people to upload files

```html
<form method="POST" action = "submit.php" enctype="multipart/form-data">
 <input type="file" name="my_image" />
 <input type="submit" value="Upload" />
</form>
```

### Backend

Create an instance of `ImageUploader` and set appropriate attributes

```php
$imageUploader = new ImageUploader();

// Compulsory
$imageUploader->setPath("my/upload/image/dir");   // The directory where images will be uploaded

// The rest are optional
$imageUploader->setSalt("my_application_specific_salt");  // It is used while hashing image names
$imageUploader->setMinFileSize(0);                           // Set minimum file size in bytes
$imageUploader->setMaxFileSize(100000);                      // Set maximum file size in bytes
```

You can also pass these attributes directly while calling the constructor.

It is advised to create the upload directory(passed above in path) as follows:

```
mkdir upload_dir
chmod 755 upload_dir
[sudo] chown www-data:www-data upload_dir
```

**Note**: `www-data` is the user that apache runs under. You might need to change it depending on your machine.

To upload an image use the `upload` function

```php
$imageUploader->upload($_FILES["my_image"], "my_id");
```

Here, `my_image` was the name of the input element in your HTML and `my_id` is a unique identifier for your image. This needs to be decided by the user.

To serve an image at a particular page use the `serve` function

```php
$imageUploader->serve("my_id");
```

The image uploaded with this particular identifier will be served. Hence, there will be no direct link to the image itself. This allows for images to be served at say /user/\<user\_id\>/image. Where `user_id` might be used as an identifier for user images.

Check out sample [example](https://github.com/DhavalKapil/image-uploader/tree/master/example) for more details.

### Additional Usage

You can also check whether a particular image exists for a given identifier using the `exists` function

```php
$result = $imageUploader->exists("my_id");
```

It returns a boolean value.

You can also customize the uploaded image, say by adding filters, cropping, etc by passing a `callback` function to the `upload` or `serve` function.

```php
$callback = function(&$image) {
 imagefilter($image, IMG_FILTER_GRAYSCALE);
};

$imageUploader->upload($_FILES["my_image"], "my_id", $callback);
```

This `callback` accepts a reference `&$image`, which is an image resource used by the [GD](http://php.net/manual/en/book.image.php) library. If passed to the `upload` function, it is called just before the image is being saved. Whereas, if passed to the `serve` function, it is called just before the image is buffered to the browser.

## Contribution

Contributions are welcome to this repository. If you know of any other security vulnerability, any bug, etc. feel free to file [issues](https://github.com/DhavalKapil/image-uploader/issues) and submit [pull requests](https://github.com/DhavalKapil/image-uploader/pulls).

## Developers

- [Dhaval Kapil](https://github.com/DhavalKapil)

- [Aditya Prakash](https://github.com/adiitya)
 
## License

image-uploader is licensed under the MIT license.
