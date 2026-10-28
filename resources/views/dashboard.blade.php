<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hôm nay
        </h2>
    </x-slot>

    @php($today = Auth::user()->localNow()->locale(app()->getLocale()))

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">Chào {{ Auth::user()->name }}!</p>
                    <p class="mt-1 text-gray-600">{{ ucfirst($today->isoFormat('dddd, DD/MM/YYYY')) }}</p>
                    <p class="mt-4 text-gray-600">Việc học của hôm nay theo lộ trình 104 tuần sẽ hiển thị ở đây (Phase 3).</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
