# Format: mc / mlt

ZX Spectrum multicolor 8×1 — one attribute byte per pixel row.

## Identifiers

| Type key | Plugin class | Pixel layout | Attribute layout | File size |
|----------|-------------|-------------|-----------------|-----------|
| `mc` | `ZxImage\Plugin\Mc` | Non-standard ZX (overridden) | Linear | 12288 bytes |
| `mlt` | `ZxImage\Plugin\Mlt` | Standard ZX non-linear | Linear | 12288 bytes |

Both extend `Standard`.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data |
| 6144 | 6144 | Attribute data (192 rows × 32 cols = 6144 bytes) |

Total: **12288 bytes**.

## Differences Between mc and mlt

### `mc`
- `calculateZXY` returns `y` unchanged — pixels are stored in simple linear (top-to-bottom) order
- `attributeHeight = 1` — one attribute per pixel row

### `mlt`
- `calculateZXY` is inherited from `Standard` — pixels are stored in ZX Spectrum's non-linear VRAM order
- Attributes are stored linearly row by row
- `parseAttributes` uses the standard attribute byte format (flash, brightness, paper, ink)
- `attributeHeight = 1`

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
