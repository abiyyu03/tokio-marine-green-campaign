<?php

/**
 * English validation messages — the counterpart of lang/id/validation.php.
 * Only the rules this application actually uses are listed.
 */
return [
    'accepted' => 'The :attribute box must be ticked.',
    'after' => 'The :attribute must be a date after :date.',
    'before' => 'The :attribute must be a date before :date.',
    'boolean' => 'The :attribute must be either yes or no.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute is not a valid date.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'email' => 'The :attribute format is not valid.',
    'exists' => 'That :attribute option is not available.',
    'in' => 'That :attribute option is not available.',
    'integer' => 'The :attribute must be a whole number.',
    'max' => [
        'array' => 'The :attribute may not have more than :max items.',
        'file' => 'The :attribute may not be larger than :max kilobytes.',
        'numeric' => 'The :attribute may not be greater than :max.',
        'string' => 'The :attribute may not be longer than :max characters.',
    ],
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'numeric' => 'The :attribute must be a number.',
    'regex' => 'The :attribute format is not valid.',
    'required' => 'The :attribute is required.',
    'string' => 'The :attribute must be text.',
    'unique' => 'That :attribute is already registered.',
    'url' => 'The :attribute must be a valid link.',

    /** Field-specific wording: examples and what to do next. */
    'custom' => [
        'name' => [
            'required' => 'Please enter your full name.',
        ],
        'email' => [
            'required' => 'Please enter your email — we send your result report there.',
            'email' => 'That email format is not valid. Example: name@email.com',
        ],
        'whatsapp' => [
            'required' => 'Please enter your WhatsApp number.',
            'regex' => 'Your WhatsApp number may only contain digits, without the +62 prefix. Example: 81234567890',
            'min' => 'Your WhatsApp number must be at least :min digits. Example: 81234567890',
            'max' => 'Your WhatsApp number may not be longer than :max digits.',
        ],
        'dob' => [
            'date' => 'That date of birth is incomplete. Pick a date from the calendar icon.',
            'before' => 'Your date of birth must be before today.',
        ],
        'gender' => [
            'in' => 'Please pick either Male or Female.',
        ],
        'intent' => [
            'in' => 'Please pick one of the available answers.',
        ],
        'consent' => [
            'accepted' => 'Please tick the Terms & Conditions and Privacy Policy box to continue.',
        ],
        'password' => [
            'required' => 'Please enter your password.',
        ],
    ],

    /** Human-readable field names used for :attribute. */
    'attributes' => [
        'name' => 'full name',
        'email' => 'email',
        'whatsapp' => 'WhatsApp number',
        'whatsapp_number' => 'WhatsApp number',
        'dob' => 'date of birth',
        'gender' => 'gender',
        'intent' => 'answer',
        'consent' => 'consent',
        'password' => 'password',
        'remember' => 'remember me',
    ],
];
