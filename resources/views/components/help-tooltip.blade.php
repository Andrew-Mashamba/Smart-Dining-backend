@props(['text', 'position' => 'top'])

<div x-data="{
    show: false,
    position: '{{ $position }}',
    positionClasses() {
        const positions = {
            'top': 'bottom-full left-1/2 transform -translate-x-1/2 mb-2',
            'bottom': 'top-full left-1/2 transform -translate-x-1/2 mt-2',
            'left': 'right-full top-1/2 transform -translate-y-1/2 mr-2',
            'right': 'left-full top-1/2 transform -translate-y-1/2 ml-2'
        };
        return positions[this.position] || positions['top'];
    },
    arrowClasses() {
        const arrows = {
            'top': 'top-full left-1/2 transform -translate-x-1/2 border-t-gray-900',
            'bottom': 'bottom-full left-1/2 transform -translate-x-1/2 border-b-gray-900',
            'left': 'left-full top-1/2 transform -translate-y-1/2 border-l-gray-900',
            'right': 'right-full top-1/2 transform -translate-y-1/2 border-r-gray-900'
        };
        return arrows[this.position] || arrows['top'];
    }
}"
@mouseenter="show = true"
@mouseleave="show = false"
class="relative inline-flex items-center">
    <!-- Help Icon -->
    <button type="button"
            class="inline-flex items-center justify-center w-5 h-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 rounded-full transition-colors"
            @click="show = !show">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </button>

    <!-- Tooltip -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         :class="positionClasses()"
         class="absolute z-50 px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm whitespace-normal max-w-xs"
         style="display: none;"
         role="tooltip">
        {{ $text }}

        <!-- Tooltip Arrow -->
        <div :class="arrowClasses()"
             class="absolute w-0 h-0 border-4 border-transparent"></div>
    </div>
</div>
