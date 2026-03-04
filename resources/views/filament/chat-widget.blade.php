@php
    $organizationId = \App\Services\TenantContext::id();
@endphp

<div
    id="chat-widget-root"
    style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;"
>
</div>

<style>
    #chat-widget-fab {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        background-color: #f59e0b;
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
        transition: background-color 0.2s;
    }
    #chat-widget-fab:hover { background-color: #d97706; }

    #chat-widget-panel {
        display: none;
        flex-direction: column;
        width: 400px;
        height: 520px;
        border-radius: 0.75rem;
        overflow: hidden;
        background: white;
        border: 1px solid #e5e7eb;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    .dark #chat-widget-panel {
        background: #111827;
        border-color: #374151;
    }
    #chat-widget-panel.cw-open {
        display: flex;
    }

    #chat-widget-panel .cw-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: #f59e0b;
        border-bottom: 1px solid #e5e7eb;
    }
    .dark #chat-widget-panel .cw-header { background: #d97706; border-color: #374151; }

    .cw-header-left { display: flex; align-items: center; gap: 0.5rem; }
    .cw-header-right { display: flex; align-items: center; gap: 0.25rem; }

    .cw-header button, .cw-header select {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        font-size: 0.75rem;
    }
    .cw-header button { padding: 0.25rem; }
    .cw-header select { padding: 0.25rem 0.5rem; font-weight: 500; }
    .cw-header select option { color: #111; }
    .cw-header h3 { color: white; font-size: 0.875rem; font-weight: 600; margin: 0; }

    .cw-body { display: flex; flex: 1; overflow: hidden; position: relative; }

    .cw-sidebar {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 10;
        width: 15rem;
        flex-direction: column;
        background: #f9fafb;
        border-right: 1px solid #e5e7eb;
    }
    .dark .cw-sidebar { background: #1f2937; border-color: #374151; }
    .cw-sidebar.cw-open { display: flex; }

    .cw-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
    }
    .dark .cw-sidebar-header { border-color: #374151; color: #9ca3af; }

    .cw-sidebar-list { flex: 1; overflow-y: auto; }

    .cw-conv-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: none;
        background: transparent;
        text-align: left;
        font-size: 0.75rem;
        color: #374151;
        cursor: pointer;
        transition: background 0.15s;
    }
    .dark .cw-conv-btn { color: #d1d5db; }
    .cw-conv-btn:hover { background: #f3f4f6; }
    .dark .cw-conv-btn:hover { background: #374151; }
    .cw-conv-btn.cw-active { background: #fffbeb; color: #b45309; }
    .dark .cw-conv-btn.cw-active { background: rgba(245,158,11,0.15); color: #fbbf24; }
    .cw-conv-btn span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; }

    .cw-conv-del {
        flex-shrink: 0;
        background: transparent;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 2px;
        margin-left: 4px;
        border-radius: 2px;
        font-size: 0.75rem;
        line-height: 1;
    }
    .cw-conv-del:hover { color: #ef4444; }

    .cw-messages-col { display: flex; flex-direction: column; flex: 1; }
    .cw-messages {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem 1rem;
    }
    .cw-empty {
        display: flex;
        height: 100%;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .cw-msg { margin-bottom: 0.75rem; display: flex; }
    .cw-msg-user { justify-content: flex-end; }
    .cw-msg-assistant { justify-content: flex-start; }

    .cw-bubble {
        max-width: 85%;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .cw-bubble-user { background: #f59e0b; color: white; margin-left: 2rem; }
    .dark .cw-bubble-user { background: #d97706; }
    .cw-bubble-assistant { background: #f3f4f6; color: #1f2937; margin-right: 2rem; }
    .dark .cw-bubble-assistant { background: #1f2937; color: #e5e7eb; }

    .cw-bubble pre { background: #f3f4f6; border-radius: 0.375rem; padding: 0.5rem; overflow-x: auto; margin: 0.25rem 0; font-size: 0.8125rem; }
    .dark .cw-bubble pre { background: #111827; }
    .cw-bubble code:not(pre code) { background: #f3f4f6; border-radius: 0.25rem; padding: 0.125rem 0.25rem; font-size: 0.8125rem; }
    .dark .cw-bubble code:not(pre code) { background: #111827; }
    .cw-bubble ul, .cw-bubble ol { padding-left: 1.25rem; margin: 0.25rem 0; }
    .cw-bubble ul { list-style: disc; }
    .cw-bubble ol { list-style: decimal; }
    .cw-bubble p { margin: 0.25rem 0; }
    .cw-bubble p:first-child { margin-top: 0; }
    .cw-bubble p:last-child { margin-bottom: 0; }

    .cw-typing { display: flex; gap: 4px; padding: 0.5rem 0.75rem; }
    .cw-dot { width: 8px; height: 8px; border-radius: 50%; background: #9ca3af; animation: cw-bounce 1.4s infinite ease-in-out both; }
    .cw-dot:nth-child(1) { animation-delay: 0ms; }
    .cw-dot:nth-child(2) { animation-delay: 160ms; }
    .cw-dot:nth-child(3) { animation-delay: 320ms; }
    @keyframes cw-bounce { 0%,80%,100%{transform:scale(0)} 40%{transform:scale(1)} }

    .cw-input-area {
        display: flex;
        align-items: flex-end;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-top: 1px solid #e5e7eb;
    }
    .dark .cw-input-area { border-color: #374151; }

    .cw-file-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: 0.5rem;
        background: #6b7280;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
        position: relative;
        overflow: hidden;
    }
    .cw-file-btn:hover { background: #4b5563; }
    .cw-file-btn input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .cw-input-area textarea {
        flex: 1;
        min-height: 36px;
        max-height: 96px;
        resize: none;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        background: white;
        color: #1f2937;
        outline: none;
        font-family: inherit;
    }
    .dark .cw-input-area textarea { background: #1f2937; color: #e5e7eb; border-color: #4b5563; }
    .cw-input-area textarea:focus { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }

    .cw-send-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: 0.5rem;
        background: #f59e0b;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }
    .cw-send-btn:hover { background: #d97706; }
    .cw-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<script>
(function() {
    const ORG_ID = @json($organizationId);
    const STORAGE_KEY = 'chat_widget_state';

    let state = {
        open: false,
        agent: 'contact',
        conversationId: null,
        conversations: [],
        messages: [],
        input: '',
        loading: false,
        showSidebar: false,
        abortController: null,
    };

    // DOM refs
    let root, fab, panel, messagesEl, inputEl, agentSelect, sidebarEl, sidebarList;

    function getCsrfToken() {
        const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        if (m) return decodeURIComponent(m[1]);
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function renderMarkdown(text) {
        if (!text) return '';
        let h = escapeHtml(text);
        h = h.replace(/```(\w*)\n?([\s\S]*?)```/g, (_, l, c) => '<pre><code>' + c.trim() + '</code></pre>');
        h = h.replace(/`([^`]+)`/g, '<code>$1</code>');
        h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        h = h.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
        h = h.replace(/^[\s]*[-*]\s+(.+)$/gm, '<li>$1</li>');
        h = h.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');
        h = h.replace(/^[\s]*\d+\.\s+(.+)$/gm, '<li>$1</li>');
        h = h.replace(/\n\n/g, '</p><p>');
        h = '<p>' + h + '</p>';
        h = h.replace(/\n/g, '<br>');
        h = h.replace(/<p>\s*<\/p>/g, '');
        return h;
    }

    function persistState() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            open: state.open,
            agent: state.agent,
            conversationId: state.conversationId,
        }));
    }

    function restoreState() {
        try {
            const s = JSON.parse(localStorage.getItem(STORAGE_KEY));
            if (s) {
                state.open = s.open ?? false;
                state.agent = s.agent ?? 'contact';
                state.conversationId = s.conversationId ?? null;
            }
        } catch {}
    }

    function scrollToBottom() {
        if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function renderMessages() {
        if (!messagesEl) return;
        let html = '';
        if (state.messages.length === 0 && !state.loading) {
            html = '<div class="cw-empty">Send a message to start chatting</div>';
        } else {
            for (const msg of state.messages) {
                const isUser = msg.role === 'user';
                const bubbleCls = isUser ? 'cw-bubble cw-bubble-user' : 'cw-bubble cw-bubble-assistant';
                const content = isUser ? escapeHtml(msg.content) : renderMarkdown(msg.content);
                html += '<div class="cw-msg ' + (isUser ? 'cw-msg-user' : 'cw-msg-assistant') + '">';
                html += '<div class="' + bubbleCls + '">' + content + '</div></div>';
            }
            if (state.loading) {
                html += '<div class="cw-msg cw-msg-assistant"><div class="cw-bubble cw-bubble-assistant"><div class="cw-typing"><div class="cw-dot"></div><div class="cw-dot"></div><div class="cw-dot"></div></div></div></div>';
            }
        }
        messagesEl.innerHTML = html;
        requestAnimationFrame(scrollToBottom);
    }

    function renderSidebar() {
        if (!sidebarList) return;
        let html = '';
        if (state.conversations.length === 0) {
            html = '<div style="padding:1rem;text-align:center;font-size:0.75rem;color:#9ca3af;">No conversations yet</div>';
        } else {
            for (const c of state.conversations) {
                const active = c.id === state.conversationId ? ' cw-active' : '';
                html += '<div class="cw-conv-btn' + active + '" data-id="' + c.id + '">';
                html += '<span>' + escapeHtml(c.title) + '</span>';
                html += '<button class="cw-conv-del" data-del="' + c.id + '" title="Delete">&times;</button>';
                html += '</div>';
            }
        }
        sidebarList.innerHTML = html;

        // Bind click handlers
        sidebarList.querySelectorAll('.cw-conv-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (e.target.closest('.cw-conv-del')) return;
                loadConversation(btn.dataset.id);
            });
        });
        sidebarList.querySelectorAll('.cw-conv-del').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteConversation(btn.dataset.del);
            });
        });
    }

    function updateUI() {
        if (!root) return;
        fab.style.display = state.open ? 'none' : 'flex';
        panel.classList.toggle('cw-open', state.open);
        sidebarEl.classList.toggle('cw-open', state.showSidebar);
        if (agentSelect) agentSelect.value = state.agent;
    }

    async function fetchConversations() {
        try {
            const url = new URL('/api/conversations', window.location.origin);
            if (ORG_ID) url.searchParams.set('organization_id', ORG_ID);
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
                credentials: 'same-origin',
            });
            if (res.ok) {
                const json = await res.json();
                state.conversations = json.data ?? [];
                renderSidebar();
            }
        } catch (e) { console.error('[ChatWidget] fetch conversations', e); }
    }

    async function loadConversation(id) {
        state.conversationId = id;
        state.showSidebar = false;
        persistState();
        updateUI();
        try {
            const res = await fetch('/api/conversations/' + id, {
                headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
                credentials: 'same-origin',
            });
            if (res.ok) {
                const json = await res.json();
                state.messages = (json.data?.messages ?? []).map(m => ({ role: m.role, content: m.content ?? '' }));
                renderMessages();
                renderSidebar();
            }
        } catch (e) { console.error('[ChatWidget] load conversation', e); }
    }

    async function deleteConversation(id) {
        try {
            await fetch('/api/conversations/' + id, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
                credentials: 'same-origin',
            });
            state.conversations = state.conversations.filter(c => c.id !== id);
            if (state.conversationId === id) {
                state.conversationId = null;
                state.messages = [];
                persistState();
                renderMessages();
            }
            renderSidebar();
        } catch (e) { console.error('[ChatWidget] delete conversation', e); }
    }

    async function handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Reset the file input
        event.target.value = '';

        // Check file size (limit to 100MB for all file types)
        if (file.size > 100 * 1024 * 1024) {
            state.messages.push({ role: 'assistant', content: 'File is too large. Please upload a file smaller than 100MB.' });
            renderMessages();
            return;
        }

        try {
            // Show upload progress message
            const uploadMessage = `📎 **Uploading file:** ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)

Please wait while the file is being uploaded...`;

            state.messages.push({ role: 'user', content: uploadMessage });
            renderMessages();

            // Upload file directly to server
            const formData = new FormData();
            formData.append('file', file);

            const uploadResponse = await fetch('/api/temp-file-upload', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: formData
            });

            if (!uploadResponse.ok) {
                const errorData = await uploadResponse.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${uploadResponse.status}`);
            }

            const uploadResult = await uploadResponse.json();

            if (!uploadResult.success) {
                throw new Error(uploadResult.message || 'Upload failed');
            }

            // Update the upload message to show success
            state.messages[state.messages.length - 1].content = `📎 **File uploaded successfully:** ${file.name} (${uploadResult.data.file_size_formatted})

Processing file with AI Assistant...`;
            renderMessages();

            // Send file processing request to AI with file path
            const fileProcessingMessage = `I've uploaded a file: "${uploadResult.data.original_name}" (${uploadResult.data.file_type}, ${uploadResult.data.file_size_formatted}).

The file has been stored at: ${uploadResult.data.file_path}

PLEASE IMMEDIATELY USE THE DOCUMENT_PROCESSOR TOOL to extract property information from this uploaded file.

Use the document_processor tool with these parameters:
- file_path: ${uploadResult.data.file_path}
- type: auto (to auto-detect if it's a project or lot)

The document_processor tool is available to you and can process PDFs, images, Word docs, and all other file types.`;

            state.loading = true;
            renderMessages();

            const payload = {
                messages: [
                    ...state.messages,
                    { role: 'user', content: fileProcessingMessage }
                ],
                agent: state.agent,
            };
            if (state.conversationId) payload.conversation_id = state.conversationId;

            if (state.abortController) state.abortController.abort();
            state.abortController = new AbortController();

            const res = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/x-ndjson',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
                signal: state.abortController.signal,
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'HTTP ' + res.status);
            }

            await parseNdjsonStream(res.body);

        } catch (error) {
            console.error('[ChatWidget] file upload failed', error);
            state.messages.push({
                role: 'assistant',
                content: `❌ **File processing failed**\n\nError: ${error.message}\n\nPlease try:\n- Uploading a smaller file\n- Using a different file format\n- Refreshing the page and trying again`
            });
        } finally {
            state.loading = false;
            state.abortController = null;
            renderMessages();
        }
    }

    async function sendMessage() {
        const text = (inputEl?.value || '').trim();
        if (!text || state.loading) return;

        state.messages.push({ role: 'user', content: text });
        inputEl.value = '';
        state.loading = true;
        renderMessages();

        const payload = {
            messages: state.messages.map(m => ({ role: m.role, content: m.content })),
            agent: state.agent,
        };
        if (state.conversationId) payload.conversation_id = state.conversationId;

        if (state.abortController) state.abortController.abort();
        state.abortController = new AbortController();

        try {
            const res = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/x-ndjson',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
                signal: state.abortController.signal,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'HTTP ' + res.status);
            }
            await parseNdjsonStream(res.body);
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error('[ChatWidget] send failed', e);
                state.messages.push({ role: 'assistant', content: 'Sorry, something went wrong. Please try again.' });
            }
        } finally {
            state.loading = false;
            state.abortController = null;
            renderMessages();
        }
    }

    async function parseNdjsonStream(body) {
        const reader = body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let assistantIdx = null;

        function getIdx() {
            if (assistantIdx === null) {
                state.messages.push({ role: 'assistant', content: '' });
                assistantIdx = state.messages.length - 1;
            }
            return assistantIdx;
        }

        function handleEvent(event) {
            switch (event.type) {
                case 'CONVERSATION_CREATED':
                    state.conversationId = event.conversationId;
                    persistState();
                    break;
                case 'TEXT_MESSAGE_START':
                    getIdx();
                    renderMessages();
                    break;
                case 'TEXT_MESSAGE_CONTENT':
                    state.messages[getIdx()].content += event.delta ?? '';
                    renderMessages();
                    break;
                case 'CONVERSATION_TITLE_UPDATED': {
                    const ex = state.conversations.find(c => c.id === event.conversationId);
                    if (ex) { ex.title = event.title; }
                    else { state.conversations.unshift({ id: event.conversationId, title: event.title }); }
                    renderSidebar();
                    break;
                }
                case 'RUN_FINISHED':
                    fetchConversations();
                    break;
            }
        }

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();
            for (const line of lines) {
                if (!line.trim()) continue;
                try { handleEvent(JSON.parse(line)); } catch {}
            }
        }
        if (buffer.trim()) {
            try { handleEvent(JSON.parse(buffer)); } catch {}
        }
    }

    function toggle() {
        state.open = !state.open;
        persistState();
        updateUI();
        if (state.open) {
            fetchConversations();
            setTimeout(() => { inputEl?.focus(); scrollToBottom(); }, 50);
        }
    }

    function newConversation() {
        state.conversationId = null;
        state.messages = [];
        state.showSidebar = false;
        persistState();
        updateUI();
        renderMessages();
        renderSidebar();
        setTimeout(() => inputEl?.focus(), 50);
    }

    function buildDOM() {
        root = document.getElementById('chat-widget-root');
        if (!root) return;

        root.innerHTML = `
            <button id="chat-widget-fab" title="Chat with AI Assistant">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </button>
            <div id="chat-widget-panel">
                <div class="cw-header">
                    <div class="cw-header-left">
                        <button id="cw-sidebar-toggle" title="Conversations">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </button>
                        <h3>AI Assistant</h3>
                    </div>
                    <div class="cw-header-right">
                        <select id="cw-agent-select">
                            <option value="contact">Contact</option>
                            <option value="property">Property</option>
                            <option value="general">General</option>
                        </select>
                        <button id="cw-minimize" title="Minimize">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="cw-body">
                    <div class="cw-sidebar" id="cw-sidebar">
                        <div class="cw-sidebar-header">
                            <span>Conversations</span>
                            <button id="cw-new-conv" title="New conversation" style="background:transparent;color:#9ca3af;font-size:1.1rem;">+</button>
                        </div>
                        <div class="cw-sidebar-list" id="cw-sidebar-list"></div>
                    </div>
                    <div class="cw-messages-col">
                        <div class="cw-messages" id="cw-messages"></div>
                        <div class="cw-input-area">
                            <button class="cw-file-btn" id="cw-file-btn" title="Upload any file">
                                <input type="file" id="cw-file-input" accept="*/*" />
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </button>
                            <textarea id="cw-input" placeholder="Type a message..." rows="1"></textarea>
                            <button class="cw-send-btn" id="cw-send" title="Send">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        fab = document.getElementById('chat-widget-fab');
        panel = document.getElementById('chat-widget-panel');
        messagesEl = document.getElementById('cw-messages');
        inputEl = document.getElementById('cw-input');
        agentSelect = document.getElementById('cw-agent-select');
        sidebarEl = document.getElementById('cw-sidebar');
        sidebarList = document.getElementById('cw-sidebar-list');

        fab.addEventListener('click', toggle);
        document.getElementById('cw-minimize').addEventListener('click', toggle);
        document.getElementById('cw-sidebar-toggle').addEventListener('click', () => {
            state.showSidebar = !state.showSidebar;
            updateUI();
        });
        document.getElementById('cw-new-conv').addEventListener('click', newConversation);
        agentSelect.addEventListener('change', () => {
            state.agent = agentSelect.value;
            persistState();
        });
        document.getElementById('cw-send').addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // File upload handling
        const fileInput = document.getElementById('cw-file-input');
        fileInput.addEventListener('change', handleFileUpload);

        restoreState();
        updateUI();
        renderMessages();

        if (state.open) {
            fetchConversations();
            if (state.conversationId) loadConversation(state.conversationId);
        }
    }

    // Initialize: DOM should already be ready since this is at BODY_END
    if (document.getElementById('chat-widget-root')) {
        buildDOM();
    } else {
        document.addEventListener('DOMContentLoaded', buildDOM);
    }
})();
</script>
