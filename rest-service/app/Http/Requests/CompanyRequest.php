<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'companyName' => 'required|string|unique:companies,company_name,NULL,id,deleted_at,NULL',
            'addressLine1' => 'required',
            'bankDetails' => 'nullable|array',
            'bankDetails.*.bankName' => 'required',
            'bankDetails.*.swift' => 'required|string',
            'warningMailAddress' => 'nullable|email',
            'notificationMail' => 'nullable|email',
            'invoiceEmailAddress' => 'nullable|email',
            'city' => 'required',
            'country' => 'required',
            'zipCode' => 'required',
        ];

        if ($this->method() == 'PUT') {
            $rules['country'] = 'nullable|string';
            $rules['status'] = 'nullable';
            $rules['zipCode'] = 'nullable|string';
            $rules['city'] = 'nullable|string';
            $rules['addressLine1'] = 'nullable|string';
            $rules['companyName'] = 'nullable|string';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'bankDetails.*.bankName.required' => 'The bank name field is required.',
            'bankDetails.*.swift.required' => 'The swift field is required.',
        ];
    }
}
