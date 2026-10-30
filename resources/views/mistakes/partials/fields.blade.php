@php
    $textarea = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
@endphp

<div>
    <x-input-label for="wrong" value="Câu / chỗ sai" />
    <textarea id="wrong" name="wrong" rows="2" lang="en" required class="{{ $textarea }}">{{ old('wrong', $mistake?->wrong) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('wrong')" />
</div>

<div>
    <x-input-label for="correct" value="Sửa đúng" />
    <textarea id="correct" name="correct" rows="2" lang="en" required class="{{ $textarea }}">{{ old('correct', $mistake?->correct) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('correct')" />
</div>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="col-span-2">
        <x-input-label for="category" value="Loại lỗi" />
        <select id="category" name="category" class="{{ $textarea }}">
            @foreach ($categories as $category)
                <option value="{{ $category->value }}" @selected(old('category', $mistake?->category->value ?? $defaultCategory) === $category->value)>{{ $category->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('category')" />
    </div>

    <div>
        <x-input-label for="week" value="Tuần" />
        <x-text-input id="week" name="week" type="number" min="1" max="104" class="mt-1 block w-full" :value="old('week', $mistake ? $mistake->week : $defaultWeek)" />
        <x-input-error class="mt-2" :messages="$errors->get('week')" />
    </div>

    @if ($mistake !== null)
        <div>
            <x-input-label for="occurrences" value="Số lần" />
            <x-text-input id="occurrences" name="occurrences" type="number" min="1" max="999" required class="mt-1 block w-full" :value="old('occurrences', $mistake->occurrences)" />
            <x-input-error class="mt-2" :messages="$errors->get('occurrences')" />
        </div>
    @endif
</div>

<div>
    <x-input-label for="note" value="Ghi chú (không bắt buộc)" />
    <x-text-input id="note" name="note" type="text" maxlength="255" class="mt-1 block w-full" placeholder="Ví dụ: thiếu -s ở ngôi thứ ba số ít" :value="old('note', $mistake?->note)" />
    <x-input-error class="mt-2" :messages="$errors->get('note')" />
</div>
