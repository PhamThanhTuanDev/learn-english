<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hôm nay
        </h2>
    </x-slot>

    @php
        $user = Auth::user();
        $today = $user->localNow()->locale(app()->getLocale());
        $day = $user->roadmapDayNumber();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">Chào {{ $user->name }}!</p>
                    <p class="mt-1 text-gray-600">{{ ucfirst($today->isoFormat('dddd, DD/MM/YYYY')) }}</p>

                    <div class="mt-4">
                        @if ($user->roadmap_start_date === null)
                            <p>
                                Bạn chưa chọn ngày bắt đầu lộ trình.
                                <a href="{{ route('settings.edit') }}" class="underline text-indigo-600 hover:text-indigo-800">Chọn trong Cài đặt học</a>.
                            </p>
                        @elseif ($day === null)
                            <p class="text-gray-600">Lộ trình bắt đầu ngày {{ $user->roadmap_start_date->format('d/m/Y') }}.</p>
                        @elseif ($day > 728)
                            <p class="text-gray-600">Bạn đã đi hết 104 tuần của lộ trình.</p>
                        @else
                            <p class="text-lg font-medium">Tuần {{ intdiv($day - 1, 7) + 1 }} · Ngày {{ ($day - 1) % 7 + 1 }}</p>
                            <p class="mt-1 text-gray-600">Ngày thứ {{ $day }}/728 của lộ trình · mục tiêu {{ $user->daily_goal_minutes }} phút.</p>
                        @endif
                    </div>

                    <p class="mt-4 text-gray-600">Việc học của hôm nay theo lộ trình 104 tuần sẽ hiển thị ở đây (Phase 3).</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
