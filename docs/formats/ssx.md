# Format: ssx / ssxRaw

Sam Coupe SSX auto-detecting container format and raw mode.

## Identifiers

| Type key | Plugin class | Description |
|----------|-------------|-------------|
| `ssx` | `ZxImage\Plugin\Ssx` | Auto-detecting dispatcher |
| `ssxRaw` | `ZxImage\Plugin\SsxRaw` | Sam Coupe 512×192 raw 8bpp palette mode |

## ssx — Auto-detecting Dispatcher

`Ssx` reads `strictFileSize` (set from the actual file size via `makeHandle`) and remaps the `Converter` type:

| File size | Redirects to |
|-----------|-------------|
| 6928 | `standard` |
| 12304 | `mc` |
| 24580 | `sam3` |
| 24592 | `sam4` |
| 98304 | `ssxRaw` |

After setting the new type, `Ssx` calls `Converter::getBinary()` again and returns the result.

## ssxRaw — Raw 8bpp Sam Mode

Canvas: 512×192 (stored), rendered as 512×384 (each pixel doubled vertically).

### File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 98304 | Raw pixel data (512×192 bytes, one byte = one pixel) |

Total: **98304 bytes** (`strictFileSize = 98304`).

### Pixel Encoding

Each byte directly encodes a Sam Coupe palette color using the same `BGRBGRBB` format as `Sam` trait (`parseSamPalette`-equivalent inline decode). Colors are calculated per-pixel without a separate palette array.

### Rendering

Pixels are written to a 512×384 image (each source row is doubled vertically).

### Output

Always PNG (`image/png`).
