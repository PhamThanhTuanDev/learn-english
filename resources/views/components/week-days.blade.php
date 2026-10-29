@props(['days', 'completions', 'today'])

{{-- The N1–N7 strip of a week: green = done, dashed = not reached yet, ring = today. --}}
<ol {{ $attributes->merge(['class' => 'grid grid-cols-7 gap-2 max-w-md', 'aria-label' => 'Các ngày trong tuần']) }}>
    @foreach ($days as $weekDay)
        @php
            $done = $completions->has($weekDay->id);
            $current = $weekDay->number === $today;
            $future = $today === null || $weekDay->number > $today;
        @endphp
        <li title="{{ $weekDay->label }}{{ $done ? ' – đã xong' : '' }}"
            @class([
                'rounded-md py-2 text-center text-sm',
                'bg-green-100 text-green-800' => $done,
                'bg-gray-100 text-gray-600' => ! $done && ! $future && ! $current,
                'border border-dashed border-gray-300 text-gray-400' => ! $done && $future,
                'border border-gray-300 text-gray-700' => ! $done && $current,
                'ring-2 ring-indigo-500' => $current,
            ])>
            N{{ $weekDay->day }}
            <span class="block text-xs">{{ $done ? '✓' : ($weekDay->is_rest ? 'nghỉ' : '·') }}</span>
        </li>
    @endforeach
</ol>
