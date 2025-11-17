<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'      => ['required','string','max:255'],
            'doc_type'   => ['required','in:resume,medical,other,mdr_philhealth,mdr_sss,mdr_pagibig'],
            'file'       => ['nullable','file','mimes:pdf,doc,docx,jpg,jpeg,png','max:10240'], // ✅ file is optional during update
            'notes'      => ['nullable','string','max:2000'],
            'visibility' => ['nullable','in:employee,hr,supervisor,hr_supervisor,private_employee'],
            'expires_at' => ['nullable','date'],
        ];
    }
}
