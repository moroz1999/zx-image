# Format: timex81

Timex multicolor 8×1 screen in Timex/Sinclair memory dump format.

## Identifiers

- Type key: `timex81`
- Plugin class: `ZxImage\Plugin\Timex81`
- Extends: `Standard`

## Description

Similar to `mlt` — one attribute per pixel row — but both pixel data and attribute data use the ZX Spectrum non-linear VRAM order (attributes are also stored in the Timex screen memory dump layout).

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data (ZX non-linear order) |
| 6144 | 6144 | Attribute data (ZX non-linear order, one per pixel row) |

Total: **12288 bytes** (`strictFileSize = 12288`).

## Rendering

`attributeHeight = 1`. The `parseAttributes` override applies `calculateZXY` to the attribute's Y coordinate, mirroring the same non-linear address transform that is applied to pixels. This correctly maps the Timex screen dump attribute layout.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
