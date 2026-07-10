/**
 * Boots the Editor.js instance used by admin/article-edit.php.
 * Expects on the page:
 *   - #editorjs                        mount point
 *   - #editorjs-initial-data (script)   JSON with the starting content (Editor.js output shape)
 *   - #content_json                     hidden input the form submits; kept in sync on save
 *   - window.ADMIN_CSRF_TOKEN           for the image tool's upload request
 */
(function () {
    const mount = document.getElementById('editorjs');
    if (!mount) return;

    const initialDataEl = document.getElementById('editorjs-initial-data');
    let initialData = { time: Date.now(), blocks: [], version: '2.29.1' };
    if (initialDataEl && initialDataEl.textContent.trim()) {
        try {
            initialData = JSON.parse(initialDataEl.textContent);
        } catch (e) {
            console.warn('Could not parse initial Editor.js data, starting empty.', e);
        }
    }

    const editor = new EditorJS({
        holder: 'editorjs',
        placeholder: 'Начните писать статью…',
        data: initialData,
        tools: {
            header: { class: Header, inlineToolbar: true, config: { levels: [2, 3, 4], defaultLevel: 2 } },
            list: { class: List, inlineToolbar: true },
            quote: { class: Quote, inlineToolbar: true },
            table: { class: Table, inlineToolbar: true },
            delimiter: Delimiter,
            marker: Marker,
            link: Link,
            image: {
                class: Image,
                config: {
                    uploader: {
                        uploadByFile(file) {
                            const fd = new FormData();
                            fd.append('file', file);
                            fd.append('csrf_token', window.ADMIN_CSRF_TOKEN || '');
                            return fetch('/api/media.php?action=upload', {
                                method: 'POST',
                                body: fd,
                                credentials: 'same-origin',
                            })
                                .then((r) => r.json())
                                .then((res) => {
                                    if (!res.success) {
                                        throw new Error(res.error || 'Upload failed');
                                    }
                                    return { success: 1, file: { url: res.file.url, id: res.file.id } };
                                });
                        },
                    },
                },
            },
            cta: CtaTool,
        },
        onReady: () => {
            window.__editor = editor;
        },
    });

    window.getEditorContentJson = function () {
        return editor.save().then((output) => JSON.stringify(output));
    };

    const form = document.getElementById('article-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitter = e.submitter;
            window.getEditorContentJson().then((json) => {
                document.getElementById('content_json').value = json;
                if (submitter && submitter.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                form.submit();
            });
        });
    }
})();
