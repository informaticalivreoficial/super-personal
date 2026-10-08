#!/usr/bin/env python3
"""Gera resources/views/components/icons/*.blade.php a partir do pacote heroicons (node_modules)."""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "resources/views/components/icons"
HERO = ROOT / "node_modules/heroicons/24"

# nome-do-icone-no-painel -> arquivo heroicons (outline por padrão)
MAP = {
    "academic-cap": "academic-cap",
    "arrow-left": "arrow-left",
    "arrow-path": "arrow-path",
    "arrow-right-on-rectangle": "arrow-right-on-rectangle",
    "arrow-uturn-left": "arrow-uturn-left",
    "arrows-pointing-out": "arrows-pointing-out",
    "banknotes": "banknotes",
    "bars": "bars-3",
    "bolt": "bolt",
    "calendar": "calendar",
    "calendar-days": "calendar-days",
    "chart-bar": "chart-bar",
    "check": "check",
    "check-badge": "check-badge",
    "chevron-down": "chevron-down",
    "chevron-left": "chevron-left",
    "chevron-right": "chevron-right",
    "clipboard": "clipboard",
    "clipboard-document-list": "clipboard-document-list",
    "clock": "clock",
    "cog": "cog-6-tooth",
    "computer-desktop": "computer-desktop",
    "currency-dollar": "currency-dollar",
    "document-currency-dollar": "document-currency-dollar",
    "envelope": "envelope",
    "exclamation-triangle": "exclamation-triangle",
    "eye": "eye",
    "home": "home",
    "identification": "identification",
    "information-circle": "information-circle",
    "lifebuoy": "lifebuoy",
    "link": "link",
    "list-bullet": "list-bullet",
    "magnifying-glass": "magnifying-glass",
    "minus": "minus",
    "paper-airplane": "paper-airplane",
    "pencil": "pencil",
    "photo": "photo",
    "plus": "plus",
    "question-mark-circle": "question-mark-circle",
    "scale": "scale",
    "shield-check": "shield-check",
    "squares-2x2": "squares-2x2",
    "trash": "trash",
    "user": "user",
    "user-plus": "user-plus",
    "users": "users",
    "x-mark": "x-mark",
    "link-2": "link",
    # extras (migração das views legadas)
    "arrow-down-tray": "arrow-down-tray",
    "arrow-up-tray": "arrow-up-tray",
    "funnel": "funnel",
    "ellipsis-vertical": "ellipsis-vertical",
    "bell": "bell",
    "document-text": "document-text",
    "folder": "folder",
    "map": "map",
    "map-pin": "map-pin",
    "fire": "fire",
    "star": "star",
    "play": "play",
    "arrows-up-down": "arrows-up-down",
    "adjustments-horizontal": "adjustments-horizontal",
    "arrow-top-right-on-square": "arrow-top-right-on-square",
    "queue-list": "queue-list",
    # aliases (Font Awesome -> heroicons)
    "times": "x-mark",
    "close": "x-mark",
    "save": "check",
    "search": "magnifying-glass",
    "pen": "pencil",
    "edit": "pencil",
    "spinner": "arrow-path",
    "sync": "arrow-path",
    "trash-restore": "arrow-uturn-left",
    "image": "photo",
    "info": "information-circle",
    "angle-right": "chevron-right",
    "angle-double-right": "chevron-right",
    "caret-down": "chevron-down",
    "sitemap": "queue-list",
    # sólidos (24/solid)
    "solid-qr-code": "solid:qr-code",
}

# ícones desenhados à mão (não existem no heroicons)
CUSTOM = {
    "circle": '''<svg class="{{ $svgClass }}" {{ $attributes->except(['class', 'name']) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
  <circle cx="12" cy="12" r="7" />
</svg>
''',
    "whatsapp": '''<svg class="{{ $svgClass }}" {{ $attributes->except(['class', 'name']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
  {{-- Simple Icons (CC0) --}}
  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
</svg>
''',
}


def extract(svg_path: Path) -> str:
    raw = svg_path.read_text(encoding="utf-8")
    # remove <title> e data-slot
    raw = re.sub(r"<title>.*?</title>", "", raw, flags=re.S)
    raw = re.sub(r'\s+data-slot="icon"', "", raw)
    # checa atributos da raiz ANTES de injetar a expressão Blade (contém ">")
    head_before = raw[: raw.index(">")]
    if "aria-hidden" not in head_before:
        raw = raw.replace("<svg ", '<svg aria-hidden="true" ', 1)
    # substitui class="..." da raiz pelo bag do Blade (ou insere)
    attrs = 'class="{{ $svgClass }}" {{ $attributes->except([\'class\', \'name\']) }}'
    if 'class="' in head_before:
        raw = re.sub(r'class="[^"]*"', attrs.replace('\\', ''), raw, count=1)
    else:
        raw = raw.replace('<svg ', '<svg ' + attrs + ' ', 1)
    return raw if raw.endswith("\n") else raw + "\n"


def main() -> int:
    OUT.mkdir(parents=True, exist_ok=True)
    ok, missing = 0, []

    for icon_name, hero_name in MAP.items():
        if hero_name.startswith("solid:"):
            path = HERO / "solid" / f"{hero_name.split(':', 1)[1]}.svg"
        else:
            path = HERO / "outline" / f"{hero_name}.svg"
        if not path.is_file():
            missing.append(f"{icon_name} <- {path}")
            continue
        (OUT / f"{icon_name}.blade.php").write_text(extract(path), encoding="utf-8")
        ok += 1

    for icon_name, content in CUSTOM.items():
        (OUT / f"{icon_name}.blade.php").write_text(content, encoding="utf-8")
        ok += 1

    print(f"gerados: {ok}")
    if missing:
        print("FALTANDO:")
        for m in missing:
            print(" -", m)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
