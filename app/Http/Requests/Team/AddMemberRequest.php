<?php

namespace App\Http\Requests\Team;

use App\Models\TeamMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['sometimes', 'string', Rule::in([TeamMember::ROLE_MEMBER, TeamMember::ROLE_LEAD])],
        ];
    }
}
