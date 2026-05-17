# Format: stellar

ZX Spectrum 64-color 64×48 resolution mode achieved via gigascreen screen switching on 4×4 pixel blocks.

## Identifiers

- Type key: `stellar`
- Plugin class: `ZxImage\Plugin\Stellar`
- Extends: `Gigascreen`

## Description

First achieved by RST7 in "Eye Ache 2" for Pentagon machines, re-implemented for original Spectrums by Gasman in "Buttercream Sputnik". By rapidly switching between two attribute screens on 128K machines, each 4×4 pixel block can display alternating bright and dark colors, giving an effective palette of 64 colors at 64×48 resolution with no attribute clash and no flicker.

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 3072 | Attribute data for both screens, interleaved as quads |

Total: **3072 bytes** (`strictFileSize = 3072`). No pixel data in the file.

## Data Format

Bytes are read in groups of four, with each group split between two screens:
- Byte 0 → screen 1 attribute
- Byte 1 → screen 1 attribute
- Byte 2 → screen 2 attribute
- Byte 3 → screen 2 attribute

Each screen receives 1536 attribute bytes (32 cols × 48 half-rows, since `attributeHeight = 4`).

## Pixel Pattern

A synthetic pixel pattern is generated: alternating `00001111` bytes fill the entire canvas. Together with the 8×4 attribute grid and gigascreen color mixing, this produces distinct 4×4 blocks at 64×48 resolution.

## Rendering

Processes both attribute sets as a standard gigascreen. Supports mix, flicker, and interlace modes.

## Output

- `mix` mode → PNG (`image/png`)
- `flicker` / `interlace` → animated GIF (`image/gif`)
