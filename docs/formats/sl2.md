# Format: sl2

ZX Spectrum Next layer 2 raw screen — 256×192 pixels with the default Next palette.

## Identifiers

- Type key: `sl2`
- Plugin class: `ZxImage\Plugin\Sl2`
- Extends: `Nxi`

## Description

`sl2` is a simplified variant of `nxi` that does not include a palette in the file. Instead, a hardcoded default ZX Next palette (256 entries, same RGB333 format as `nxi`) is used.

## File Layout

Two variants based on file size:

| File size | Layout |
|-----------|--------|
| 49152 bytes | Raw pixels only (256×192, one byte per pixel) |
| 49280 bytes (49152 + 128) | 128-byte header, then raw pixels |

When `strictFileSize = 49152 + 128`, the first 128 bytes are skipped (`fseek`).

## Default Palette

The `$defaultNextPalette` static array contains 256 16-bit strings representing the standard ZX Spectrum Next color palette in RGB333 format. The RGB values follow a pattern: RGB components encode ZX Spectrum Next's standard 256-color set with one extra blue-LSB bit.

## Output

Always PNG (`image/png`).
