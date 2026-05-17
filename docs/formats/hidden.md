# Format: hidden

Debug / analysis view that reveals "hidden" pixel data in standard ZX Spectrum screens.

## Identifiers

- Type key: `hidden`
- Plugin class: `ZxImage\Plugin\Hidden`
- Extends: `Standard`

## Description

In standard ZX screens, cells where ink color equals paper color make pixel data invisible (ink pixels look the same as paper). This format renders those hidden pixels as bright orange (`#FF8000`) so they become visible for analysis.

## Rendering Rule per Pixel

1. If the cell has `ink == paper` AND `pixel = 1` → render as orange (`0xFF8000`)
2. If the cell has `ink == paper` AND `pixel = 0` → render normally (paper color)
3. Otherwise (ink ≠ paper) → render normally (ink or paper depending on pixel bit)
4. Flash-reversed frame: ink and paper are swapped for flash cells

## File Layout

Same as `standard`: 6144 bytes pixel data + 768 bytes attributes = **6912 bytes**.

## Output

- Flash present → animated GIF (`image/gif`)
- No flash → PNG (`image/png`)
