# Format: monochrome

Standard ZX Spectrum monochrome screen without attribute data.

## Identifiers

- Type key: `monochrome`
- Plugin class: `ZxImage\Plugin\Monochrome`
- Extends: `Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data (bitmap only) |

Total: **6144 bytes** (`strictFileSize = 6144`).

## Rendering

No attributes are stored. The plugin synthesizes a uniform attribute map: bright white ink (`1111`) over bright black paper (`1000`) for every cell. Pixel layout follows standard ZX Spectrum non-linear VRAM order (`calculateZXY`).

## Output

Always GIF (`image/gif`) — `makeGifFromGd` is called unconditionally.
