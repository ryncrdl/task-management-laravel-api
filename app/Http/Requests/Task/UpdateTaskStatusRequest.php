<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: ' . implode(', ', Task::STATUSES) . '.',
        ];
    }
}
