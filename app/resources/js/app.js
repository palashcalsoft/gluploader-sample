import './bootstrap';

// Sidebar toggle hooks for any future enhancements
export function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const collapseBtn = document.getElementById('collapseBtn');

    function toggleSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('hidden');
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
    if (collapseBtn) collapseBtn.addEventListener('click', () => {
        if (!sidebar) return;
        sidebar.classList.toggle('w-64');
        sidebar.classList.toggle('w-16');
        sidebar.classList.toggle('collapsed');
    });
}

if (document.readyState !== 'loading') {
    initSidebarToggle();
    initGLUploader();
    initUploadsTable();
} else {
    document.addEventListener('DOMContentLoaded', () => {
        initSidebarToggle();
        initGLUploader();
        initUploadsTable();
    });
}

function formatDateTime(value) {
	if (!value) return '';
	try {
		const date = value instanceof Date ? value : new Date(value);
		if (isNaN(date.getTime())) return String(value);
		return new Intl.DateTimeFormat(undefined, {
			year: 'numeric',
			month: 'short',
			day: '2-digit',
			hour: '2-digit',
			minute: '2-digit',
			hour12: false,
		}).format(date);
	} catch (e) {
		return String(value);
	}
}

function initGLUploader() {
    const form = document.getElementById('glUploadForm');
    if (!form) return; // Only on uploader page

    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const loader = document.getElementById('loader');
    const message = document.getElementById('message');
    const fullscreenLoader = document.getElementById('fullscreenLoader');

    // legacy variables removed after UI change

    function setMessage(text, type = 'info') {
        message.textContent = text;
        message.className = 'text-sm ' + (type === 'success' ? 'text-green-600' : type === 'error' ? 'text-red-600' : 'text-gray-700');
    }

    function showLoader(show) {
        loader.classList.toggle('hidden', !show);
    }

    function showFullscreenLoader(show) {
        if (!fullscreenLoader) return;
        fullscreenLoader.classList.toggle('hidden', !show);
    }

    function handleFiles(files) {
        if (!files || files.length === 0) return;
        if (files.length > 1) {
            setMessage('Please select only one file.', 'error');
            return;
        }
        const file = files[0];
        if (!file.name.toLowerCase().endsWith('.csv')) {
            setMessage('Only CSV files are allowed.', 'error');
            return;
        }
        fileInput.files = files;
        fileName.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        setMessage('');
    }

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('bg-gray-100');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('bg-gray-100'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('bg-gray-100');
        handleFiles(e.dataTransfer.files);
    });
    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        setMessage('');
        // failed panel removed

        const file = fileInput.files?.[0];
        if (!file) {
            setMessage('Please select a CSV file.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('loft_username', form.loft_username.value);
        formData.append('password', form.password.value);

        showLoader(true);
        showFullscreenLoader(true);
        try {
            const resp = await window.axios.post('/gl/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setMessage('Upload successful.', 'success');
            form.reset();
            fileName.textContent = '';
            // refresh uploads table if present
            if (typeof window.__refreshUploads === 'function') window.__refreshUploads();
        } catch (err) {
            const data = err?.response?.data;
            setMessage(data?.message || 'Upload failed.', 'error');
        } finally {
            showLoader(false);
            showFullscreenLoader(false);
        }
    });
}

function initUploadsTable() {
    const tbody = document.getElementById('uploadsTbody');
    const info = document.getElementById('uploadsInfo');
    const prev = document.getElementById('uploadsPrev');
    const next = document.getElementById('uploadsNext');
    if (!tbody || !info || !prev || !next) return;

    let pageUrl = '/gl/masters';
    let pollTimer = null;

    async function load(url) {
        const resp = await window.axios.get(url);
        const data = resp.data;
        const items = data.data || [];
        tbody.innerHTML = items.map(item => {
            const statusMap = { 'In Progress': 'text-blue-600', 'Success': 'text-green-600', 'Failed': 'text-red-600' };
            const statusClass = statusMap[item.status] || 'text-gray-700';
            return `<tr>
                <td class="px-3 py-2">${item.id}</td>
                <td class="px-3 py-2">${item.uploaded_by ?? ''}</td>
                <td class="px-3 py-2">${item.file_name ?? ''}</td>
                <td class="px-3 py-2">${formatDateTime(item.uploaded_at)}</td>
                <td class="px-3 py-2">${item.total_rows ?? 0}</td>
                <td class="px-3 py-2 ${statusClass}">${item.status ?? ''}</td>
                <td class="px-3 py-2">
                    <a target="_blank" rel="noopener" href="/gl/masters/${item.id}" class="text-indigo-600 hover:underline">Open</a>
                </td>
            </tr>`;
        }).join('');

        info.textContent = `Page ${data.current_page} of ${data.last_page} • ${data.total} total`;
        prev.disabled = !data.prev_page_url;
        next.disabled = !data.next_page_url;
        prev.onclick = () => { if (data.prev_page_url) load(data.prev_page_url); };
        next.onclick = () => { if (data.next_page_url) load(data.next_page_url); };
    }

    window.__refreshUploads = () => load(pageUrl).catch(() => {});
    const startPolling = () => {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => load(pageUrl).catch(() => {}), 3000);
    };
    load(pageUrl).catch(() => {}).finally(startPolling);
}
