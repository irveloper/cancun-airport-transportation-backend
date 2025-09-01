<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\LocalizedRequest;

class QuoteRequest extends LocalizedRequest
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
     */
    public function rules(): array
    {
        return [
            'service_type' => 'required|string|max:50|in:round-trip,one-way,hotel-to-hotel,arrival,departure',
            'from_location_id' => 'required|integer|exists:locations,id',
            'to_location_id' => 'required|integer|exists:locations,id|different:from_location_id',
            'pax' => 'required|integer|min:1|max:50',
            'date' => 'nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom validation messages for the request.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'service_type.in' => __('api.validation.service_type_invalid'),
            'to_location_id.different' => __('api.validation.locations_must_be_different'),
            'pax.min' => __('api.validation.passenger_count_min'),
            'pax.max' => __('api.validation.passenger_count_max'),
            'date.after_or_equal' => __('api.validation.date_future'),
        ]);
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'service_type' => __('validation.attributes.service_type'),
            'from_location_id' => __('validation.attributes.pickup_location'),
            'to_location_id' => __('validation.attributes.dropoff_location'),
            'pax' => __('validation.attributes.passenger_count'),
            'date' => __('validation.attributes.pickup_date'),
        ]);
    }
}