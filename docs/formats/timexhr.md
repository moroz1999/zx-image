# Format: timexhr / timexhrg

Timex/Sinclair hi-res 512×192 monochrome screen (and its gigascreen variant).

## Identifiers

| Type key | Plugin class | Extends | File size |
|----------|-------------|---------|-----------|
| `timexhr` | `ZxImage\Plugin\Timexhr` | `Standard` | 12289 bytes |
| `timexhrg` | `ZxImage\Plugin\Timexhrg` | `Gigascreen` | 24578 bytes |

## timexhr — File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Odd-column pixel data |
| 6144 | 6144 | Even-column pixel data |
| 12288 | 1 | Color attribute byte |

Total: **12289 bytes** (`strictFileSize = 12289`). Output canvas: 512×192 (doubled vertically to 512×384 in GD).

## timexhrg — File Layout

Two `timexhr` blocks concatenated:

| Offset | Size | Content |
|--------|------|---------|
| 0 | 12289 | First timexhr screen |
| 12289 | 12289 | Second timexhr screen |

Total: **24578 bytes** (`strictFileSize = 12289 * 2`).

## Pixel Interleaving

The two 6144-byte pixel arrays are interleaved column-by-column to produce 512 columns from 256 each:
```
column[2n]   = odd_array[n]
column[2n+1] = even_array[n]
```
Each pixel row in the result is duplicated vertically (each pixel draws 2 rows) so the final GD canvas is 512×384.

## Color Attribute Byte

Bits 3–5 encode the color mode (3 bits = 8 possible color pairs):

| Code | Ink | Paper |
|------|-----|-------|
| `000` | Bright black (bright) | Bright white |
| `001` | Bright blue | Bright yellow |
| `010` | Bright red | Bright cyan |
| `011` | Bright magenta | Bright green |
| `100` | Bright green | Bright magenta |
| `101` | Bright cyan | Bright red |
| `111` | Bright white | Bright black |

(Code `110` — yellow on blue — is present in `Timexhrg` code but not in `Timexhr`.)

## timexhrg Rendering

Uses gigascreen mode. Both screens are blended or flickered via the same logic as `Gigascreen`. `exportDataMerged` uses `gigaColors` combining both screens' ink/paper per pixel (doubled rows again). The border color is taken from the paper color of each screen's attribute byte.

## Output

- `timexhr`: always PNG (`image/png`)
- `timexhrg`: depends on gigascreen mode — PNG (`mix`) or animated GIF (`flicker`/`interlace`)
