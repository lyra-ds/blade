# Artefato de API para a documentação — Frente A Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fazer o `lyra-ds/blade` publicar, a cada release, um `docs/api.json` com props, snippet de uso e HTML renderizado dos 72 componentes — o artefato que o site `lyra-ds.dev` consome para acender a aba Blade.

**Architecture:** O parser de `@props` que hoje é privado do `BoostGuidelinesGenerator` vira uma classe própria, reusada por um segundo gerador. Cada componente ganha um snippet de uso curado em `resources/docs-examples/`, **compilado e renderizado pela suíte Pest** — é isso que impede o snippet de apodrecer. O `bin/generate-docs-api` boota uma app Laravel via Testbench (verificado: renderiza `<x-lyra::button>` fora do PHPUnit) para embutir o HTML real no artefato. Um teste de frescor, no molde do `BoostGuidelinesTest`, deixa a suíte vermelha se alguém mexer nos componentes sem regenerar.

**Tech Stack:** PHP 8.3/8.4, Laravel 12/13, Pest 3/4, Orchestra Testbench 10/11, Pint, release-please, GitHub Actions.

**Escopo:** só o repositório `lyra-ds/blade`. O consumo do artefato acontece na Frente B (`lyra-ds/lyra`, tasks 11–12 do plano `2026-08-10-docs-multi-stack-frente-b.md`).

**Spec:** `lyra-ds/lyra:docs/superpowers/specs/2026-08-10-documentacao-multi-stack-design.md`, §5.

## Global Constraints

- **Este pacote nunca embarca CSS nem inventa API.** O artefato descreve o que existe; não é lugar de estender contrato.
- **Um snippet que não compila é pior que snippet nenhum** — a mesma regra que já vale para props aqui. Todo exemplo é renderizado sob teste.
- **Artefato gerado é commitado** e protegido por teste de frescor, como `resources/boost/guidelines/lyra-blade.md`.
- **A versão sai de `.release-please-manifest.json`** (hoje `{".":"0.9.0"}`) — fonte única, nunca digitada duas vezes.
- **Pint é o style gate:** `vendor/bin/pint` antes de cada commit; o CI roda `vendor/bin/pint --test`.
- **Suíte inteira verde a cada commit:** `vendor/bin/pest` (hoje 1149 testes, 6365 assertions). Registre o número novo em cada passo que adiciona testes.
- **Contrato do `api.json`** (a Frente B depende dele literalmente):

```jsonc
{
  "version": "0.9.0",
  "components": [
    {
      "slug": "dropdown",
      "usage": "<lyra:dropdown …>…</lyra:dropdown>",   // conteúdo de resources/docs-examples/dropdown.blade.php
      "html": "<div class=\"lyra-dropdown\" …>…</div>", // render real do snippet acima
      "binding": "lyraDropdown",                        // null quando o componente é estático
      "props": [
        { "name": "align", "default": "'start'", "required": false, "values": ["start", "end"] }
      ]
    }
  ]
}
```

  `components` é ordenado por `slug`. `values` sai das fixtures de class-emission quando existirem, e é `[]` quando não houver observação — nunca inventado.

## File Structure

**Novos**
| Arquivo | Responsabilidade |
|---|---|
| `src/BladePropParser.php` | Lê o `@props` de um template e devolve os props estruturados |
| `src/DocsApiGenerator.php` | Monta o `api.json` a partir dos componentes, exemplos e fixtures |
| `bin/generate-docs-api` | Boota o Testbench, renderiza os exemplos, escreve `docs/api.json` |
| `resources/docs-examples/<slug>.blade.php` | 72 snippets de uso canônico |
| `docs/api.json` | Artefato gerado, commitado |
| `tests/Feature/DocsExamplesTest.php` | Todo componente tem exemplo, e todo exemplo renderiza |
| `tests/Feature/DocsApiTest.php` | Frescor e forma do artefato |

**Modificados**
| Arquivo | Mudança |
|---|---|
| `src/BoostGuidelinesGenerator.php` | Passa a delegar o parsing ao `BladePropParser` |
| `.github/workflows/release.yml` | Anexa `api.json` ao release criado |
| `README.md` | Seção descrevendo o artefato e seu contrato |
| `AGENTS.md` | Regra: componente novo exige exemplo + regeneração |

---

### Task 1: Extrair o parser de `@props`

`parseProps` e seus auxiliares são privados do `BoostGuidelinesGenerator`. O novo gerador precisa exatamente deles. Movimentação pura: o teste de frescor das guidelines é a prova — a saída tem que continuar idêntica byte a byte.

**Files:**
- Create: `src/BladePropParser.php`
- Modify: `src/BoostGuidelinesGenerator.php`
- Create: `tests/Feature/BladePropParserTest.php`

**Interfaces:**
- Produces: `LyraDs\Blade\BladePropParser` com o método público

```php
/** @return list<array{name: string, hasDefault: bool, default: string, valueKnown: bool, value: mixed}> */
public function parse(string $template, string $componentPath): array
```

  As tasks 4 e 5 consomem.

- [ ] **Step 1: Fixe o baseline**

```bash
cd /home/franciscpd/Projects/lyra-ds/blade
vendor/bin/pest
```

Esperado: tudo verde (1149 testes). Anote o número; ele é a referência das próximas tasks. Se já houver vermelho, pare e resolva antes de mover código.

- [ ] **Step 2: Escreva o teste do parser novo**

Create `tests/Feature/BladePropParserTest.php`:

```php
<?php

use LyraDs\Blade\BladePropParser;

it('reads names, defaults and required props from a @props directive', function (): void {
    $props = (new BladePropParser)->parse(<<<'BLADE'
        @props([
            'label',
            'variant' => 'primary',
            'loading' => false,
        ])
        <div></div>
        BLADE, 'sample.blade.php');

    expect($props)->toHaveCount(3)
        ->and($props[0]['name'])->toBe('label')
        ->and($props[0]['hasDefault'])->toBeFalse()
        ->and($props[1]['name'])->toBe('variant')
        ->and($props[1]['value'])->toBe('primary')
        ->and($props[2]['value'])->toBeFalse();
});

it('ignores a @props directive that only appears inside a Blade comment', function (): void {
    $props = (new BladePropParser)->parse(<<<'BLADE'
        {{-- @props(['ghost' => true]) --}}
        @props(['real' => 1])
        <div></div>
        BLADE, 'sample.blade.php');

    expect($props)->toHaveCount(1)->and($props[0]['name'])->toBe('real');
});

it('refuses a template with two @props directives', function (): void {
    expect(fn (): array => (new BladePropParser)->parse(
        "@props(['a' => 1])\n@props(['b' => 2])\n<div></div>",
        'sample.blade.php',
    ))->toThrow(RuntimeException::class);
});

it('returns no props when the template has no directive', function (): void {
    expect((new BladePropParser)->parse('<div></div>', 'sample.blade.php'))->toBe([]);
});
```

- [ ] **Step 3: Rode e veja falhar**

```bash
vendor/bin/pest --filter=BladePropParser
```

Esperado: `Class "LyraDs\Blade\BladePropParser" not found`.

- [ ] **Step 4: Mova o código**

Crie `src/BladePropParser.php` com `namespace LyraDs\Blade;`, classe `final class BladePropParser`, e mova **sem alterar a lógica** os métodos `parseProps` (renomeado para `parse`, agora `public`), `extractDirectiveExpression`, `splitTopLevel`, `splitProp`, `parseStringLiteral` e `parseLiteral` de `BoostGuidelinesGenerator`, todos como `private` exceto `parse`.

Em `BoostGuidelinesGenerator`, remova esses seis métodos e passe a delegar. Onde havia `$props = $this->parseProps($template, $componentPath);`, use:

```php
$props = (new BladePropParser)->parse($template, $componentPath);
```

Se algum outro método privado ainda chamar `parseStringLiteral` ou `parseLiteral`, torne-os públicos no `BladePropParser` e delegue do mesmo jeito — **não duplique** o corpo em dois arquivos.

- [ ] **Step 5: Prove que nada mudou**

```bash
vendor/bin/pest
vendor/bin/pint --test
```

Esperado: os 1149 testes anteriores continuam verdes — inclusive `keeps the committed Boost guidelines synchronized with the component sources`, que é a prova de que a saída não mudou — mais os 4 novos. Total: 1153.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add src/BladePropParser.php src/BoostGuidelinesGenerator.php tests/Feature/BladePropParserTest.php
git commit -m "refactor: extrai o parser de @props para uma classe própria"
```

---

### Task 2: O contrato dos exemplos, provado por teste

Escreva primeiro o teste que **exige** um exemplo por componente e que renderiza cada um. Ele vai falhar 72 vezes; três exemplos entram agora para fixar o padrão, e a task 3 fecha o resto.

**Files:**
- Create: `tests/Feature/DocsExamplesTest.php`
- Create: `resources/docs-examples/button.blade.php`
- Create: `resources/docs-examples/input.blade.php`
- Create: `resources/docs-examples/dropdown.blade.php`

**Interfaces:**
- Produces: a convenção `resources/docs-examples/<slug>.blade.php` — um arquivo por componente, contendo **uma** invocação da tag, na sintaxe curta `<lyra:slug>`, sem `<?php` e sem wrapper.
- Produces: a função `documentedComponentSlugs(): list<string>`, declarada neste arquivo de teste. O Pest carrega todos os arquivos de teste antes de filtrar, então a task 4 pode chamá-la de `DocsApiTest.php` mesmo rodando com `--filter=DocsApi`.
- Consumida pelas tasks 3, 4 e 5.

- [ ] **Step 1: Escreva o teste**

Create `tests/Feature/DocsExamplesTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;

/** @return list<string> */
function documentedComponentSlugs(): array
{
    $paths = glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php') ?: [];
    $slugs = array_map(
        static fn (string $path): string => basename($path, '.blade.php'),
        $paths,
    );
    sort($slugs, SORT_STRING);

    return $slugs;
}

function docsExamplePath(string $slug): string
{
    return dirname(__DIR__, 2)."/resources/docs-examples/{$slug}.blade.php";
}

it('ships a documentation example for every component', function (string $slug): void {
    expect(is_file(docsExamplePath($slug)))->toBeTrue(
        "Faltou resources/docs-examples/{$slug}.blade.php — todo componente precisa de um exemplo de uso.",
    );
})->with(documentedComponentSlugs());

it('renders every documentation example', function (string $slug): void {
    $example = file_get_contents(docsExamplePath($slug));

    expect($example)->not->toBeFalse();

    $html = Blade::render($example);

    expect(trim($html))->not->toBe('')
        ->and($html)->toContain('lyra-');
})->with(documentedComponentSlugs());

it('writes every example with the short tag syntax', function (string $slug): void {
    $example = file_get_contents(docsExamplePath($slug));

    expect($example)->toContain("<lyra:{$slug}")
        ->and($example)->not->toContain('<?php');
})->with(documentedComponentSlugs());
```

- [ ] **Step 2: Rode e conte as falhas**

```bash
vendor/bin/pest --filter=DocsExamples
```

Esperado: 216 casos, praticamente todos vermelhos por arquivo ausente. Esse número é o placar da task 3.

- [ ] **Step 3: Escreva os três primeiros exemplos**

`resources/docs-examples/button.blade.php`:

```blade
<lyra:button variant="primary" size="md">Save changes</lyra:button>
```

`resources/docs-examples/input.blade.php`:

```blade
<lyra:input
    name="email"
    type="email"
    label="Email address"
    placeholder="you@example.com"
    autocomplete="email"
/>
```

`resources/docs-examples/dropdown.blade.php` — confira os nomes de prop no próprio `resources/views/components/dropdown.blade.php` antes de escrever, e use exatamente os que existirem lá:

```blade
<lyra:dropdown align="end" :items="[
    ['type' => 'label', 'label' => 'Project'],
    ['label' => 'Rename project'],
    ['type' => 'separator'],
    ['label' => 'Archive project', 'danger' => true],
]">
    <x-slot:trigger>
        <lyra:button variant="secondary">Project actions</lyra:button>
    </x-slot:trigger>
</lyra:dropdown>
```

- [ ] **Step 4: Rode e veja três passarem**

```bash
vendor/bin/pest --filter="DocsExamples"
```

Esperado: os casos de `button`, `input` e `dropdown` verdes nos três testes; o resto ainda vermelho por arquivo ausente. Se `dropdown` falhar no render, o erro do Blade nomeia a prop errada — corrija o exemplo contra o componente, nunca o contrário.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add tests/Feature/DocsExamplesTest.php resources/docs-examples
git commit -m "test: exige e renderiza um exemplo de documentação por componente"
```

---

### Task 3: Os 69 exemplos restantes

Mecânico e longo. Faça em lotes de ~10, um commit por lote, sempre com o mesmo ciclo.

**Files:**
- Create: `resources/docs-examples/<slug>.blade.php` para os 69 componentes restantes

- [ ] **Step 1: Liste o que falta**

```bash
cd /home/franciscpd/Projects/lyra-ds/blade
comm -23 \
  <(ls resources/views/components | sed 's/\.blade\.php//' | sort) \
  <(ls resources/docs-examples 2>/dev/null | sed 's/\.blade\.php//' | sort)
```

- [ ] **Step 2: Para cada componente do lote**

1. Leia `resources/views/components/<slug>.blade.php` e anote o `@props` — os nomes e defaults reais.
2. Leia `tests/Feature/<Pascal>Test.php`: os casos já mostram invocações válidas, e é a fonte mais barata de um exemplo correto.
3. Escreva o exemplo com **valores plausíveis de produto**, não `foo`/`bar` — este snippet vai aparecer na documentação pública.
4. Se o componente exige dados estruturados, passe array literal com `:prop="[...]"`, como no `dropdown`.

- [ ] **Step 3: Rode o lote**

```bash
vendor/bin/pest --filter=DocsExamples
```

Esperado: o placar de falhas cai pelo tamanho do lote. Nenhum caso pode ficar verde por acidente: se um exemplo renderiza vazio, o teste `renders every documentation example` pega.

- [ ] **Step 4: Commit por lote**

```bash
vendor/bin/pint
git add resources/docs-examples
git commit -m "docs: exemplos de uso dos componentes <primeiro>…<último>"
```

- [ ] **Step 5: Feche a task**

```bash
vendor/bin/pest
```

Esperado: 216 casos de `DocsExamples` verdes e a suíte inteira verde. Só então siga para a task 4.

---

### Task 4: `DocsApiGenerator` e o `api.json`

**Files:**
- Create: `src/DocsApiGenerator.php`
- Create: `bin/generate-docs-api`
- Create: `docs/api.json` (gerado)
- Create: `tests/Feature/DocsApiTest.php`

**Interfaces:**
- Consumes: `BladePropParser` (task 1); `resources/docs-examples/*` (tasks 2–3).
- Produces:

```php
final class DocsApiGenerator
{
    /** @param  callable(string): string  $render */
    public function generate(
        string $componentsDirectory,
        string $examplesDirectory,
        string $fixturesDirectory,
        string $version,
        callable $render,
    ): string;
}
```

  Devolve o JSON pronto (com `\n` final). O `render` é injetado para que a classe não dependa do container — quem boota a app é o `bin`, e o teste passa um render de verdade vindo do Testbench.

- [ ] **Step 1: Escreva o teste do gerador**

Create `tests/Feature/DocsApiTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\DocsApiGenerator;

function generatedDocsApi(): array
{
    $root = dirname(__DIR__, 2);
    $manifest = json_decode((string) file_get_contents($root.'/.release-please-manifest.json'), true);

    $json = (new DocsApiGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/resources/docs-examples',
        $root.'/tests/Fixtures/class-emission',
        $manifest['.'],
        static fn (string $template): string => Blade::render($template),
    );

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

it('stamps the released version on the artifact', function (): void {
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/.release-please-manifest.json'),
        true,
    );

    expect(generatedDocsApi()['version'])->toBe($manifest['.']);
});

it('covers every component, sorted by slug', function (): void {
    $slugs = array_column(generatedDocsApi()['components'], 'slug');
    $expected = documentedComponentSlugs();

    expect($slugs)->toBe($expected);
});

it('carries usage and rendered html for every component', function (): void {
    foreach (generatedDocsApi()['components'] as $component) {
        expect($component['usage'])->not->toBe('', "usage vazio em {$component['slug']}")
            ->and($component['html'])->toContain('lyra-');
    }
});

it('reports button props with defaults and observed values', function (): void {
    $button = collect(generatedDocsApi()['components'])->firstWhere('slug', 'button');

    $variant = collect($button['props'])->firstWhere('name', 'variant');

    expect($variant['required'])->toBeFalse()
        ->and($variant['values'])->toContain('primary')
        ->and($variant['values'])->toContain('danger');
});

it('keeps the committed artifact synchronized with the sources', function (): void {
    $root = dirname(__DIR__, 2);
    $manifest = json_decode((string) file_get_contents($root.'/.release-please-manifest.json'), true);

    $fresh = (new DocsApiGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/resources/docs-examples',
        $root.'/tests/Fixtures/class-emission',
        $manifest['.'],
        static fn (string $template): string => Blade::render($template),
    );

    expect(is_file($root.'/docs/api.json'))->toBeTrue();
    expect(file_get_contents($root.'/docs/api.json'))->toBe($fresh);
});
```

- [ ] **Step 2: Rode e veja falhar**

```bash
vendor/bin/pest --filter=DocsApi
```

Esperado: `Class "LyraDs\Blade\DocsApiGenerator" not found`.

- [ ] **Step 3: Implemente o gerador**

Create `src/DocsApiGenerator.php`:

```php
<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use RuntimeException;

/**
 * Monta o artefato de API consumido por lyra-ds.dev. Descreve o que os componentes já
 * são — props do @props, snippet curado e o HTML que ele realmente produz. Nada aqui
 * inventa contrato: valores observados saem das fixtures de class-emission, e ausência
 * de observação vira lista vazia, nunca um chute.
 */
final class DocsApiGenerator
{
    /** @param  callable(string): string  $render */
    public function generate(
        string $componentsDirectory,
        string $examplesDirectory,
        string $fixturesDirectory,
        string $version,
        callable $render,
    ): string {
        $paths = glob($componentsDirectory.'/*.blade.php');

        if ($paths === false || $paths === []) {
            throw new RuntimeException("Unable to discover components in {$componentsDirectory}.");
        }

        sort($paths, SORT_STRING);

        $parser = new BladePropParser;
        $components = [];

        foreach ($paths as $path) {
            $slug = basename($path, '.blade.php');
            $template = file_get_contents($path);
            $examplePath = $examplesDirectory."/{$slug}.blade.php";

            if ($template === false || ! is_file($examplePath)) {
                throw new RuntimeException("Missing template or documentation example for {$slug}.");
            }

            $usage = rtrim((string) file_get_contents($examplePath), "\n");
            $fixture = $this->readFixture($fixturesDirectory."/{$slug}.json");

            $components[] = [
                'slug' => $slug,
                'usage' => $usage,
                'html' => trim($render($usage)),
                'binding' => null,
                'props' => array_map(
                    fn (array $prop): array => [
                        'name' => $prop['name'],
                        'default' => $prop['hasDefault'] ? $prop['default'] : null,
                        'required' => ! $prop['hasDefault'],
                        'values' => $this->observedValues($fixture, $prop['name']),
                    ],
                    $parser->parse($template, $path),
                ),
            ];
        }

        return json_encode(
            ['version' => $version, 'components' => $components],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n";
    }

    /** @return list<array<string, mixed>> */
    private function readFixture(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<array<string, mixed>>  $fixture
     * @return list<string>
     */
    private function observedValues(array $fixture, string $prop): array
    {
        $values = [];

        foreach ($fixture as $case) {
            $value = $case['props'][$prop] ?? null;

            if (! is_string($value) || in_array($value, $values, true)) {
                continue;
            }

            $values[] = $value;
        }

        sort($values, SORT_STRING);

        return $values;
    }
}
```

- [ ] **Step 4: Escreva o bin**

Create `bin/generate-docs-api` (o bootstrap abaixo foi verificado neste repositório — renderiza `<x-lyra::button>` fora do PHPUnit):

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\DocsApiGenerator;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
putenv('APP_KEY='.$_ENV['APP_KEY']);

Orchestra\Testbench\Foundation\Application::create(
    basePath: $root.'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [
        BladeUI\Icons\BladeIconsServiceProvider::class,
        MallardDuck\LucideIcons\BladeLucideIconsServiceProvider::class,
        LyraDs\Blade\BladeServiceProvider::class,
    ]]],
);

$manifest = json_decode((string) file_get_contents($root.'/.release-please-manifest.json'), true, flags: JSON_THROW_ON_ERROR);

$artifact = (new DocsApiGenerator)->generate(
    $root.'/resources/views/components',
    $root.'/resources/docs-examples',
    $root.'/tests/Fixtures/class-emission',
    $manifest['.'],
    static fn (string $template): string => Blade::render($template),
);

if (! is_dir($root.'/docs') && ! mkdir($root.'/docs', 0755, true)) {
    throw new RuntimeException('Unable to create the docs directory.');
}

if (file_put_contents($root.'/docs/api.json', $artifact) === false) {
    throw new RuntimeException('Unable to write docs/api.json.');
}

fwrite(STDOUT, "Generated docs/api.json\n");
```

- [ ] **Step 5: Gere e verifique**

```bash
php bin/generate-docs-api
php -r '$a=json_decode(file_get_contents("docs/api.json"),true); echo $a["version"], " ", count($a["components"]), "\n";'
vendor/bin/pest --filter=DocsApi
```

Esperado: `0.9.0 72` e os 5 testes verdes — inclusive o de frescor, que compara o arquivo recém-escrito com uma geração fresca.

- [ ] **Step 6: Prove que o frescor morde**

```bash
php -r '$a=json_decode(file_get_contents("docs/api.json"),true); $a["components"][0]["usage"]="mexido"; file_put_contents("docs/api.json", json_encode($a, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");'
vendor/bin/pest --filter="keeps the committed artifact"
php bin/generate-docs-api
```

Esperado: o teste falha com a diferença, e a última linha restaura o arquivo. Sem essa prova o guard é decorativo.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint
git add src/DocsApiGenerator.php bin/generate-docs-api docs/api.json tests/Feature/DocsApiTest.php
git commit -m "feat: gera docs/api.json com props, uso e html renderizado dos componentes"
```

---

### Task 5: O binding Alpine no artefato

29 dos 72 componentes emitem `x-data="lyra*(...)"`. O site precisa desse nome para dizer, na aba Blade, de onde vem o comportamento — e para linkar a aba irmã.

**Files:**
- Modify: `src/DocsApiGenerator.php`
- Modify: `tests/Feature/DocsApiTest.php`
- Modify: `docs/api.json` (regerado)

**Interfaces:**
- Produces: campo `binding` preenchido com o nome do factory (`"lyraDropdown"`) ou `null`.

- [ ] **Step 1: Escreva o teste**

Acrescente a `tests/Feature/DocsApiTest.php`:

```php
it('names the Alpine binding of interactive components', function (): void {
    $components = collect(generatedDocsApi()['components'])->keyBy('slug');

    expect($components['dropdown']['binding'])->toBe('lyraDropdown')
        ->and($components['time-picker']['binding'])->toBe('lyraTimePicker')
        ->and($components['button']['binding'])->toBeNull();
});

it('finds a binding for every component whose template carries x-data', function (): void {
    $root = dirname(__DIR__, 2);
    $components = collect(generatedDocsApi()['components'])->keyBy('slug');

    foreach (glob($root.'/resources/views/components/*.blade.php') ?: [] as $path) {
        $slug = basename($path, '.blade.php');
        $hasBinding = (bool) preg_match('/x-data="(lyra[A-Z]\w*)\(/', (string) file_get_contents($path));

        expect($components[$slug]['binding'] !== null)->toBe(
            $hasBinding,
            "binding divergente em {$slug}",
        );
    }
});
```

- [ ] **Step 2: Rode e veja falhar**

```bash
vendor/bin/pest --filter="Alpine binding"
```

Esperado: falha porque `binding` é sempre `null`.

- [ ] **Step 3: Implemente a detecção**

Em `src/DocsApiGenerator.php`, troque `'binding' => null,` por `'binding' => $this->bindingName($template),` e adicione o método:

```php
    /**
     * O nome do factory Alpine que anima o componente, lido do próprio template.
     * Deliberadamente uma leitura, não uma lista mantida à mão: uma lista envelhece
     * silenciosamente, o template não.
     */
    private function bindingName(string $template): ?string
    {
        if (preg_match('/x-data="(lyra[A-Z]\w*)\(/', $template, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
```

- [ ] **Step 4: Regenere e rode tudo**

```bash
php bin/generate-docs-api
vendor/bin/pest
php -r '$a=json_decode(file_get_contents("docs/api.json"),true); echo count(array_filter($a["components"], fn($c) => $c["binding"] !== null)), "\n";'
```

Esperado: suíte verde e **29** componentes com binding — o mesmo número que `grep -rl "x-data" resources/views/components | wc -l` devolve.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add src/DocsApiGenerator.php tests/Feature/DocsApiTest.php docs/api.json
git commit -m "feat: identifica o binding Alpine de cada componente no artefato de API"
```

---

### Task 6: O artefato sai na release

**Files:**
- Modify: `.github/workflows/release.yml`

**Interfaces:**
- Produces: `api.json` anexado ao release do GitHub, baixável com `gh release download --repo lyra-ds/blade --pattern api.json` — exatamente o comando que a task 11 da Frente B usa.

- [ ] **Step 1: Adicione os passos ao job de release**

Em `.github/workflows/release.yml`, dê `id: release` ao passo do release-please e acrescente, depois dele:

```yaml
      - name: Check out the repository
        if: ${{ steps.release.outputs.release_created }}
        uses: actions/checkout@v4

      - name: Set up PHP
        if: ${{ steps.release.outputs.release_created }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
          tools: composer:v2

      - name: Install dependencies
        if: ${{ steps.release.outputs.release_created }}
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Generate the documentation API artifact
        if: ${{ steps.release.outputs.release_created }}
        run: php bin/generate-docs-api

      - name: Attach the artifact to the release
        if: ${{ steps.release.outputs.release_created }}
        env:
          GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: gh release upload "${{ steps.release.outputs.tag_name }}" docs/api.json --clobber
```

O `docs/api.json` regenerado neste passo é idêntico ao commitado, **exceto** pelo campo `version`, que agora reflete a versão que o release-please acabou de gravar no manifesto. É por isso que ele é gerado aqui e não simplesmente enviado do repositório.

- [ ] **Step 2: Valide o workflow**

```bash
actionlint .github/workflows/release.yml || docker run --rm -v "$PWD":/repo -w /repo rhysd/actionlint:latest -color
```

Esperado: sem achados. Se `actionlint` não estiver instalado localmente, o CI do monorepo principal roda o equivalente — mas não pule a verificação: erro de sintaxe aqui só aparece na próxima release.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/release.yml
git commit -m "ci: anexa docs/api.json ao release do GitHub"
```

---

### Task 7: Documentar o contrato

O artefato é consumido por outro repositório. Sem isto, a primeira pessoa que renomear um campo quebra o site sem saber.

**Files:**
- Modify: `README.md`
- Modify: `AGENTS.md`
- Modify: `WORK.md`

- [ ] **Step 1: Seção no README**

Depois da seção de componentes, uma seção curta declarando: o caminho (`docs/api.json`), que ele é gerado por `php bin/generate-docs-api`, que é commitado e protegido por teste de frescor, que sai anexado a cada release, e **quem consome** (`lyra-ds.dev`, para a aba Blade da documentação). Inclua o exemplo de forma do contrato — um componente só, com os campos `slug`, `usage`, `html`, `binding` e `props`.

- [ ] **Step 2: Regra no AGENTS.md**

Uma linha, junto das outras regras de componente:

```markdown
- Componente novo exige `resources/docs-examples/<slug>.blade.php` e `php bin/generate-docs-api` — a suíte falha sem os dois. O artefato é contrato público consumido por lyra-ds.dev; não renomeie campos sem abrir a mudança no repositório do site.
```

- [ ] **Step 3: Rode a suíte inteira e feche**

```bash
vendor/bin/pest
vendor/bin/pint --test
php bin/generate-docs-api && git diff --exit-code docs/api.json
```

Esperado: tudo verde, e o `git diff --exit-code` sem saída — o artefato commitado já está fresco.

- [ ] **Step 4: Commit**

```bash
git add README.md AGENTS.md WORK.md
git commit -m "docs: descreve o contrato do artefato de API consumido pelo site"
```

---

## Notas de execução

- **Ordem obrigatória:** 1 → 2 → 3 → 4 → 5. As tasks 6 e 7 podem sair em qualquer ordem depois da 5.
- **A task 3 é a única longa** (69 exemplos). Delegue por lote, com `button`, `input` e `dropdown` como referência no brief, e a regra explícita: o exemplo se ajusta ao componente, nunca o contrário.
- **Quando a task 5 fechar**, avise a Frente B: as tasks 11 e 12 do plano do site estão bloqueadas exatamente até a primeira release com `api.json` anexado.
- **O render é injetado de propósito.** `DocsApiGenerator` não conhece o container: o teste passa o `Blade::render` do Testbench e o `bin` passa o da app que ele mesmo boota. Se alguém "simplificar" isso chamando a facade dentro da classe, a classe deixa de ser testável fora de uma app.
