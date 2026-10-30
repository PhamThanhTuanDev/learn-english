<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sửa lỗi
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('mistakes.update', $mistake) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        @include('mistakes.partials.fields')

                        <div class="flex items-center gap-4">
                            <x-primary-button>Lưu</x-primary-button>
                            <a href="{{ route('mistakes') }}" class="text-sm underline text-gray-600 hover:text-gray-900">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
