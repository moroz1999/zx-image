# Format: sxg

ZX Evolution (also TSConf) screen in SXG format — supports 16-color and 256-color modes.

## Identifiers

- Type key: `sxg`
- Plugin class: `ZxImage\Plugin\Sxg`
- Extends: `Plugin` (directly)

## File Header

| Offset | Size | Content |
|--------|------|---------|
| 0 | 1 | Magic byte `127` (0x7F) |
| 1 | 3 | Signature `SXG` |
| 4 | 1 | Version |
| 5 | 1 | Background color |
| 6 | 1 | Packed flag |
| 7 | 1 | Format: `1` = 16 colors, `2` = 256 colors |
| 8 | 2 | Width (little-endian word) |
| 10 | 2 | Height (little-endian word) |
| 12 | 2 | Palette shift (offset of palette data from start of variable part) |
| 14 | 2 | Pixels shift (offset of pixel data from start of variable part) |
| 16 | paletteShift−2 | Padding/reserved |
| 16+paletteShift | paletteLength×2 | Palette (16-bit words, little-endian) |
| 16+pixelsShift | width×height or width×height/2 | Pixel data |

## Palette Format (16-bit words, little-endian)

Two variants based on the high bit of the word:

**Bit 15 = 0** (interpolated):
- Bits 14–10: R (0–23 → mapped via lookup table to 0–255)
- Bits 9–5: G (0–23)
- Bits 4–0: B (0–23)

**Bit 15 = 1** (direct RGB):
- Bits 14–10: R (shifted left 3)
- Bits 9–5: G (shifted left 3)
- Bits 4–0: B (shifted left 3)

The lookup table maps values 0–24 to linear 0–255.

## Pixel Format

| Mode | Encoding |
|------|----------|
| `FORMAT_16` (1) | 2 pixels per byte (high nibble first, low nibble second) |
| `FORMAT_256` (2) | 1 pixel per byte |

## Rendering

Pixels are mapped directly to palette entries. No border is applied. Zoom and rotation are applied after rendering.

## Output

Always PNG (`image/png`).
