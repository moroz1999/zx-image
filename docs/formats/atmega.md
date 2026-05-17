# Format: atmega

ATM Turbo 2+ EGA graphics mode — 320×200 pixels, 16 colors.

## Identifiers

- Type key: `atmega`
- Plugin class: `ZxImage\Plugin\Atmega`
- Extends: `Plugin` (directly)

## File Layout

Two variants based on file size:

### Compact (32128 bytes)
```
pixel_data (32000 bytes) | palette (21 bytes)
```
Pixels stored as 4 pages of 8000 bytes each in a single contiguous block.

### Extended (32896 bytes)
```
page0 (8000 px) | pad (192) | page1 (8000 px) | pad (192) |
page2 (8000 px) | pad (192) | page3 (8000 px) | pad (192) | palette (21 bytes)
```
Each page is followed by 192 padding bytes (bank-fill artifacts).

## Pixel Encoding

Each byte encodes 2 pixels (2×4 bits):
- Pixel 1: bits 6, 2–0 → `b_G_R_B` (bit pattern)
- Pixel 0: bits 7, 3–1 → `g_r_b_?` (bit pattern)

The exact nibble assignment (extracted in `parsePixels`):
```
p1 = bits[1] + bits[5:7]   // 4 bits
p2 = bits[0] + bits[2:4]   // 4 bits
```

Pixels are placed in column pairs, advancing 4 columns per byte. Page boundaries reset Y and advance the X base.

## Palette

The file contains a 21-byte palette, but it is **ignored** — a hardcoded 16-color EGA-like palette is used instead (matching ATM Turbo hardware colors):

| Index | Color |
|-------|-------|
| 0 | Black |
| 1–7 | Low-brightness colors |
| 8–15 | High-brightness / full colors |

Palette byte format (ATM Turbo): `gGRB` where bit positions encode Red, Green, Blue at two intensity levels each. The `parseAtmPalette` method uses a 4-level scale `[0, 0x55, 0xAA, 0xFF]`.

## Output

Always PNG (`image/png`).
