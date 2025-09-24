@extends('layouts.app')

@section('title', 'GL Entries Details')

@section('content')
<div class="mx-auto max-w-6xl space-y-4" x-data="glDetails()" x-init="init()">
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">GL Entries File #<span x-text="master?.id"></span></h2>
            <div class="text-sm text-gray-600">Status: <span class="font-medium" x-text="master?.status"></span></div>
        </div>
        <div class="mt-1 text-sm text-gray-600">
            File: <span x-text="master?.file_name"></span> • Uploaded by <span x-text="master?.uploaded_by"></span>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="mb-3 flex items-center gap-4 border-b pb-2">
            <button :class="tab==='failed' ? 'font-semibold' : 'text-gray-600'" @click="tab='failed'">Validation failed</button>
            <button :class="tab==='success' ? 'font-semibold' : 'text-gray-600'" @click="tab='success'">Success</button>
        </div>

        <template x-if="tab==='failed'">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <input x-model="search" type="text" placeholder="Search..." class="w-64 rounded-md border border-gray-300 p-2 text-sm" />
                    <button @click="retrySelected" class="rounded-md bg-gray-900 px-3 py-1.5 text-sm text-white">Retry selected</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2"><input type="checkbox" @change="toggleAll($event)"></th>
                                <th class="px-3 py-2">Failure Reason</th>
                                <th class="px-3 py-2">Row</th>
                                <th class="px-3 py-2">Posting Date</th>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Journal Code</th>
                                <th class="px-3 py-2">Account#</th>
                                <th class="px-3 py-2">Posting Description</th>
                                <th class="px-3 py-2">Debit</th>
                                <th class="px-3 py-2">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in filteredFailed()" :key="row.row_number">
                                <tr>
                                    <td class="px-3 py-2"><input type="checkbox" x-model="row.selected"></td>
                                    <td class="px-3 py-2 text-red-600" x-text="row.failure_reason"></td>
                                    <td class="px-3 py-2" x-text="row.row_number"></td>
                                    <td class="px-3 py-2"><input class="w-36 rounded border p-1 text-sm" x-model="row.posting_date"></td>
                                    <td class="px-3 py-2"><input class="w-28 rounded border p-1 text-sm" x-model="row.reference"></td>
                                    <td class="px-3 py-2"><input class="w-28 rounded border p-1 text-sm" x-model="row.journal_code"></td>
                                    <td class="px-3 py-2"><input class="w-28 rounded border p-1 text-sm" x-model="row.account_number"></td>
                                    <td class="px-3 py-2"><input class="w-64 rounded border p-1 text-sm" x-model="row.posting_description"></td>
                                    <td class="px-3 py-2"><input class="w-24 rounded border p-1 text-sm" x-model="row.debit"></td>
                                    <td class="px-3 py-2"><input class="w-24 rounded border p-1 text-sm" x-model="row.credit"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template x-if="tab==='success'">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Row</th>
                            <th class="px-3 py-2">Posting Date</th>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2">Journal Code</th>
                            <th class="px-3 py-2">Account#</th>
                            <th class="px-3 py-2">Posting Description</th>
                            <th class="px-3 py-2">Debit</th>
                            <th class="px-3 py-2">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in success" :key="row.row_number">
                            <tr>
                                <td class="px-3 py-2" x-text="row.row_number"></td>
                                <td class="px-3 py-2" x-text="row.posting_date"></td>
                                <td class="px-3 py-2" x-text="row.reference"></td>
                                <td class="px-3 py-2" x-text="row.journal_code"></td>
                                <td class="px-3 py-2" x-text="row.account_number"></td>
                                <td class="px-3 py-2" x-text="row.posting_description"></td>
                                <td class="px-3 py-2" x-text="row.debit"></td>
                                <td class="px-3 py-2" x-text="row.credit"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

<script>
function glDetails() {
    return {
        master: null,
        failed: [],
        success: [],
        tab: 'failed',
        search: '',
        id: null,
        async init() {
            const match = window.location.pathname.match(/\/(\d+)/);
            this.id = match ? match[1] : null;
            const resp = await window.axios.get(`/api/gl/masters/${this.id}`);
            this.master = resp.data.master;
            this.failed = (resp.data.failed || []).map(r => ({...r, selected: false}));
            this.success = resp.data.success || [];
        },
        filteredFailed() {
            const q = this.search.toLowerCase();
            if (!q) return this.failed;
            return this.failed.filter(r => JSON.stringify(r).toLowerCase().includes(q));
        },
        toggleAll(e) { this.failed.forEach(r => r.selected = e.target.checked); },
        async retrySelected() {
            const rows = this.failed.filter(r => r.selected).map(({selected, ...rest}) => rest);
            if (rows.length === 0) return;
            try {
                const overlay = document.getElementById('fullscreenLoader');
                if (overlay) overlay.classList.remove('hidden');
                await window.axios.post(`/gl/masters/${this.id}/retry`, { rows });
                await this.init();
                alert('Retry successful for selected rows.');
            } catch (err) {
                const data = err?.response?.data;
                if (data?.failed_records) {
                    const messages = data.failed_records.map(r => r.failure_reason).filter(Boolean);
                    if (messages.length) {
                        alert(messages.join('\n'));
                    } else {
                        alert('Some rows still failing, please fix and retry.');
                    }
                    const map = new Map(data.failed_records.map(r => [r.row_number, r.failure_reason]));
                    this.failed.forEach(r => { if (map.has(r.row_number)) r.failure_reason = map.get(r.row_number); });
                } else {
                    alert(data?.message || 'Retry failed');
                }
            } finally {
                const overlay = document.getElementById('fullscreenLoader');
                if (overlay) overlay.classList.add('hidden');
            }
        }
    }
}
</script>
@endsection


