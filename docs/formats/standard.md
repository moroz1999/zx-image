# Format: standard

Standard ZX Spectrum screen memory dump (`.scr`).

## Identifiers

- Type key: `standard`
- Plugin class: `ZxImage\Plugin\Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data (bitmap) |
| 6144 | 768 | Attribute data |

Total: **6912 bytes** minimum. `strictFileSize` is not enforced — the parser reads until EOF.

## Pixel Data Layout

The 6144-byte pixel area is divided into three 2048-byte thirds (top, middle, bottom). Within each third, bytes are stored in a peculiar ZX Spectrum order: the address encoding puts character row inside each third before the scanline within each character. The method `calculateZXY` converts the linear read order into a proper Y coordinate:

```
Y offset = (third * 64) + (scanline_within_char * 8) + char_row_within_third
```

This non-linear layout is a consequence of ZX Spectrum VRAM organization.

## Attribute Byte Format

Each attribute byte covers an 8×8 pixel cell (32 cells × 24 rows = 768 bytes):

```
Bit 7   — Flash
Bits 6  — Bright
Bits 5–3 — Paper color (0–7)
Bits 2–0 — Ink color (0–7)
```

The parser stores ink as a 4-bit string `B + GRB` (brightness + green/red/blue) and paper similarly.

## Rendering

- Default canvas: 256×192
- Pixels are mapped via `inkMap` / `paperMap` arrays indexed by `[charRow][charCol]`
- Flash attribute: if any cell has flash=1, output is an **animated GIF** with two frames (normal and ink/paper swapped), each with 32 cs delay (≈1.6 s per cycle)
- No flash: static **PNG**

## Border

Uses base `Plugin::drawBorder`. Border color is a ZX color index 0–7. Default border adds 32 px left/right, 24 px top/bottom (total 320×240).

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
