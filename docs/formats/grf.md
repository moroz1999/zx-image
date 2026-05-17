# Format: grf

Profi GRF file — variable-resolution graphics format for the Profi ZX clone. Partial support for hi-res 16-color EGA mode.

## Identifiers

- Type key: `grf`
- Plugin class: `ZxImage\Plugin\Grf`
- Extends: `Standard`

## File Header (128 bytes)

| Offset | Size | Content |
|--------|------|---------|
| 0 | 2 | Width in pixels (little-endian word) |
| 2 | 2 | Height in scan lines (little-endian word) |
| 4 | 1 | BPP (bits per pixel or pixels per byte) |
| 5 | 1 | AMOD (0 = byte interleaved attrs, 1 = direct color per pixel) |
| 6 | 2 | BPS (bytes per scan line) |
| 8 | 1 | HLEN (header length in 128-byte records) |
| 9 | 1 | Format marker (19 = `0x13` means palette follows) |
| 10 | 16 | Palette (16 entries × 1 byte each, format GGGRRRBB) — only when format marker = 19 |
| 10+palette | 102 | Reserved (or 118 bytes if no palette) |

## Currently Implemented Mode

Only the **hi-res 16-color mode** is implemented (AMOD=1, BPP=4):
- Bytes are stored interleaved: each pixel byte is followed by an attribute byte
- Pixel byte: 8 bits = 8 pixel flags (0 = paper color, 1 = ink color)
- Attribute byte: ink = bits `[1][5:7]`, paper = bits `[0][2:4]` (as 4-bit color indices)

## Palette Format

Each palette byte: `GGGRRRBB`
- Bits 7–5: Green (0–7 → × 36)
- Bits 4–2: Red (0–7 → × 36)
- Bits 1–0: Blue (0–3 → × 85)

## Aspect Ratio Correction

The rendered image is vertically stretched by factor 1.6384 before applying zoom, to correct the non-square pixel aspect ratio of Profi's EGA-like output (similar to PC CGA/EGA 320×200 on a 4:3 display).

## Output

Always PNG (`image/png`).

## Notes

Other Profi GRF modes (PROFI-mono, PROFI-color, CGA, VGA) are not implemented. The code always reads in hi-res 16-color mode regardless of header BPP/AMOD values.
