<?php

declare(strict_types=1);

return [
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'boolean' => 'The :attribute field must be a boolean value.',
        'integer' => 'The :attribute field must be an integer.',
        'array' => 'The :attribute field must be an array.',
        'confirmed' => 'The :attribute field confirmation does not match.',
        'unique' => 'The :attribute has already been taken.',
        'exists' => 'The selected :attribute is invalid.',
        'min' => [
            'string' => 'The :attribute must be at least :min characters.',
            'array' => 'The :attribute must contain at least :min items.',
            'numeric' => 'The :attribute must be at least :min.',
        ],
        'max' => [
            'string' => 'The :attribute must not exceed :max characters.',
            'array' => 'The :attribute must not contain more than :max items.',
            'numeric' => 'The :attribute must not exceed :max.',
        ],
    ],
];

