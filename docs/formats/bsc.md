# Format: bsc

Border Screen — ZX Spectrum screen with embedded border pixel data.

## Identifiers

- Type key: `bsc`
- Plugin class: `ZxImage\Plugin\Bsc`
- Extends: `Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data |
| 6144 | 768 | Attribute data |
| 6912 | 4224 | Border pixel data |

Total: **11136 bytes** (`strictFileSize = 11136`).

## Border Data Format

The 4224-byte border area encodes the entire visible border surrounding the 256×192 image at 8-pixel horizontal resolution. Border dimensions are 64 px left/right, 56 px top/bottom (total canvas 384×304).

Each border byte encodes two 8-pixel-wide "pixels":
- Bits 2–0 of the right nibble → left block color (3-bit ZX color index, padded to 4 bits: `0GRB`)
- Bits 5–3 of the byte → right block color

The border is drawn row by row. When a row reaches the start of the image area horizontally (for rows within the image Y range), the horizontal position jumps over the 256-pixel image width.

The image is composited into the border image offset by `(borderWidth=64, borderHeight=56+8)` — there is an 8-pixel vertical offset added beyond the declared `borderHeight`.

## Rendering

Inherits `Standard` rendering for the center image. Flash → animated GIF, no flash → PNG.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
