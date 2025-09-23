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
} else {
    document.addEventListener('DOMContentLoaded', () => {
        initSidebarToggle();
        initGLUploader();
    });
}

function initGLUploader() {
    const form = document.getElementById('glUploadForm');
    if (!form) return; // Only on uploader page

    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const loader = document.getElementById('loader');
    const message = document.getElementById('message');
    const failurePanel = document.getElementById('failurePanel');
    const failedTbody = document.getElementById('failedTbody');
    const searchInput = document.getElementById('searchInput');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const paginationInfo = document.getElementById('paginationInfo');
    const downloadCsvBtn = document.getElementById('downloadCsvBtn');
    const fullscreenLoader = document.getElementById('fullscreenLoader');

    let failedRecords = [];
    let filtered = [];
    let currentPage = 1;
    const pageSize = 100;

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
        failurePanel.classList.add('hidden');

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
        } catch (err) {
            const data = err?.response?.data;
            if (data && data.failed_records) {
                failedRecords = data.failed_records;
                filtered = failedRecords;
                currentPage = 1;
                renderTable();
                failurePanel.classList.remove('hidden');
                setMessage('Some rows failed validation. See details below.', 'error');
            } else {
                setMessage(data?.message || 'Upload failed.', 'error');
            }
        } finally {
            showLoader(false);
            showFullscreenLoader(false);
        }
    });

    function renderTable() {
        const q = searchInput.value.trim().toLowerCase();
        filtered = failedRecords.filter((r) => {
            if (!q) return true;
            return (
                String(r.row_number).includes(q) ||
                (r.posting_date || '').toLowerCase().includes(q) ||
                (r.reference || '').toLowerCase().includes(q) ||
                (r.journal_code || '').toLowerCase().includes(q) ||
                (r.account_number || '').toLowerCase().includes(q) ||
                (r.posting_description || '').toLowerCase().includes(q) ||
                String(r.debit || '').toLowerCase().includes(q) ||
                String(r.credit || '').toLowerCase().includes(q) ||
                (r.failure_reason || '').toLowerCase().includes(q)
            );
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const page = filtered.slice(start, start + pageSize);

        failedTbody.innerHTML = page
            .map((r) => {
                return `<tr>
                    <td class="px-3 py-2">${r.row_number ?? ''}</td>
                    <td class="px-3 py-2">${r.posting_date ?? ''}</td>
                    <td class="px-3 py-2">${r.reference ?? ''}</td>
                    <td class="px-3 py-2">${r.journal_code ?? ''}</td>
                    <td class="px-3 py-2">${r.account_number ?? ''}</td>
                    <td class="px-3 py-2">${r.posting_description ?? ''}</td>
                    <td class="px-3 py-2">${r.debit ?? ''}</td>
                    <td class="px-3 py-2">${r.credit ?? ''}</td>
                    <td class="px-3 py-2">${r.failure_reason ?? ''}</td>
                </tr>`;
            })
            .join('');

        paginationInfo.textContent = `Showing ${filtered.length === 0 ? 0 : start + 1}-${Math.min(
            start + page.length,
            filtered.length
        )} of ${filtered.length}`;
        prevPage.disabled = currentPage === 1;
        nextPage.disabled = currentPage >= totalPages;
    }

    prevPage.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });
    nextPage.addEventListener('click', () => {
        currentPage++;
        renderTable();
    });
    searchInput.addEventListener('input', () => {
        currentPage = 1;
        renderTable();
    });

    downloadCsvBtn.addEventListener('click', () => {
        const headers = [
            'Row','Posting Date','Reference','Journal Code','Account#','Posting Description','Debit','Credit','Failure Reason'
        ];
        const rows = filtered.map((r) => [
            r.row_number, r.posting_date, r.reference, r.journal_code, r.account_number, r.posting_description, r.debit, r.credit, r.failure_reason
        ]);
        const csv = [headers.join(','), ...rows.map((cols) => cols.map(escapeCsv).join(','))].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'failed_records.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    function escapeCsv(value) {
        const s = String(value ?? '');
        if (s.includes(',') || s.includes('"') || s.includes('\n')) {
            return '"' + s.replace(/"/g, '""') + '"';
        }
        return s;
    }
}
