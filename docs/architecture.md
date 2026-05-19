# Architecture

Implementation details for the ZX-Image library. Domain concepts are in [domain.md](domain.md).

---

## Component Overview

```
Converter  ──────────────────────────► PluginInterface implementation
  setType()                               loadBits()
  setPath() / setSourceFileContents()     parseScreen()
  setBorder()                             renderImage()
  setZoom()                               ImageEncoder
  setRotation()
  setPalette()
  setGigascreenMode()
  addPreFilter() / addPostFilter()
  getBinary()  ──► generateBinary()  ──► Plugin::convert()
```

---

## Converter (`ZxImage/Converter.php`)

Entry point and configuration holder. Responsibilities:
- Holds all rendering parameters (type, border, zoom, rotation, palette, gigascreenMode, pre/post filters)
- Maps `type` string to a `Plugin` subclass under `ZxImage\Plugin\` via `ucfirst(className)`
- Type aliases: `mg1/mg2/mg4/mg8` → `Multiartist`, `chr$` → `Chrd`
- Manages the file cache: stores rendered output as flat files, names them by MD5 hash of rendering parameters
- Exposes `getBinary()` → `generateBinary()` (direct) or `generateCacheFile()` (cached)
- After conversion, `getResultMime()` returns the MIME type of the last output

---

## Plugins (`ZxImage/Plugin/`)

Plugins implement `PluginInterface` directly. Shared behavior is being moved into services instead of a common abstract base class.

### Pipeline

Most standard-like plugins run through three methods in sequence:

1. **`loadBits(): ?RawScreen`** — opens the source and reads raw unsigned bytes
2. **`parseScreen(RawScreen $data): ParsedScreen`** — decodes bytes into pixel maps and attribute maps
3. **`renderImage(ParsedScreen $parsedData, ...)`** — draws a GD image, applies border, resize, and rotation

`StandardScreenPipeline` orchestrates the standard pipeline and produces the final PNG or GIF binary via `ImageEncoder`. `GigascreenPipeline` handles dual-screen mix, flicker, and interlace modes. Some container plugins implement a custom `convert()` while reusing the same DTOs and services.

Indexed and SAM Coupe formats use narrower render services:
- `IndexedScreenRenderer` draws linear 8-bit indexed pixels through the active palette correction matrix.
- `SamCoupeScreenRenderer` decodes SAM mode 3/4 pixels and SAM palette bytes, then applies the shared image processor.

### Runtime State

`PluginRuntime` holds plugin configuration and shared services for composition-based plugins. It replaces trait-owned mutable state for migrated plugins.

### File Reading Primitives

`FileLoader` opens the source as a seekable `BitReader`. All reads go through:
- `readByte()` → one unsigned byte
- `readWord()` → little-endian 16-bit integer
- `readString(n)` → raw string of n bytes
- `readBytes(n)` → array of n unsigned bytes
- `seek(offset)` → absolute seek

### Character Screen Builder

`ZxImage\Service\CharacterScreenBuilder` contains shared 8×8 character layout logic for token-based and CHR$ formats:
- ZX-VRAM ordered byte streams for fixed 32-column text screens (`s80`, `s81`)
- linear character-cell pixel maps for variable-size CHR$ screens

### `strictFileSize`

When set on a plugin runtime or loader call, `FileLoader` checks that the source file size matches exactly. Mismatched files are rejected.

### Rendering Helpers

- `drawBorder($centerImage, ...)` — creates a larger canvas, fills with border color, pastes center image
- `resizeImage($srcImage)` — applies pre-filters, resamples to zoom factor (gamma-corrected), applies post-filters
- `checkRotation($image)` — pixel-by-pixel rotation at 90°/180°/270°
- `applyPreFilters` / `applyPostFilters` — instantiates `Filter` classes by name and calls `apply()`

---

## Color Encoding

ZX Spectrum colors are represented as 4-character binary strings encoding `BGRB` bits:

```
Position: 0    1    2    3
Meaning:  bright green red blue
Example:  '1'  '1'  '0'  '1'  = bright cyan
```

Two lookup tables are precomputed from the active palette:
- **`$colors`** — maps 4-bit codes (`'0000'`–`'1111'`) to 32-bit RGB integers
- **`$gigaColors`** — maps 8-bit codes (two stacked 4-bit codes) to averaged RGB integers for gigascreen blending

### Palette String Format

```
ZZ,ZN,NN,NB,BB,ZB:R11,R12,R13;R21,R22,R23;R31,R32,R33
```

- First six hex values: brightness levels — zero (Z), normal (N), bright (B) in zero-to-normal, normal-to-bright, etc. combinations
- `R11..R33`: 3×3 color correction matrix (hex). Each output channel is a weighted sum of all three input channels divided by 0xFF

Color computation:
```
R_out = round((r * R11 + g * R12 + b * R13) / 0xFF)
G_out = round((r * R21 + g * R22 + b * R23) / 0xFF)
B_out = round((r * R31 + g * R32 + b * R33) / 0xFF)
```

---

## Filter (`ZxImage/Filter/Filter.php`)

Abstract class with a single method `apply($image, $srcImage)`. Pre-filters receive the source image before zoom; post-filters receive both the scaled destination image and the original source.

| Class | Key | Notes |
|-------|-----|-------|
| `Atari` | `atari` | Shrinks horizontally 2×, then stretches back — spreads pixels to simulate wide PAL pixels |
| `Blur` | `blur` | Gaussian blur + luminance-based alpha halo + per-pixel scanline/column darkening |
| `Scanlines` | `scanlines` | Multiplies every even row by 0.75 |
| `MinSize384` | `minSize384` | Scales up integer factor to fit 384×288, then centers on black canvas |
| `MinSize768` | `minSize768` | Same as MinSize384, target 768×576 |

---

## Gigascreen Implementation

Gigascreen-compatible plugins use `GigascreenPipeline` for multi-screen blending:

- **`mix`**: calls `exportDataMerged()` — iterates pixels from both screens simultaneously and looks up the combined 8-bit color code in `$gigaColors`
- **`flicker`**: renders each screen separately via `exportData()`, encodes as GIF palette images, assembles animated GIF with 2 cs per frame delay
- **`interlace1` / `interlace2`**: same as flicker, but additionally calls `interlaceMix()` to swap rows between the two GD images (1-row or 2-row pitch) before GIF encoding

Flash + gigascreen: 32 GIF frames cycle through (screen1 normal, screen2 normal, screen1 flashed, screen2 flashed).

---

## Flash Animation

Detected by checking `flashMap` in parsed attribute data. If any cell has flash=1:
1. `exportData($parsedData, false)` renders the normal frame
2. `exportData($parsedData, true)` renders the flipped frame (ink↔paper swapped for flash cells)
3. Both are converted to paletted GIF images via `getRightPaletteGif()`
4. `buildAnimatedGif()` assembles them with 32 cs delay each (≈1.6 Hz)

---

## Zoom

`resizeImage()` applies gamma correction before and after scaling:
1. `imagegammacorrect($src, 2.2, 1.0)` — linearize
2. Apply pre-filters
3. `imagecopyresampled()` to the target zoom dimensions
4. Apply post-filters
5. `imagegammacorrect($dst, 1.0, 2.2)` — re-apply gamma

Zoom values 0.25/0.5/2/3/4 scale by the given factor. Zoom 1 skips resampling.

---

## Cache Implementation

Cache filename = `cachePath + MD5(hash_input)`.

Hash input is the concatenation of:
- Source file path + `filemtime` (or MD5 of in-memory content)
- Type string
- Gigascreen mode (only for animated types: gigascreen, tricolor, multiartist, mg*, lowresgs, chr$, bsp, timexhrg, stellar)
- Border value
- Palette string
- Zoom value
- Pre-filter and post-filter lists
- Rotation (if non-zero)

Expiry sweep: triggered probabilistically. If `time() % cacheDeletionPeriod == 0`, up to `cacheDeletionAmount` files older than `cacheExpirationLimit` (default 30 days) are deleted.

---

## Output Encoding

| MIME | Method | Trigger |
|------|--------|---------|
| `image/png` | `imagepng()` via output buffer | Static images |
| `image/gif` | `imagegif()` + `GifCreator::create()` | Animated images |
| `image/avif` | `imageavif()` via output buffer | Available but not used in standard flow |

`GifCreator` (composer dependency) takes an array of GIF binary strings and per-frame delay values (centiseconds) and assembles a single animated GIF binary.

---

## Plugin Structure

There is no active abstract plugin base class. Migrated standard-like plugins use:
- `PluginRuntime` for configuration and service access
- `StandardScreenPipeline` for default SCR loading/parsing/rendering
- Narrow render services such as `IndexedScreenRenderer` or `SamCoupeScreenRenderer` when the format is not SCR-shaped
- format-specific private methods only for the parts that differ

Legacy `PluginConfigTrait` still exists for plugins that have not been migrated to `PluginRuntime` yet.
