Portfolio & Blog
================

https://thomas.jarrand.fr

## Installation

    make install build

## Development

### Launch asset watcher

    make watch

### Launch server

    make start

Got to: http://localhost:8000

## Structure

One-pager (`/` in French, `/en/` in English) + blog (`/blog`), built as a static site with [Stenope](https://github.com/StenopePHP/Stenope).

- Content: `content/{game,talk,tool,event,article}` (YAML / Markdown).
- Copy: `translations/messages.{fr,en}.yaml`.
- Templates: `templates/base.html.twig` (shared nav + contact footer), `templates/portfolio/`, `templates/blog/`.
- Styles: `assets/css/` (`abstracts/_tokens.scss` holds the palette, fonts and breakpoint).
- 3D: `assets/js/home/scene/` (three.js), lazy-loaded on the homepage.

### 3D scene

The hero and section icons load a single file: `public/3d/scene.glb`.
It must contain the objects `laptop`, `coffee_cup`, `gamepad`, `longboard` and `plant`.

- Without a `floor` mesh, the scene is lit in realtime (hemisphere + shadow-casting sun).
- With a `floor` mesh, the scene is considered baked: unlit materials using the GLB textures, no realtime lights.

Replacing `public/3d/scene.glb` with the Blender bake requires no code change.

The Blender source lives in `3D/`: `scene1.blend` is the scene, `bake_export.py` bakes it (colour + light atlas, floor shadow) and exports `public/3d/scene.glb`:

```sh
cd 3D && ATLAS=2048 FLOOR=1024 SAMPLES=1024 \
    /Applications/Blender.app/Contents/MacOS/Blender --background scene1.blend --python bake_export.py
```

`scene1.blend` is never modified by the bake; its outputs (`scene1_baked.blend`, `baked/`) are not versioned.
`leaves_from_mask.py` rebuilt the monstera leaves as real geometry from their alpha mask.
`public/3d/coffee_cup.png` is a static render of the coffee cup used in the blog footer.

### Cover images

Two images are screenshots of the dev-only page `/cover/`, text in the site fonts with the live 3D scene, each version a fixed frame:

- `public/cover.png` (1200×630, `/cover/`): the link preview, used for `og:image` and `twitter:image`, with the name and tagline;
- `public/linkedin.png` (1584×396, `/cover/?version=linkedin`): the LinkedIn profile banner, led by the tagline (the profile already shows the name), with the text and the desk kept clear of the profile photo that overlaps the bottom left corner.

To regenerate them, start the dev server (`make start`) and run `make cover`: it builds the assets and screenshots both pages with the installed Chrome once the scene is drawn.
The framing of the desk (zoom, offset) is set per version in `templates/portfolio/cover.html.twig`.

### Resume

The resume is a page, `/cv/` (in French, linked from the footer next to the PDF), and `public/CV_Thomas_Jarrand.pdf` is that same page printed: two A4 pages. The content is in `templates/portfolio/cv.html.twig` and the styles in `assets/css/cv.scss` (screen and phone layout, plus print rules).
To regenerate it, start the dev server (`make start`) and run `make cv`: it builds the assets and prints the page to PDF with headless Chrome.
Check it still prints 2 pages; the page balance rules at the end of `cv.scss` (print only) are tuned for the current content.
