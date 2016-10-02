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

# Supported formats
* "standard" - standard ZX Spectrum screen memory dump. Size: 6912. 6144 bytes of pixel data, 768 bytes of attributes.
* "ulaplus" - ZX Spectrum screen with attached ULA+ palette. Size: 6976. 6144 bytes of pixel data, 768 bytes of attributes, 64 bytes of ULA+ palette.
* "sam4" - Sam Coupe mode 4 screen dump. Size: 24617. 24576 of pixel data, 41 bytes of Mode4 palette.
* "zxevo" - ZX Evolution screen saved as standard BMP file with 16 colors.
* "sxg" - ZX Evolution (also supports TSConf screens) screen in SXG format.
* "bsc" - Border Screen. Size: 11136. 6144 bytes of pixel data, 768 bytes of attributes, 4224 bytes of border "pixels".
* "bmc4" - multicolor 8*4 with border. Size: 11904. 6144 bytes of pixel data, 1536 bytes of attributes, 4224 bytes of border "pixels".
* "gigascreen" - two standard screens shown as 50hz software flickering. Size: 13824. Contains two standard SCR files: 6144 bytes of pixel data, 768 bytes of attributes, 6144 bytes of pixel data, 768 bytes of attributes.
* "chrd" - CHR$ format by Alone Coder, supports monochrome, standard and gigascreen images of variable width/height. Size: variable according to images width/height. Data is contained char by char separately.
* "monochrome" - standard ZX Spectrum monochrome screen without attributes. Size: 6144, contains only pixel data.
* "flash" - Screens made for hardware "Flash color" modification. Size: 6912. Same format as standard screen, but flash bit is used for mixing paper+ink for ink and forcing black paper.
* "tricolor" - Software-flickering RGB image. Size: 18432. 6144 bytes of red pixels data, 6144 bytes of green pixels data, 6144 bytes of blue pixels data.
* "multicolor" - multicolor 8*2. Size: 9216. 6144 bytes of pixels data, 3072 bytes of attributes data.
* "multicolor4" - multicolor 8*4. Size: 7680. 6144 bytes of pixels data, 1536 bytes of attributes data.
* "multiartist" - ZX Spectrum multicolor gigascreen image exported from Multiartist (MG1, MG2, MG4, MG8, not PC-native MGS). Size: varies.
* "attributes" - ZX Spectrum native attributes screen, 53colors achieved by using grid. Size: 768. Contains only 768 bytes of attributes.
* "lowresgs" - ZX Spectrum gigascreen 8*4 attributes screen. Size: 1628. 84 bytes of ?, 8 bytes of texture, 768 bytes of first screen attributes, 768 bytes of second screen attributes.  

## License
Creative Commons Zero v1.0 Universal
