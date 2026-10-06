#!/usr/bin/env python3
"""T069 - generate the favicon and PWA icon set from the vendored brand asset.

Deliberately NOT part of `npm run prebuild`. It needs Pillow, which is not a project dependency and
should not become one for an artifact that changes once a year; the outputs are committed instead.
Run it by hand when the brand asset changes:

    python3 scripts/build-favicons.py

The source is 162x162. That is a consequence of ui-conflicts.md C-06: the brand assets are raster
upscales from the brand board and vector originals were never supplied. Sizes at or below 162 are
genuine downscales and are crisp. 180, 192 and 512 are UPSCALES and are not - they are generated
because a PWA manifest and iOS both ask for them, and an absent icon is worse than a soft one. When
C-06 is resolved, re-run this against the vector export and the upscales become real.

Nothing is written to design-system/, which is vendored read-only.
"""
from __future__ import annotations

import json
import pathlib
import sys

try:
    from PIL import Image
except ImportError:
    sys.exit('Pillow is required: pip install Pillow')

ROOT = pathlib.Path(__file__).resolve().parent.parent
SOURCE = ROOT / 'design-system' / 'assets' / 'app-icon-light.png'
ICON_DIR = ROOT / 'public' / 'icons'

# 16-48 for browser tabs and bookmarks, 64-162 for desktop shortcuts, 180 for iOS home screen,
# 192 and 512 because the PWA manifest specification names them.
SIZES = (16, 32, 48, 64, 128, 152, 162, 180, 192, 512)

# From the token layer: --track-bg and --track-blue. Hard-coded here rather than parsed out of
# colors.css because a manifest is a static artifact generated once, not a stylesheet - but if these
# drift from the tokens the browser chrome stops matching the application.
BACKGROUND_COLOR = '#F8FAFC'
THEME_COLOR = '#0F4C5C'


def main() -> int:
    if not SOURCE.is_file():
        sys.exit(f'Cannot read {SOURCE}. The design system must be vendored first (task T004).')

    source = Image.open(SOURCE).convert('RGBA')
    ICON_DIR.mkdir(parents=True, exist_ok=True)

    upscaled = []

    for size in SIZES:
        # Lanczos is the least-bad resampling for flat brand artwork in either direction.
        resized = source.resize((size, size), Image.Resampling.LANCZOS)
        resized.save(ICON_DIR / f'app-icon-{size}.png', optimize=True)

        if size > source.width:
            upscaled.append(size)

    # A real multi-resolution .ico, not a renamed PNG: Windows and several crawlers still request
    # /favicon.ico by path and will not accept a PNG served under that name.
    source.resize((48, 48), Image.Resampling.LANCZOS).save(
        ROOT / 'public' / 'favicon.ico', sizes=[(16, 16), (32, 32), (48, 48)]
    )

    manifest = {
        'name': 'TRACK',
        'short_name': 'TRACK',
        'start_url': '/',
        'display': 'standalone',
        'background_color': BACKGROUND_COLOR,
        'theme_color': THEME_COLOR,
        # Arabic is the platform default (FR-074, the default_locale setting), so an installed app
        # opens right-to-left unless the user has chosen otherwise.
        'dir': 'rtl',
        'lang': 'ar',
        'icons': [
            {'src': f'/icons/app-icon-{size}.png', 'sizes': f'{size}x{size}', 'type': 'image/png'}
            for size in (64, 128, 192, 512)
        ],
    }

    manifest_path = ROOT / 'public' / 'manifest.webmanifest'
    manifest_path.write_text(json.dumps(manifest, indent=4, ensure_ascii=False) + '\n', encoding='utf-8')

    print(f'  source:   {SOURCE.name} {source.width}x{source.height}')
    print(f'  icons:    {len(SIZES)} png -> public/icons/')
    print('  favicon:  public/favicon.ico (16, 32, 48)')
    print(f'  manifest: {manifest_path.relative_to(ROOT)}')

    if upscaled:
        sizes = ', '.join(f'{size}px' for size in upscaled)
        print(f'  WARNING:  {sizes} are UPSCALED from {source.width}px - see ui-conflicts C-06')

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
