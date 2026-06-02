# Refactoring

## Plugin Runtime Removal

`PluginRuntime` has been removed. Plugins now orchestrate conversion through explicit DTOs and services:
- `PluginInput` carries source path or in-memory contents.
- `PluginGeometry` carries format dimensions, attribute dimensions, border dimensions, and strict file size.
- `RenderSettings` carries converter-provided rendering options.
- `PluginServices` carries shared loaders, palette handling, image processing, and encoding services.

Plugins return `FrameSet` DTOs containing GD frames and delays. `OutputRenderer` is responsible for final PNG or GIF encoding and MIME selection.

Plugin-specific DTOs belong next to the plugin that owns them.
