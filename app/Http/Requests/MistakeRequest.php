<?php

namespace App\Http\Requests;

use App\Enums\MistakeCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MistakeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request. The count is only
     * sent from the edit form.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wrong' => ['required', 'string', 'max:1000'],
            'correct' => ['required', 'string', 'max:1000'],
            'category' => ['required', Rule::enum(MistakeCategory::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'week' => ['nullable', 'integer', 'between:1,104'],
            'occurrences' => ['sometimes', 'required', 'integer', 'between:1,999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'wrong' => 'câu sai',
            'correct' => 'câu đúng',
            'category' => 'loại lỗi',
            'note' => 'ghi chú',
            'week' => 'tuần',
            'occurrences' => 'số lần',
        ];
    }
}
