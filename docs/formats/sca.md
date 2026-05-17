# Format: sca

ZX Spectrum multi-frame animation container.

## Identifiers

- Type key: `sca`
- Plugin class: `ZxImage\Plugin\Sca`
- Extends: `Standard`

## File Header

| Offset | Size | Content |
|--------|------|---------|
| 0 | 3 | Signature `SCA` |
| 3 | 1 | Version (must be `1`) |
| 4 | 2 | Width in pixels (word) |
| 6 | 2 | Height in pixels (word) |
| 8 | 1 | Border color |
| 9 | 2 | Number of frames |
| 11 | 1 | Payload type (must be `0` for uncompressed) |
| 12 | 2 | Data pointer (offset where frame delays and pixel data begin) |

## Data Section (at offset `dataPointer`)

| Layout | Size per item |
|--------|--------------|
| Frame delay array | 1 byte per frame (converted to centiseconds: `delay * (100/50)`) |
| Frame data | 6144 bytes pixels + 768 bytes attributes per frame |

## Rendering

Each frame is rendered as a standard ZX Spectrum screen (same as `Standard::exportData`). All frames are encoded as GIF palette images and assembled into a single animated GIF using `GifCreator`. Frame delays come from the file's delay table.

## Output

Always animated GIF (`image/gif`), regardless of whether any attribute has the flash bit set.
