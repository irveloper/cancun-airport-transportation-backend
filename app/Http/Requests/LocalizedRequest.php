<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LocalizedRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $errors = $validator->errors()->toArray();
            
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => __('api.validation_failed'),
                    'errors' => $errors,
                    'timestamp' => now()->toISOString(),
                    'request_id' => request()->id ?? uniqid(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get custom validation messages for the request.
     */
    public function messages(): array
    {
        return [
            'required' => __('validation.required'),
            'email' => __('validation.email'),
            'unique' => __('validation.unique'),
            'min' => __('validation.min.string'),
            'max' => __('validation.max.string'),
            'numeric' => __('validation.numeric'),
            'integer' => __('validation.integer'),
            'boolean' => __('validation.boolean'),
            'date' => __('validation.date'),
            'in' => __('validation.in'),
            'exists' => __('validation.exists'),
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return __('validation.attributes');
    }
}