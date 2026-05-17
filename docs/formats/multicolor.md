# Format: multicolor / multicolor4

ZX Spectrum multicolor modes with reduced attribute cell height.

## Identifiers

| Type key | Plugin class | Attribute height | File size |
|----------|-------------|-----------------|-----------|
| `multicolor` | `ZxImage\Plugin\Multicolor` | 8×**2** px | 9216 bytes |
| `multicolor4` | `ZxImage\Plugin\Multicolor4` | 8×**4** px | 7680 bytes |

Both extend `Multicolor`, which extends `Standard`.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data (standard ZX non-linear order) |
| 6144 | (size - 6144) | Attribute data (one byte per 8×H cell) |

### Attribute counts

- `multicolor` (H=2): 32 cols × 96 rows = **3072 attributes** → total 9216 bytes
- `multicolor4` (H=4): 32 cols × 48 rows = **1536 attributes** → total 7680 bytes

## Rendering

Same as `standard` except `attributeHeight` is 2 or 4 instead of 8. Each pixel row references the attribute cell at `y / attributeHeight`. Flash behavior and animation are inherited from `Standard`.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
