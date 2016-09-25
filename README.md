# SXG
PHP-based ZX Spectrum images parsing into PNG/GIF. Supports animation, supports file caching.

## Basic usage example
```php
$converter = new \ZxImage\Converter();
$converter->setType('standard');
$converter->setPath('/6192.scr'); //
$converter->setBorder(0); //black
$converter->setSize(1); //1 for 320*240 (256*192 with border)
$binary = $converter->convertToBinary(); //convert and return image data
$imageType = $converter->getResultMime(); //after conversion we can ask for a mime type of last operation

//do something with the image
header('Content-Type: '.$imageType);
echo $binary;
```

## File cache setup example
Converting on the fly is slow and resource-demanding, so it's advised to set up built-in file caching.

```php
//ensure that there is a folder for converted images cache
if (!is_dir($folderForCache)) {
	mkdir($folderForCache);
}
//set cache path
$converter->setCachePath($folderForCache);
//enable file cache
$converter->setCacheEnabled(true);
```
After this all images will be stored in folder as a cache. Converter will check and delete outdated images automatically.


## Installation
Composer
```json
{
    "require": {
		"moroz1999/zx-image": "*"
    }
}
```

## Links

## License
Creative Commons Zero v1.0 Universal
