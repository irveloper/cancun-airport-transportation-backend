<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'status' => 'sometimes|in:pending,confirmed,in_progress,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|string|max:500',
            'special_requests' => 'sometimes|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: pending, confirmed, in_progress, completed, cancelled.',
            'cancellation_reason.required_if' => 'Cancellation reason is required when status is cancelled.',
            'cancellation_reason.max' => 'Cancellation reason cannot exceed 500 characters.',
            'special_requests.max' => 'Special requests cannot exceed 1000 characters.',
        ];
    }
}
