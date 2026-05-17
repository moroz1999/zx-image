# Format: flash

ZX Spectrum screen for the hardware "Flash color" modification.

## Identifiers

- Type key: `flash`
- Plugin class: `ZxImage\Plugin\Flash`
- Extends: `Standard`

## Description

The Flash color hardware modification repurposes the attribute flash bit for a different blending effect:
- Flash bit = 0: normal rendering (ink/paper as usual)
- Flash bit = 1 (flash cell):
  - Ink pixel (`pixel = 1`): blend of ink + paper using `gigaColors` table
  - Paper pixel (`pixel = 0`): always rendered as black (`0000`)

This creates a "mixed" color effect on ink pixels while forcing black paper in flash cells.

## File Layout

Same as `standard`: 6144 bytes pixel data + 768 bytes attributes = **6912 bytes**.

## Rendering

Always produces a single static frame even when flash bits are set (no animation).

## Output

Always GIF (`image/gif`) — `makeGifFromGd` is called unconditionally.
