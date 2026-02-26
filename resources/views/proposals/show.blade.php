<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('proposals.index') }}" class="text-gray-500 hover:text-gray-700">← Proposals</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $proposal->title }}</h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    @if($proposal->status === 'draft') bg-gray-100 text-gray-700
                    @elseif($proposal->status === 'sent') bg-blue-100 text-blue-800
                    @elseif($proposal->status === 'accepted') bg-green-100 text-green-800
                    @else bg-red-100 text-red-700 @endif">{{ ucfirst($proposal->status) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('proposals.edit', $proposal) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                <a href="{{ route('proposals.download', $proposal) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800">Download PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <dl class="divide-y divide-gray-200">
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Reference</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->reference }}</dd>
                    </div>
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Client</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->client_name }}{{ $proposal->client_company ? ' — ' . $proposal->client_company : '' }}</dd>
                    </div>
                    @if($proposal->client_email)
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->client_email }}</dd>
                    </div>
                    @endif
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->currency }} {{ number_format($proposal->amount, 2) }}</dd>
                    </div>
                    @if($proposal->valid_until)
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Valid until</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->valid_until->format('d M Y') }}</dd>
                    </div>
                    @endif
                    @if($proposal->summary)
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Summary</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->summary }}</dd>
                    </div>
                    @endif
                    @if($proposal->body)
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Details</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 whitespace-pre-wrap">{{ $proposal->body }}</dd>
                    </div>
                    @endif
                    <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ $proposal->created_at->format('d M Y H:i') }}{{ $proposal->creator ? ' by ' . $proposal->creator->name : '' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
