<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proposals</h2>
            <a href="{{ route('proposals.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                New Proposal
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                @forelse($proposals as $proposal)
                    <div class="flex items-center justify-between p-4 border-b border-gray-100 hover:bg-gray-50">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">{{ $proposal->title }}</span>
                                <span class="text-xs text-gray-500">{{ $proposal->reference }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($proposal->status === 'draft') bg-gray-100 text-gray-700
                                    @elseif($proposal->status === 'sent') bg-blue-100 text-blue-800
                                    @elseif($proposal->status === 'accepted') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($proposal->status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $proposal->client_name }} · {{ $proposal->currency }} {{ number_format($proposal->amount, 2) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('proposals.show', $proposal) }}" class="text-gray-600 hover:text-gray-900 text-sm">View</a>
                            <a href="{{ route('proposals.download', $proposal) }}" class="text-gray-600 hover:text-gray-900 text-sm">PDF</a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <p>No proposals yet.</p>
                        <a href="{{ route('proposals.create') }}" class="mt-2 inline-block text-gray-900 font-medium hover:underline">Create your first proposal</a>
                    </div>
                @endforelse
            </div>

            @if($proposals->hasPages())
                <div class="mt-4">{{ $proposals->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
