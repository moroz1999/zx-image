# Format: sl2

ZX Spectrum Next Layer 2 raw screen dump.

## Identifiers

- Type key: `sl2`
- Plugin class: `ZxImage\Plugin\Sl2`

## Description

SL2 stores raw Layer 2 video memory followed by an optional ZX Next palette. Palette entries may use the 8-bit `RRRGGGBB` form or the 9-bit two-byte `RRRGGGBB, P000000B` form.

## File Layout

| Resolution and depth | Pixel data | Optional palette |
|----------------------|------------|------------------|
| 256×192×8 | 49152 bytes, row-major | 256 or 512 bytes |
| 320×256×8 | 81920 bytes, columns from top to bottom and left to right | 256 or 512 bytes |
| 640×256×4 | 81920 bytes, columns from top to bottom and left to right, two horizontal pixels per byte | 16 or 32 bytes |

A 49280-byte legacy file contains a 128-byte `+3DOS` header followed by 49152 bytes of 256×192 pixel data.

An 81920-byte file without a palette is interpreted as 320×256×8 because its size is indistinguishable from 640×256×4.

## Default Palette

Files without a palette use the default 256-entry ZX Next palette.

## Output

Always PNG (`image/png`).
