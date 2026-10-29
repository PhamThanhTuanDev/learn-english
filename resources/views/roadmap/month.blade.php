<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tháng {{ $month->number }}
        </h2>
    </x-slot>

    @php
        $gateDay = $month->gateOpensOnDay();
        $gateOpen = $user->hasReachedRoadmapDay($gateDay);
        $gateDate = $user->roadmapDayDate($gateDay);
        $passed = old('passed', $gateResult === null ? null : (int) $gateResult->passed);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500">
                        <a href="{{ route('roadmap') }}" class="underline hover:text-gray-800">Lộ trình</a> › Tháng {{ $month->number }}
                    </p>
                    <p class="mt-2 text-lg font-medium">Tháng {{ $month->number }} · tuần {{ $month->first_week }}–{{ $month->last_week }}</p>
                    <p class="mt-1 text-gray-600">Giai đoạn {{ $month->stage->number }}: {{ $month->stage->title }}</p>
                    <p class="mt-3 text-gray-700"><span class="font-medium">Trọng tâm:</span> {{ $month->focus }}</p>

                    <p class="mt-4 flex gap-6 text-sm">
                        @if ($month->number > 1)
                            <a href="{{ route('roadmap.months.show', $month->number - 1) }}" class="underline text-indigo-600 hover:text-indigo-800">← Tháng {{ $month->number - 1 }}</a>
                        @endif
                        @if ($month->number < 24)
                            <a href="{{ route('roadmap.months.show', $month->number + 1) }}" class="underline text-indigo-600 hover:text-indigo-800">Tháng {{ $month->number + 1 }} →</a>
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Các tuần</h3>

                    <ul class="mt-4 divide-y divide-gray-100">
                        @foreach ($month->weeks as $week)
                            <li class="py-4">
                                <p>
                                    <a href="{{ route('roadmap.weeks.show', $week->number) }}" class="font-medium underline text-indigo-600 hover:text-indigo-800">Tuần {{ $week->number }}: {{ $week->title }}</a>
                                    <span class="text-sm text-gray-500">· {{ $week->days->filter(fn ($day) => $completions->has($day->id))->count() }}/7 ngày</span>
                                </p>
                                <x-week-days class="mt-2" :days="$week->days" :completions="$completions" :today="$today" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div id="gate" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Cổng kiểm tra cuối tháng</h3>
                    <p class="mt-2 text-gray-700">{{ $month->gate }}</p>

                    @if ($gateResult !== null)
                        <p @class(['mt-4 font-medium', 'text-green-700' => $gateResult->passed, 'text-red-700' => ! $gateResult->passed])>
                            {{ $gateResult->passed ? '✓ Đạt' : '✗ Chưa đạt' }}
                            <span class="font-normal text-gray-600">· ghi lúc {{ $gateResult->checked_at->setTimezone($user->timezone)->format('H:i d/m/Y') }}</span>
                        </p>
                        @if ($gateResult->note !== null)
                            <p class="mt-1 whitespace-pre-line text-gray-700">{{ $gateResult->note }}</p>
                        @endif
                    @elseif (session('status') === 'gate-deleted')
                        <p class="mt-4 text-sm text-gray-600">Đã xóa kết quả.</p>
                    @endif

                    @if ($gateOpen)
                        <form method="post" action="{{ route('months.gate.update', ['month' => $month->number]) }}" class="mt-6 space-y-4 max-w-xl">
                            @csrf
                            @method('put')

                            <fieldset>
                                <legend class="block font-medium text-sm text-gray-700">{{ $gateResult === null ? 'Nhập kết quả' : 'Làm lại cổng? Nhập kết quả mới' }}</legend>
                                <div class="mt-2 flex gap-6">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="passed" value="1" required @checked($passed !== null && (int) $passed === 1)
                                            class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Đạt
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="passed" value="0" required @checked($passed !== null && (int) $passed === 0)
                                            class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Chưa đạt
                                    </label>
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('passed')" />
                            </fieldset>

                            <div>
                                <x-input-label for="note" value="Ghi chú (không bắt buộc)" />
                                <textarea id="note" name="note" rows="3" maxlength="2000"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('note', $gateResult?->note) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('note')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Lưu kết quả</x-primary-button>

                                @if (session('status') === 'gate-saved')
                                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600">Đã lưu.</p>
                                @endif
                            </div>
                        </form>

                        @if ($gateResult !== null)
                            <form method="post" action="{{ route('months.gate.destroy', ['month' => $month->number]) }}" class="mt-3"
                                onsubmit="return confirm('Xóa kết quả cổng kiểm tra của tháng {{ $month->number }}?')">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">Xóa kết quả</button>
                            </form>
                        @endif
                    @elseif ($gateDate === null)
                        <p class="mt-4 text-sm text-gray-500">Chọn ngày bắt đầu lộ trình trong Cài đặt học để nhập kết quả cổng.</p>
                    @else
                        <p class="mt-4 text-sm text-gray-500">
                            Nhập được kết quả từ ngày {{ $gateDate->format('d/m/Y') }} (ngày đầu của tuần {{ $month->last_week }}).
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
