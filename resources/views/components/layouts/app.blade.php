<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ isset($title) ? $title.' | TOKOKU' : 'TOKOKU' }}</title>

    @include('components.spatials.head')
</head>

<body>
    <div class="wrapper">
        @include('components.spatials.header')

        @include('components.spatials.sidebar')

        <div class="content-page">
            <div class="content">
                @yield('content')
                {{ $slot ?? '' }}
            </div>

            @include('components.spatials.footer')
        </div>
    </div>
    @include('components.spatials.foot')
</body>

</html>