# Refactoring Plan: ZX-Image

## Context

Three structural problems:
1. **Deep inheritance with overriding** — 40 plugins override a shared abstract pipeline; logic is scattered across hierarchy levels instead of isolated services.
2. **Array shapes as structs** — `loadBits()` and `parseScreen()` pass raw associative arrays with no type safety.
3. **Binary string parsing** — bytes are converted to `'00001111'` strings and sliced with `substr()` / `bindec()`. Should use bitwise operations on raw integers.

Incremental: one plugin at a time. `composer test` after each plugin. Legacy methods marked `@deprecated`, removed when no callers remain.

Tests: 71 integration SHA256 hash comparisons in `tests/ConverterFixtureTest.php` — pixel output must not change.

---

## Phase 1 — Infrastructure

### 1a. DTOs (`ZxImage/Dto/`)

- [x] `ZxImage\Dto\AttributeMap` — `readonly class`; `int[][] $inkMap`, `int[][] $paperMap`, `bool[][] $flashMap`
- [x] `ZxImage\Dto\ParsedScreen` — `readonly class`; `int[][] $pixelsData`, `AttributeMap $attributes`, `int[] $colorOverrides = []`
- [x] `ZxImage\Dto\PaletteConfig` — `readonly class`; 15 named int properties (`$zz`, `$zn`, ..., `$r33`) replacing assoc array

### 1b. Color lookup key change

- [x] Change `$colors` keys from binary strings (`'0101'`) to ints (`5`) in `Plugin::generateColors()`
- [x] Change `$gigaColors` keys from binary strings (`'01011010'`) to ints (`90`) in `Plugin::generateGigaColors()`
- [x] Update color key assembly in `Standard::parseAttributes()`, `Gigascreen::exportDataMerged()`, and all other callers
- [x] Run `composer test`

### 1c. `BitReader` service (`ZxImage/Service/BitReader.php`)

- [x] Create `readonly class BitReader` wrapping file handle
  - `readByte(): ?int`, `readWord(): ?int`, `readBytes(int $n): array`, `readWords(int $n): array`, `seek(int $offset): void`, `getSize(): int`
  - Static helpers: `bit(int $byte, int $position): int`, `bits(int $byte, int $offset, int $length): int`
- [x] Mark `Plugin::read8BitString()`, `read8BitStrings()`, `read16BitString()`, `read16BitStrings()` as `@deprecated`
- [x] Run `composer test`

---

## Phase 2 — Standard Plugin Services

Extract from `Standard.php` into `ZxImage/Plugin/Standard/`:

- [x] `AttributeParser` — `parseAttributes()` logic; input: `int[]` raw bytes; output: `AttributeMap`; use `BitReader::bit()` / `bits()` instead of `substr()`
- [x] `PixelParser` — `parsePixels()` logic; input: `int[]` raw bytes; output: `int[y][x]`; ZX non-linear mapping via `calculateZXY`
- [x] `PixelRenderer` — `exportData()` drawing loop; input: `ParsedScreen`, `bool $flashedImage`, `int[] $colors`; output: `GdImage`
- [x] Update `Standard::parseScreen()` to return `ParsedScreen` DTO
- [x] Update `Standard::exportData()` to accept `ParsedScreen`
- [x] Run `composer test`

---

## Phase 3 — Plugin Migration (one at a time)

Each step: update `loadBits()` to use `BitReader` (raw bytes), update `parseScreen()` to return `ParsedScreen`, remove binary-string calls, run `composer test`.

- [x] `Monochrome` — no attributes, minimal loadBits
- [x] `Attributes` — attributes only, no pixel data
- [x] `Hidden` — Standard variant, orange override for hidden pixels
- [x] `Flash` — Standard variant, always GIF
- [x] `Multicolor` / `Multicolor4` — differ only in `$attributeHeight`
- [x] `Mc` / `Mlt` — pixel-order variant
- [x] `Timex81`
- [x] `Sam2`, `Sam3`, `Sam4`
- [x] `Ulaplus` — custom palette; use `colorOverrides` field of `ParsedScreen`
- [x] `Nxi` / `Sl2` — indexed 256-color
- [x] `Specscii` / `S81` / `S80` — token-based text
- [x] `Bsc` / `Bmc4` — embedded border pixel data
- [x] `Timexhr`
- [x] `Grf`
- [x] `Lowresgs`
- [x] `Stellar`
- [x] `Chrd`
- [x] `Multiartist`
- [x] `Bsp`
- [x] `Gigascreen` + `Timexhrg`
- [x] `Tricolor`
- [x] `Sca`
- [x] `Atmega`
- [x] `Sxg`
- [x] `Zxevo`
- [x] `Ssx` / `SsxRaw`
- [x] Extract shared character-screen layout into `ZxImage\Service\CharacterScreenBuilder`
- [x] Fix `chrd`, `s80`, and `s81` fixture regressions after byte-based parser migration
- [x] Remove unused legacy `Plugin` abstract base, `Configurable` interface, and `Sam` trait
- [x] Introduce `PluginRuntime` and `StandardScreenPipeline` for composition-based standard rendering
- [x] Migrate `Standard`, `Attributes`, `Multicolor`, `Multicolor4`, `S80`, and `S81` off `StandardConvertTrait`
- [x] Migrate `Flash`, `Hidden`, `Mc`, `Mlt`, `Timex81`, `Sam2`, `Ulaplus`, `Specscii`, `Bsc`, and `Bmc4` off `StandardConvertTrait`
- [x] Remove `StandardConvertTrait`
- [x] Extract special renderers: `FlashPixelRenderer`, `HiddenPixelRenderer`, `BscBorderRenderer`
- [x] Introduce `GigascreenPipeline` for mix/flicker/interlace rendering
- [x] Migrate `Gigascreen`, `Lowresgs`, `Stellar`, and `Timexhrg` off `GigascreenConvertTrait`
- [x] Remove `GigascreenConvertTrait`
- [x] Migrate `Monochrome` off `PluginConfigTrait`
- [x] Introduce `IndexedScreenRenderer` for indexed 256-color formats
- [x] Migrate `Nxi` and `Sl2` off `PluginConfigTrait`
- [x] Introduce `SamCoupeScreenRenderer` for SAM Coupe mode 3/4 rendering
- [x] Migrate `Sam3` and `Sam4` off `PluginConfigTrait`
- [x] Migrate `Ssx` routing plugin off `PluginConfigTrait`
- [x] Migrate `Zxevo` BMP import plugin off `PluginConfigTrait`

---

## Phase 4 — Cleanup

- [x] Remove deprecated `read8BitString*` / `read16BitString*` with the legacy base plugin layer
- [x] Remove deprecated abstract method variants if replaced
- [ ] Run `composer psalm` and fix all issues — currently blocked by broad pre-existing Psalm debt and stale baseline entries
- [x] Final `composer test` — all 71 fixtures green

---

## Key Files

| File | Role |
|------|------|
| `ZxImage/Service/PluginRuntime.php` | Runtime configuration and shared service holder for migrated plugins |
| `ZxImage/Service/StandardScreenPipeline.php` | Standard SCR loading/parsing/rendering pipeline |
| `ZxImage/Service/GigascreenPipeline.php` | Dual-screen gigascreen rendering pipeline |
| `ZxImage/Service/IndexedScreenRenderer.php` | Indexed 256-color image renderer |
| `ZxImage/Service/SamCoupeScreenRenderer.php` | SAM Coupe mode 3/4 renderer |
| `ZxImage/Plugin/Standard.php` | Standard SCR plugin adapter |
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
- `ZxImage/Plugin/Standard/FlashPixelRenderer.php`
- `ZxImage/Plugin/Standard/HiddenPixelRenderer.php`
- `ZxImage/Plugin/Standard/BscBorderRenderer.php`
- `ZxImage/Service/CharacterScreenBuilder.php`
- `ZxImage/Service/PluginRuntime.php`
- `ZxImage/Service/StandardScreenPipeline.php`
- `ZxImage/Service/GigascreenPipeline.php`
- `ZxImage/Service/IndexedScreenRenderer.php`
- `ZxImage/Service/SamCoupeScreenRenderer.php`
