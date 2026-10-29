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

                            <ol class="mt-4 grid grid-cols-7 gap-2 max-w-md" aria-label="Các ngày trong tuần">
                                @foreach ($weekDays as $weekDay)
                                    @php
                                        $done = $completions->has($weekDay->id);
                                        $current = $weekDay->number === $day->number;
                                    @endphp
                                    <li title="{{ $weekDay->label }}{{ $done ? ' – đã xong' : '' }}"
                                        @class([
                                            'rounded-md py-2 text-center text-sm',
                                            'bg-green-100 text-green-800' => $done,
                                            'bg-gray-100 text-gray-600' => ! $done && $weekDay->number < $day->number,
                                            'border border-dashed border-gray-300 text-gray-400' => ! $done && $weekDay->number > $day->number,
                                            'border border-gray-300 text-gray-700' => ! $done && $current,
                                            'ring-2 ring-indigo-500' => $current,
                                        ])>
                                        N{{ $weekDay->day }}
                                        <span class="block text-xs">{{ $done ? '✓' : ($weekDay->is_rest ? 'nghỉ' : '·') }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </div>

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
        </div>
    </div>
</x-app-layout>
