@php
    $messages = [
        'mistake-added' => 'Đã thêm lỗi.',
        'mistake-updated' => 'Đã lưu thay đổi.',
        'mistake-deleted' => 'Đã xóa lỗi.',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sổ lỗi
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Ghi lỗi mới</h3>
                    <p class="mt-1 text-sm text-gray-600">Chép câu sai và câu đã sửa. Gặp lại đúng lỗi đó thì bấm "+1 lần" ở danh sách bên dưới.</p>

                    <form method="POST" action="{{ route('mistakes.store') }}" class="mt-4 space-y-4">
                        @csrf
                        @include('mistakes.partials.fields', ['mistake' => null])

                        <div class="flex items-center gap-4">
                            <x-primary-button>Thêm lỗi</x-primary-button>
                            @if (isset($messages[session('status')]))
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                    class="text-sm text-gray-600">{{ $messages[session('status')] }}</p>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0 text-gray-900">
                    <h3 class="text-lg font-medium">Các lỗi đã ghi</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ $mistakes->total() }} lỗi, mới nhất ở trên.</p>
                </div>

                @if ($mistakes->isEmpty())
                    <p class="p-6 text-gray-600">Chưa có lỗi nào.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-100">
                        @foreach ($mistakes as $mistake)
                            <li id="mistake-{{ $mistake->id }}" class="p-4 sm:px-6">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                    <span class="rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-700">{{ $mistake->category->label() }}</span>
                                    @if ($mistake->week !== null)
                                        <span class="text-gray-500">Tuần {{ $mistake->week }}</span>
                                    @endif
                                    <span class="text-gray-500">· {{ $mistake->created_at->setTimezone($user->timezone)->format('d/m/Y') }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-line break-words text-red-700 line-through decoration-red-300" lang="en">{{ $mistake->wrong }}</p>
                                <p class="mt-1 whitespace-pre-line break-words text-green-700" lang="en">{{ $mistake->correct }}</p>
                                @if ($mistake->note !== null)
                                    <p class="mt-1 text-sm text-gray-600">{{ $mistake->note }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                    <span class="text-gray-700">Đã gặp <span class="font-semibold">{{ $mistake->occurrences }}</span> lần</span>
                                    <form method="POST" action="{{ route('mistakes.repeat', $mistake) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-gray-300 px-2 py-1 font-medium text-gray-700 hover:bg-gray-50">+1 lần</button>
                                    </form>
                                    <a href="{{ route('mistakes.edit', $mistake) }}" class="underline text-indigo-600 hover:text-indigo-800">Sửa</a>
                                    <form method="POST" action="{{ route('mistakes.destroy', $mistake) }}" onsubmit="return confirm('Xóa lỗi này khỏi sổ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="underline text-red-600 hover:text-red-800">Xóa</button>
                                    </form>
                                    @if (session('status') === 'mistake-repeated' && session('mistake_id') === $mistake->id)
                                        <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                            class="text-gray-600">Đã tăng số lần.</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($mistakes->hasPages())
                        <div class="p-4 sm:px-6 border-t border-gray-100">
                            {{ $mistakes->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
