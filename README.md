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
`public/3d/coffee_cup.png` is a static render of the coffee cup used in the blog footer.
