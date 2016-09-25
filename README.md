# SXG
PHP-based ZX Spectrum images parsing into PNG/GIF. Supports animation, supports file caching.

## Basic usage example
```php
$converter = new \ZxImage\Converter();
$converter->setType('standard');
$converter->setPath('/6192.scr'); //
$converter->setBorder(0); //black
$converter->setSize($value); //1 for 320*240 (256*192 with border)
$binary = $converter->convertToBinary(); //convert and return image data
$imageType = $converter->getResultMime();
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

## Installation
Composer
```json
{
    "require": {
		"moroz1999/ZxImage": "*"
    }
}
```

## Links

## License
Creative Commons Zero v1.0 Universal
