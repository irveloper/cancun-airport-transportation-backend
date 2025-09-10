<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreBookingRequest extends FormRequest
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
            'customerInfo' => 'required|array',
            'customerInfo.firstName' => 'required|string|max:100',
            'customerInfo.lastName' => 'nullable|string|max:100',
            'customerInfo.email' => 'required|email|max:255',
            'customerInfo.phone' => 'required|string|max:20',
            'customerInfo.country' => 'required|string|max:100',

            'pickupLocation' => 'required|string|max:255',
            'dropoffLocation' => 'required|string|max:255',
            'pickupDateTime' => 'required|string',
            'passengers' => 'required|integer|min:1|max:50',
            'serviceId' => 'nullable',
            'serviceName' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
            'totalPrice' => 'required|numeric|min:0',
            'specialRequests' => 'nullable|string|max:1000',

            'childSeats' => 'sometimes|integer|min:0|max:10',
            'wheelchairAccessible' => 'sometimes|boolean',
            'hotelReservationName' => 'nullable|string|max:255',
            'fromLocationId' => 'required',
            'toLocationId' => 'required',
            'fromLocationType' => 'required|in:airport,location,zone',
            'toLocationType' => 'required|in:airport,location,zone',
            'tripType' => 'required|in:arrival,departure,round-trip,hotel-to-hotel',
            'bookingDate' => 'required|date',

            'arrivalFlightInfo' => 'nullable|array',
            'arrivalFlightInfo.airline' => 'required_with:arrivalFlightInfo|string|max:100',
            'arrivalFlightInfo.flightNumber' => 'required_with:arrivalFlightInfo|string|max:20',
            'arrivalFlightInfo.date' => 'required_with:arrivalFlightInfo|date',
            'arrivalFlightInfo.time' => 'required_with:arrivalFlightInfo|string',

            'departureFlightInfo' => 'nullable|array',
            'departureFlightInfo.airline' => 'required_with:departureFlightInfo|string|max:100',
            'departureFlightInfo.flightNumber' => 'required_with:departureFlightInfo|string|max:20',
            'departureFlightInfo.date' => 'required_with:departureFlightInfo|date',
            'departureFlightInfo.time' => 'required_with:departureFlightInfo|string',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $data = $this->all();

        // Convert string IDs to integers
        if (isset($data['fromLocationId'])) {
            $data['fromLocationId'] = (int)$data['fromLocationId'];
        }
        if (isset($data['toLocationId'])) {
            $data['toLocationId'] = (int)$data['toLocationId'];
        }
        if (isset($data['serviceId'])) {
            $data['serviceId'] = (int)$data['serviceId'];
        }

        // Fix pickupDateTime if incomplete
        if (isset($data['pickupDateTime'])) {
            $pickupDateTime = $data['pickupDateTime'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}T$/', $pickupDateTime)) {
                $data['pickupDateTime'] = $pickupDateTime . '12:00:00';
            }
            try {
                $parsedDateTime = Carbon::parse($data['pickupDateTime']);
                $data['pickupDateTime'] = $parsedDateTime->toISOString();
            } catch (\Exception $e) {
                // Leave as is, validation will catch it
            }
        }

        // Fix bookingDate if incomplete
        if (isset($data['bookingDate'])) {
            $bookingDate = $data['bookingDate'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}T$/', $bookingDate)) {
                $data['bookingDate'] = $bookingDate . '12:00:00';
            }
            try {
                $parsedDateTime = Carbon::parse($data['bookingDate']);
                $data['bookingDate'] = $parsedDateTime->toISOString();
            } catch (\Exception $e) {
                // Leave as is, validation will catch it
            }
        }

        // Normalize flight times
        if (isset($data['arrivalFlightInfo']['time'])) {
            $data['arrivalFlightInfo']['time'] = $this->normalizeFlightTime($data['arrivalFlightInfo']['time']);
        }
        if (isset($data['departureFlightInfo']['time'])) {
            $data['departureFlightInfo']['time'] = $this->normalizeFlightTime($data['departureFlightInfo']['time']);
        }

        $this->replace($data);
    }

    /**
     * Normalize flight time formats
     */
    private function normalizeFlightTime(string $time): string
    {
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time;
        }

        try {
            $parsedTime = Carbon::createFromFormat('H:i', $time);
            return $parsedTime->format('H:i');
        } catch (\Exception $e) {
            return $time;
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customerInfo.required' => 'Customer information is required.',
            'customerInfo.firstName.required' => 'Customer first name is required.',
            'customerInfo.email.required' => 'Customer email is required.',
            'customerInfo.email.email' => 'Customer email must be a valid email address.',
            'customerInfo.phone.required' => 'Customer phone number is required.',
            'customerInfo.country.required' => 'Customer country is required.',
            'pickupLocation.required' => 'Pickup location is required.',
            'dropoffLocation.required' => 'Drop-off location is required.',
            'pickupDateTime.required' => 'Pickup date and time is required.',
            'passengers.required' => 'Number of passengers is required.',
            'passengers.min' => 'At least 1 passenger is required.',
            'passengers.max' => 'Maximum 50 passengers allowed.',
            'serviceName.required' => 'Service name is required.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-character code.',
            'totalPrice.required' => 'Total price is required.',
            'totalPrice.min' => 'Total price must be greater than or equal to 0.',
            'fromLocationId.required' => 'From location ID is required.',
            'toLocationId.required' => 'To location ID is required.',
            'fromLocationType.required' => 'From location type is required.',
            'toLocationType.required' => 'To location type is required.',
            'tripType.required' => 'Trip type is required.',
            'bookingDate.required' => 'Booking date is required.',
        ];
    }
}
