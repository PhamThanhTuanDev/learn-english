<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cài đặt học
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <form method="post" action="{{ route('settings.update') }}" class="max-w-xl space-y-6">
                    @csrf
                    @method('patch')

                    <div>
                        <x-input-label for="roadmap_start_date" value="Ngày bắt đầu lộ trình" />
                        <x-text-input id="roadmap_start_date" name="roadmap_start_date" type="date" class="mt-1 block w-full"
                            :value="old('roadmap_start_date', $user->roadmap_start_date?->format('Y-m-d'))" />
                        <p class="mt-1 text-sm text-gray-600">Ngày này là Ngày 1 của Tuần 1. Lộ trình dài 104 tuần.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('roadmap_start_date')" />
                    </div>

                    <div>
                        <x-input-label for="daily_goal_minutes" value="Mục tiêu mỗi ngày (phút)" />
                        <x-text-input id="daily_goal_minutes" name="daily_goal_minutes" type="number" min="15" max="600" step="5"
                            class="mt-1 block w-full" required :value="old('daily_goal_minutes', $user->daily_goal_minutes)" />
                        <p class="mt-1 text-sm text-gray-600">Lộ trình được thiết kế cho 120 phút/ngày, 6 ngày/tuần.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('daily_goal_minutes')" />
                    </div>

                    <div>
                        <x-input-label for="timezone" value="Múi giờ" />
                        <select id="timezone" name="timezone" required
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $user->timezone) === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-600">Dùng để tính "hôm nay" theo giờ của bạn.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>

                        @if (session('status') === 'settings-updated')
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                class="text-sm text-gray-600">{{ __('Saved.') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
