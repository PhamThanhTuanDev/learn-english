<?php

namespace App\Http\Requests;

use App\Models\RoadmapDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDayCompletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var RoadmapDay $day */
        $day = $this->route('roadmapDay');

        return $this->user()->hasReachedRoadmapDay($day->number);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'minutes' => ['required', 'integer', 'min:0', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'minutes' => 'số phút đã học',
        ];
    }
}
