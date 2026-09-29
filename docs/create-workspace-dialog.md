# Create workspace dialog

Install `@lyra-ds/alpine` ^1.2.0 and `@lyra-ds/styles` ^1.1.0. The [rendered example](../resources/docs-examples/create-workspace-dialog.blade.php) contains a controller that acknowledges each `lyra:create-workspace` proposal by its `operationId`. The application owns persistence and calls `accept`, `reject`, or `cancel` on the dialog's Alpine data.

To localize the same flow in Brazilian Portuguese, pass every visible string and the validation messages:

```blade
<div x-data="{ dialogOpen: false, handleCreate(event) {
    const { operationId, name, slug } = event.detail;
    const dialog = Alpine.$data(event.target);
    saveWorkspace({ name, slug }).then(
        () => dialog.accept(operationId),
        error => dialog.reject(operationId, { message: error.message })
    );
} }">
    <button id="abrir-workspace" type="button" @click="dialogOpen = true">Criar workspace</button>
    <lyra:create-workspace-dialog
        x-model="dialogOpen"
        return-focus-to="#abrir-workspace"
        title="Criar workspace"
        close-label="Fechar"
        name-label="Nome do workspace"
        slug-label="URL"
        slug-hint="Letras minúsculas, números e hifens."
        preview-hint="O avatar usa as iniciais do nome."
        error-label="Erro ao criar workspace"
        cancel-label="Cancelar"
        create-label="Criar workspace"
        :messages="[
            'nameRequired' => 'Informe o nome do workspace.',
            'slugRequired' => 'Informe a URL do workspace.',
            'createFailed' => 'Não foi possível criar o workspace. Tente novamente.',
        ]"
        x-on:lyra:create-workspace="handleCreate($event)"
    />
</div>
```

For long running requests, listen to `lyra:create-workspace:cancel`, abort the request associated with that `operationId`, and call `cancel(operationId)` when it settles. `return-focus-to` accepts a CSS selector, like other Blade overlays.
