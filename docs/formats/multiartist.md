# Format: multiartist (mg1, mg2, mg4, mg8)

ZX Spectrum multicolor gigascreen images exported from the Multiartist editor.

## Identifiers

- Type keys: `multiartist`, `mg1`, `mg2`, `mg4`, `mg8` (mg* aliases are remapped to `multiartist` by `Converter`)
- Plugin class: `ZxImage\Plugin\Multiartist`
- Extends: `Gigascreen`

## File Header (256 bytes)

| Offset | Size | Content |
|--------|------|---------|
| 0 | 3 | Signature `MGH` |
| 3 | 1 | Version (must be `1`) |
| 4 | 1 | MGH mode (attribute height mode) |
| 5 | 1 | Border color for screen 1 |
| 6 | 1 | Border color for screen 2 |
| 7–255 | 249 | Reserved / unused |

## MGH Modes

| Mode byte | Attr height | Attributes per screen | Extra data |
|-----------|------------|----------------------|------------|
| 1 | 1 px | 3072 inner + 384 outer | Outer attributes for columns 0–7 and 24–31 |
| 2 | 2 px | 3072 | — |
| 4 | 4 px | 1536 | — |
| 8 | 8 px | 768 | — |

## Data Layout (after 256-byte header)

For modes 2, 4, 8:
```
screen1_pixels (6144) | screen2_pixels (6144) | screen1_attrs | screen2_attrs
```

For mode 1 (8×1 multicolor):
```
screen1_pixels (6144) | screen2_pixels (6144) |
screen1_inner_attrs (3072) | screen2_inner_attrs (3072) |
screen1_outer_attrs (384) | screen2_outer_attrs (384)
```

### Mode 1 Outer Attributes

In mode 1, the central 16 columns (8–23) have per-row attributes, but the outer 8 columns on each side (0–7 and 24–31) share one attribute per 8-row block. The 384 outer bytes cover 8 leftmost + 8 rightmost columns at character-row granularity (8×24 + 8×24 = 384).

## Rendering

Fully supports all gigascreen modes (mix, flicker, interlace1, interlace2).

Border: in `mix` mode the border color is blended via `gigaColors` using both screens' border bytes. In flicker/interlace modes each frame uses its own screen's border color.

## Output

- `mix` mode, no flash → PNG (`image/png`)
- All other cases → animated GIF (`image/gif`)
