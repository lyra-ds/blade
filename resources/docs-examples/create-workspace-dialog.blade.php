{{-- Application persistence is owned by the parent. The binding proposes an operation; the controller acknowledges that same ID. --}}
<div x-data="{
    dialogOpen: false,
    requests: {},
    handleCreate(event) {
        const { operationId, name, slug } = event.detail;
        const dialog = Alpine.$data(event.target);
        const controller = new AbortController();
        this.requests[operationId] = controller;
        // Replace this Promise with your API request and pass controller.signal to fetch.
        Promise.resolve({ name, slug }).then(() => {
            if (controller.signal.aborted) dialog.cancel(operationId);
            else if (slug === 'taken') dialog.reject(operationId, {
                fieldErrors: { slug: 'This URL is already in use.' },
                message: 'Choose another workspace URL.',
            });
            else dialog.accept(operationId);
            delete this.requests[operationId];
        }, error => {
            dialog.reject(operationId, { message: error.message });
            delete this.requests[operationId];
        });
    },
    handleCancel(event) {
        const id = event.detail.operationId;
        this.requests[id]?.abort();
        Alpine.$data(event.target).cancel(id);
        delete this.requests[id];
    },
}">
    <button id="workspace-trigger" type="button" @click="dialogOpen = true">Create workspace</button>
    <lyra:create-workspace-dialog
        x-model="dialogOpen"
        return-focus-to="#workspace-trigger"
        x-on:lyra:create-workspace="handleCreate($event)"
        x-on:lyra:create-workspace:cancel="handleCancel($event)"
    />
</div>
