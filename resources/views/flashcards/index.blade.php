@php
    $ratingStyles = [
        1 => 'border-red-300 text-red-700 hover:bg-red-50',
        2 => 'border-amber-300 text-amber-700 hover:bg-amber-50',
        3 => 'border-green-300 text-green-700 hover:bg-green-50',
        4 => 'border-sky-300 text-sky-700 hover:bg-sky-50',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Flashcard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($cards === [])
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        @if ($user->roadmap_start_date === null)
                            <p>
                                Từ mới được thêm mỗi ngày theo lộ trình, nhưng bạn chưa chọn ngày bắt đầu.
                                <a href="{{ route('settings.edit') }}" class="underline text-indigo-600 hover:text-indigo-800">Chọn trong Cài đặt học</a>.
                            </p>
                        @else
                            <p>Hôm nay không còn thẻ nào cần ôn.</p>
                        @endif
                    </div>
                </div>
            @else
                <div x-data="flashcardReview(@js($cards))" @keydown.window="onKey($event)">
                    <div class="flex justify-between px-4 sm:px-0 text-sm text-gray-600">
                        <p>Còn <span class="font-semibold text-gray-900" x-text="queue.length">{{ count($cards) }}</span> thẻ (<span x-text="newCount"></span> từ mới)</p>
                        <p>Đã trả lời <span class="font-semibold text-gray-900" x-text="answered">0</span></p>
                    </div>

                    <template x-if="card">
                        <div class="mt-3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-center">
                                <div class="flex justify-center gap-2 text-xs font-medium">
                                    <span x-show="card.is_new" class="rounded bg-indigo-100 px-2 py-0.5 text-indigo-700">Từ mới</span>
                                    <span x-show="card.level" x-text="card.level" class="rounded bg-gray-100 px-2 py-0.5 text-gray-700"></span>
                                </div>
                                <p class="mt-3 text-3xl font-semibold text-gray-900 break-words" lang="en" x-text="card.word"></p>
                                <p class="mt-1 text-gray-600">
                                    <span class="italic" x-text="card.part_of_speech"></span>
                                    <span x-text="card.pronunciation"></span>
                                </p>
                                <button type="button" @click="speak(card.word)" class="mt-3 inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50" title="Nghe phát âm (phím P)">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                    </svg>
                                    Nghe
                                </button>
                            </div>

                            <div x-show="flipped" class="border-t border-gray-100 p-6 space-y-4">
                                <p class="text-center text-xl font-medium text-gray-900" x-text="card.meaning"></p>
                                <div x-show="card.definition">
                                    <p class="text-gray-900" lang="en" x-text="card.definition"></p>
                                    <p class="mt-1 text-sm text-gray-600" x-text="card.definition_vi"></p>
                                </div>
                                <div x-show="card.example">
                                    <p class="text-gray-900 italic" lang="en">
                                        <span x-text="card.example"></span>
                                        <button type="button" @click="speak(card.example)" class="ms-1 align-middle text-indigo-600 hover:text-indigo-800" title="Nghe câu ví dụ">
                                            <svg class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                            </svg>
                                            <span class="sr-only">Nghe câu ví dụ</span>
                                        </button>
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600" x-text="card.example_vi"></p>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 p-4">
                                <button type="button" x-show="!flipped" @click="flip()" class="w-full rounded-md bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-700">
                                    Xem nghĩa <span class="ms-1 text-xs font-normal text-gray-300">(Space)</span>
                                </button>
                                <div x-show="flipped" class="grid grid-cols-4 gap-2">
                                    @foreach ($ratings as $rating)
                                        <button type="button" @click="answer({{ $rating['value'] }})" :disabled="busy"
                                            class="rounded-md border px-2 py-3 text-sm font-semibold disabled:opacity-50 {{ $ratingStyles[$rating['value']] }}">
                                            {{ $rating['label'] }}
                                            <span class="block text-xs font-normal text-gray-500">{{ $rating['value'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-600"></p>
                            </div>
                        </div>
                    </template>

                    <template x-if="!card">
                        <div class="mt-3 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <p class="text-lg font-medium">Xong! Bạn đã ôn hết thẻ của hôm nay.</p>
                                <p class="mt-1 text-gray-600">Đã trả lời <span x-text="answered"></span> lần.</p>
                                <p class="mt-3">
                                    <a href="{{ route('dashboard') }}" class="underline text-indigo-600 hover:text-indigo-800">Về trang Hôm nay</a>
                                </p>
                            </div>
                        </div>
                    </template>

                    <p x-show="card" class="mt-4 px-4 sm:px-0 text-sm text-gray-500">
                        Phím tắt: Space hoặc Enter để xem nghĩa · 1 Quên, 2 Khó, 3 Nhớ, 4 Dễ · P để nghe lại.
                        Thẻ bấm "Quên" sẽ quay lại cuối hàng để ôn lần nữa trong hôm nay.
                    </p>
                </div>
            @endif

            @if ($stats->learned > 0)
                @php
                    $retention = $stats->retentionPercent();
                @endphp

                <div class="mt-8 grid grid-cols-3 gap-3 px-4 sm:px-0">
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-600">Đã học</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats->learned, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-gray-500">từ đã ôn ít nhất 1 lần</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-600">Đã thuộc</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats->known, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-gray-500">khoảng cách ôn từ {{ $stats::KNOWN_INTERVAL }} ngày</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-600">Tỉ lệ nhớ</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $retention === null ? '–' : number_format($retention, 0).'%' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $stats::RETENTION_DAYS }} ngày, {{ $stats->retentionReviews }} lần ôn</p>
                    </div>
                </div>

                <div class="mt-4 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium">Thẻ đến hạn {{ $stats::FORECAST_DAYS }} ngày tới</h3>
                        <p class="mt-1 text-sm text-gray-600">Chưa tính từ mới sẽ được thêm vào mỗi ngày học.</p>

                        <ul class="mt-4 space-y-3">
                            @foreach ($stats->forecast as $day)
                                <li class="flex items-center gap-3">
                                    <p class="w-28 shrink-0 text-sm text-gray-700">
                                        {{ $loop->first ? 'Ngày mai' : ucfirst($day['date']->locale(app()->getLocale())->isoFormat('dd DD/MM')) }}
                                    </p>
                                    <div class="h-3 flex-1 rounded-full bg-gray-100" aria-hidden="true">
                                        <div class="h-3 rounded-full bg-indigo-500" style="width: {{ $day['share'] }}%"></div>
                                    </div>
                                    <p class="w-16 shrink-0 text-right text-sm text-gray-700">{{ $day['cards'] }} thẻ</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
