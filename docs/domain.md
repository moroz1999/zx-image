# Domain: ZX-Image

A library that converts native binary screen formats from ZX Spectrum and its clones into PNG or animated GIF images.

See [architecture.md](architecture.md) for implementation details.

---

## ZX Spectrum Color Model

ZX Spectrum has 16 colors: 8 hues at two brightness levels (normal and bright). Each pixel's color is determined by the **attribute** cell it belongs to. An attribute cell covers an 8×8 pixel block and stores:
- **Ink** — foreground color (1-bit pixels)
- **Paper** — background color (0-bit pixels)
- **Bright** — applies to both ink and paper, raising luminosity
- **Flash** — causes ink and paper to swap at ~1.6 Hz

The standard canvas is **256×192 pixels**, organized as 32×24 attribute cells of 8×8 pixels each. When a border color is set, the canvas is padded to **320×240**.

---

## Palettes

Five named palettes control the exact color levels emitted, corresponding to different emulators and hardware measurements. Default is `srgb`.

| Name | Origin |
|------|--------|
| `srgb` | sRGB-calibrated (default) |
| `pulsar` | Pulsar emulator |
| `orthodox` | Orthodox emulator |
| `alone` | Alone Coder measurement |
| `electroscale` | Electroscale (greenish tint) |

---

## Gigascreen

Some formats store two separate screens intended to alternate at 50 Hz on real hardware, allowing the eye to perceive blended colors and thus an expanded palette. Four rendering modes are available:

| Mode | Result |
|------|--------|
| `mix` | Static image with colors averaged between both screens |
| `flicker` | Animated image alternating both screens at ~50 Hz |
| `interlace1` | Animated, alternating horizontal lines (1-line pitch) |
| `interlace2` | Animated, alternating horizontal lines (2-line pitch) |

---

## Flash Animation

When any attribute cell has the flash bit set, the output is an animated image cycling between normal and ink/paper-swapped frames at ~1.6 Hz.

---

## Border

An optional color margin added around the image. Color is one of 8 ZX Spectrum hues (0–7). Some formats embed custom border pixel data; for those, the border area is rendered from file data rather than a solid color.

---

## Post-processing Filters

Optional filters applied before or after zoom. Multiple filters may be chained.

| Filter | Effect |
|--------|--------|
| `atari` | Simulates Atari PAL non-square pixel aspect ratio |
| `blur` | Phosphor glow with scanline darkening |
| `scanlines` | Dims every other horizontal line (CRT effect) |
| `minSize384` | Pads image to minimum 384×288 (letterbox) |
| `minSize768` | Pads image to minimum 768×576 (letterbox) |

---

## Zoom and Rotation

- **Zoom**: 0.25×, 0.5×, 1×, 2×, 3×, 4× of the source canvas
- **Rotation**: 0°, 90°, 180°, 270°

---

## File Cache

When enabled, converted images are stored as files. The cache key is derived from source content, type, and all rendering parameters. Expired entries are automatically pruned.

---

## Output

- Static images → **PNG**
- Animated images (flash, gigascreen flicker/interlace, SCA animation containers) → **GIF**

---

## Supported Formats

| Type identifier | Platform | Doc |
|-----------------|----------|-----|
| `standard` | ZX Spectrum | [standard.md](formats/standard.md) |
| `ulaplus` | ZX Spectrum + ULA+ | [ulaplus.md](formats/ulaplus.md) |
| `flash` | ZX Spectrum (hardware mod) | [flash.md](formats/flash.md) |
| `monochrome` | ZX Spectrum | [monochrome.md](formats/monochrome.md) |
| `attributes` | ZX Spectrum | [attributes.md](formats/attributes.md) |
| `hidden` | ZX Spectrum (debug view) | [hidden.md](formats/hidden.md) |
| `gigascreen` | ZX Spectrum | [gigascreen.md](formats/gigascreen.md) |
| `tricolor` | ZX Spectrum | [tricolor.md](formats/tricolor.md) |
| `multicolor` | ZX Spectrum | [multicolor.md](formats/multicolor.md) |
| `multicolor4` | ZX Spectrum | [multicolor.md](formats/multicolor.md) |
| `mc` | ZX Spectrum | [mc.md](formats/mc.md) |
| `mlt` | ZX Spectrum | [mc.md](formats/mc.md) |
| `timex81` | Timex/Sinclair | [timex81.md](formats/timex81.md) |
| `timexhr` | Timex/Sinclair | [timexhr.md](formats/timexhr.md) |
| `timexhrg` | Timex/Sinclair | [timexhr.md](formats/timexhr.md) |
| `bsc` | ZX Spectrum (demo format) | [bsc.md](formats/bsc.md) |
| `bmc4` | ZX Spectrum (demo format) | [bmc4.md](formats/bmc4.md) |
| `bsp` | ZX Spectrum (Trefi format) | [bsp.md](formats/bsp.md) |
| `chrd` / `chr$` | ZX Spectrum (Alone Coder) | [chrd.md](formats/chrd.md) |
| `multiartist` / `mg1` / `mg2` / `mg4` / `mg8` | ZX Spectrum (Multiartist) | [multiartist.md](formats/multiartist.md) |
| `lowresgs` | ZX Spectrum | [lowresgs.md](formats/lowresgs.md) |
| `stellar` | ZX Spectrum (Pentagon) | [stellar.md](formats/stellar.md) |
| `sam3` | Sam Coupe mode 3 | [sam.md](formats/sam.md) |
| `sam4` | Sam Coupe mode 4 | [sam.md](formats/sam.md) |
| `ssx` | Sam Coupe (container) | [ssx.md](formats/ssx.md) |
| `zxevo` | ZX Evolution | [zxevo.md](formats/zxevo.md) |
| `sxg` | ZX Evolution / TSConf | [sxg.md](formats/sxg.md) |
| `atmega` | ATM Turbo 2+ | [atmega.md](formats/atmega.md) |
| `nxi` | ZX Spectrum Next | [nxi.md](formats/nxi.md) |
| `sl2` | ZX Spectrum Next | [sl2.md](formats/sl2.md) |
| `grf` | Profi (partial) | [grf.md](formats/grf.md) |
| `specscii` | ZX Spectrum text | [specscii.md](formats/specscii.md) |
| `s81` | ZX81 text | [s81.md](formats/s81.md) |
| `s80` | ZX80 text | [s81.md](formats/s81.md) |
| `sca` | ZX Spectrum animation | [sca.md](formats/sca.md) |
