# Format: ulaplus

ZX Spectrum screen with ULA+ extended palette.

## Identifiers

- Type key: `ulaplus`
- Plugin class: `ZxImage\Plugin\Ulaplus`
- Extends: `Standard`

## File Layout

| Offset | Size | Content |
|--------|------|---------|
| 0 | 6144 | Pixel data |
| 6144 | 768 | Attribute data (ULA+ encoding) |
| 6912 | 64 | ULA+ palette (64 GGGRRRBB bytes) |

Total: **6976 bytes** (`strictFileSize = 6976`).

## ULA+ Attribute Encoding

ULA+ uses a different attribute byte format than standard. Each byte encodes:
- Bits 7–6: brightness/group selector (0–3)
- Bits 5–3: paper color index within group (0–7) → paper palette index = group * 16 + color + 8
- Bits 2–0: ink color index within group (0–7) → ink palette index = group * 16 + color

This gives 64 distinct ink entries and 64 distinct paper entries.

## ULA+ Palette Format

Each of the 64 palette bytes is encoded as `GGGRRRBB`:
- Bits 7–5: green (0–7 → 0–224)
- Bits 4–2: red (0–7 → 0–224)
- Bits 1–0: blue (0–3 → 0–192)

Palette values are passed through the active color correction matrix before storage.

## Rendering

No flash support — ULA+ images always render as static **PNG**.

## Output

Always PNG (`image/png`).
