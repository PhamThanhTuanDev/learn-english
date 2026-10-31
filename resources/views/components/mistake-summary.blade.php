@props(['mistake'])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p class="whitespace-pre-line break-words text-red-700 line-through decoration-red-300" lang="en">{{ $mistake->wrong }}</p>
    <p class="whitespace-pre-line break-words text-green-700" lang="en">{{ $mistake->correct }}</p>
    <p class="mt-0.5 text-xs text-gray-500">
        {{ $mistake->category->label() }} · gặp {{ $mistake->occurrences }} lần
        @if ($mistake->note !== null)
            · {{ $mistake->note }}
        @endif
    </p>
</div>
