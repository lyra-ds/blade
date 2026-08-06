# Lyra Blade component guidelines

## Non-negotiable rules

- Use only the components and props listed in the generated reference below. Never invent a Blade component or prop. React design-system components absent from this list have not been ported to Blade and are not available here yet.
- The React component contracts are the source of truth and API parity is mandatory. Do not introduce a Blade-only prop.
- Both tag syntaxes are supported and are exact aliases: `<x-lyra::button>Save</x-lyra::button>` and `<lyra:button>Save</lyra:button>`.
- This package never ships CSS. All appearance comes from the npm package `@lyra-ds/styles`; never write or override `.lyra-*` CSS for these components.
- Phase 1 components are static-only. Do not attribute interactivity, JavaScript behavior, or Alpine directives such as `x-data` and `x-on` to them.

## Generated component reference

This section is generated from `resources/views/components/*.blade.php` and `tests/Fixtures/class-emission/*.json`. Regenerate it with `php bin/generate-boost-guidelines`.

<!-- Generated component entries: do not edit by hand. -->

### action-bar

Tags: `<x-lyra::action-bar>...</x-lyra::action-bar>` or `<lyra:action-bar>...</lyra:action-bar>`.

Root class combination observed in fixtures: `lyra-actionbar`.

Props:
- `open` — default: `true`; no fixture examples (fixtures do not constrain this prop).
- `count` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `label` — default: `"selected"`; no fixture examples (fixtures do not constrain this prop).

### alert

Tags: `<x-lyra::alert>...</x-lyra::alert>` or `<lyra:alert>...</lyra:alert>`.

Root class alternatives observed in fixtures: `lyra-alert lyra-alert--info` | `lyra-alert lyra-alert--success` | `lyra-alert lyra-alert--warning` | `lyra-alert lyra-alert--danger`.

Props:
- `tone` — default: `"info"`; class-selector values evidenced by defaults and fixtures: `"danger"`, `"info"`, `"success"`, `"warning"`.

### avatar

Tags: `<x-lyra::avatar>...</x-lyra::avatar>` or `<lyra:avatar>...</lyra:avatar>`.

Root class alternatives observed in fixtures: `lyra-avatar lyra-avatar--md` | `lyra-avatar lyra-avatar--sm` | `lyra-avatar lyra-avatar--lg` | `lyra-avatar lyra-avatar--xl` | `lyra-avatar lyra-avatar--md lyra-avatar--square`.

Props:
- `src` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `name` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`, `"xl"`.
- `shape` — default: `"circle"`; class-selector values evidenced by defaults and fixtures: `"circle"`, `"square"`.
- `status` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `statusLabel` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### badge

Tags: `<x-lyra::badge>...</x-lyra::badge>` or `<lyra:badge>...</lyra:badge>`.

Root class alternatives observed in fixtures: `lyra-badge lyra-badge--neutral` | `lyra-badge lyra-badge--accent` | `lyra-badge lyra-badge--success` | `lyra-badge lyra-badge--warning` | `lyra-badge lyra-badge--danger` | `lyra-badge lyra-badge--info`.

Props:
- `tone` — default: `"neutral"`; class-selector values evidenced by defaults and fixtures: `"accent"`, `"danger"`, `"info"`, `"neutral"`, `"success"`, `"warning"`.
- `dot` — default: `false`; no fixture examples (fixtures do not constrain this prop).

### brand

Tags: `<x-lyra::brand>...</x-lyra::brand>` or `<lyra:brand>...</lyra:brand>`.

Root class combination observed in fixtures: `lyra-brand`.

Props:
- `mark` — required; no fixture examples (fixtures do not constrain this prop).
- `markDark` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `size` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `href` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### breadcrumb

Tags: `<x-lyra::breadcrumb>...</x-lyra::breadcrumb>` or `<lyra:breadcrumb>...</lyra:breadcrumb>`.

Root class combination observed in fixtures: `lyra-breadcrumb`.

Props:
- `items` — required; no fixture examples (fixtures do not constrain this prop).

### button

Tags: `<x-lyra::button>...</x-lyra::button>` or `<lyra:button>...</lyra:button>`.

All root class combinations in this product were observed: `lyra-btn` + one of (`lyra-btn--primary`, `lyra-btn--secondary`, `lyra-btn--soft`, `lyra-btn--ghost`, `lyra-btn--danger`) + one of (`lyra-btn--sm`, `lyra-btn--md`, `lyra-btn--lg`).
Additional specific root class combinations observed: `lyra-btn lyra-btn--primary lyra-btn--md lyra-btn--loading` | `lyra-btn lyra-btn--primary lyra-btn--md lyra-btn--full` | `lyra-btn lyra-btn--primary lyra-btn--md lyra-btn--loading lyra-btn--full` | `lyra-btn lyra-btn--danger lyra-btn--sm lyra-btn--loading`.

Props:
- `variant` — default: `"primary"`; class-selector values evidenced by defaults and fixtures: `"danger"`, `"ghost"`, `"primary"`, `"secondary"`, `"soft"`.
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`.
- `loading` — default: `false`; fixture examples (not constraints): `true`.
- `disabled` — default: `false`; no fixture examples (fixtures do not constrain this prop).
- `full` — default: `false`; fixture examples (not constraints): `true`.

### card

Tags: `<x-lyra::card>...</x-lyra::card>` or `<lyra:card>...</lyra:card>`.

Root class alternatives observed in fixtures: `lyra-card lyra-card--padded` | `lyra-card` | `lyra-card lyra-card--interactive lyra-card--padded`.

Props:
- `padded` — default: `true`; fixture examples (not constraints): `false`.
- `interactive` — default: `false`; fixture examples (not constraints): `true`.

### checkbox

Tags: `<x-lyra::checkbox>...</x-lyra::checkbox>` or `<lyra:checkbox>...</lyra:checkbox>`.

Root class combination observed in fixtures: `lyra-checkbox`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### container

Tags: `<x-lyra::container>...</x-lyra::container>` or `<lyra:container>...</lyra:container>`.

Root class combination observed in fixtures: `lyra-container`.

Props:
- `max` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### empty-state

Tags: `<x-lyra::empty-state>...</x-lyra::empty-state>` or `<lyra:empty-state>...</lyra:empty-state>`.

Root class combination observed in fixtures: `lyra-empty`.

Props: none (slots and pass-through attributes only).

### fieldset

Tags: `<x-lyra::fieldset>...</x-lyra::fieldset>` or `<lyra:fieldset>...</lyra:fieldset>`.

Root class combination observed in fixtures: `lyra-fieldset`.

Props: none (slots and pass-through attributes only).

### footer

Tags: `<x-lyra::footer>...</x-lyra::footer>` or `<lyra:footer>...</lyra:footer>`.

Root class combination observed in fixtures: `lyra-footer`.

Props:
- `linksLabel` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### form-row

Tags: `<x-lyra::form-row>...</x-lyra::form-row>` or `<lyra:form-row>...</lyra:form-row>`.

Root class combination observed in fixtures: `lyra-formrow`.

Props:
- `columns` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### grid

Tags: `<x-lyra::grid>...</x-lyra::grid>` or `<lyra:grid>...</lyra:grid>`.

Root class combination observed in fixtures: `lyra-grid`.

Props:
- `columns` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `minItem` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `gap` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### icon-button

Tags: `<x-lyra::icon-button>...</x-lyra::icon-button>` or `<lyra:icon-button>...</lyra:icon-button>`.

Root class alternatives observed in fixtures: `lyra-btn lyra-btn--icon lyra-btn--secondary lyra-btn--md` | `lyra-btn lyra-btn--icon lyra-btn--primary lyra-btn--md` | `lyra-btn lyra-btn--icon lyra-btn--soft lyra-btn--md` | `lyra-btn lyra-btn--icon lyra-btn--ghost lyra-btn--md` | `lyra-btn lyra-btn--icon lyra-btn--danger lyra-btn--md` | `lyra-btn lyra-btn--icon lyra-btn--secondary lyra-btn--sm` | `lyra-btn lyra-btn--icon lyra-btn--secondary lyra-btn--lg` | `lyra-btn lyra-btn--icon lyra-btn--danger lyra-btn--sm`.

Props:
- `label` — required; no fixture examples (fixtures do not constrain this prop).
- `variant` — default: `"secondary"`; class-selector values evidenced by defaults and fixtures: `"danger"`, `"ghost"`, `"primary"`, `"secondary"`, `"soft"`.
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`.

### input

Tags: `<x-lyra::input>...</x-lyra::input>` or `<lyra:input>...</lyra:input>`.

Root class alternatives observed in fixtures: `lyra-input` | `lyra-input lyra-input--sm` | `lyra-input lyra-input--lg` | `lyra-input lyra-input--error` | `lyra-input lyra-input--sm lyra-input--error`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `hint` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `error` — default: `null`; fixture examples (not constraints): `"Invalid"`.
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`.

### nav-link

Tags: `<x-lyra::nav-link>...</x-lyra::nav-link>` or `<lyra:nav-link>...</lyra:nav-link>`.

Root class alternatives observed in fixtures: `lyra-navlink` | `lyra-navlink lyra-navlink--active`.

Props:
- `active` — default: `false`; fixture examples (not constraints): `true`.

### page-header

Tags: `<x-lyra::page-header>...</x-lyra::page-header>` or `<lyra:page-header>...</lyra:page-header>`.

Root class combination observed in fixtures: `lyra-pageheader`.

Props:
- `title` — required; fixture examples (not constraints): `"Reports"`.
- `titleAs` — default: `"h1"`; no fixture examples (fixtures do not constrain this prop).

### pagination

Tags: `<x-lyra::pagination>...</x-lyra::pagination>` or `<lyra:pagination>...</lyra:pagination>`.

Root class combination observed in fixtures: `lyra-pagination`.

Props:
- `page` — required; no fixture examples (fixtures do not constrain this prop).
- `total` — required; no fixture examples (fixtures do not constrain this prop).
- `url` — required; no fixture examples (fixtures do not constrain this prop).
- `previousLabel` — default: `"Previous page"`; no fixture examples (fixtures do not constrain this prop).
- `nextLabel` — default: `"Next page"`; no fixture examples (fixtures do not constrain this prop).

### person-cell

Tags: `<x-lyra::person-cell>...</x-lyra::person-cell>` or `<lyra:person-cell>...</lyra:person-cell>`.

Root class combination observed in fixtures: `lyra-personcell`.

Props:
- `name` — required; fixture examples (not constraints): `"Ada Lovelace"`.
- `detail` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `src` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### progress

Tags: `<x-lyra::progress>...</x-lyra::progress>` or `<lyra:progress>...</lyra:progress>`.

Root class alternatives observed in fixtures: `lyra-progress` | `lyra-progress lyra-progress--success` | `lyra-progress lyra-progress--danger`.

Props:
- `value` — required; fixture examples (not constraints): `30`.
- `tone` — default: `null`; class-selector values evidenced by defaults and fixtures: `"danger"`, `"success"`, `null`.

### radio

Tags: `<x-lyra::radio>...</x-lyra::radio>` or `<lyra:radio>...</lyra:radio>`.

Root class combination observed in fixtures: `lyra-radio`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### segmented-ring

Tags: `<x-lyra::segmented-ring>...</x-lyra::segmented-ring>` or `<lyra:segmented-ring>...</lyra:segmented-ring>`.

Root class alternatives observed in fixtures: `lyra-ring lyra-ring--lg` | `lyra-ring lyra-ring--md` | `lyra-ring lyra-ring--lg lyra-ring--stacked`.

Props:
- `segments` — default: unknown; no fixture examples (fixtures do not constrain this prop).
- `total` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `centerValue` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `centerLabel` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `size` — default: `"lg"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`.
- `stacked` — default: `false`; fixture examples (not constraints): `true`.
- `showLegend` — default: `true`; no fixture examples (fixtures do not constrain this prop).

### select

Tags: `<x-lyra::select>...</x-lyra::select>` or `<lyra:select>...</lyra:select>`.

Root class alternatives observed in fixtures: `lyra-input` | `lyra-input lyra-input--sm` | `lyra-input lyra-input--lg` | `lyra-input lyra-input--error`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `hint` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `error` — default: `null`; fixture examples (not constraints): `"Invalid"`.
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`.

### separator

Tags: `<x-lyra::separator>...</x-lyra::separator>` or `<lyra:separator>...</lyra:separator>`.

Root class alternatives observed in fixtures: `lyra-separator` | `lyra-separator lyra-separator--vertical` | `lyra-separator--label`.

Props:
- `orientation` — default: `"horizontal"`; class-selector values evidenced by defaults and fixtures: `"horizontal"`, `"vertical"`.

### skeleton

Tags: `<x-lyra::skeleton>...</x-lyra::skeleton>` or `<lyra:skeleton>...</lyra:skeleton>`.

Root class alternatives observed in fixtures: `lyra-skeleton` | `lyra-skeleton lyra-skeleton--circle`.

Props:
- `width` — default: `"100%"`; no fixture examples (fixtures do not constrain this prop).
- `height` — default: `14`; no fixture examples (fixtures do not constrain this prop).
- `circle` — default: `false`; fixture examples (not constraints): `true`.

### spinner

Tags: `<x-lyra::spinner>...</x-lyra::spinner>` or `<lyra:spinner>...</lyra:spinner>`.

Root class alternatives observed in fixtures: `lyra-spinner lyra-spinner--sm` | `lyra-spinner lyra-spinner--md` | `lyra-spinner lyra-spinner--lg`.

Props:
- `size` — default: `"md"`; class-selector values evidenced by defaults and fixtures: `"lg"`, `"md"`, `"sm"`.

### stack

Tags: `<x-lyra::stack>...</x-lyra::stack>` or `<lyra:stack>...</lyra:stack>`.

Root class combination observed in fixtures: `lyra-stack`.

Props:
- `direction` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `gap` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `align` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `justify` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `wrap` — default: `false`; no fixture examples (fixtures do not constrain this prop).
- `as` — default: `"div"`; no fixture examples (fixtures do not constrain this prop).

### stat

Tags: `<x-lyra::stat>...</x-lyra::stat>` or `<lyra:stat>...</lyra:stat>`.

Root class combination observed in fixtures: `lyra-stat`.

Props:
- `direction` — default: `"flat"`; class-selector values evidenced by defaults and fixtures: `"down"`, `"flat"`, `"up"`.

### stepper

Tags: `<x-lyra::stepper>...</x-lyra::stepper>` or `<lyra:stepper>...</lyra:stepper>`.

Root class combination observed in fixtures: `lyra-stepper`.

Props:
- `steps` — required; fixture examples (not constraints): `["Account"]`.
- `active` — required; fixture examples (not constraints): `0`.

### switch

Tags: `<x-lyra::switch>...</x-lyra::switch>` or `<lyra:switch>...</lyra:switch>`.

Root class combination observed in fixtures: `lyra-switch`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).

### table

Tags: `<x-lyra::table>...</x-lyra::table>` or `<lyra:table>...</lyra:table>`.

Root class combination observed in fixtures: `lyra-table-wrap`.

Props:
- `columns` — required; no fixture examples (fixtures do not constrain this prop).
- `rows` — required; no fixture examples (fixtures do not constrain this prop).
- `hover` — default: `false`; fixture examples (not constraints): `true`.

### tag

Tags: `<x-lyra::tag>...</x-lyra::tag>` or `<lyra:tag>...</lyra:tag>`.

Root class combination observed in fixtures: `lyra-tag`.

Props: none (slots and pass-through attributes only).

### textarea

Tags: `<x-lyra::textarea>...</x-lyra::textarea>` or `<lyra:textarea>...</lyra:textarea>`.

Root class alternatives observed in fixtures: `lyra-input lyra-textarea` | `lyra-input lyra-textarea lyra-input--error`.

Props:
- `label` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `hint` — default: `null`; no fixture examples (fixtures do not constrain this prop).
- `error` — default: `null`; fixture examples (not constraints): `"Invalid"`.

### toast-stack

Tags: `<x-lyra::toast-stack>...</x-lyra::toast-stack>` or `<lyra:toast-stack>...</lyra:toast-stack>`.

Root class combination observed in fixtures: `lyra-toast-stack`.

Props: none (slots and pass-through attributes only).

### toast

Tags: `<x-lyra::toast>...</x-lyra::toast>` or `<lyra:toast>...</lyra:toast>`.

Root class combination observed in fixtures: `lyra-toast`.

Props:
- `tone` — default: `"info"`; no fixture examples (fixtures do not constrain this prop).
