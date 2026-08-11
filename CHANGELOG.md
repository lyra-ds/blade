# Changelog

## [0.10.0](https://github.com/lyra-ds/blade/compare/v0.9.0...v0.10.0) (2026-08-11)


### Features

* gera docs/api.json com props, uso e html renderizado dos componentes ([2e8faf4](https://github.com/lyra-ds/blade/commit/2e8faf4e41422af663b6ff04a8f2461d69166350))
* identifica o binding Alpine de cada componente no artefato de API ([541ed63](https://github.com/lyra-ds/blade/commit/541ed63d2214f22fbec1c6de4e985abf84aeeb66))


### Bug Fixes

* **test:** frescor do artefato vigia conteúdo, não o carimbo de versão ([19c2ba7](https://github.com/lyra-ds/blade/commit/19c2ba704f0836ff0b0f44ec6e19b155c67b48f2))


### Code Refactoring

* extrai o parser de [@props](https://github.com/props) para uma classe própria ([5d387e5](https://github.com/lyra-ds/blade/commit/5d387e565f79505489c4fdf5df232940c1f3550b))

## [0.9.0](https://github.com/lyra-ds/blade/compare/v0.8.1...v0.9.0) (2026-08-10)


### Features

* **theme:** accept storage key in theme script ([fe5ff29](https://github.com/lyra-ds/blade/commit/fe5ff29da20d565eab4e1cec3cfc018a80da4d2b))
* **time-picker:** add configurable time options label ([7e5f58a](https://github.com/lyra-ds/blade/commit/7e5f58ab47457c4c479cc27462b5f5f64afbcbb3))

## [0.8.1](https://github.com/lyra-ds/blade/compare/v0.8.0...v0.8.1) (2026-08-10)


### Bug Fixes

* **weekly-schedule-editor:** removing a time range no longer throws ([22f4c56](https://github.com/lyra-ds/blade/commit/22f4c5620d30573a48fadc3daf7dfb275bc32b2b))


### Miscellaneous Chores

* record release 0.8.0; phase 2 shipped end to end (batuta) ([e9ec962](https://github.com/lyra-ds/blade/commit/e9ec962608ec992b604a45021d7a82cb41050ff4))
* record task 35 (x-on standardisation) cycle (batuta) ([296d012](https://github.com/lyra-ds/blade/commit/296d0122f16976501ebb0eca4529ecdbf290c7da))
* record task 36 (removeRange console errors) cycle (batuta) ([7ebd66e](https://github.com/lyra-ds/blade/commit/7ebd66e97407fe47649f1b678465adc14b91ef4f))

## [0.8.0](https://github.com/lyra-ds/blade/compare/v0.7.0...v0.8.0) (2026-08-10)


### Features

* add data table component ([0dc7648](https://github.com/lyra-ds/blade/commit/0dc76487f88da027aed96390447816f514599561))
* add recurrence selector component ([836ca74](https://github.com/lyra-ds/blade/commit/836ca74f8a45784db5c5f5f7602bccbc6cdbb9e7))
* add slot picker component ([a9dd9f5](https://github.com/lyra-ds/blade/commit/a9dd9f5cb75174b2d6b8548db7eace3b8d19521f))
* add weekly schedule editor component ([ba7fc30](https://github.com/lyra-ds/blade/commit/ba7fc3006602c0d400c89a274273debe7210f1a6))


### Miscellaneous Chores

* close phase 2 — waves B–F delivered, catalogue complete ([c94832b](https://github.com/lyra-ds/blade/commit/c94832bb1507d81c97ae497c22a9f095ed028b30))
* handoff da pausa após a onda E (batuta) ([f924fea](https://github.com/lyra-ds/blade/commit/f924fea3d5f9926be493f368dbd9ca674f8f2ab4))
* record release 0.7.0; onda E shipped end to end (batuta) ([8d189a9](https://github.com/lyra-ds/blade/commit/8d189a959c4a46368d045994c8cb9b093d1b7f1c))
* record task 29 (data-table) cycle (batuta) ([1183192](https://github.com/lyra-ds/blade/commit/1183192ca3ee8a170e1ca6673e366799faad655e))
* record task 30 (recurrence-selector) cycle (batuta) ([817acb0](https://github.com/lyra-ds/blade/commit/817acb06e12c51dc7365670b20536f4a6f5bf77e))
* record task 31 (slot-picker) cycle (batuta) ([db34490](https://github.com/lyra-ds/blade/commit/db34490d334ed84a3c820921a115250e37b8785f))
* record task 32 (weekly-schedule-editor) cycle (batuta) ([978f0ab](https://github.com/lyra-ds/blade/commit/978f0ab5767c5bbb04cc7226d43e450f257ae0bf))
* record task 33.1 (demo gallery, wave F) cycle (batuta) ([7cee042](https://github.com/lyra-ds/blade/commit/7cee0421747d1012a7072c3e3ade48fb82b1d5c7))
* record task 33.2 (README catalogue refresh) cycle (batuta) ([8ce905b](https://github.com/lyra-ds/blade/commit/8ce905bc5c9d1f95d427483a3310eecf80144563))

## [0.7.0](https://github.com/lyra-ds/blade/compare/v0.6.0...v0.7.0) (2026-08-09)


### Features

* add command palette component ([ddd59c6](https://github.com/lyra-ds/blade/commit/ddd59c610b46a1d920f977b770cfbf4db990a459))
* **combobox:** add searchable select component ([5da51c8](https://github.com/lyra-ds/blade/commit/5da51c80e48d1dfc2108ee3db4037171f1b6d1fe))
* **time-zone-picker:** add Blade component ([287d666](https://github.com/lyra-ds/blade/commit/287d6667849fb109b59666be02734ea711a54605))


### Miscellaneous Chores

* fix WORK.md — tasks 23 e 24 estavam acima do cabeçalho Done (batuta) ([a63e4c1](https://github.com/lyra-ds/blade/commit/a63e4c1fc4256eaa2caca5245de719a8c6506455))
* ondas E e F verificadas destravadas no dist 0.3.0 publicado (batuta) ([2ce073b](https://github.com/lyra-ds/blade/commit/2ce073b5bc2fd0bfa42089922ceaa233fd15844f))
* record demo gallery for wave E (batuta) ([a0abcf3](https://github.com/lyra-ds/blade/commit/a0abcf327564c9dc69a9117279fa5d666c383285))
* record release 0.6.0; onda D shipped end to end (batuta) ([a5a0701](https://github.com/lyra-ds/blade/commit/a5a0701e4814902d139deb5a62c561c0cceee6b5))
* record task 25 (combobox) cycle (batuta) ([8eeb338](https://github.com/lyra-ds/blade/commit/8eeb338a0b5b6035ff95c359a665030703afdd85))
* record task 26 (time-zone-picker) cycle (batuta) ([773b38a](https://github.com/lyra-ds/blade/commit/773b38ab326608e62545410c2743912afb04dd4f))
* record task 27 (command-palette) cycle (batuta) ([611f22e](https://github.com/lyra-ds/blade/commit/611f22e1ac6ee4f142cea08f8fb8462f19e1d102))

## [0.6.0](https://github.com/lyra-ds/blade/compare/v0.5.0...v0.6.0) (2026-08-09)


### Features

* add app sidebar component ([394078f](https://github.com/lyra-ds/blade/commit/394078f028beeb5a3aa81be571e27af259e8f78f))
* add calendar component ([457224f](https://github.com/lyra-ds/blade/commit/457224f10c10d021971b25bede322e4e9a4d9a1d))
* add date picker component ([285a474](https://github.com/lyra-ds/blade/commit/285a474936a6909a21e7621e71a0aadffd875eb0))
* add date range picker component ([c353914](https://github.com/lyra-ds/blade/commit/c353914f91cd30eb20e5985950e05b78f394003c))
* **time-picker:** add responsive time picker ([ff40a35](https://github.com/lyra-ds/blade/commit/ff40a35bbb91aa99b6e5733eb7f56b822f2c8c7b))
* ToastStack dynamic queue wiring with tone icons and close button ([186d30b](https://github.com/lyra-ds/blade/commit/186d30baf897377c3701556bf3863a8920522771))


### Miscellaneous Chores

* destrava task 7 (lyraAppSidebar publicado no alpine 0.3.0) e ignora .playwright-mcp (batuta) ([f18d33d](https://github.com/lyra-ds/blade/commit/f18d33d84107311bfe3753a0730e30956465e2df))
* record demo toasts showcase cycle (batuta) ([9e89264](https://github.com/lyra-ds/blade/commit/9e89264b76c09e832a868edc731bc90bae87d4e9))
* record release 0.5.0; onda C shipped end to end (batuta) ([51bb286](https://github.com/lyra-ds/blade/commit/51bb286628feaa4cd14742b5ef8f13a7f0df308e))
* record task 19 (calendar) cycle (batuta) ([b586dca](https://github.com/lyra-ds/blade/commit/b586dca6290601c88ed716172c87bf89892e7114))
* record task 20 (date-picker) cycle and close the styles backlog item (batuta) ([e95f06c](https://github.com/lyra-ds/blade/commit/e95f06cc1e352368226d1e1cd1a09bb6f3d6b4cd))
* record task 21 (date-range-picker) cycle (batuta) ([07ceb34](https://github.com/lyra-ds/blade/commit/07ceb34af151f7dbfbfe94cda556c53347cc1536))
* record task 22 (time-picker) cycle and register the lyraTimePicker upstream candidate (batuta) ([f47b4e8](https://github.com/lyra-ds/blade/commit/f47b4e813222887fbeb8ef292aeef60a58af7a2c))
* record task 23 (demo dates gallery) cycle (batuta) ([e1afc59](https://github.com/lyra-ds/blade/commit/e1afc59d99568e941ee43c645fe7e7142b4698ff))
* record task 7 (app-sidebar) cycle (batuta) ([29f4aa0](https://github.com/lyra-ds/blade/commit/29f4aa0f7ded48ee458b25c39e13a9892e9211d1))
* record ToastStack dynamic queue cycle (batuta) ([24e0d42](https://github.com/lyra-ds/blade/commit/24e0d42950ebc12f63c7ebde64fed0cd010ecd22))
* registra fix candidato do styles (segmented active vs hover) (batuta) ([fe721a4](https://github.com/lyra-ds/blade/commit/fe721a4d9702d8d91384d2747450ebbaabafc3d7))

## [0.5.0](https://github.com/lyra-ds/blade/compare/v0.4.0...v0.5.0) (2026-08-09)


### Features

* [@lyra](https://github.com/lyra)ThemeScript directive with anti-flash inline script ([668a7de](https://github.com/lyra-ds/blade/commit/668a7de5cf3a16e44ecb9a9f80737cb13760db63))
* BottomSheet component, structural sibling of Dialog and Drawer ([b39dcbd](https://github.com/lyra-ds/blade/commit/b39dcbd004c25ac2936914df5b9798d6135e9867))
* FileManager component with served dual trees and client filter ([22ba8b2](https://github.com/lyra-ds/blade/commit/22ba8b2078b84c0ecd8a5817e23f9b91258f5eb9))
* FileUpload component with runtime item template ([bfa2f6b](https://github.com/lyra-ds/blade/commit/bfa2f6be8bf80e0243f630122823c364e7127b3f))
* TableOfContents component wired to the scroll-spy binding ([4d2cf18](https://github.com/lyra-ds/blade/commit/4d2cf189c47db6d5e3a94c593109a020a598eb0c))
* TimeInput component with served spinbutton semantics ([fd68ef2](https://github.com/lyra-ds/blade/commit/fd68ef210fc6251b44bbeaa271b5dc78ea01d5fe))


### Miscellaneous Chores

* fecha a decisão da task 16 — diretiva [@lyra](https://github.com/lyra)ThemeScript (batuta) ([5fd0210](https://github.com/lyra-ds/blade/commit/5fd02105b71bd8352c8153fccce4f5323d86fb93))
* record release 0.4.0; onda B shipped end to end (batuta) ([3cd875b](https://github.com/lyra-ds/blade/commit/3cd875b5da5ea18d7dcb7a6f2b3acadd2aabe87e))
* record task 11; task 12 delegated (batuta) ([7bb016e](https://github.com/lyra-ds/blade/commit/7bb016e72bb567b970fdcb2757d445547830962c))
* record task 12; task 13 delegated (batuta) ([40ff82e](https://github.com/lyra-ds/blade/commit/40ff82ee0075ec2c46103c55ef37a811765ae19d))
* record task 13; task 14 delegated (batuta) ([d6ef008](https://github.com/lyra-ds/blade/commit/d6ef008b6b1073ded66e0d34580c55c4902e3cc8))
* record task 14; task 15 delegated (batuta) ([e860bf2](https://github.com/lyra-ds/blade/commit/e860bf2191bbd219b050f604437da4fd0858cede))
* record task 15; task 16 delegated (batuta) ([6f0408f](https://github.com/lyra-ds/blade/commit/6f0408f2c38f5a1f3d41d6cbec28d980d72da4fc))
* record task 16; task 17 delegated (batuta) ([7e5b8ed](https://github.com/lyra-ds/blade/commit/7e5b8edf732e85e01850a028828453d0d7b6825c))
* record task 17 (batuta) ([e148d1a](https://github.com/lyra-ds/blade/commit/e148d1ab7df5a3dbe485ca2743ce9d377b78e608))

## [0.4.0](https://github.com/lyra-ds/blade/compare/v0.3.0...v0.4.0) (2026-08-09)


### Features

* add the CodeBlock component ([34457c0](https://github.com/lyra-ds/blade/commit/34457c07cc31c7517f94d03b18c66e11e8c13a65))
* add the CookieBanner component ([974b953](https://github.com/lyra-ds/blade/commit/974b9532d09497c2d76b894a8b06f6ab17eb4292))
* add the SegmentedControl component ([2541a2e](https://github.com/lyra-ds/blade/commit/2541a2e11ef8beed62365a5a94afb21f066d7572))
* add the SidebarGroup component ([549b264](https://github.com/lyra-ds/blade/commit/549b2643b8c4a17570fb9b32e7054d2a61dce5ea))
* add workspace switcher component ([1844f51](https://github.com/lyra-ds/blade/commit/1844f51522bb42eb656345fb135b2e970130ef17))
* CheckboxGroup component with native form name adaptation ([572993a](https://github.com/lyra-ds/blade/commit/572993a0f1ae88e4988f1c9724222bb32126b5a5))
* RadioGroup component with shared native name ([228e2d3](https://github.com/lyra-ds/blade/commit/228e2d39ca42369f783bb30548520bab46c7ad79))


### Bug Fixes

* accept numeric-string spacing/dimension props (gap, columns, height, width) ([#5](https://github.com/lyra-ds/blade/issues/5)) ([716c344](https://github.com/lyra-ds/blade/commit/716c34479e78597ee6fa90e366c7facf52995b75))


### Miscellaneous Chores

* **batuta:** fixa &lt;lyra:...&gt; como padrão da casa e a regra de atributos servidos ([65dea8b](https://github.com/lyra-ds/blade/commit/65dea8be11a7db3a78b5e09c376e82ea99810d50))
* **batuta:** handoff da pausa após fechar a onda B ([4f61876](https://github.com/lyra-ds/blade/commit/4f618762124da04f33ab9ecf630545b5d097335c))
* **batuta:** registra o ciclo do bump do demo para alpine 0.2.0 ([e332b29](https://github.com/lyra-ds/blade/commit/e332b290f9320d17dda231c6637471bc403d2030))
* **batuta:** registra o ciclo do CodeBlock ([27159a1](https://github.com/lyra-ds/blade/commit/27159a150998c6aa1a6a55f491b827c343249d0d))
* **batuta:** registra o ciclo do CookieBanner ([d05ffaf](https://github.com/lyra-ds/blade/commit/d05ffaffc7f2004ec002b1cd4d4d181f0069e2bc))
* **batuta:** registra o ciclo do SegmentedControl ([b8ea27b](https://github.com/lyra-ds/blade/commit/b8ea27bc9167672e70c06225b24274199d1f73d8))
* **batuta:** registra o ciclo do SidebarGroup ([a77435a](https://github.com/lyra-ds/blade/commit/a77435a3de1eb46e31a9fc03148b8d0cf1f5ffab))
* **batuta:** registra o ciclo do WorkspaceSwitcher e fecha a onda B ([52e0a68](https://github.com/lyra-ds/blade/commit/52e0a682b851bd279719e230deafebe295f5092a))
* consume resume handoff (batuta) ([5b403fa](https://github.com/lyra-ds/blade/commit/5b403fa4bf255cd41ceb95132cc97531c17eb106))
* record 0.3.0 release (batuta) ([9955953](https://github.com/lyra-ds/blade/commit/9955953b499391895ccf08ed5f668db3e7ee1a93))
* record CheckboxGroup cycle; RadioGroup delegated (batuta) ([4aa9042](https://github.com/lyra-ds/blade/commit/4aa90423c7933fafbcfe501087705c45294aed4f))
* record demo gallery cycle (batuta) ([54fce81](https://github.com/lyra-ds/blade/commit/54fce81a830f6163f5097c7907756f34740d1b26))
* record demo gallery sweep cycle (batuta) ([0990ced](https://github.com/lyra-ds/blade/commit/0990ceda9a97fe213d25ce40bf9bac61a0507f2c))
* record RadioGroup cycle; onda B statics complete (batuta) ([2b4b4d2](https://github.com/lyra-ds/blade/commit/2b4b4d2cc74193b2206001a709568053152076e8))
* record stack/grid numeric cycle (superseded by PR [#5](https://github.com/lyra-ds/blade/issues/5)) (batuta) ([861ce87](https://github.com/lyra-ds/blade/commit/861ce87d377251104a87f6bd96ab46899b7d9abc))

## [0.3.0](https://github.com/lyra-ds/blade/compare/v0.2.0...v0.3.0) (2026-08-08)


### Features

* Accordion component with Alpine bindings ([d0aa40f](https://github.com/lyra-ds/blade/commit/d0aa40fa46d253a9c9deb5d1ec577d60cd1a940a))
* Dialog component with Alpine bindings ([d3a0fa3](https://github.com/lyra-ds/blade/commit/d3a0fa3861f7d7dc3564e31543457f36b32d666b))
* Drawer component with Alpine bindings ([9e94cb2](https://github.com/lyra-ds/blade/commit/9e94cb2dcb0f1e1cf55dcfb1f1c8d6a97b6f2354))
* Dropdown component with Alpine bindings (first interactive) ([56f130d](https://github.com/lyra-ds/blade/commit/56f130da60e62729514813024098f9ac60f5cad2))
* Popover component with Alpine bindings ([f3284a3](https://github.com/lyra-ds/blade/commit/f3284a34debfaa46994ee92ef8c42b4cbed47cf3))
* Tabs component with Alpine bindings ([f0ebefd](https://github.com/lyra-ds/blade/commit/f0ebefdd09f7377f0ee8a2fe2734ab557a8239a2))
* Tooltip component with Alpine bindings ([66d8f3a](https://github.com/lyra-ds/blade/commit/66d8f3a8e33dd5cc1d6bb28d6b6bb2d425de1dd2))


### Miscellaneous Chores

* record Accordion cycle (batuta) ([0992bda](https://github.com/lyra-ds/blade/commit/0992bda5e7adb1dacf33261dff8094953137f74a))
* record Alpine design closure and cross-repo handoff in backlog ([5a7a592](https://github.com/lyra-ds/blade/commit/5a7a5927a695b5e44f8e3c5bfee4dea95ff45b3d))
* record blade-demo cycle (batuta) ([ca96e5c](https://github.com/lyra-ds/blade/commit/ca96e5cd5d0007b3412b3b1d9c47b2ae6c7910f5))
* record Dialog cycle (batuta) ([42fd70f](https://github.com/lyra-ds/blade/commit/42fd70f980a2cd08ccb220e2821de3bed4b5badb))
* record Drawer cycle (batuta) ([6e8b4a0](https://github.com/lyra-ds/blade/commit/6e8b4a02190174f83259102943b8d4157fde0dc3))
* record Livewire groundwork and Dropdown cycles (batuta) ([e879e95](https://github.com/lyra-ds/blade/commit/e879e9501fb02295f886701fc39e81c6f7d4ceb4))
* record opencode trivial-lane hang pattern (batuta routing) ([7946ae3](https://github.com/lyra-ds/blade/commit/7946ae34d5b220db260c5af7f7f3fdf8982115db))
* record Popover cycle; core wave complete (batuta) ([f77aa63](https://github.com/lyra-ds/blade/commit/f77aa63fdd72459426b47ea0fb82261c57a88a47))
* record README closure; phase-2 core wave shipped end to end (batuta) ([953042b](https://github.com/lyra-ds/blade/commit/953042bc4d867d6e9f96f52129d2ba0955690d36))
* record Tabs cycle (batuta) ([3c037f9](https://github.com/lyra-ds/blade/commit/3c037f9b510be5265562d7cb785411702ddde1cc))
* record Tooltip cycle (batuta) ([a485b87](https://github.com/lyra-ds/blade/commit/a485b87a7202a6f1533c41ddce4761489a8e958e))

## [0.2.0](https://github.com/lyra-ds/blade/compare/v0.1.0...v0.2.0) (2026-08-07)


### Features

* ActionBar component with class-emission parity tests ([fb78772](https://github.com/lyra-ds/blade/commit/fb78772a1d95ea5bf308ed3f5a41756c4541e16c))
* BottomNav component with class-emission parity tests ([8200b7c](https://github.com/lyra-ds/blade/commit/8200b7c9fe63c26a2a8d2cde91bc3bf64a80cbc1))
* Brand component with class-emission parity tests ([9a1385f](https://github.com/lyra-ds/blade/commit/9a1385f3013b3c2f135463dfae7b24cb5f31d61f))
* factor exact class products in the Boost guidelines ([1151556](https://github.com/lyra-ds/blade/commit/115155668bc982723b0392eab5e2f1757bafbe7c))
* Footer component with class-emission parity tests ([a555b25](https://github.com/lyra-ds/blade/commit/a555b251bb4f257b7b5810464720313de25d9904))
* Icon component backed by blade-lucide-icons ([91ef07b](https://github.com/lyra-ds/blade/commit/91ef07ba36a47285e87378c412510f4147fd8abd))
* Navbar component with class-emission parity tests ([a7b669b](https://github.com/lyra-ds/blade/commit/a7b669be0acfe11f6d85d0a561c61ca975b8d208))
* NavLink component with class-emission parity tests ([1e70ef5](https://github.com/lyra-ds/blade/commit/1e70ef5a393efaf5d607f2cc849560fb91d651f1))
* PageHeader component with class-emission parity tests ([dbf1732](https://github.com/lyra-ds/blade/commit/dbf17323bd695d0ac97aa03eff3ced9bd0c0689e))
* PersonCell component with class-emission parity tests ([26f5a45](https://github.com/lyra-ds/blade/commit/26f5a4503a188bd1a1418969fd57444736b74e40))
* SegmentedRing component with exact arc-algorithm port ([079e105](https://github.com/lyra-ds/blade/commit/079e105b755376a42bd309b80951b40186bb29c4))
* Shell component with class-emission parity tests ([237ac3f](https://github.com/lyra-ds/blade/commit/237ac3f74253e0b8a4902af9eb35c1eeee3bf05b))
* ship Boost AI guidelines generated from the package sources ([d9c5420](https://github.com/lyra-ds/blade/commit/d9c54203f126a67705a01eaa105d1dcf0514f4ab))
* short &lt;lyra:*&gt; component syntax as an alias of &lt;x-lyra::*&gt; ([497809a](https://github.com/lyra-ds/blade/commit/497809ab6182a87aaedcad493df90e25d073c11b))
* Stepper component with class-emission parity tests ([f5ea638](https://github.com/lyra-ds/blade/commit/f5ea63834502b05ae6578cd0418d1e681504f67f))
* Toast and ToastStack components with class-emission parity tests ([0bcd840](https://github.com/lyra-ds/blade/commit/0bcd840a8460a416798c5c31d7f988132b3ea93f))


### Bug Fixes

* let feat commits bump minor pre-1.0 (drop bump-patch-for-minor-pre-major) ([23a73e3](https://github.com/lyra-ds/blade/commit/23a73e3c6dade87971fe9f44924d78ee10107761))


### Miscellaneous Chores

* consume handoff and record resume (release PR now 0.2.0) ([e83b60e](https://github.com/lyra-ds/blade/commit/e83b60e259eeab1f032984109b652729a2d69826))
* keep pre-1.0 versioning (bump-minor-pre-major) ([0c365e1](https://github.com/lyra-ds/blade/commit/0c365e1840f458d3d5ff8ff96c96a3eaeb8d1212))
* plain v-tags for Packagist, drop first-release pin ([3c919d0](https://github.com/lyra-ds/blade/commit/3c919d03f693158e041ed54d9e00c7a1c6ea36f4))
* record action-bar cycle (WORK.md + run trail) ([6da7cd6](https://github.com/lyra-ds/blade/commit/6da7cd60312045554fce0c9160eb6ee8d7d0fc77))
* record boost-guidelines cycle (WORK.md + run trail) ([1a5f56f](https://github.com/lyra-ds/blade/commit/1a5f56f2a910463ef8e84331890c5945b9213953))
* record bottom-nav cycle (WORK.md + run trail) ([2fca67c](https://github.com/lyra-ds/blade/commit/2fca67c9608855e67cc31dac33cc3e4682b333a1))
* record brand cycle (WORK.md + run trail) ([347ddfd](https://github.com/lyra-ds/blade/commit/347ddfdc12327fbfeb2db80df211672ff9d52638))
* record compact-guidelines cycle; update research lane reliability log ([4551c40](https://github.com/lyra-ds/blade/commit/4551c40debff627775dfd3e2ee11927fd5b0bb1f))
* record footer cycle (WORK.md + run trail) ([5dd67af](https://github.com/lyra-ds/blade/commit/5dd67afddb96bb4a5a16b76cbd2e3572b61a8816))
* record icon cycle and wave-A completion; research lane reliability note ([e257221](https://github.com/lyra-ds/blade/commit/e257221a9afd3be82132ac4c780b08c728ce8aec))
* record nav-link cycle (WORK.md + run trail) ([be40294](https://github.com/lyra-ds/blade/commit/be4029407d0c7c8bca2055b832e1f41c8034a2a8))
* record navbar cycle (WORK.md + run trail) ([4846ec1](https://github.com/lyra-ds/blade/commit/4846ec1491595abaa9edb658b09528517f91954c))
* record page-header cycle (WORK.md + run trail) ([86ebfdd](https://github.com/lyra-ds/blade/commit/86ebfdd28ab1e015312cc4672042fdb0b22ce4f4))
* record person-cell cycle; close icon SVG decision (blade-lucide-icons) ([2957b90](https://github.com/lyra-ds/blade/commit/2957b90f7ba82070850eb2ffeef6cd35773a3f73))
* record segmented-ring cycle (WORK.md + run trail) ([46c1615](https://github.com/lyra-ds/blade/commit/46c161521126fa604e101698a4a42ef78d6296f5))
* record shell cycle (WORK.md + run trail) ([a4f941b](https://github.com/lyra-ds/blade/commit/a4f941b0cacbad2ca01b6adfea1d99d6f96db123))
* record short-syntax cycle (WORK.md + run trail) ([c092678](https://github.com/lyra-ds/blade/commit/c092678b1a3b255305fa1036ee8ec513d00005bd))
* record stepper cycle (WORK.md + run trail) ([d236ab7](https://github.com/lyra-ds/blade/commit/d236ab7ef6521c9d594674be6f920e36ae711000))
* record toast cycle (WORK.md + run trail) ([c1e32da](https://github.com/lyra-ds/blade/commit/c1e32da868d02933a5451c7eb381e8c6a5883247))
* session handoff (batuta:pause) ([e8d4bfb](https://github.com/lyra-ds/blade/commit/e8d4bfb0f6df96277027e8569b3c04fbcb214ae6))
* swap research lane to deepseek-v4-flash (user-confirmed) ([1183bd1](https://github.com/lyra-ds/blade/commit/1183bd1694f41c0f333b78442bbabbde182cb950))

## 0.1.0 (2026-08-06)


### ⚠ BREAKING CHANGES

* support Laravel 12/13, drop EOL Laravel 11

### Features

* Blade alert component with class-emission parity tests ([e33b29c](https://github.com/lyra-ds/blade/commit/e33b29ccac2a96c0016b5086e1c9af78a55af391))
* Blade avatar component with class-emission parity tests ([6512273](https://github.com/lyra-ds/blade/commit/6512273f1f1626636c45929dee6a330be1c3c53e))
* Blade badge component with class-emission parity tests ([aa0a328](https://github.com/lyra-ds/blade/commit/aa0a32891e87d130baf097f8b824a93c1358cf2b))
* Blade breadcrumb component with class-emission parity tests ([ef84995](https://github.com/lyra-ds/blade/commit/ef8499569b2d87fec558aea13a461de5549bd2d9))
* Blade button component with class-emission parity tests ([67920bb](https://github.com/lyra-ds/blade/commit/67920bb1e6abc6dc8facea101fc150b62d9ba99f))
* Blade card component with class-emission parity tests ([e5a61a3](https://github.com/lyra-ds/blade/commit/e5a61a337089fe9f74e8a80f6a80f28a1d9de266))
* Blade checkbox component with class-emission parity tests ([68c7c39](https://github.com/lyra-ds/blade/commit/68c7c39fc788764ec40314f85a0cd2a0aa4e12c5))
* Blade container component with class-emission parity tests ([d071c78](https://github.com/lyra-ds/blade/commit/d071c782ef492c472ecbb971539a989c93de6055))
* Blade empty-state component with class-emission parity tests ([ce7b572](https://github.com/lyra-ds/blade/commit/ce7b572b36ad452e8ad49a247065cbbc6b0f9c83))
* Blade fieldset component with class-emission parity tests ([d0fce4a](https://github.com/lyra-ds/blade/commit/d0fce4a68f59cc0b12882f69f2baa2b3928e9192))
* Blade form-row component with class-emission parity tests ([1f67265](https://github.com/lyra-ds/blade/commit/1f67265a4492861f8b1c116a1fc819756a19feaf))
* Blade grid component with class-emission parity tests ([5143108](https://github.com/lyra-ds/blade/commit/5143108b428e274f2080b35e8ea369a7b93217e4))
* Blade icon-button component with class-emission parity tests ([0937e27](https://github.com/lyra-ds/blade/commit/0937e271acf8d6260520b54b7aab8c4a851e89e4))
* Blade input component with class-emission parity tests ([ff61ee2](https://github.com/lyra-ds/blade/commit/ff61ee2268526a600938cca3ab3fa67e81b7c4e7))
* Blade pagination component with class-emission parity tests ([a082a51](https://github.com/lyra-ds/blade/commit/a082a5143df39e314e09499d684c3be08ac35b55))
* Blade progress component with class-emission parity tests ([9515c9c](https://github.com/lyra-ds/blade/commit/9515c9c453808ce45b234524adf15151821aa039))
* Blade radio component with class-emission parity tests ([005eb9d](https://github.com/lyra-ds/blade/commit/005eb9d7541ee147d7e65c3a4ba0a4a54f330655))
* Blade select component with class-emission parity tests ([5bb9522](https://github.com/lyra-ds/blade/commit/5bb9522b62a24621a4f303a1cbf34762dcb4a17b))
* Blade separator component with class-emission parity tests ([970fda1](https://github.com/lyra-ds/blade/commit/970fda11712b471f29c3143bf89b35d85a19a3d4))
* Blade skeleton component with class-emission parity tests ([723ad24](https://github.com/lyra-ds/blade/commit/723ad24587ecc75feb959fcc40a4c39d5419d208))
* Blade spinner component with class-emission parity tests ([a8e4624](https://github.com/lyra-ds/blade/commit/a8e46248429c63fcabf4d6d1309b914d6a9c6a3a))
* Blade stack component with class-emission parity tests ([06a607e](https://github.com/lyra-ds/blade/commit/06a607e9d8552b959f7079e8797658f86e0876d1))
* Blade stat component with class-emission parity tests ([78236f9](https://github.com/lyra-ds/blade/commit/78236f9dfa4b5fe05ba1c14eedcab3efb0d4daa9))
* Blade switch component with class-emission parity tests ([5b5f14e](https://github.com/lyra-ds/blade/commit/5b5f14e88ad8a6a35f652df03548349587a5aeaa))
* Blade table component with class-emission parity tests ([9eaa7c0](https://github.com/lyra-ds/blade/commit/9eaa7c0d2f9747d2c4b14d0c4cdf0dbad7e9c73f))
* Blade tag component with class-emission parity tests ([85b10a7](https://github.com/lyra-ds/blade/commit/85b10a7f58157ce889a5d8662ea3654d2c9aa773))
* Blade textarea component with class-emission parity tests ([120abd7](https://github.com/lyra-ds/blade/commit/120abd778eceb1b2b5ed375eb7da7c4a74f29e8b))
* package skeleton (service provider, Pest, Pint, CI, docs) ([8dda105](https://github.com/lyra-ds/blade/commit/8dda1057d04dc97b2f8bf73119c14544a5da47e5))
* support Laravel 12/13, drop EOL Laravel 11 ([dc6d971](https://github.com/lyra-ds/blade/commit/dc6d9718127581aecc5c71d4bbdbff720a6b7b96))


### Miscellaneous Chores

* batuta onboarding (profile, routing, work log) ([eb5ac25](https://github.com/lyra-ds/blade/commit/eb5ac250e4690b997a309ed72d0d69f685a473b1))
* close out Alert delegation (work log, run trail) ([1bacce2](https://github.com/lyra-ds/blade/commit/1bacce24d0b158168e767c10c77371507ea9a69d))
* close out Avatar delegation (work log, run trail) ([53a7c6e](https://github.com/lyra-ds/blade/commit/53a7c6e17b2646962b74374f18a4d81632a174a5))
* close out Badge delegation (work log, run trail) ([d5b308c](https://github.com/lyra-ds/blade/commit/d5b308cac21dc8b996dd385ec73b171070e7cd28))
* close out Button delegation (work log, routing confirmation) ([48b221d](https://github.com/lyra-ds/blade/commit/48b221dafc7d15cba4c90c46c6e243d4ad403c72))
* close out Card delegation (work log, run trail) ([e075b8e](https://github.com/lyra-ds/blade/commit/e075b8e3dd61bc70b6710f41d97671da6bd3292f))
* close out Container delegation (work log, run trail) ([01a5985](https://github.com/lyra-ds/blade/commit/01a5985001be927abb25929ec005ce001cef8ca5))
* close out EmptyState delegation (work log, run trail) ([f37a1b1](https://github.com/lyra-ds/blade/commit/f37a1b1d5d87d571aac621a21d31039fc6cee1bb))
* close out IconButton delegation (work log, run trail) ([627749e](https://github.com/lyra-ds/blade/commit/627749e0e03c6bf967b1de05e21d303a55a41a6b))
* close out Progress delegation (work log, run trail) ([7e0a8f4](https://github.com/lyra-ds/blade/commit/7e0a8f42e51b4f94739a121a4bcfc5f89a2c2b0d))
* close out Separator delegation (work log, run trail) ([8e78e22](https://github.com/lyra-ds/blade/commit/8e78e225163bf083a6daa143710ffbfee4a45901))
* close out skeleton delegation (work log, routing re-confirmation, ignore .compozy) ([bb23666](https://github.com/lyra-ds/blade/commit/bb2366642cb581fa44a48c1f2cb130eddbda7e1c))
* close out Skeleton delegation (work log, run trail) ([32ae81a](https://github.com/lyra-ds/blade/commit/32ae81aae2e68daed514b15e9940097e91421509))
* close out Spinner delegation (work log, run trail) ([043e0f0](https://github.com/lyra-ds/blade/commit/043e0f0b349eb60f2923ccdc67b0a9ab08fdaf17))
* close out Stat delegation (work log, run trail) ([74d6c4c](https://github.com/lyra-ds/blade/commit/74d6c4c08b088c1d81c9e3ea27122eb609cb4c17))
* close out Tag delegation (work log, run trail) ([f180be6](https://github.com/lyra-ds/blade/commit/f180be616ffc5593497409f5993f9d4645ee2f2e))
* close out wave 4 (Stack/Grid work log, run briefs) ([c65459e](https://github.com/lyra-ds/blade/commit/c65459ec4a984993d3ece4ce735f6f783ccb3668))
* close out wave 5 (work log) ([778f243](https://github.com/lyra-ds/blade/commit/778f24330d74fdece2b18f26bb8523744aad5d62))
* close out wave 6 — phase 1 complete (work log) ([911c33d](https://github.com/lyra-ds/blade/commit/911c33d05128c0e4589960e8baf7bebc4ed96bb5))
* correct Container executor attribution (Sonnet 5, not codex) ([66b6b8c](https://github.com/lyra-ds/blade/commit/66b6b8c740158dda616bc4027c48cdb80fc7565f))
* fix main-repo path in batuta profile (~/Projects/lyra-ds/lyra) ([f75ef59](https://github.com/lyra-ds/blade/commit/f75ef59ab3e6113d98949cbcdadec79ffa81af53))
* record Button run trail and brief ([471aa63](https://github.com/lyra-ds/blade/commit/471aa63802b3c626b43c102fe210e22a2124e8ee))
* record post-phase-1 milestones (repo, README, Laravel 12/13 CI green) ([c0b0e98](https://github.com/lyra-ds/blade/commit/c0b0e986ca308b0f48c48559b1314707f0dd4288))
* record README run brief ([3dda753](https://github.com/lyra-ds/blade/commit/3dda75394a0ff2d75d82b4d06a7bb4636153b5ab))
* update profile CI matrix (L12/13), record laravel13 brief ([143a5fd](https://github.com/lyra-ds/blade/commit/143a5fdeba6b28272075cffedda4818afaa8fde1))
* wave-5 briefs (8 static form components) ([bb51115](https://github.com/lyra-ds/blade/commit/bb511152ffadffb0e37d79a4dd99a4fabff3aeac))
* wave-6 briefs (Breadcrumb, Pagination links, Table) ([0f0571c](https://github.com/lyra-ds/blade/commit/0f0571c146b17d4900c42163b56f525269bbec8c))
