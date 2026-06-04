# Architecture

Implementation details for the ZX-Image library. Domain concepts are in [domain.md](domain.md).

---

## Component Overview

```
Converter  ──────────────────────────► FramePluginInterface implementation
  setType()                               convertFrames(): ?FrameSet
  setPath() / setSourceFileContents()     plugin-local loaders/parsers/renderers
  setBorder()                             Frame DTOs with GD images and delays
  setZoom()                               OutputRenderer
  setRotation()
  setPalette()
  setGigascreenMode()
  addPreFilter() / addPostFilter()
  getBinary()  ──► generateBinary()  ──► PluginFactory ──► OutputRenderer::render()
```

---

## Converter (`ZxImage/Converter.php`)

Entry point and configuration holder. Responsibilities:
- Holds all rendering parameters (type, border, zoom, rotation, palette, gigascreenMode, pre/post filters)
- Normalizes string parameters through internal enums while keeping the public setter contract string-based
- Delegates plugin creation to `PluginFactory`
- Plugin type aliases: `mg1/mg2/mg4/mg8` → `Multiartist`, `chr$` → `Chrd`
- Delegates cache state and cache file operations to `ConversionCacheManager`
- Delegates hash construction to `ConversionHashBuilder`
- Exposes `getBinary()` → `generateBinary()` (direct) or `generateCacheFile()` (cached)
- After conversion, `getResultMime()` returns the MIME type of the last output

---

## Plugins (`ZxImage/Plugin/`)

Plugins implement `FramePluginInterface` directly. Shared behavior lives in services instead of a common abstract base class.

### Pipeline

Most standard-like plugins run through three steps:

1. Load source bytes into plugin-specific DTOs or common screen DTOs.
2. Parse bytes into pixel maps, attribute maps, palette data, or plugin-local data.
3. Render one or more GD frame images and return a `FrameSet`.

`StandardScreenPipeline` coordinates the standard SCR path. `StandardRawScreenLoader`, `StandardParsedScreenParser`, `StandardFrameRenderer`, and `StandardFrameSetBuilder` own loading, parsing, rendering, and flash/static frame-set assembly.

`OutputRenderer` turns `FrameSet` DTOs into final PNG/GIF binaries and returns `RenderedImage` with MIME.

`GigascreenPipeline` coordinates dual-screen processing. `GigascreenScreenParser`, `GigascreenFrameRenderer`, and `GigascreenFrameSetBuilder` own parsing, single/merged rendering, and mix/flicker/interlace frame-set assembly.

Indexed and SAM Coupe formats use narrower render services:
- `IndexedScreenRenderer` draws linear 8-bit indexed pixels through the active palette correction matrix.
- `SamCoupeScreenRenderer` decodes SAM mode 3/4 pixels and SAM palette bytes, then applies the shared image processor.

### Runtime State

`RenderSettings` is the immutable rendering configuration DTO. `Converter` builds it once and passes it to each plugin through `FramePluginInterface::configure()`. This keeps filters, zoom, rotation, border, palette, and gigascreen mode grouped for the common renderer.

`PluginInput` holds source input. `PluginGeometry` holds format dimensions, attribute cell size, border dimensions, and required file size. Plugins pass these DTOs to pipeline services and plugin-local loaders.

`ParsedScreen` keeps encoded linear border bytes in `borderBytes` and decoded coordinate-indexed border pixels in `borderPixels`. Renderers consume only the representation used by their format.

`PluginServices` holds shared stateless services used by plugins and pipelines:
- `FileLoader`
- `PaletteService`
- `ImageProcessor`

### Frame Output

Migrated plugins implement `FramePluginInterface` and return `FrameSet`:
- `Frame` holds a GD image and an optional delay in centiseconds.
- `Frame` can override render settings when a format needs per-frame render metadata, such as frame-specific border color.
- `FrameSet` carries an array or one-pass stream with a known frame count alongside `RenderSettings`, `RenderGeometry`, and `ColorTable`.
- `FrameSet` can request interlace mixing for animated pairs after final frame processing.
- `OutputRenderer` chooses static PNG or animated GIF output.
- `FrameFinalizer` applies border, resize, filters, and rotation.
- `PngOutputRenderer` and `AnimatedGifOutputRenderer` encode the final image binary.
- `RenderedImage` carries the final binary and MIME.

### File Reading Primitives

`FileLoader` opens the source as a seekable `BitReader`. All reads go through:
- `readByte()` → one unsigned byte
- `readWord()` → little-endian 16-bit integer
- `readString(n)` → raw string of n bytes
- `readBytes(n)` → array of n unsigned bytes
- `seek(offset)` → absolute seek

Single-value and fixed-length reads return `null` when the requested data is unavailable. Reaching EOF does not close the underlying resource, so the same reader can still seek or report its size.

### Character Screen Builder

`ZxImage\Service\CharacterScreenBuilder` contains shared 8×8 character layout logic for token-based and CHR$ formats:
- ZX-VRAM ordered byte streams for fixed 32-column text screens (`s80`, `s81`)
- linear character-cell pixel maps for variable-size CHR$ screens

### `strictFileSize`

When set on `PluginGeometry` and passed to a loader call, `FileLoader` checks that the source file size matches exactly. Mismatched files are rejected.

### Rendering Helpers

`ImageProcessor` is a compatibility facade over smaller image services:
- `BorderApplier` creates a larger canvas, fills it with border color, and pastes the center image.
- `ImageResizer` applies gamma correction, pre-filters, resampling, post-filters, and final gamma correction.
- `ImageRotator` performs pixel-by-pixel rotation at 90°/180°/270°.
- `InterlaceMixer` swaps alternating line bands between animated frame pairs.
- `FilterApplier` maps filter keys through `FilterType` and applies pre/post filters.

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

Abstract class with a single typed method `apply(GdImage $image, ?GdImage $srcImage): GdImage`. Pre-filters receive the source image before zoom; post-filters receive both the scaled destination image and the original source.

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

- **`mix`**: renders one merged GD frame by iterating pixels from both screens and looking up the combined 8-bit color code in `$gigaColors`.
- **`flicker`**: returns alternating GD frames with 2 cs delay.
- **`interlace1` / `interlace2`**: returns alternating GD frames and marks the `FrameSet` for 1-row or 2-row interlace mixing in `OutputRenderer`.

Flash + gigascreen: 32 GD frames cycle through screen1/screen2 normal and flashed states. `OutputRenderer` decides whether the final binary is PNG or GIF.

---

## Flash Animation

Detected by checking `flashMap` in parsed attribute data. If any cell has flash=1:
1. The plugin renders the normal GD frame.
2. The plugin renders the flipped GD frame with ink and paper swapped for flash cells.
3. The plugin returns both frames in a `FrameSet` with 32 cs delay each (about 1.6 Hz).
4. `OutputRenderer` encodes the final animated GIF.

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

Cache state is owned by `ConversionCacheManager`.

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

Cache expiry is managed by the application using the converter.

---

## Output Encoding

| MIME | Method | Trigger |
|------|--------|---------|
| `image/png` | `imagepng()` via output buffer | Static images |
| `image/gif` | `imagegif()` + `GifCreator::create()` | Animated images |
| `image/avif` | `imageavif()` via output buffer | Available but not used in standard flow |

`GifCreator` (composer dependency) takes an array of GIF binary strings and per-frame delay values (centiseconds) and assembles a single animated GIF binary.

Animated frames are finalized and encoded sequentially to avoid retaining all resized GD images. Interlace modes finalize and encode one frame pair at a time.

---

## Plugin Structure

There is no active abstract plugin base class. Migrated standard-like plugins use:
- `RenderSettings` for converter-provided rendering settings
- `PluginInput` for source input
- `PluginGeometry` for format geometry and strict file size
- `PluginServices` for shared loader, palette, processing, and encoding services
- `StandardScreenPipeline` and its focused helper services for default SCR loading/parsing/rendering
- Narrow render services such as `IndexedScreenRenderer` or `SamCoupeScreenRenderer` when the format is not SCR-shaped
- plugin-local DTOs, loaders, parsers, and renderers for format-specific behavior
