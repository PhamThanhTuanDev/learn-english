<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users only ever edit their own settings; the route requires auth.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roadmap_start_date' => ['nullable', 'date'],
            'daily_goal_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'roadmap_start_date' => 'ngày bắt đầu lộ trình',
            'daily_goal_minutes' => 'mục tiêu phút mỗi ngày',
            'timezone' => 'múi giờ',
        ];
    }
}
