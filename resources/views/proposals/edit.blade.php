<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('proposals.show', $proposal) }}" class="text-gray-500 hover:text-gray-700">← {{ $proposal->title }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Proposal</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('proposals.update', $proposal) }}" method="POST" class="space-y-6 bg-white rounded-xl border border-gray-200 p-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $proposal->title) }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="client_name" class="block text-sm font-medium text-gray-700">Client name *</label>
                        <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $proposal->client_name) }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        @error('client_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="client_email" class="block text-sm font-medium text-gray-700">Client email</label>
                        <input type="email" name="client_email" id="client_email" value="{{ old('client_email', $proposal->client_email) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        @error('client_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="client_company" class="block text-sm font-medium text-gray-700">Company</label>
                    <input type="text" name="client_company" id="client_company" value="{{ old('client_company', $proposal->client_company) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    @error('client_company')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="summary" class="block text-sm font-medium text-gray-700">Summary</label>
                    <textarea name="summary" id="summary" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old('summary', $proposal->summary) }}</textarea>
                    @error('summary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">Details / Terms</label>
                    <textarea name="body" id="body" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old('body', $proposal->body) }}</textarea>
                    @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount *</label>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $proposal->amount) }}" step="0.01" min="0" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                        <input type="text" name="currency" id="currency" value="{{ old('currency', $proposal->currency) }}" maxlength="3"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="valid_until" class="block text-sm font-medium text-gray-700">Valid until</label>
                        <input type="date" name="valid_until" id="valid_until" value="{{ old('valid_until', $proposal->valid_until?->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        @error('valid_until')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        <option value="draft" {{ old('status', $proposal->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ old('status', $proposal->status) === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="accepted" {{ old('status', $proposal->status) === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="declined" {{ old('status', $proposal->status) === 'declined' ? 'selected' : '' }}>Declined</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('proposals.show', $proposal) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">Update Proposal</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
