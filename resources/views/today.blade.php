@use('App\Support\StudyStats')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hôm nay
        </h2>
    </x-slot>

    @php
        $today = $user->localNow()->locale(app()->getLocale());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        @elseif ($number === null)
                            <p class="text-gray-600">Lộ trình bắt đầu ngày {{ $user->roadmap_start_date->format('d/m/Y') }}.</p>
                        @elseif ($number > 728)
                            <p class="text-gray-600">Bạn đã đi hết 104 tuần của lộ trình.</p>
                        @elseif ($day !== null)
                            <p class="text-lg font-medium">Tuần {{ $week->number }} · Ngày {{ $day->day }}: {{ $week->title }}</p>
                            <p class="mt-1 text-gray-600">
                                Ngày thứ {{ $day->number }}/728 · Tháng {{ $week->month->number }} ·
                                Giai đoạn {{ $stage->number }}: {{ $stage->title }} · mục tiêu {{ $user->daily_goal_minutes }} phút
                            </p>

                            <x-week-days class="mt-4" :days="$weekDays" :completions="$completions" :today="$day->number" />

                            <p class="mt-3 text-sm">
                                <a href="{{ route('roadmap.weeks.show', $week->number) }}" class="underline text-indigo-600 hover:text-indigo-800">Xem cả tuần {{ $week->number }}</a>
                                ·
                                <a href="{{ route('roadmap.months.show', $week->month->number) }}" class="underline text-indigo-600 hover:text-indigo-800">Tháng {{ $week->month->number }} và cổng kiểm tra</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if ($stats !== null)
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-0">
                    <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                        <p class="text-sm text-gray-600">Hoàn thành lộ trình</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats->percentDone(), 1, ',', '.') }}%</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $stats->doneDays }}/{{ $stats->totalDays }} ngày</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                        <p class="text-sm text-gray-600">Chuỗi ngày học</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats->currentStreak }} ngày</p>
                        <p class="mt-1 text-sm text-gray-500">Dài nhất: {{ $stats->longestStreak }} ngày</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                        <p class="text-sm text-gray-600">Tổng thời gian học</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ StudyStats::duration($stats->totalMinutes) }}</p>
                        <p class="mt-1 text-sm text-gray-500">Trung bình {{ $stats->averageMinutes() }} phút/ngày đã xong</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                        <p class="text-sm text-gray-600">Cổng tháng đã đạt</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats->gatesPassed }}/{{ $stats->totalGates }}</p>
                        <p class="mt-1 text-sm"><a href="{{ route('roadmap') }}" class="underline text-indigo-600 hover:text-indigo-800">Xem lộ trình</a></p>
                    </div>
                </div>
            @endif

            @if ($day !== null)
                @php
                    $completion = $completions->get($day->id);
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Việc của hôm nay: {{ $day->label }}</h3>
                        <p class="mt-2 text-gray-700">{{ $day->task }}</p>

                        @if ($completion !== null)
                            <p class="mt-4 font-medium text-green-700">
                                ✓ Đã xong lúc {{ $completion->completed_at->setTimezone($user->timezone)->format('H:i d/m/Y') }} · {{ $completion->minutes }} phút
                            </p>
                        @endif

                        <form method="post" action="{{ route('days.completion.update', ['roadmapDay' => $day->number]) }}" class="mt-4 flex flex-wrap items-end gap-3">
                            @csrf
                            @method('put')

                            <div>
                                <x-input-label for="minutes" value="Số phút đã học" />
                                <x-text-input id="minutes" name="minutes" type="number" min="0" max="600" class="mt-1 block w-32" required
                                    :value="old('minutes', $completion?->minutes ?? $user->daily_goal_minutes)" />
                            </div>

                            <x-primary-button>{{ $completion === null ? 'Đánh dấu đã xong' : 'Lưu số phút' }}</x-primary-button>

                            @if (session('status') === 'day-saved')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                    class="text-sm text-gray-600">Đã lưu.</p>
                            @endif

                            <x-input-error class="w-full" :messages="$errors->get('minutes')" />
                        </form>

                        @if ($completion !== null)
                            <form method="post" action="{{ route('days.completion.destroy', ['roadmapDay' => $day->number]) }}" class="mt-3">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">Bỏ đánh dấu đã xong</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Các khối cố định mỗi ngày</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Giai đoạn {{ $stage->number }} · tổng {{ array_sum(array_column($stage->blocks, 'minutes')) }} phút
                        </p>

                        <ul class="mt-4 divide-y divide-gray-100">
                            @foreach ($stage->blocks as $block)
                                <li class="py-3 sm:flex sm:gap-4">
                                    <p class="sm:w-56 shrink-0 font-medium">
                                        {{ $block['title'] }} <span class="font-normal text-gray-500">· {{ $block['minutes'] }} phút</span>
                                    </p>
                                    <p class="text-gray-700">{{ $block['description'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Tuần {{ $week->number }}: {{ $week->title }}</h3>

                        <dl class="mt-4 divide-y divide-gray-100">
                            @foreach ($week->details as $detail)
                                <div @class(['py-3 sm:flex sm:gap-4', 'bg-amber-50 -mx-2 px-2 rounded-md' => $detail['highlight']])>
                                    <dt class="sm:w-56 shrink-0 font-medium">{{ $detail['label'] }}</dt>
                                    <dd class="text-gray-700">{{ $detail['text'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                @if ($resources->isNotEmpty())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium">Tài liệu nhắc tới hôm nay</h3>

                            <ul class="mt-4 space-y-2">
                                @foreach ($resources as $resource)
                                    <li>
                                        <span class="font-mono text-sm text-gray-500">{{ $resource->code }}</span>
                                        @if (str_starts_with($resource->source, 'https://'))
                                            <a href="{{ $resource->source }}" target="_blank" rel="noopener noreferrer"
                                                class="underline text-indigo-600 hover:text-indigo-800">{{ $resource->name }}</a>
                                        @else
                                            <span class="font-medium">{{ $resource->name }}</span> ({{ $resource->source }})
                                        @endif
                                        <span class="text-gray-600">– {{ $resource->usage }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @endif

            @if ($stats !== null && $stats->months !== [])
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Thời gian học theo tháng</h3>
                        <p class="mt-1 text-sm text-gray-600">Theo tháng của lộ trình; ngày học bù tính vào tháng của ngày đó.</p>

                        <ul class="mt-4 space-y-3">
                            @foreach ($stats->months as $month)
                                <li class="sm:flex sm:items-center sm:gap-4">
                                    <p class="sm:w-36 shrink-0">
                                        <a href="{{ route('roadmap.months.show', $month['number']) }}" class="font-medium underline text-indigo-600 hover:text-indigo-800">Tháng {{ $month['number'] }}</a>
                                        @if ($month['current'])
                                            <span class="ms-1 text-xs font-medium text-indigo-700">đang học</span>
                                        @endif
                                    </p>
                                    <div class="mt-1 sm:mt-0 h-3 flex-1 rounded-full bg-gray-100" aria-hidden="true">
                                        <div class="h-3 rounded-full bg-indigo-500" style="width: {{ $month['share'] }}%"></div>
                                    </div>
                                    <p class="mt-1 sm:mt-0 sm:w-44 shrink-0 text-sm text-gray-700 sm:text-right">
                                        {{ StudyStats::duration($month['minutes']) }} · {{ $month['days'] }} ngày
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
