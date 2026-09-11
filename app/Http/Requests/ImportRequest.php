<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 
                'file', 
                'extensions:csv,txt,json,xml',
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile && $value->getSize() === 0) {
                        $fail('The uploaded file is empty.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'An import file is required.',
            'file.mimes' => 'Only CSV, JSON, and XML files are permitted.',
            'file.empty' => 'The uploaded file is empty.',
        ];
    }
}
