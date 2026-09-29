# Migrating to lyra-ds/blade 1.0

This guide covers upgrading `lyra-ds/blade` from `0.10.x` to `1.0.0`.

Release `1.0.0` establishes standard [Semantic Versioning](https://semver.org/) for the Blade adapter, aligning its markup, accessibility semantics, and interactivity contracts with `@lyra-ds/alpine` `^1.2` and `@lyra-ds/styles` `^1.1`.

> [!NOTE]
> `@lyra-ds/alpine` `^1.2` is required because `1.0.0` ships `calendar-view` and `create-workspace-dialog`, backed by the `lyraCalendarView` and `lyraCreateWorkspaceDialog` bindings published in `@lyra-ds/alpine` `1.2.0`.

---

## 1. Before You Upgrade

### System Requirements

| Package / Runtime | Minimum Requirement | Notes |
| --- | --- | --- |
| **PHP** | `>= 8.3` | Tested against PHP 8.3 and PHP 8.4 |
| **Laravel (`illuminate/*`)** | `^12.41.1` or `^13.24` | Laravel 11 reached security end-of-life in March 2026 and is not supported |
| **`@lyra-ds/styles`** | `^1.1` | Required CSS contract for progressive enhancement, live regions, and overlays |
| **`@lyra-ds/alpine`** | `^1.2` | Required runtime plugin factories (`lyraTabs`, `lyraFileUpload`, `lyraToastStack`, `lyraCalendarView`, `lyraCreateWorkspaceDialog`, etc.) |
| **`alpinejs`** | `>= 3.13 < 4` | Consumer-installed peer dependency |

### Synchronized Upgrade Requirement

> [!IMPORTANT]
> **You must upgrade `lyra-ds/blade`, `@lyra-ds/styles`, and `@lyra-ds/alpine` simultaneously.**
>
> Blade 1.0 emits the progressive-enhancement markup and ARIA contract expected by Alpine 1.x. Blade `0.10.x` is **incompatible** with `@lyra-ds/alpine` `1.x` (Alpine 1.x validators will reject 0.10 markup), and Blade `1.0.x` is **incompatible** with `@lyra-ds/alpine` `0.x` (Alpine 0.x cannot enhance 1.0 markup).

Update your `composer.json` and `package.json`:

```bash
composer require "lyra-ds/blade:^1.0"
npm install @lyra-ds/styles@^1.1 @lyra-ds/alpine@^1.2
```

---

## 2. Quick Checklist

Use this checklist to plan and verify your upgrade:

- [ ] Verify your environment runs PHP `>= 8.3` and Laravel `>= 12.41.1` or `>= 13.24`.
- [ ] Upgrade `@lyra-ds/styles` to `^1.1` and `@lyra-ds/alpine` to `^1.2` alongside `lyra-ds/blade` `^1.0.0`.
- [ ] **Audit `x-data` attributes**: Remove any direct `x-data` on interactive Lyra components and wrap them in parent containers (`<div x-data="...">`).
- [ ] **Tabs**: Update CSS and test selectors. The root element is now `div[data-lyra-tabs]` with the element ID, while the tablist is `[data-lyra-tabs-enhanced]` (or `.lyra-tabs`) and panels are `section[data-value]` with heading tags.
- [ ] **FileUpload**: Migrate from simulated upload props (`uploadDuration`, `defaultItems`, `doneLabel`, status `done`) to the controlled lifecycle (`items`, `x-model`, and `lyra:file-upload:*` event listeners).
- [ ] **WorkspaceSwitcher**: Update selectors from listbox semantics (`[role="listbox"]`, `[role="option"]`, `[aria-selected]`) to disclosure semantics (`.lyra-wssw__pop[role="group"]`, `.lyra-wssw__item[data-id]`, `[aria-current="true"]`). Note that items with `href` now navigate natively.
- [ ] **Navigation (`app-sidebar`, `sidebar-group`, `bottom-nav`)**: Update test/CSS selectors that expected `<button>` for linked navigation items to target `<a>` (or `:is(a, button)`).
- [ ] **Breadcrumb**: Update test selectors expecting `<a href="#">` for non-link items; these now render as `<span>`.
- [ ] **ToastStack**: Update test/CSS selectors that relied on `[role="status"]` on the stack root or toast rows. Select `[data-lyra-toast-region] .lyra-toast` instead.
- [ ] **Overlays**: Replace manual focus restoration hacks with the standard `return-focus-to` prop using a CSS selector.
- [ ] **Container**: Ensure `max` uses supported keywords (`sm`, `md`, `lg`, `xl`) or valid numeric pixel values.
- [ ] Run automated tests and inspect browser console logs for any warnings or runtime exceptions.

---

## 3. Component-by-Component Changes

### Tabs

Blade `tabs` now emits the complete progressive-enhancement structure required by `@lyra-ds/alpine` 1.x `lyraTabs` (`hasValidStructure` check).

#### Key Changes
- **Root Element**: A passthrough `id` now lands on the outer root wrapper (`div[data-lyra-tabs]`) rather than the tablist. Without an explicit `id`, `lyra-tabs-<uniqid>` is generated.
- **Fallback Navigation**: Emits a `<nav data-lyra-tabs-fallback>` containing anchor links to each section panel, ensuring readable, navigable content before JavaScript initializes or if JS fails.
- **Enhanced Tablist**: The tablist (`div[data-lyra-tabs-enhanced]`) carries `.lyra-tabs` and starts `hidden`. Static `role="tab"`, `aria-selected`, `tabindex`, and active classes are removed from the server markup; Alpine applies them dynamically on hydration.
- **Panel Elements**: Panels are now semantic `<section id="<rootId>-panel-<index>" data-value="..." x-bind="panel">` containing an `<h2>` heading with the tab label (replacing unlabelled `div[role=tabpanel]`).
- **Disabled Tabs**: Tab items with `'disabled' => true` emit the native `disabled` attribute on `<button class="lyra-tab">`. Alpine uses `tab.disabled` to ignore clicks and skip them during keyboard arrow navigation.
- **New Prop**: `label` (defaults to `'Tabs'`), used as the accessible `aria-label` for both fallback nav and enhanced tablist.

#### Before (0.10.x)
```blade
{{-- 0.10: Tablist received the id; panels were plain divs --}}
<x-lyra::tabs
    id="project-tabs"
    active="overview"
    :items="[
        ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Overview content'],
        ['id' => 'issues', 'label' => 'Issues', 'panel' => 'Issues list'],
    ]"
/>
```

#### After (1.0.x)
```blade
{{-- 1.0: Root receives the id; progressive enhancement markup generated --}}
<lyra:tabs
    id="project-tabs"
    label="Project sections"
    active="overview"
    :items="[
        ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Overview content'],
        ['id' => 'issues', 'label' => 'Issues', 'panel' => 'Issues list', 'count' => 5],
        ['id' => 'archived', 'label' => 'Archived', 'panel' => 'Archived content', 'disabled' => true],
    ]"
/>
```

#### DOM & Selector Migration
| Contract | 0.10.x | 1.0.x |
| --- | --- | --- |
| Root element | `<div x-data="lyraTabs(...)">` | `<div id="project-tabs" data-lyra-tabs x-data="lyraTabs(...)">` |
| Tablist selector | `#project-tabs` / `div[role=tablist]` | `#project-tabs [data-lyra-tabs-enhanced]` or `#project-tabs .lyra-tabs` |
| Tab buttons | `button.lyra-tab` with server-rendered `aria-selected` | `button[data-value="..."]` (ARIA attributes set by Alpine at runtime) |
| Panel selector | `div[role=tabpanel]` | `section[data-value="..."]` or `#project-tabs-panel-N` |
| Fallback nav | None | `nav[data-lyra-tabs-fallback]` with `<a href="#project-tabs-panel-N">` |

---

### FileUpload

Blade `file-upload` adopts the controlled upload lifecycle of `@lyra-ds/alpine` 1.x and `@lyra-ds/styles` 1.x. The component no longer simulates upload progress, fake timers, or completion internally; your application owns the `items` array and the network transport.

#### Key Changes
- **Removed Props**: `uploadDuration`, `defaultItems` (use `items`), `doneLabel` (use `statusLabels`), and `removeLabel` (use `messages['remove']`).
- **Removed Methods & CSS**: Component method `remove(id)` is removed in favor of `lyra:file-upload:remove`. CSS classes `.lyra-upload__bar-fill` and `.lyra-upload__check` are replaced by native `<progress class="lyra-upload__bar">`.
- **Status Lifecycle**: Standardized statuses: `selected`, `uploading`, `canceling`, `success`, `canceled`, `error`. The old status `done` is removed and replaced by `success`.
- **Progress Contract**: Progress is an object: `{ kind: 'indeterminate' }` or `{ kind: 'determinate', value: number }`. Numeric scalar progress is no longer supported.
- **New Props**: `id`, `name`, `disabled`, `required`, `items`, `messages`, `statusLabels`, `cancelLabel`, `retryLabel`.
- **Root ID Requirement**: The component root requires an `id` (`$id ?? $attributes->get('id') ?? 'lyra-upload-'.uniqid()`). The dropzone is a `<label class="lyra-upload__zone" for="...">` referencing a sibling hidden `<input type="file">`.
- **Bubbling Custom Events**:
  - `lyra:file-upload:select`: Dispatched on file drop or input selection. Detail: `{ selections: [{ id, file, name, size, type, proposedItem, proposedAttemptId? }] }`. Echo each `proposedItem` into your `items` array. A valid file has `proposedItem.status === 'selected'` and a `proposedAttemptId`: start its transport with that id. An invalid file (wrong type or too large) has a `proposedItem` with `status: 'error'` and a `validation` error, and **no** `proposedAttemptId`: show it, but do not upload it.
  - `lyra:file-upload:retry`: Dispatched on retry button click (for `canceled` items and retryable `error` items). Detail: `{ id, previousAttemptId, proposedAttemptId }`. Start a new transport with `proposedAttemptId`; `previousAttemptId` identifies the failed or canceled attempt.
  - `lyra:file-upload:cancel`: Dispatched on cancel button click. Detail: `{ id, attemptId }`.
  - `lyra:file-upload:remove`: Dispatched on remove button click. Detail: `{ id }`.

#### Before (0.10.x)
```blade
{{-- 0.10: Component simulated its own upload timer --}}
<x-lyra::file-upload
    name="documents"
    :upload-duration="3000"
    done-label="Uploaded successfully"
    remove-label="Delete file"
    :default-items="[
        ['id' => '1', 'name' => 'guide.pdf', 'status' => 'done', 'progress' => 100],
    ]"
/>
```

#### Item Shape (1.0.x)
Every item carries `id`, `name`, `size` (bytes), and `type` (MIME type), plus fields that depend on `status`:

| `status` | Extra fields |
| --- | --- |
| `selected` | none |
| `uploading`, `canceling` | `attemptId`, `progress` (`{ kind: 'indeterminate' }` or `{ kind: 'determinate', value }`) |
| `success`, `canceled` | `attemptId` |
| `error` (transport) | `attemptId`, `error: { kind: 'transport', code?, message, retryable }` |
| `error` (validation) | `error: { kind: 'validation', code: 'accept' \| 'max-size', message, retryable: false }` (no `attemptId`) |

`progress` is only valid on `uploading` and `canceling` items. Transport states (`uploading`, `canceling`, `success`, `canceled`, transport `error`) need the `attemptId` of the upload attempt that produced them.

To migrate a 0.10 `default-items` entry, rewrite it in this shape and pass it as the initial `items` (or the initial value of the `x-model` state). The 0.10 entry `['id' => '1', 'name' => 'guide.pdf', 'status' => 'done', 'progress' => 100]` becomes:

```php
['id' => '1', 'name' => 'guide.pdf', 'size' => 482133, 'type' => 'application/pdf', 'status' => 'success', 'attemptId' => 'guide-1']
```

#### After (1.0.x)
The example below shows the wiring. For a complete controller with a simulated transport, progress, cancel, and retry, see [`resources/docs-examples/file-upload.blade.php`](../resources/docs-examples/file-upload.blade.php).

```blade
{{-- 1.0: Controlled lifecycle; application owns items and transport --}}
<div
    x-data="{
        uploadItems: [
            { id: '1', name: 'guide.pdf', size: 482133, type: 'application/pdf', status: 'success', attemptId: 'guide-1' },
        ],
        handleSelect({ selections }) {
            this.uploadItems = [...this.uploadItems, ...selections.map(s => s.proposedItem)];
            for (const { id, proposedAttemptId } of selections) {
                // Invalid selections have no proposedAttemptId: nothing to upload.
                if (typeof proposedAttemptId !== 'string') continue;
                // Start your real transport (fetch / XHR) for id with proposedAttemptId here.
            }
        },
        handleRetry({ id, previousAttemptId, proposedAttemptId }) { /* start a new transport with proposedAttemptId */ },
        handleCancel({ id, attemptId }) { /* abort transport */ },
        handleRemove({ id }) {
            this.uploadItems = this.uploadItems.filter(item => item.id !== id);
        },
    }"
>
    <lyra:file-upload
        id="attachment-upload"
        name="attachments[]"
        label="Choose attachments"
        hint="PNG or PDF up to 10 MB."
        accept="image/png,application/pdf"
        :max-size-m-b="10"
        multiple
        x-model="uploadItems"
        x-on:lyra:file-upload:select="handleSelect($event.detail)"
        x-on:lyra:file-upload:retry="handleRetry($event.detail)"
        x-on:lyra:file-upload:cancel="handleCancel($event.detail)"
        x-on:lyra:file-upload:remove="handleRemove($event.detail)"
    />
</div>
```

---

### WorkspaceSwitcher

`workspace-switcher` migrated from a listbox design pattern to an accessible disclosure pattern, aligning with `@lyra-ds/alpine` ^1.1 and `@lyra-ds/styles` ^1.1.

#### Key Changes
- **Container Semantics**: The dropdown popover (`.lyra-wssw__pop`) now emits `role="group"` with IDs `{id}-popover` and `{id}-popover-label` (previously `role="listbox"` with `{id}-listbox`).
- **Trigger Semantics**: The trigger button controls `{id}-popover` via `aria-controls` and no longer emits `aria-haspopup="listbox"`.
- **Items**: Workspace items (`.lyra-wssw__item[data-id]`) no longer emit `role="option"` or `aria-selected`. The active workspace is identified via `aria-current="true"`.
- **Real Links for Navigation**: Workspaces with an `href` render as native anchor links (`<a class="lyra-wssw__item" href="...">`) that navigate natively on click instead of emitting events. Button items (`<button type="button">`) and the create action continue to dispatch `lyra:change` with `{ value: dataId }`.
- **New Labels Prop**: `labels` accepts `listLabel`, `placeholder`, and a `members` translation array (e.g. `['one' => ':count member', 'other' => ':count members']`).

#### DOM & Selector Migration
| Target | 0.10.x | 1.0.x |
| --- | --- | --- |
| Popover container | `div[role="listbox"]` / `#{id}-listbox` | `div.lyra-wssw__pop[role="group"]` / `#{id}-popover` |
| Trigger popup attribute | `aria-haspopup="listbox"` | Removed (standard disclosure trigger) |
| Workspace options | `button[role="option"]` / `[aria-selected]` | `.lyra-wssw__item[data-id]` / `[aria-current="true"]` |
| Direct children selector | `.lyra-wssw__pop > button` | `.lyra-wssw__pop .lyra-wssw__item` |
| Navigation items | Emitted JavaScript event | Native `<a href="...">` navigation |

---

### Navigation (`app-sidebar`, `sidebar-group`, `bottom-nav`, `breadcrumb`)

Navigation components now emit semantic, accessible HTML anchor elements for linked items.

#### Key Changes
- **Real `<a>` Links**: In `app-sidebar`, `sidebar-group`, and `bottom-nav`, items defining an `href` now render as native `<a>` elements (`.lyra-sbgroup__item` and `.lyra-bottomnav__item`) with `target`, `rel`, and `aria-current="page"` when active.
- **Action Buttons Preserved**: Items without `href` continue to render as `<button type="button">`.
- **Selection Events**: Both link and button items dispatch the bubbling `lyra:select` event with the item ID.
- **Breadcrumb No-Href Items**: Non-final items without an `href` now render as `<span>{{ $item['label'] }}</span>` rather than `<a href="#">`. The current/last page renders as `<span class="lyra-breadcrumb__current" aria-current="page">`.

#### Before (0.10.x)
```blade
{{-- 0.10: Sidebar and bottom-nav items with href rendered buttons; breadcrumb rendered a[href="#"] --}}
<x-lyra::sidebar-group
    label="Projects"
    :items="[
        ['id' => 'p1', 'label' => 'Core App', 'href' => '/projects/core'],
    ]"
/>
<x-lyra::breadcrumb
    :items="[
        ['label' => 'Home'], {{-- rendered <a href="#">Home</a> --}}
        ['label' => 'Settings'],
    ]"
/>
```

#### After (1.0.x)
```blade
{{-- 1.0: Real anchors rendered for href; breadcrumb non-links render <span> --}}
<lyra:sidebar-group
    label="Projects"
    :items="[
        ['id' => 'p1', 'label' => 'Core App', 'href' => '/projects/core', 'active' => true],
    ]"
/>
<lyra:breadcrumb
    :items="[
        ['label' => 'Home'], {{-- renders <span>Home</span> --}}
        ['label' => 'Settings'], {{-- renders <span class="lyra-breadcrumb__current" aria-current="page">Settings</span> --}}
    ]"
/>
```

#### Selector Migration
Update test and CSS selectors:
- Replace `button.lyra-sbgroup__item` with `.lyra-sbgroup__item` or `:is(a, button).lyra-sbgroup__item`.
- Replace `button.lyra-bottomnav__item` with `.lyra-bottomnav__item` or `:is(a, button).lyra-bottomnav__item`.
- Remove tests or selectors expecting `a[href="#"]` inside `.lyra-breadcrumb`.

---

### Shell

`shell` introduces accessible banner placement and skip link support, while refining landmark semantics.

#### Key Changes
- **Banner Slot**: Added `<x-slot:banner>` (or `$banner`). Renders as `<header class="lyra-shell__banner">` outside `<main>` and before the sidebar landmark.
- **Accessible Skip Link**: Pass `skipLink` (e.g. `['label' => 'Skip to main content']`). It renders `<a class="lyra-shell__skip-link">` as the first tab stop in the DOM. When enabled, `<main>` automatically receives `id` (`mainId` or generated `lyra-shell-main-<uniqid>`) and `tabindex="-1"`.
- **Sidebar Landmark Fix**: When `sidebar-as="div"`, `aria-label` is suppressed on the sidebar container to avoid creating an invalid labeled landmark on a generic `<div>`.

#### Example (1.0.x)
```blade
<lyra:shell
    :skip-link="['label' => 'Skip to main content']"
    main-id="main-content"
>
    <x-slot:banner>
        <p>System maintenance scheduled for 02:00 UTC.</p>
    </x-slot:banner>

    <x-slot:sidebar>
        <lyra:app-sidebar
            :groups="[
                ['heading' => 'Workspace', 'items' => [
                    ['id' => 'overview', 'label' => 'Overview', 'href' => '/overview', 'active' => true],
                ]],
            ]"
        />
    </x-slot:sidebar>

    <h1>Dashboard</h1>
    <p>Main application content.</p>
</lyra:shell>
```

---

### ToastStack

`toast-stack` markup has been overhauled to announce notifications reliably across screen readers.

#### Key Changes
- **Persistent Live Regions**: Live announcements are no longer placed on the stack root. The stack now contains two persistent regions present in the served DOM before any toasts exist:
  - `[data-lyra-toast-region="polite"]` (`aria-live="polite"`, `aria-relevant="additions"`)
  - `[data-lyra-toast-region="assertive"]` (`aria-live="assertive"`, `aria-relevant="additions"`)
- **Tone Routing**: Danger toasts render in the assertive region; success, info, and warning toasts render in the polite region.
- **Row Semantics**: Dynamic toast rows inside the stack no longer carry `role="status"` or `role="alert"`, preventing duplicate screen reader speech.
- **Static Children**: Static `<x-lyra::toast>` elements passed into the default slot render in the polite region and have their default `role="status"` stripped. Standalone toasts outside the stack continue to emit `role="status"`.
- **Translatable Close Button**: Configure the close button label via `Alpine.store('lyraToasts').closeLabel` (default: `'Close notification'`).
- **Owned Root**: The component owns its Alpine binding (`lyraToastStack()`). Passing `x-data` directly throws in development; wrap the stack if parent state is needed.

#### DOM & Selector Migration
| Target | 0.10.x | 1.0.x |
| --- | --- | --- |
| Stack root | `role="region"` / live region | `.lyra-toast-stack` (no live region on root) |
| Toast row selector | `.lyra-toast[role="status"]` | `[data-lyra-toast-region] .lyra-toast` |
| Danger toast selector | `.lyra-toast--danger[role="status"]` | `[data-lyra-toast-region="assertive"] .lyra-toast` |

---

### Overlays (`dialog`, `drawer`, `bottom-sheet`, `command-palette`, `date-picker`)

All overlay components now support the standard focus-restoration contract of `@lyra-ds/alpine` `1.1.0`.

#### Key Changes
- **`return-focus-to` Prop**: On every overlay (`dialog`, `drawer`, `bottom-sheet`, `command-palette`, `date-picker`), the value is a CSS selector, not a bare element id. Blade compiles it to `document.querySelector(<selector>)` (`LyraDs\Blade\FocusResolver::selector()`), and Alpine focuses the match when the overlay closes. Use `return-focus-to="#open-button"` or `return-focus-to="[data-trigger='edit']"`; a bare `open-button` matches an `<open-button>` element and focus is not restored. An empty value falls back to the element that was focused before the overlay opened.
- **CommandPalette**: Same selector contract. Write `return-focus-to="#open-cmdk"` for a trigger with `id="open-cmdk"`.
- **DatePicker**: `return-focus-to` applies to the mobile bottom sheet (the desktop popover already returns focus to its trigger). Without a value, the sheet returns focus to the picker's own `.lyra-datepicker__btn` trigger.
- **BottomSheet**: The title id is `label-id` when you pass it; otherwise it is `lyra-bottom-sheet-title-<uniqid>`, unique per instance and different on every render. Pass an explicit `label-id` when tests, CSS, or `aria-labelledby` references need a stable id.
- **Translated `closeLabel`**: Preserved after Alpine hydration.
- **Open state**: `dialog`, `drawer`, `bottom-sheet`, and `command-palette` roots are `x-modelable="open"` (`date-picker` models its `selected` value instead). Bind `x-model` to state owned by a parent wrapper to open and close the overlay.

#### Example (1.0.x)
```blade
<div x-data="{ settingsOpen: false }">
    <button id="open-settings-dialog" type="button" @click="settingsOpen = true">
        Open Settings
    </button>

    <lyra:dialog
        title="Account Settings"
        x-model="settingsOpen"
        return-focus-to="#open-settings-dialog"
        close-label="Close settings"
    >
        <p>User profile and settings form.</p>
    </lyra:dialog>
</div>
```

---

### Dropdown & Tooltip

#### Dropdown
- **Item IDs & Events**: Items accept `id` (emitted as `data-id`) and `href` (renders as anchor `menuitem`). Selecting an item dispatches `lyra:select` with `{ id }`.
- **Button-Styled Trigger**: New `trigger-variant` (e.g. `'primary'`, `'secondary'`) and `trigger-size` props style the trigger element (`span[role=button]`) with `.lyra-btn` classes, ensuring exactly one tab stop without nested button anti-patterns.
- **Accessible Icons**: Icons in menu items are wrapped with `aria-hidden="true"`, preventing icon text from corrupting accessible names or typeahead search.
- **Selection Event**: The `@lyra-ds/alpine` 1.1.0 dropdown plugin does not emit `lyra:select`. Blade adds `x-on:click="$dispatch('lyra:select', { id })"` on each item so the event bubbles from the item with its `data-id`. This is independent of disabled items.
- **Disabled Items** (upstream [lyra#289](https://github.com/lyra-ds/lyra/issues/289)): Alpine 1.1.0 and Styles 1.1.0 have no disabled menu item contract. Until upstream ships one, Blade mitigates it:
  - Disabled items render `aria-disabled="true"` and stay focusable, so arrow-key roving does not stall (arrow keys still visit them).
  - A capture-phase click handler calls `preventDefault()` and `stopImmediatePropagation()`, so activation (click, Enter, Space) does not dispatch `lyra:select` and does not navigate.
  - A disabled link item drops its `href` and gets `tabindex="-1"`.
  - An inline style (`opacity:.4;cursor:not-allowed`) marks it as disabled, until `@lyra-ds/styles` styles `.lyra-menu__item[aria-disabled="true"]`.

#### Tooltip
- **Required `tip`**: The tooltip text is the required `tip` prop. The default slot is the trigger (one focusable element); there is no `trigger` slot.
- **`bubble-id` Prop**: Removes the inner `<span x-bind="target">` wrapper around the trigger. The root `<span class="lyra-tooltip">` stays, because the plugin binds hover and focus behavior to it. The bubble renders `id="<bubble-id>"`, so your focusable trigger carries `aria-describedby="<bubble-id>"` itself, with no extra tab stop. Without `bubble-id`, `aria-describedby` lands on the non-focusable inner wrapper.

#### Example (1.0.x)
```blade
{{-- Dropdown with button-styled trigger and item IDs --}}
<lyra:dropdown
    trigger-variant="secondary"
    trigger-size="sm"
    :items="[
        ['id' => 'rename', 'label' => 'Rename project'],
        ['type' => 'separator'],
        ['id' => 'delete', 'label' => 'Delete project', 'danger' => true],
    ]"
>
    <x-slot:trigger>Project Options</x-slot:trigger>
</lyra:dropdown>

{{-- Tooltip with bubble-id and direct describedby --}}
<lyra:tooltip tip="Additional instructions for the user." bubble-id="help-tooltip">
    <button type="button" aria-describedby="help-tooltip">Help</button>
</lyra:tooltip>
```

---

### Container

`container` aligns its `max` prop validation with React's `resolveMax`.

#### Key Changes
- **Keyword Mapping**: Keywords map to fixed maximum widths: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px). A keyword must match exactly: `" lg"`, `"lg "`, and `"LG"` are not keywords and emit no style.
- **Numeric Values**: Integers and finite floats emit `--container-max: <value>px`. A numeric string is trimmed first (ASCII and Unicode whitespace, including the BOM), then it must match `/^\d+(\.\d+)?$/D`. So `" 5"` and `"960 "` are accepted and emit `5px` and `960px`.
- **Invalid Values Suppressed**: Other values emit no inline style, which prevents broken CSS: `"+5"`, `"-5"`, `"1e3"`, `".5"`, `"5."`, `"5px"`, `"lg "`, `INF`, `NAN`, and non-scalar values.

| `max` value | Output |
| --- | --- |
| `"lg"` | `--container-max: 1024px` |
| `960` / `960.5` | `--container-max: 960px` / `960.5px` |
| `" 5"` / `"960 "` | `--container-max: 5px` / `960px` (trimmed) |
| `" lg"`, `"LG"`, `"+5"`, `"-5"`, `"1e3"`, `"5px"` | no `style` attribute |

#### Example (1.0.x)
```blade
{{-- Keyword --}}
<lyra:container max="lg">...</lyra:container> {{-- style="--container-max: 1024px" --}}

{{-- Numeric pixel value --}}
<lyra:container :max="960">...</lyra:container> {{-- style="--container-max: 960px" --}}

{{-- Numeric string: trimmed before validation --}}
<lyra:container max=" 5">...</lyra:container> {{-- style="--container-max: 5px" --}}

{{-- Rejected: keywords are not trimmed --}}
<lyra:container max=" lg">...</lyra:container> {{-- no style attribute --}}
```

---

### Brand

`brand` makes the `mark` prop/slot optional, matching React's fallback initial logic.

#### Key Changes
- When `mark` is omitted: generates an initial from the wordmark text (`strip_tags` of the default slot).
- When the wordmark slot is also empty: falls back to the first character of the `aria-label` attribute.
- Explicit `mark` and `markDark` images continue to render normally.

#### Example (1.0.x)
```blade
{{-- Generates initial "L" from slot text --}}
<lyra:brand>Lyra Design System</lyra:brand>

{{-- Generates initial "A" from aria-label --}}
<lyra:brand aria-label="Acme Inc." />
```

---

## 4. Owned-Root `x-data` Policy

Blade 1.0 enforces the **Owned-Root Policy** on all interactive components that emit a `lyra*` Alpine binding (for example `tabs`, `dropdown`, `toast-stack`, `accordion`, `dialog`, `file-upload`, and `otp-input`). Every component whose template calls `LyraDs\Blade\OwnedRoot::guard()` is covered.

### Why This Policy Exists

In HTML and the DOM parser, multiple attributes with the same name on a single element cannot coexist:

```html
<!-- Invalid HTML: browser discards one attribute silently -->
<div x-data="lyraToastStack()" x-data="{ open: true }">
```

When this occurred in earlier versions, Alpine would silently discard one of the scopes, causing missing state (`open is not defined`) or breaking component behavior without an explanatory error.

### Enforcement Rules

Through `\LyraDs\Blade\OwnedRoot::guard()`:

1. **Local & Testing Environments**: Passing `x-data` directly to an Alpine-bound component immediately throws an `InvalidArgumentException`:
   ```
   <lyra:tabs> owns x-data; wrap it in a parent element that owns your state.
   ```
2. **Production Environment**: Passing `x-data` is silently stripped to ensure the component initializes reliably without crashing.
3. **Conflicting Directives**: Conflicting consumer attributes (such as duplicate `x-modelable` or `x-bind`) are also stripped from the root.
4. **Static Components**: Components without Alpine bindings (or `code-block` when copy controls are disabled) accept consumer `x-data` passthrough.

### Migration Pattern: Wrapper Composition

Move your application state to an enclosing wrapper element. Alpine child components seamlessly inherit parent scope while maintaining their own encapsulated state:

#### Before (Anti-pattern)
```blade
{{-- Throws InvalidArgumentException in local/testing --}}
<lyra:tabs
    x-data="{ count: 0 }"
    active="overview"
    :items="$tabs"
/>
```

#### After (Recommended Wrapper Pattern)
```blade
{{-- Parent owns consumer state; component retains internal scope --}}
<div x-data="{ count: 0 }">
    <button type="button" @click="count++">Increment: <span x-text="count"></span></button>

    <lyra:tabs
        id="project-tabs"
        active="overview"
        :items="$tabs"
    />
</div>
```

---

## 5. New Components and Props

A quick reference for new features introduced in `1.0.0`:

- **`<lyra:calendar-view>`**: Day, week, and month event schedule backed by `lyraCalendarView` in `@lyra-ds/alpine` **1.2.0 or later**. Pass local-time `events`, weekday-keyed `availability`, `start-hour`, `end-hour`, `week-starts-on`, `slot-step`, `locale`, and translated `labels`. `view` and `date` set the initial state; use `x-model` for updates (`x-modelable="date"` by default, or `"view"`). Listen for `lyra:view-change`, `lyra:change`, `lyra:event-open`, and `lyra:slot-create`. Named `toolbar-actions` and `popover` slots replace React's render props. The popover slot can read `popover?.event` and call `closePopover()`. Date cells and event chips are generated by Alpine after hydration.
  ```blade
  <lyra:calendar-view date="2026-08-12" view="week" :events="$events"
      x-on:lyra:event-open="showEvent($event.detail)">
      <x-slot:popover><span x-text="popover?.event.title"></span></x-slot:popover>
  </lyra:calendar-view>
  ```
- **`<lyra:create-workspace-dialog>`**: Uses `@lyra-ds/alpine` ^1.2.0 for a controlled workspace-creation form. It emits `lyra:create-workspace` with `{ operationId, name, slug }`; the application must call `accept(operationId)` or `reject(operationId, { fieldErrors?, message? })` on the Alpine data. Closing while pending emits `lyra:create-workspace:cancel`; acknowledge with `cancel(operationId)`. Use `x-model` or `wire:model` for `open`, and `return-focus-to` with a CSS selector. The dedicated docs example shows a working controller.

- **`<x-lyra::otp-input>`**: New component implementing Alpine v1 `lyraOtpInput`. Features accessible digit labels, numeric entry, clipboard paste, SMS/email autofill (`autocomplete="one-time-code"`), hidden form input, and Laravel validation error integration.
  ```blade
  <lyra:otp-input
      name="otp"
      length="6"
      label="Verification code"
      hint="Enter the 6-digit code sent to your phone."
  />
  ```
- **`data-table` Accessibility**:
  - `caption` and `captionHidden` props emit `<caption>` as the first child of `<table>`.
  - **`aria-label` / `aria-labelledby` move to `<table>`**: These attributes now name the table itself. Blade no longer puts them on the `.lyra-table-wrap` wrapper; every other attribute still lands on the wrapper. Update selectors such as `.lyra-table-wrap[aria-label="Invoices"]` to `.lyra-table-wrap table[aria-label="Invoices"]` (or `getByRole('table', { name: 'Invoices' })`).
  - **`scroll-label` names the scroll region**: The focusable scroll container `.lyra-table-scroll` (`role="region"`, `tabindex="0"`) takes its name from `scroll-label`. Without it, the region falls back to the caption (`aria-labelledby` = caption id), then the table's `aria-labelledby`, then the table's `aria-label`, then `"Data table"`. Pass `scroll-label` when the region needs a different name from the table, for example `scroll-label="Invoices, scrollable"`.
    ```blade
    <lyra:data-table
        aria-label="Invoices"
        scroll-label="Invoices, scrollable"
        :columns="[['key' => 'number', 'label' => 'Number', 'rowHeader' => true], ['key' => 'total', 'label' => 'Total']]"
        :rows="[['id' => 1, 'number' => 'INV-001', 'total' => '$120.00']]"
    />
    ```
  - Column definition `rowHeader: true` renders body cells as `<th scope="row">`.
  - `loading` prop sets `aria-busy="true"` and announces via visually hidden `role="status"`.
- **`date-range-picker` Announcements**:
  - Persistent polite live region announcing completed date range selections.
  - Configurable `labels.rangeAnnouncement` template string (defaults to `'{start} to {end}'`).
- **Icon Registry Additions**:
  - Registered icons: `menu`, `panel-left`, `panel-left-close`, `panel-left-open`, `panel-right`, `panel-right-close`, `panel-right-open`.

---

## 6. Troubleshooting & Common Errors

### 1. `[Lyra FileUpload] The server-authored root requires a unique id.`
- **Cause**: `@lyra-ds/alpine` 1.x `lyraFileUpload` requires every upload instance to have an `id` attribute on its root element to bind the file input and zone labels.
- **Fix**: Supply an explicit `id` on `<lyra:file-upload id="my-uploader" ... />`, or do not override the auto-generated `lyra-upload-<uniqid>` ID.

### 2. Tabs showing all panels stacked or tablist missing
- **Cause**: Version mismatch between `lyra-ds/blade` and `@lyra-ds/alpine`. Blade 1.0 emits progressive-enhancement fallback anchors and `<section>` panels expecting Alpine 1.x `lyraTabs`. Older Alpine 0.x plugins fail structure validation and do not enhance the markup.
- **Fix**: Upgrade `@lyra-ds/alpine` to `^1.2` and `@lyra-ds/styles` to `^1.1`. Ensure Alpine is initialized with `Alpine.plugin(lyraAlpine)`.

### 3. `InvalidArgumentException: <lyra:tabs> owns x-data; wrap it in a parent element that owns your state.`
- **Cause**: You passed an `x-data` attribute directly to an interactive Lyra Blade component.
- **Fix**: Move `x-data` to an enclosing `<div>` or container element as detailed in the [Owned-Root Policy](#4-owned-root-x-data-policy).

### 4. Navigation links with `href` not triggering JavaScript click handlers
- **Cause**: In Blade 1.0, items in `app-sidebar`, `sidebar-group`, `bottom-nav`, and `workspace-switcher` that define an `href` render as real `<a>` tags and navigate natively on click.
- **Fix**: If an item is intended to trigger a modal or perform a client-side action without navigating, omit `href` and rely on `id` and the `lyra:select` / `lyra:change` events.
