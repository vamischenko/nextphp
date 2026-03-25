# View / Шаблоны

`nextphp/view` — blade-like шаблонизатор с директивами и наследованием.

## Основной синтаксис

```html
<!-- resources/views/welcome.html -->
<h1>Hello, {{ $name }}!</h1>
<p>Today: {{ date('Y-m-d') }}</p>

{{-- Этот комментарий не попадёт в HTML --}}

{!! $rawHtml !!}  <!-- без экранирования -->
```

## Директивы

```html
@if ($user->isAdmin())
    <a href="/admin">Admin panel</a>
@elseif ($user->isModerator())
    <span>Moderator</span>
@else
    <span>Guest</span>
@endif

@foreach ($posts as $post)
    <article>
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->excerpt }}</p>
    </article>
@endforeach

@for ($i = 0; $i < 5; $i++)
    <span>{{ $i }}</span>
@endfor

@while ($queue->isNotEmpty())
    {{ $queue->pop() }}
@endwhile
```

## Наследование шаблонов

```html
<!-- resources/views/layouts/app.html -->
<!DOCTYPE html>
<html>
<head><title>@yield('title', 'MyApp')</title></head>
<body>
    @include('partials.nav')
    <main>@yield('content')</main>
    @include('partials.footer')
</body>
</html>
```

```html
<!-- resources/views/users/show.html -->
@extends('layouts.app')

@section('title', 'User Profile')

@section('content')
    <h1>{{ $user->name }}</h1>
    <p>{{ $user->email }}</p>
@endsection
```

## Использование в PHP

```php
use Nextphp\View\ViewFactory;

$factory = new ViewFactory('/resources/views');

$html = $factory->render('users.show', [
    'user' => $user,
]);

return new Response(200, ['Content-Type' => 'text/html'], $html);
```

## Партиалы и компоненты

```html
@include('partials.card', ['title' => 'Stats', 'value' => 42])
@include('components.button', ['label' => 'Save', 'type' => 'submit'])
```
