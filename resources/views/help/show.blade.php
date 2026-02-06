<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('help.index') }}"
                   class="inline-flex items-center text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="ml-1">Back to Help</span>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $title }}
                </h2>
            </div>
            <a href="{{ route('help.pdf', $filename) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-8">
                    <!-- Documentation Content -->
                    <div class="prose prose-blue max-w-none">
                        <style>
                            /* Custom styling for documentation content */
                            .prose {
                                color: #374151;
                                font-size: 1rem;
                                line-height: 1.75;
                            }
                            .prose h1 {
                                color: #111827;
                                font-size: 2.25rem;
                                font-weight: 800;
                                margin-top: 0;
                                margin-bottom: 1.5rem;
                                line-height: 1.2;
                            }
                            .prose h2 {
                                color: #1f2937;
                                font-size: 1.875rem;
                                font-weight: 700;
                                margin-top: 2rem;
                                margin-bottom: 1rem;
                                padding-bottom: 0.5rem;
                                border-bottom: 2px solid #e5e7eb;
                            }
                            .prose h3 {
                                color: #374151;
                                font-size: 1.5rem;
                                font-weight: 600;
                                margin-top: 1.5rem;
                                margin-bottom: 0.75rem;
                            }
                            .prose h4 {
                                color: #4b5563;
                                font-size: 1.25rem;
                                font-weight: 600;
                                margin-top: 1.25rem;
                                margin-bottom: 0.5rem;
                            }
                            .prose p {
                                margin-bottom: 1.25rem;
                            }
                            .prose ul, .prose ol {
                                margin-top: 0.75rem;
                                margin-bottom: 1.25rem;
                                padding-left: 1.5rem;
                            }
                            .prose li {
                                margin-bottom: 0.5rem;
                            }
                            .prose strong {
                                color: #111827;
                                font-weight: 600;
                            }
                            .prose em {
                                font-style: italic;
                                color: #6b7280;
                            }
                            .prose a {
                                color: #2563eb;
                                text-decoration: underline;
                                font-weight: 500;
                            }
                            .prose a:hover {
                                color: #1d4ed8;
                            }
                            .prose code {
                                background-color: #f3f4f6;
                                color: #1f2937;
                                padding: 0.2rem 0.4rem;
                                border-radius: 0.25rem;
                                font-size: 0.875rem;
                                font-family: 'Courier New', monospace;
                            }
                            .prose pre {
                                background-color: #1f2937;
                                color: #f3f4f6;
                                padding: 1rem;
                                border-radius: 0.5rem;
                                overflow-x: auto;
                                margin-top: 1rem;
                                margin-bottom: 1.5rem;
                            }
                            .prose pre code {
                                background-color: transparent;
                                color: #f3f4f6;
                                padding: 0;
                                font-size: 0.875rem;
                            }
                            .prose hr {
                                border: 0;
                                border-top: 2px solid #e5e7eb;
                                margin: 2rem 0;
                            }
                            .prose blockquote {
                                border-left: 4px solid #3b82f6;
                                padding-left: 1rem;
                                color: #6b7280;
                                font-style: italic;
                                margin: 1.5rem 0;
                            }
                            .prose table {
                                width: 100%;
                                border-collapse: collapse;
                                margin: 1.5rem 0;
                            }
                            .prose th {
                                background-color: #f3f4f6;
                                font-weight: 600;
                                text-align: left;
                                padding: 0.75rem;
                                border: 1px solid #d1d5db;
                            }
                            .prose td {
                                padding: 0.75rem;
                                border: 1px solid #d1d5db;
                            }
                            .prose img {
                                max-width: 100%;
                                height: auto;
                                border-radius: 0.5rem;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                                margin: 1.5rem 0;
                            }
                        </style>

                        {!! $content !!}
                    </div>

                    <!-- Navigation Footer -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('help.index') }}"
                               class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back to Help Center
                            </a>
                            <a href="{{ route('help.pdf', $filename) }}"
                               class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download as PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Note -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Need more help? Look for the <span class="inline-flex items-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span> icon throughout the application for context-specific help tips.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
