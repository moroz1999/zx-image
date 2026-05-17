# Format: nxi

ZX Spectrum Next native screen format — 256×192 pixels, 256-color indexed palette.

## Identifiers

- Type key: `nxi`
- Plugin class: `ZxImage\Plugin\Nxi`
- Extends: `Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 512 | Palette (256 entries × 2 bytes each, RGB333 format) |
| 512 | 49152 | Pixel data (256×192 bytes, one byte per pixel) |

Total: **49664 bytes** (`strictFileSize = 49664`).

## Palette Format (16-bit words, big-endian)

Each 16-bit word encodes an RGB333 color:
- Bits 15–13: Red (0–7)
- Bits 12–10: Green (0–7)
- Bits 9–8: Blue (0–3, 2 bits)
- Bit 0: Blue LSB (extra blue precision)

Blue reconstruction: `bindec(bits[9:8] + bits[0])` → 3-bit blue index → mapped via `rgb3torgb8` lookup.

The `rgb3torgb8` lookup maps 0–7 linearly to `[0, 36, 73, 109, 146, 182, 219, 255]`.

## Pixel Data

Linear row-major order: 256 pixels per row, 192 rows. Each byte is a palette index (0–255).

## Output

Always PNG (`image/png`).
