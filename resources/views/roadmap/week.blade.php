<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tuần {{ $week->number }}
        </h2>
    </x-slot>

    @php
        $month = $week->month;
        $locale = app()->getLocale();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500">
                        <a href="{{ route('roadmap') }}" class="underline hover:text-gray-800">Lộ trình</a> ›
                        <a href="{{ route('roadmap.months.show', $month->number) }}" class="underline hover:text-gray-800">Tháng {{ $month->number }}</a> ›
                        Tuần {{ $week->number }}
                    </p>
                    <p class="mt-2 text-lg font-medium">Tuần {{ $week->number }}: {{ $week->title }}</p>
                    <p class="mt-1 text-gray-600">Tháng {{ $month->number }} · Giai đoạn {{ $month->stage->number }}: {{ $month->stage->title }}</p>

                    <x-week-days class="mt-4" :days="$days" :completions="$completions" :today="$today" />

                    @if ($week->number === $month->last_week)
                        <p class="mt-4 text-sm">
                            Tuần cuối của tháng {{ $month->number }}:
                            <a href="{{ route('roadmap.months.show', $month->number) }}#gate" class="underline text-indigo-600 hover:text-indigo-800">nhập kết quả cổng kiểm tra</a>.
                        </p>
                    @endif

                    <p class="mt-4 flex gap-6 text-sm">
                        @if ($week->number > 1)
                            <a href="{{ route('roadmap.weeks.show', $week->number - 1) }}" class="underline text-indigo-600 hover:text-indigo-800">← Tuần {{ $week->number - 1 }}</a>
                        @endif
                        @if ($week->number < 104)
                            <a href="{{ route('roadmap.weeks.show', $week->number + 1) }}" class="underline text-indigo-600 hover:text-indigo-800">Tuần {{ $week->number + 1 }} →</a>
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Các ngày trong tuần</h3>
                    <p class="mt-1 text-sm text-gray-600">Ngày đã qua mà chưa học thì đánh dấu học bù ở đây.</p>

                    <ul class="mt-4 divide-y divide-gray-100">
                        @foreach ($days as $day)
                            @php
                                $completion = $completions->get($day->id);
                                $date = $user->roadmapDayDate($day->number);
                                $reached = $today !== null && $day->number <= $today;
                                // Ticked on a later date than the day itself: the day was made up.
                                $madeUp = $completion !== null && $date !== null
                                    && $completion->completed_at->setTimezone($user->timezone)->toDateString() > $date->toDateString();
                                // One form per day: only the form that was sent shows its old input and errors.
                                $sent = (int) old('day') === $day->number;
                            @endphp
                            <li id="day-{{ $day->number }}" class="py-4">
                                <p class="font-medium">
                                    N{{ $day->day }} · {{ $day->label }}
                                    @if ($date !== null)
                                        <span class="font-normal text-gray-500">· {{ ucfirst($date->locale($locale)->isoFormat('dddd, DD/MM/YYYY')) }}</span>
                                    @endif
                                    @if ($day->number === $today)
                                        <span class="ms-1 text-xs font-medium text-indigo-700">hôm nay</span>
                                    @endif
                                </p>
                                <p class="mt-1 text-gray-700">{{ $day->task }}</p>

                                @if ($completion !== null)
                                    <p class="mt-2 text-sm font-medium text-green-700">
                                        ✓ Đã xong lúc {{ $completion->completed_at->setTimezone($user->timezone)->format('H:i d/m/Y') }} · {{ $completion->minutes }} phút{{ $madeUp ? ' · học bù' : '' }}
                                    </p>
                                @endif

                                @if ($reached)
                                    <form method="post" action="{{ route('days.completion.update', ['roadmapDay' => $day->number]) }}" class="mt-3 flex flex-wrap items-end gap-3">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="day" value="{{ $day->number }}">

                                        <div>
                                            <x-input-label for="minutes-{{ $day->number }}" value="Số phút đã học" />
                                            <x-text-input id="minutes-{{ $day->number }}" name="minutes" type="number" min="0" max="600" class="mt-1 block w-32" required
                                                :value="$sent ? old('minutes') : ($completion?->minutes ?? $user->daily_goal_minutes)" />
                                        </div>

                                        <x-secondary-button type="submit">
                                            {{ $completion !== null ? 'Lưu số phút' : ($day->number < $today ? 'Đánh dấu học bù' : 'Đánh dấu đã xong') }}
                                        </x-secondary-button>

                                        @if (session('status') === 'day-saved' && session('saved_day') === $day->number)
                                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                                class="text-sm text-gray-600">Đã lưu.</p>
                                        @endif

                                        @if ($sent)
                                            <x-input-error class="w-full" :messages="$errors->get('minutes')" />
                                        @endif
                                    </form>

                                    @if ($completion !== null)
                                        <form method="post" action="{{ route('days.completion.destroy', ['roadmapDay' => $day->number]) }}" class="mt-2">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">Bỏ đánh dấu đã xong</button>
                                        </form>
                                    @endif
                                @else
                                    <p class="mt-2 text-sm text-gray-500">Chưa tới ngày này.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Thông tin tuần</h3>

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
        </div>
    </div>
</x-app-layout>
