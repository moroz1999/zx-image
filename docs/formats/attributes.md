# Format: attributes

ZX Spectrum attributes-only screen. Achieves 53-color effect by using a pixel grid pattern.

## Identifiers

- Type key: `attributes`
- Plugin class: `ZxImage\Plugin\Attributes`
- Extends: `Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 768 | Attribute data only |

Total: **768 bytes** (`strictFileSize = 768`).

## Rendering

No pixel data is stored. The plugin generates a synthetic pixel map with a checkerboard-like pattern:
- Rows alternate between `01010101` and `10101010` patterns (4 rows each, repeated for 3 screen thirds)
- This creates a fine grid that, at normal zoom, blends ink and paper colors optically, yielding up to 53 distinct perceived colors from 16 ZX colors

Flash support: if any attribute has the flash bit set, output is an animated GIF.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
