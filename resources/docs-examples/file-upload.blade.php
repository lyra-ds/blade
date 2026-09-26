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
