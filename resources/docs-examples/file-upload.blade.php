<div
    x-data="{
        uploadItems: [],
        files: new Map(),
        timers: new Map(),

        patch(id, attemptId, statuses, update) {
            this.uploadItems = this.uploadItems.map((item) =>
                item.id === id && item.attemptId === attemptId && statuses.includes(item.status) ? update(item) : item,
            );
        },

        beginUpload(id, attemptId) {
            const base = ({ name, size, type }) => ({ id, name, size, type, attemptId });
            this.uploadItems = this.uploadItems.map((item) =>
                item.id === id ? { ...base(item), status: 'uploading', progress: { kind: 'indeterminate' } } : item,
            );

            // Simulated transport: replace this timer with your real upload (fetch/XHR).
            let percent = 0;
            const timer = setInterval(() => {
                percent += 20;
                const file = this.files.get(id);
                if (percent < 100) {
                    this.patch(id, attemptId, ['uploading', 'canceling'], (item) => ({ ...item, progress: { kind: 'determinate', value: percent } }));
                } else if (file.name.startsWith('fail')) {
                    clearInterval(timer);
                    this.patch(id, attemptId, ['uploading', 'canceling'], (item) => ({
                        ...base(item),
                        status: 'error',
                        error: { kind: 'transport', message: 'The server rejected the upload.', retryable: true },
                    }));
                } else {
                    clearInterval(timer);
                    this.patch(id, attemptId, ['uploading', 'canceling'], (item) => ({ ...base(item), status: 'success' }));
                }
            }, 400);
            this.timers.set(attemptId, timer);
        },

        startUploads({ selections }) {
            this.uploadItems = [...this.uploadItems, ...selections.map(({ proposedItem }) => proposedItem)];
            for (const selection of selections) {
                if (typeof selection.proposedAttemptId !== 'string') continue;
                this.files.set(selection.id, selection.file);
                this.beginUpload(selection.id, selection.proposedAttemptId);
            }
        },

        retryUpload({ id, proposedAttemptId }) {
            this.beginUpload(id, proposedAttemptId);
        },

        cancelUpload({ id, attemptId }) {
            clearInterval(this.timers.get(attemptId));
            this.patch(id, attemptId, ['uploading'], (item) => ({
                id: item.id, name: item.name, size: item.size, type: item.type, attemptId, status: 'canceled',
            }));
        },

        removeUpload({ id }) {
            this.files.delete(id);
            this.uploadItems = this.uploadItems.filter((item) => item.id !== id);
        },
    }"
>
    <lyra:file-upload
        id="attachment-upload"
        name="attachments[]"
        label="Choose attachments"
        hint="PNG or JPG up to 5 MB."
        accept="image/png,image/jpeg"
        :max-size-m-b="5"
        multiple
        x-model="uploadItems"
        x-on:lyra:file-upload:select="startUploads($event.detail)"
        x-on:lyra:file-upload:retry="retryUpload($event.detail)"
        x-on:lyra:file-upload:cancel="cancelUpload($event.detail)"
        x-on:lyra:file-upload:remove="removeUpload($event.detail)"
    />
</div>
