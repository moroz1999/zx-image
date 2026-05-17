# Format: specscii

ZX Spectrum text screen decoded from a token stream (Specscii format).

## Identifiers

- Type key: `specscii`
- Plugin class: `ZxImage\Plugin\Specscii`
- Extends: `Standard`
- File extension: typically `.C`

## Description

Specscii files contain a stream of bytes encoding ZX Spectrum BASIC-style control codes and character tokens. Control codes change the current ink, paper, flash, or bright attributes. Non-control bytes are rendered as characters using the built-in ZX Spectrum ROM font.

## Control Codes (via `Chr` enum)

| Code | Value | Effect |
|------|-------|--------|
| `INK` | 16 | Next byte sets ink color (0–7) |
| `PAPER` | 17 | Next byte sets paper color (0–7) |
| `FLASH` | 18 | Next byte enables (1) or disables (0) flash |
| `BRIGHT` | 19 | Next byte enables (1) or disables (0) bright |
| `INVERSE` | 20 | (parsed but not rendered) |
| `OVER` | 21 | (parsed but not rendered) |

After a control code, the following byte is the parameter, not a character.

## Font Data

The `FontData` class contains a static array of 96 characters (ASCII 32–127 + some ZX-specific block graphics). Each character is 8 rows of 8-bit binary strings. Characters are stored in the standard ZX Spectrum ROM font layout.

## Pixel Layout

Characters are placed into the pixel and attribute arrays using the same non-linear ZX VRAM organization as `Standard`, honoring the three-thirds structure:

```
pixelY = base + attrY * 32 + row * 256 + attrX
```

where `base` is 0, `32*8*8`, or `32*8*16` depending on the character row.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
