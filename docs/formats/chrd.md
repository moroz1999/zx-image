# Format: chrd (chr$)

CHR$ format by Alone Coder — variable-size image with monochrome, standard, or gigascreen color modes.

## Identifiers

- Type key: `chrd` (also aliased as `chr$` → remapped to `chrd` by `Converter`)
- Plugin class: `ZxImage\Plugin\Chrd`
- Extends: `Gigascreen`
- No border (`usesBorder = false`)

## File Header (7 bytes)

| Offset | Size | Content |
|--------|------|---------|
| 0 | 4 | Signature `chr$` (case-insensitive) |
| 4 | 1 | Width in characters (×8 = pixel width) |
| 5 | 1 | Height in characters (×8 = pixel height) |
| 6 | 1 | Color type |

Color types:

| Value | Mode | Data per character cell |
|-------|------|------------------------|
| `8` | Monochrome | 8 pixel rows |
| `9` | Standard color | 8 pixel rows + 1 attribute byte |
| `18` | Gigascreen | 8 pixel rows + 1 attribute + 8 pixel rows + 1 attribute (two screens) |

## Data Layout

Data is stored char-by-char in reading order (left-to-right, top-to-bottom characters). Pixel rows within each character are stored in linear (non-ZX-VRAM) order.

The `parsePixels` override reconstructs the 2D pixel map character by character.

## Rendering

- Color type `8` (monochrome): not fully rendered (returns null from `convert` — falls through)
- Color type `9` (standard): renders like `Standard`, with flash animation if applicable
- Color type `18` (gigascreen): delegates to `Gigascreen::convert()` with all gigascreen modes available

## Output

- Type `9`, no flash → PNG (`image/png`)
- Type `9`, flash present → animated GIF (`image/gif`)
- Type `18` → same as `gigascreen` format output
