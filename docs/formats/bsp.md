# Format: bsp

Border Screen by Trefi — advanced border format with optional gigascreen and embedded border data.

## Identifiers

- Type key: `bsp`
- Plugin class: `ZxImage\Plugin\Bsp`
- Extends: `Gigascreen`

## File Header (70 bytes)

| Offset | Size | Content |
|--------|------|---------|
| 0 | 3 | Signature `bsp` |
| 3 | 1 | Config byte |
| 4 | 1 | Reserved |
| 5 | 1 | Border color byte |
| 6 | 32 | Title (null-padded ASCII) |
| 38 | 32 | Author (null-padded ASCII) |

Config byte bit flags:
- Bit 7: `hasGigaData` — file contains two screens
- Bit 6: `hasBorderData` — file contains border pixel data

If `hasBorderData = 0`: border color byte encodes two 3-bit ZX color indices (for each of the two gigascreen frames).

If `hasBorderData = 1` and `hasGigaData = 1`: a 2-byte `secondBorderDataOffset` follows the header, then two screen blocks, then two border data blocks.

## Screen Data

Each screen block: 6144 bytes pixels + 768 bytes attributes = 6912 bytes.

## Border Data Format

Variable-length RLE-like encoding per pixel pair:
- Bits 2–0: ZX color index (3-bit)
- Bits 7–3: run/tact count:
  - `0` = fill until end of current scanline
  - `1` = next byte gives explicit line count
  - `2` = 12 lines (× 2 for pixel doubling)
  - `3+` = `tacts + 13` lines (× 2)

Border dimensions: 64 px left/right, 64 px top, 48 px bottom.

## Gigascreen Mode

Fully supports all gigascreen modes (mix, flicker, interlace1, interlace2). Border is also blended in gigascreen modes using `gigaColors`.

## Output

- `mix` mode, no flash → PNG (`image/png`)
- All other cases → animated GIF (`image/gif`)
