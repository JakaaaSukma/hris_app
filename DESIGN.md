# GYOKAI HR Enterprise — Design System & UI Contract

## Design Read
> Internal Enterprise HRIS & Multi-Division Operations Portal for Indonesian executives, department managers, secretaries, and employees, styled in a **Precision Carbon & Warm Alabaster Editorial** system with purposeful **Skiper UI Motion**, Dial: **ENERGY 2 / RHYTHM 3 / MOTION 3**.

## Explicit Palette (`R-29`, `R-34`)
- **Dark Ground ("Carbon Obsidian"):** `#0B0D11` (page canvas), `#121620` (elevated chrome), `#161A23` (card container), `#1D222E` (nested subsurface), `#262C3A` (micro hairline border), `#F1F5F9` (primary text), `#94A3B8` (muted metadata).
- **Light Ground ("Warm Alabaster Paper"):** `#F6F7F9` (page canvas), `#FFFFFF` (card & chrome), `#EEF1F6` (nested subsurface), `#DFE4EE` (hairline border), `#0F172A` (primary text), `#475569` (muted metadata).
- **Primary Brand Accent:** `#E11D48` / `#DC2626` (Gyokai Crimson) used selectively for primary actions, active navigation rail, and focal highlights.
- **Functional State Colors (Data Only):** `#10B981` Emerald (on-time, paid, approved), `#F59E0B` Amber (pending review, due soon), `#EF4444` Red (overdue, rejected, late).

## Typography (`R-06`, `R-25`)
- **Display & Body:** `Plus Jakarta Sans` (weights `400–800`) — clean geometric humanism with crisp readability across dense Indonesian enterprise tables and headings.
- **Data & Codes:** `JetBrains Mono` with `font-variant-numeric: tabular-nums` exclusively for NIP identifiers, timestamps (`WIB`), invoice numbers, percentages, and Rupiah/Yen figures.

## Motion Choreography (`R-19`, `R-39`)
- **Skiper40 (`<x-skiper-link>`):** Directional underline wipe & blend-difference link micro-interactions across contextual navigation links and actions.
- **Skiper99 (`<x-animated-icon>`):** Stateful micro-interactions (`menu` 3-bar hamburger morphing to X, `arrow` chevron stem extension on hover, `theme` morphing sun/moon, `bell` nudge).
- **Skiper19 (`<x-scroll-flow>`):** GSAP `ScrollTrigger`-driven architectural SVG stroke progress tracing down scrollable views plus top header scroll rail.
- **Skiper49 (`<x-perspective-carousel>`):** Swiper `EffectCoverflow` inverted 3D perspective carousel (`rotate: -32`, concave depth) for multi-division spotlights.
- All non-essential motion halts gracefully under `@media (prefers-reduced-motion: reduce)`, and fixed overlay modals strictly disable `backdrop-filter` blur for GPU responsiveness.
