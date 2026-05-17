# Format: lowresgs

ZX Spectrum gigascreen 8×4 attributes low-resolution screen.

## Identifiers

- Type key: `lowresgs`
- Plugin class: `ZxImage\Plugin\Lowresgs`
- Extends: `Gigascreen`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 84 | Unknown/reserved header |
| 84 | 8 | Texture bytes (8 rows of a tile pattern) |
| 92 | 768 | Screen 1 attribute data |
| 860 | 768 | Screen 2 attribute data |

Total: **1628 bytes** (`strictFileSize = 1628`).

## Rendering

No actual pixel data is stored. The 8 texture bytes define a repeating tile pattern that is tiled across the full 256×192 canvas (3 screen thirds × 8 character rows × each row uses one texture byte, repeated 32×8 times horizontally).

The two attribute sets are applied to the same shared pixel pattern, producing two slightly different color-mapped screens that are then processed as a standard gigascreen (mix or flicker/interlace modes).

Attribute height: 8 (standard ZX character grid).

## Output

- `mix` mode → PNG (`image/png`)
- `flicker` / `interlace` → animated GIF (`image/gif`)
