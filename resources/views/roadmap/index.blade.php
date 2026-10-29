<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lộ trình
        </h2>
    </x-slot>

    @php
        $currentWeek = $today !== null && $today <= 728 ? intdiv($today - 1, 7) + 1 : null;
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">24 tháng · 104 tuần · 728 ngày</p>
                    <p class="mt-1 text-gray-600">
                        @if ($user->roadmap_start_date === null)
                            Bạn chưa chọn ngày bắt đầu lộ trình.
                            <a href="{{ route('settings.edit') }}" class="underline text-indigo-600 hover:text-indigo-800">Chọn trong Cài đặt học</a>.
                        @elseif ($currentWeek !== null)
                            Hôm nay là ngày {{ $today }}/728.
                            <a href="{{ route('roadmap.weeks.show', $currentWeek) }}" class="underline text-indigo-600 hover:text-indigo-800">Xem tuần {{ $currentWeek }}</a>.
                        @elseif ($today === null)
                            Lộ trình bắt đầu ngày {{ $user->roadmap_start_date->format('d/m/Y') }}.
                        @else
                            Bạn đã đi hết 104 tuần của lộ trình.
                        @endif
                    </p>
                </div>
            </div>

            @foreach ($stages as $months)
                @php
                    $stage = $months->first()->stage;
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Giai đoạn {{ $stage->number }}: {{ $stage->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $stage->goal }}</p>

                        <ul class="mt-4 divide-y divide-gray-100">
                            @foreach ($months as $month)
                                @php
                                    $gateResult = $gateResults->get($month->id);
                                    $isCurrent = $currentWeek !== null && $currentWeek >= $month->first_week && $currentWeek <= $month->last_week;
                                @endphp
                                <li @class(['py-3 sm:flex sm:items-baseline sm:gap-4', 'bg-indigo-50 -mx-2 px-2 rounded-md' => $isCurrent])>
                                    <p class="sm:w-52 shrink-0">
                                        <a href="{{ route('roadmap.months.show', $month->number) }}" class="font-medium underline text-indigo-600 hover:text-indigo-800">Tháng {{ $month->number }}</a>
                                        <span class="text-sm text-gray-500">· tuần {{ $month->first_week }}–{{ $month->last_week }}</span>
                                        @if ($isCurrent)
                                            <span class="ms-1 text-xs font-medium text-indigo-700">đang học</span>
                                        @endif
                                    </p>
                                    <p class="text-gray-700 sm:flex-1">{{ $month->focus }}</p>
                                    <p class="mt-1 sm:mt-0 shrink-0 text-sm text-gray-600">{{ $month->done_days_count }}/{{ $month->days_count }} ngày</p>
                                    <p class="shrink-0 sm:w-36 text-sm">
                                        @if ($gateResult === null)
                                            <span class="text-gray-500">Cổng: chưa kiểm tra</span>
                                        @elseif ($gateResult->passed)
                                            <span class="font-medium text-green-700">Cổng: ✓ Đạt</span>
                                        @else
                                            <span class="font-medium text-red-700">Cổng: ✗ Chưa đạt</span>
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
