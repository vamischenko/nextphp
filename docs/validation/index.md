# Validation

`nextphp/validation` — rule-based валидатор с расширяемыми правилами.

## Базовое использование

```php
use Nextphp\Validation\Validator;

$validator = new Validator(
    data: $request->getParsedBody(),
    rules: [
        'name'     => 'required|string|min:2|max:100',
        'email'    => 'required|email',
        'age'      => 'required|integer|min:18',
        'password' => 'required|min:8|confirmed',
        'avatar'   => 'nullable|file|mimes:jpg,png|max:2048',
    ],
);

if ($validator->fails()) {
    return new Response(422, [], json_encode($validator->errors()));
}

$data = $validator->validated();
```

## Встроенные правила

| Правило | Описание |
|---------|----------|
| `required` | Поле обязательно |
| `nullable` | Допускает null/пустое значение |
| `string` | Должно быть строкой |
| `integer` | Целое число |
| `numeric` | Число (int или float) |
| `boolean` | Булево значение |
| `array` | Массив |
| `email` | Валидный email |
| `url` | Валидный URL |
| `min:N` | Минимум N (символов/значение) |
| `max:N` | Максимум N |
| `in:a,b,c` | Одно из значений |
| `not_in:a,b` | Не должно быть в списке |
| `regex:/pattern/` | Соответствие регулярному выражению |
| `confirmed` | Поле `{field}_confirmation` должно совпадать |
| `unique:table,column` | Уникальность в БД |
| `exists:table,column` | Существование в БД |
| `file` | Загруженный файл |
| `mimes:jpg,png` | Допустимые MIME-типы |

## Пользовательские правила

```php
use Nextphp\Validation\Rules\RuleInterface;

class PhoneRule implements RuleInterface
{
    public function passes(string $field, mixed $value, array $data): bool
    {
        return preg_match('/^\+?[1-9]\d{10,14}$/', (string) $value) === 1;
    }

    public function message(string $field): string
    {
        return "The {$field} must be a valid phone number.";
    }
}

$validator = new Validator(
    data: $data,
    rules: ['phone' => ['required', new PhoneRule()]],
);
```

## Nested-данные

```php
$validator = new Validator(
    data: $data,
    rules: [
        'address.city'    => 'required|string',
        'address.country' => 'required|in:US,CA,GB',
        'items.*.price'   => 'required|numeric|min:0',
    ],
);
```
