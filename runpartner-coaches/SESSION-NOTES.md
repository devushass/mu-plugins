# RunPartner Coaches — Session Notes

## Current State (May 8, 2026)

### What exists

**mu-plugin: `runpartner-coaches`**
- `mu-plugins/runpartner-coaches.php` — Entry point
- `mu-plugins/runpartner-coaches/includes/class-coaches-cpt.php` — Full CPT class

**Coach CPT:**
- Post type: `coach`, REST base: `wp/v2/coaches`
- Supports: `title`, `editor`, `thumbnail`, `excerpt`
- Menu icon: `dashicons-groups`, slug: `/coaches/%postname%`

**Meta fields (8, all `show_in_rest: true`):**

| # | Field | Meta Key | Type | Notes |
|---|-------|----------|------|-------|
| 1 | Subtitle | `_rp_coach_subtitle` | string | Single-line text |
| 2 | Nationality | `_rp_coach_nationality` | string | Text |
| 3 | Birth Year | `_rp_coach_birth_year` | integer | 1800–today |
| 4 | Death Year | `_rp_coach_death_year` | integer | 1800–today, 0 = alive |
| 5 | Era Start | `_rp_coach_era_start` | integer | Decade active began |
| 6 | Era End | `_rp_coach_era_end` | integer | 0 = Present/active |
| 7 | Approach | `_rp_coach_approach` | string | Textarea, training philosophy |
| 8 | Notable Athletes | `_rp_coach_notable_athletes` | string | Textarea |
| 9 | Contributions | `_rp_coach_contributions` | string | Textarea |

**REST API:**
- Composite field `coach_data` bundles all meta (read + write)
- Custom collection params: `nationality`, `era_start`, `era_end`, `year` (birth year)
- Schema validation: `era_end` allows 0 (Present), others 1800+

**Admin:**
- Meta box "Coach Details" in normal context, high priority
- All fields rendered with inline styles

**Theme templates (skeletons):**
- `templates/single-coach.html` — Hero cover with columns (featured image + title), post content, footer
- `templates/archive-coach.html` — Empty (0 lines)

### Architecture (following existing 3-layer pattern)

```
mu-plugin (data/API)  →  plugin (presentation blocks)  →  theme (layout/templates)
```

The coach CPT follows the same pattern as the existing `events` CPT.

---

## To-Do (for next session)

### 1. Presentation Blocks (plugin: `advanced-multi-block`)
Dynamic blocks to render coach meta via `render.php`:

| Block | Slug | Renders |
|-------|------|---------|
| Coach Subtitle | `runpartner/coach-subtitle` | Subtitle text |
| Coach Nationality | `runpartner/coach-nationality` | Country text |
| Coach Years | `runpartner/coach-years` | Birth–death range |
| Coach Era | `runpartner/coach-era` | "1950–Present" range |
| Training Philosophy | `runpartner/coach-approach` | Styled text/card |
| Notable Athletes | `runpartner/coach-notable-athletes` | List |
| Key Contributions | `runpartner/coach-contributions` | Rich text section |

### 2. Theme Templates

**`single-coach.html`** — Refine skeleton:
- Hero: left column (featured image) + right column stacked:
  - Post title (`.text-gradient`)
  - Subtitle
  - Nationality
  - Birth–death years
  - Era range (e.g. "1950–Present")
  - Approach excerpt (2 lines)
- Below hero (`.animate-on-scroll` sections):
  - Training Philosophy card (full text)
  - Notable Athletes card (`.stagger-children`)
  - Key Contributions card
  - Post Content

**`archive-coach.html`** — Build from scratch:
- Introduction section (heading + description)
- Query loop grid with coach cards

### 3. Theme CSS
Coach-specific component styles:
- `.coach-meta-card` — detail card for meta below title
- `.coach-approach-card` — philosophy card
- `.coach-contributions-card` — contributions section
- `.coach-archive-grid` — archive layout

### 4. Data Seeding
Add notable running coaches as test content.

---

## Key Conventions (match existing)

- Textdomain: `runpartner`
- Meta prefix: `_rp_coach_*`
- Block namespace: `runpartner/coach-*`
- CSS naming: BEM-like with `coach-` prefix
- Animation: `.animate-on-scroll` + `.is-visible`, `.stagger-children`, `.text-gradient`
