@extends('layouts.app')

@section('title', 'GL Entry uploader')

@section('content')
<div class="mx-auto max-w-5xl space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold">GL Entry uploader</h2>
        <p class="mt-1 text-sm text-gray-600">Upload a CSV with required GL columns. Use Loft username and password.</p>

        <form id="glUploadForm" class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Loft Username</label>
                    <input type="text" name="loft_username" class="mt-1 w-full rounded-md border border-gray-300 p-2" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" class="mt-1 w-full rounded-md border border-gray-300 p-2" required />
                </div>
            </div>

            <div id="dropzone" class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-md border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center hover:bg-gray-100">
                <input id="fileInput" type="file" accept=".csv" class="hidden" />
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-10 text-gray-500"><path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h4.135a2.25 2.25 0 011.59.659l1.207 1.207c.14.14.33.219.53.219H20.25A2.25 2.25 0 0122.5 8.25v9A2.25 2.25 0 0120.25 19.5H3.75A2.25 2.25 0 011.5 17.25V6zm5.25 6a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z" clip-rule="evenodd"/></svg>
                <p class="mt-2 text-sm text-gray-700">Drag and drop your CSV here, or click to select</p>
                <p id="fileName" class="mt-1 text-xs text-gray-500"></p>
            </div>

            <div class="flex items-center gap-3">
                <button id="uploadBtn" type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Upload</button>
                <div id="loader" class="hidden h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"></div>
                <span id="message" class="text-sm"></span>
            </div>
        </form>
    </div>

    <div id="failurePanel" class="hidden rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">Failed Records</h3>
            <button id="downloadCsvBtn" class="rounded-md bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200">Download CSV</button>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <input id="searchInput" type="text" placeholder="Search..." class="w-64 rounded-md border border-gray-300 p-2 text-sm" />
        </div>
        <div class="mt-4 overflow-x-auto">
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
                        <th class="px-3 py-2">Failure Reason</th>
                    </tr>
                </thead>
                <tbody id="failedTbody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <div id="paginationInfo" class="text-xs text-gray-600"></div>
            <div class="flex items-center gap-2">
                <button id="prevPage" class="rounded-md border px-2 py-1 text-sm disabled:opacity-50">Prev</button>
                <button id="nextPage" class="rounded-md border px-2 py-1 text-sm disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection


