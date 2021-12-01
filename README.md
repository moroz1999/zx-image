# ZX-Image
PHP-based ZX Spectrum images parsing into PNG/GIF. Supports animation, supports file caching.

## Basic usage example
```php
<?php
include_once('ZxImage/Converter.php');

$converter = new \ZxImage\Converter();
$converter->setType('standard');
$converter->setPath('example.scr'); //
$converter->setBorder(5); //cyan
$converter->setZoom(1); //1 for 320*240 (256*192 with border)

//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    if ($imageType = $converter->getResultMime()) {
        header('Content-Type: ' . $imageType);
    }

    //send image contents to browser
    echo $binary;
}
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
Installation through Composer:
```json
{
    "require": {
		"moroz1999/zx-image": "*"
    }
}
```
The library is also capable of working without Composer autoloader.

# Supported formats
* "standard" - standard ZX Spectrum screen memory dump. Size: 6912. 6144 bytes of pixel data, 768 bytes of attributes.
* "ulaplus" - ZX Spectrum screen with attached ULA+ palette. Size: 6976. 6144 bytes of pixel data, 768 bytes of attributes, 64 bytes of ULA+ palette.
* "sam3" - Sam Coupe mode 3 screen dump. Size: 24617. 24576 of pixel data, 4 bytes of Mode3 palette, 37 bytes unused.
* "sam4" - Sam Coupe mode 4 screen dump. Size: 24617. 24576 of pixel data, 16 bytes of Mode4 palette, 25 bytes unused.
* "zxevo" - ZX Evolution screen saved as standard BMP file with 16 colors.
* "sxg" - ZX Evolution (also supports TSConf screens) screen in SXG format.
* "bsc" - Border Screen. Size: 11136. 6144 bytes of pixel data, 768 bytes of attributes, 4224 bytes of border "pixels".
* "bsp" - Border Screen by Trefi. Size: varies. Supports 6912, gigascreen, byte for border data, as well as optional border "pixel" data.
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
* "mc" - ZX Spectrum multicolor 8*1 screen. Size: 12288. 6144 bytes of linear pixel data, 6144 bytes of linear attributes data.
* "mlt" - ZX Spectrum multicolor 8*1 screen. Size: 12288. 6144 bytes of non-linear pixel data, 6144 bytes of linear attributes data.
* "timex81" - Timex multicolor 8*1 screen. Size: 12288. 6144 bytes of non-linear pixel data, 6144 bytes of attributes in non-linear format (Timex screen memory dump).
* "timexhr" - Timex hi-res 512*192 screen. Size: 12289. 6144 bytes of odd columns pixel data, 6144 bytes of even columns pixel data, 1 byte of color information.  
* "timexhrg" - Timex hi-res gigascreen 512*192 screen. Size: 24578. Two timexhr screens one by one  
* "stellar" - ZX Spectrum graphics mode combining multicolour and 128K screen switching to produce 4x4-pixel blocks of alternating bright and dark colours, giving an effective palette of 64 colours at 64x48 resolution with no attribute restrictions and no flicker. First achieved by RST7 in Eye Ache 2 for Pentagon machines, and re-implemented for original Spectrums by Gasman in Buttercream Sputnik.
* "atmega" - ATM Turbo 2+ EGA graphics mode. 32128 file containing pixel data and palette.
* "nxi" - ZX Spectrum Next nxi file. 49664 file containing 512 bytes of RGB333 palette and 256*192 bytes of pixels.
* "grf" - Profi GRF file (partial support for hi-res 16 colors mode)

## License
Creative Commons Zero v1.0 Universal
