# Format: gigascreen

Two standard ZX Spectrum screens shown at 50 Hz software flickering to produce an extended color palette.

## Identifiers

- Type key: `gigascreen`
- Plugin class: `ZxImage\Plugin\Gigascreen`
- Extends: `Standard`

## Description

Gigascreen exploits the 50 Hz screen refresh of the ZX Spectrum. Two different screens are alternated every frame, and the human eye perceives the average of the two colors, giving access to a much larger effective palette.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Screen 1 pixel data |
| 6144 | 768 | Screen 1 attribute data |
| 6912 | 6144 | Screen 2 pixel data |
| 13056 | 768 | Screen 2 attribute data |

Total: **13824 bytes** (`strictFileSize = 13824`).

## Rendering Modes

Controlled by `gigascreenMode` (see [domain.md](../domain.md#gigascreen-modes)):

### `mix` (default)
Averages both screens per pixel using the `gigaColors` precomputed lookup. Produces a static PNG.

### `flicker`
Alternates screen 1 and screen 2 at 2 cs per frame (≈50 Hz). Produces animated GIF with 2 frames.

### `interlace1` / `interlace2`
Mixes screens by alternating horizontal lines (1-line or 2-line pitch). Produces animated GIF with 2 frames (2 cs each). The `interlaceMix` helper swaps pixel rows between the two GD images before GIF encoding.

## Flash + Gigascreen

If either screen has flash cells, the output has 32 GIF frames cycling through (screen1, screen2) × (normal, flashed) combinations.

## Output

- `mix` mode, no flash → PNG (`image/png`)
- All other cases → animated GIF (`image/gif`)
