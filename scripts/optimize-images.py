"""Build the website's optimized images.

Reads the original photos in image/, writes resized WebP files with a content hash
in their names to assets/img/, and writes the lookup table src/images.php.
The hash changes whenever an image changes, so browsers can cache the files forever.

Run after adding or replacing a photo:  python scripts/optimize-images.py
Needs Pillow:                           pip install Pillow
"""

import hashlib
import io
from pathlib import Path

from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "image"
OUTPUT = ROOT / "assets" / "img"
MANIFEST = ROOT / "src" / "images.php"

# name: (source file, widths, crop, options)
# crop: None keeps the whole photo, (w, h) center-crops to that aspect ratio,
#       (left, top, right, bottom) as 0..1 fractions crops a fixed box.
IMAGES = {
    # Shared
    "logo": ("logos.webp", [96], None, {"quality": 90}),
    "auth": ("image23 (1).webp", [420, 760], (0.38, 0.0, 0.88, 1.0), {"quality": 78}),
    "booking-visit": ("image23 (1).webp", [480, 760], (4, 3), {}),
    # Home page: hero (a real clinic visit) and a check-up close-up
    "hero-visit": ("image41.webp", [560, 880], (0.0, 0.0625, 1.0, 1.0), {"quality": 80}),
    # About: the clinic storefront; the two sides of the system
    "about-clinic": ("image20.jpg", [640, 1000], (4, 3), {"quality": 80}),
    "side-owner": ("image16.webp", [480, 720], (4, 5), {}),
    "side-staff": ("image38.webp", [480, 720], (4, 5), {}),
    # Services, one photo that shows each service
    "svc-consult": ("image55.jpg", [560, 800], (5, 4), {}),
    "svc-vaccine": ("image24.webp", [560, 900], (5, 4), {}),
    "svc-parasite": ("image44.jpg", [560, 900], (5, 4), {}),
    "svc-grooming": ("image30.webp", [560, 811], (5, 4), {}),
    "svc-surgery": ("image26.webp", [560, 900], (5, 4), {}),
    "svc-general": ("image23 (1).webp", [560, 900], (5, 4), {}),
    "why-bg": ("image22.jpg", [960], None, {"blur": 4, "quality": 58}),
    # Gallery: real moments at the clinic
    "gallery-1": ("image10.webp", [560, 900], (4, 5), {}),
    "gallery-2": ("image11.webp", [420, 640], (1, 1), {}),
    "gallery-3": ("image12.webp", [420, 640], (1, 1), {}),
    "gallery-4": ("image13.webp", [420, 640], (1, 1), {}),
    "gallery-5": ("image40.webp", [420, 640], (1, 1), {}),
    "gallery-6": ("image35.webp", [420, 640], (1, 1), {}),
    "gallery-7": ("image39.webp", [420, 640], (1, 1), {}),
    # Contact: the clinic entrance
    "entrance": ("image19.webp", [520, 700], (4, 3), {"quality": 80}),
    # Supplies
    "prod-harness": ("prod1.jpg", [360, 600], (1, 1), {"quality": 80}),
    "prod-ecollar": ("prod2.jpg", [360, 600], (1, 1), {"quality": 80}),
    "prod-calming": ("prod3.jpg", [360, 600], (1, 1), {"quality": 80}),
    "prod-pedigree": ("prod4.webp", [360, 600], (1, 1), {"quality": 80}),
    "prod-cuties": ("prod5.jpg", [360, 600], (1, 1), {"quality": 80}),
    "prod-kidney": ("prod6.jpg", [360, 600], (1, 1), {"quality": 80}),
    # Happy owners
    "avatar-1": ("image16.webp", [96], (0.22, 0.04, 0.72, 0.42), {}),
    "avatar-2": ("image10.webp", [96], (0.18, 0.02, 0.62, 0.35), {}),
    "avatar-3": ("image11.webp", [96], (0.52, 0.185, 0.80, 0.395), {}),
}


def cropped(image, crop):
    if crop is None:
        return image
    width, height = image.size
    if len(crop) == 4:
        left, top, right, bottom = crop
        return image.crop((round(left * width), round(top * height), round(right * width), round(bottom * height)))
    ratio = crop[0] / crop[1]
    if width / height > ratio:
        new_width = round(height * ratio)
        left = (width - new_width) // 2
        return image.crop((left, 0, left + new_width, height))
    new_height = round(width / ratio)
    top = (height - new_height) // 2
    return image.crop((0, top, width, top + new_height))


def build():
    OUTPUT.mkdir(parents=True, exist_ok=True)
    for old in OUTPUT.glob("*.webp"):
        old.unlink()
    manifest = {}
    for name, (file, widths, crop, options) in IMAGES.items():
        with Image.open(SOURCE / file) as original:
            image = cropped(original.convert("RGBA" if original.mode in ("RGBA", "LA", "P") else "RGB"), crop)
        if options.get("blur"):
            image = image.filter(ImageFilter.GaussianBlur(options["blur"]))
        sources = {}
        for width in widths:
            width = min(width, image.width)
            height = round(image.height * width / image.width)
            resized = image.resize((width, height), Image.LANCZOS) if width < image.width else image
            buffer = io.BytesIO()
            resized.save(buffer, "WEBP", quality=options.get("quality", 72), method=6)
            data = buffer.getvalue()
            digest = hashlib.sha1(data).hexdigest()[:10]
            target = OUTPUT / f"{name}-{width}.{digest}.webp"
            target.write_bytes(data)
            sources[width] = f"/assets/img/{target.name}"
        manifest[name] = {"w": width, "h": height, "src": sources}
        print(f"{name}: {', '.join(str(w) for w in sources)}")
    lines = ["<?php", "// Generated by scripts/optimize-images.py. Do not edit by hand.", "return ["]
    for name, item in manifest.items():
        sources = ", ".join(f'{width} => "{url}"' for width, url in item["src"].items())
        lines.append(f'    "{name}" => ["w" => {item["w"]}, "h" => {item["h"]}, "src" => [{sources}]],')
    lines.append("];")
    MANIFEST.write_text("\n".join(lines) + "\n", encoding="utf-8")


if __name__ == "__main__":
    build()
