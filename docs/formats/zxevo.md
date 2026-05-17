# Format: zxevo

ZX Evolution screen saved as a standard BMP file with 16 colors.

## Identifiers

- Type key: `zxevo`
- Plugin class: `ZxImage\Plugin\Zxevo`
- Extends: `Plugin` (directly)

## Description

ZX Evolution graphics mode stores screens as standard Windows BMP files. The converter loads the BMP using PHP's `imagecreatefrombmp`, validates that the palette has at most 16 colors, then quantizes each palette entry to the nearest multiple of 85 (simulating the 2-bit-per-channel EGA-like palette of the ZX Evolution):

```
R_out = round(R / 85) * 85  → values 0, 85, 170, 255
G_out = round(G / 85) * 85
B_out = round(B / 85) * 85
```

## Input Validation

The BMP must have ≤ 16 colors in its palette (`imagecolorstotal`). If the palette has more colors or is empty (returns 0), the image is rejected.

## File Source

Only file path is supported (`sourceFilePath`). In-memory content (`sourceFileContents`) is not supported because `imagecreatefrombmp` requires a file path.

## Output

Always PNG (`image/png`).
