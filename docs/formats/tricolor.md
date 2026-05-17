# Format: tricolor

Software-flickering RGB image for ZX Spectrum using three monochrome screens.

## Identifiers

- Type key: `tricolor`
- Plugin class: `ZxImage\Plugin\Tricolor`
- Extends: `Standard`

## Description

The technique uses three separate monochrome screens with fixed color assignments (red, green, blue), flickered rapidly in sequence so the display appears to show true RGB color at reduced brightness.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Red channel pixel data |
| 6144 | 6144 | Green channel pixel data |
| 12288 | 6144 | Blue channel pixel data |

Total: **18432 bytes** (`strictFileSize = 18432`). No attribute data — each screen is monochrome.

## Color Assignments

| Screen | Ink color code | Description |
|--------|---------------|-------------|
| 0 | `1010` | Bright red |
| 1 | `1100` | Bright green |
| 2 | `1001` | Bright blue |

Paper is always `0000` (black) for all three screens.

## Rendering Modes

### `mix` (default)
Adds the three screen images as RGB channels into a single PNG. Each source image contributes only its own channel, so pixel colors add up correctly.

### `flicker`
Alternates the three screens as animated GIF frames at 2 cs per frame (≈50 Hz).

## Output

- `mix` mode → PNG (`image/png`)
- `flicker` mode → animated GIF (`image/gif`)
