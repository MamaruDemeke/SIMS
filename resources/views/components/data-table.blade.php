@props(['striped' => true])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
