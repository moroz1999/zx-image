# Format: bmc4

Border Multicolor 8×4 — ZX Spectrum multicolor screen with embedded border pixel data.

## Identifiers

- Type key: `bmc4`
- Plugin class: `ZxImage\Plugin\Bmc4`
- Extends: `Bsc`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data |
| 6144 | 1536 | Attribute data (8×4 cells, 32×48 = 1536 attributes) |
| 7680 | 4224 | Border pixel data |

Total: **11904 bytes** (`strictFileSize = 11904`). `attributesLength = 1536`.

## Difference from bsc

- `attributeHeight = 4` (8×4 cells instead of 8×8)
- The raw attribute array is 1536 bytes for inner attributes + 768 bytes interleaved differently. The `loadBits` override reorders attributes from two interleaved tables into the correct sequential order expected by `parseAttributes`:
  - For each of 24 char rows: first append 32 normal attributes, then 32 additional attributes from offset 768.

## Border

Identical to `bsc` — 4224 bytes, 64 px side borders, 56 px top/bottom.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
