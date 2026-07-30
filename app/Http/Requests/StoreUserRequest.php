<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'school_id' => 'nullable|exists:schools,id',
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'staff_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $roleId = $this->input('role_id');

            if ($roleId) {
                $role = \App\Models\Role::find($roleId);

                if ($role) {
                    // HOD must have a department
                    if ($role->slug === 'hod' && !$this->input('department_id')) {
                        $validator->errors()->add(
                            'department_id',
                            'Department is required for HOD role.'
                        );
                    }

                    // Dean must have a school
                    if ($role->slug === 'dean' && !$this->input('school_id')) {
                        $validator->errors()->add(
                            'school_id',
                            'School is required for Dean role.'
                        );
                    }
                }
            }
        });
    }
}
