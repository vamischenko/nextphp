<?php

declare(strict_types=1);

return [
    'validation' => [
        'required' => 'Поле :attribute обязательно.',
        'email' => 'Поле :attribute должно быть корректным email адресом.',
        'boolean' => 'Поле :attribute должно быть логическим значением.',
        'integer' => 'Поле :attribute должно быть целым числом.',
        'array' => 'Поле :attribute должно быть массивом.',
        'confirmed' => 'Подтверждение поля :attribute не совпадает.',
        'unique' => 'Значение поля :attribute уже занято.',
        'exists' => 'Выбранное значение поля :attribute недопустимо.',
        'min' => [
            'string' => 'Поле :attribute должно содержать минимум :min символов.',
            'array' => 'Поле :attribute должно содержать минимум :min элементов.',
            'numeric' => 'Поле :attribute должно быть не меньше :min.',
        ],
        'max' => [
            'string' => 'Поле :attribute должно содержать не более :max символов.',
            'array' => 'Поле :attribute должно содержать не более :max элементов.',
            'numeric' => 'Поле :attribute должно быть не больше :max.',
        ],
    ],
];

