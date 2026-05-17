# Format: sam3 / sam4

Sam Coupe native screen modes 3 and 4.

## Identifiers

| Type key | Plugin class | Mode | File size |
|----------|-------------|------|-----------|
| `sam3` | `ZxImage\Plugin\Sam3` | Mode 3 (4 colors, 2 bpp) | 24617 bytes |
| `sam4` | `ZxImage\Plugin\Sam4` | Mode 4 (16 colors, 4 bpp) | 24617 bytes |

Both use the `Sam` trait for shared `loadBits` and `parseSamPalette` logic and extend `Standard`.

## sam3 — Mode 3 (4 colors, 2 bpp)

Canvas: 512×192 pixels (stored), rendered as 512×384 (each pixel doubled vertically).

### File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 24576 | Pixel data (512×192 / 4 pixels per byte = 24576 bytes) |
| 24576 | 4 | Palette (4 entries, 1 byte each) |
| 24580 | 37 | Unused |

Total: **24617 bytes**.

### Pixel Encoding

Each byte encodes 4 pixels (2 bits each). The plugin reads pairs of 2-bit values from each byte and swaps color indices 1 and 2 (a quirk of Sam Coupe mode 3 color ordering).

## sam4 — Mode 4 (16 colors, 4 bpp)

Canvas: 256×192 pixels.

### File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 24576 | Pixel data (256×192 / 2 pixels per byte = 24576 bytes) |
| 24576 | 16 | Palette (16 entries, 1 byte each) |
| 24592 | 25 | Unused |

Total: **24617 bytes**.

### Pixel Encoding

Each byte encodes 2 pixels (4 bits each): high nibble = first pixel, low nibble = second pixel.

## Sam Coupe Palette Byte Format (`BGRBGRBB` / 8-bit Sam palette)

```
Bit 7: g (low green)
Bit 6: r (low red)
Bit 5: G (high green)
Bit 4: bright
Bit 3: b (low blue)
Bit 2: R (high red)
Bit 1: G2 (high green alt)
Bit 0: B (high blue)
```

Actual formula used:
```
R = (R_high * 4 + R_low * 2 + bright) * 36
G = (G_high * 4 + G_low * 2 + bright) * 36
B = (B_high * 4 + B_low * 2 + bright) * 36
```

Result is passed through the active color correction matrix.

## Output

Always PNG (`image/png`).
