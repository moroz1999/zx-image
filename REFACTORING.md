# Refactoring Plan: ZX-Image

## Context

Three structural problems:
1. **Deep inheritance with overriding** — 40 plugins override a shared abstract pipeline; logic is scattered across hierarchy levels instead of isolated services.
2. **Array shapes as structs** — `loadBits()` and `parseScreen()` pass raw associative arrays with no type safety.
3. **Binary string parsing** — bytes are converted to `'00001111'` strings and sliced with `substr()` / `bindec()`. Should use bitwise operations on raw integers.

Incremental: one plugin at a time. `composer test` after each plugin. Legacy methods marked `@deprecated`, removed when no callers remain.

Tests: 72 integration SHA256 hash comparisons in `tests/ConverterFixtureTest.php` — pixel output must not change.

---

## Phase 1 — Infrastructure

### 1a. DTOs (`ZxImage/Dto/`)

- [ ] `ZxImage\Dto\AttributeMap` — `readonly class`; `int[][] $inkMap`, `int[][] $paperMap`, `bool[][] $flashMap`
- [ ] `ZxImage\Dto\ParsedScreen` — `readonly class`; `int[][] $pixelsData`, `AttributeMap $attributes`, `int[] $colorOverrides = []`
- [ ] `ZxImage\Dto\PaletteConfig` — `readonly class`; 15 named int properties (`$zz`, `$zn`, ..., `$r33`) replacing assoc array

### 1b. Color lookup key change

- [ ] Change `$colors` keys from binary strings (`'0101'`) to ints (`5`) in `Plugin::generateColors()`
- [ ] Change `$gigaColors` keys from binary strings (`'01011010'`) to ints (`90`) in `Plugin::generateGigaColors()`
- [ ] Update color key assembly in `Standard::exportData()` to use bitwise ops: `($bright << 3) | $color3bit`
- [ ] Update color key assembly in `Gigascreen::exportDataMerged()`: `($colorA << 4) | $colorB`
- [ ] Run `composer test`

### 1c. `BitReader` service (`ZxImage/Service/BitReader.php`)

- [ ] Create `readonly class BitReader` wrapping file handle
  - `readByte(): ?int`, `readWord(): ?int`, `readBytes(int $n): array`, `readWords(int $n): array`, `seek(int $offset): void`, `getSize(): int`
  - Static helpers: `bit(int $byte, int $position): int`, `bits(int $byte, int $offset, int $length): int`
- [ ] Mark `Plugin::read8BitString()`, `read8BitStrings()`, `read16BitString()`, `read16BitStrings()` as `@deprecated`
- [ ] Run `composer test`

---

## Phase 2 — Standard Plugin Services

Extract from `Standard.php` into `ZxImage/Plugin/Standard/`:

- [ ] `AttributeParser` — `parseAttributes()` logic; input: `int[]` raw bytes; output: `AttributeMap`; use `BitReader::bit()` / `bits()` instead of `substr()`
- [ ] `PixelParser` — `parsePixels()` logic; input: `int[]` raw bytes; output: `int[y][x]`; ZX non-linear mapping via `calculateZXY`
- [ ] `PixelRenderer` — `exportData()` drawing loop; input: `ParsedScreen`, `bool $flashedImage`, `int[] $colors`; output: `GdImage`
- [ ] Update `Standard::parseScreen()` to return `ParsedScreen` DTO
- [ ] Update `Standard::exportData()` to accept `ParsedScreen`
- [ ] Run `composer test`

---

## Phase 3 — Plugin Migration (one at a time)

Each step: update `loadBits()` to use `BitReader` (raw bytes), update `parseScreen()` to return `ParsedScreen`, remove binary-string calls, run `composer test`.

- [ ] `Monochrome` — no attributes, minimal loadBits
- [ ] `Attributes` — attributes only, no pixel data
- [ ] `Hidden` — Standard variant, orange override for hidden pixels
- [ ] `Flash` — Standard variant, always GIF
- [ ] `Multicolor` / `Multicolor4` — differ only in `$attributeHeight`
- [ ] `Mc` / `Mlt` — pixel-order variant
- [ ] `Timex81`
- [ ] `Sam2`, `Sam3`, `Sam4`
- [ ] `Ulaplus` — custom palette; use `colorOverrides` field of `ParsedScreen`
- [ ] `Nxi` / `Sl2` — indexed 256-color
- [ ] `Specscii` / `S81` / `S80` — token-based text
- [ ] `Bsc` / `Bmc4` — embedded border pixel data
- [ ] `Timexhr`
- [ ] `Grf`
- [ ] `Lowresgs`
- [ ] `Stellar`
- [ ] `Chrd`
- [ ] `Multiartist`
- [ ] `Bsp`
- [ ] `Gigascreen` + `Timexhrg`
- [ ] `Tricolor`
- [ ] `Sca`
- [ ] `Atmega`
- [ ] `Sxg`
- [ ] `Zxevo`
- [ ] `Ssx` / `SsxRaw`

---

## Phase 4 — Cleanup

- [ ] Remove deprecated `read8BitString*` / `read16BitString*` from `Plugin.php` (verify no callers with grep)
- [ ] Remove deprecated abstract method variants if replaced
- [ ] Run `composer psalm` and fix all issues
- [ ] Final `composer test` — all 72 fixtures green

---

## Key Files

| File | Role |
|------|------|
| `ZxImage/Plugin/Plugin.php` | Base class — color tables, read methods, pipeline |
| `ZxImage/Plugin/Standard.php` | Core renderer — pixel/attribute parsing, exportData |
| `ZxImage/Plugin/Gigascreen.php` | Dual-screen base — exportDataMerged, interlace |
| `ZxImage/Converter.php` | Entry point — palette parsing, cache key |
| `tests/ConverterFixtureTest.php` | Integration tests |

New files:
- `ZxImage/Dto/AttributeMap.php`
- `ZxImage/Dto/ParsedScreen.php`
- `ZxImage/Dto/PaletteConfig.php`
- `ZxImage/Service/BitReader.php`
- `ZxImage/Plugin/Standard/AttributeParser.php`
- `ZxImage/Plugin/Standard/PixelParser.php`
- `ZxImage/Plugin/Standard/PixelRenderer.php`