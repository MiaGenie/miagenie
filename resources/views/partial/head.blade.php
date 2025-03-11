@php
    use App\MiaGenie;
@endphp
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('partial.favicon')
{!! \Inovector\Mixpost\Facades\Theme::render() !!}
{{ MiaGenie::assets() }}
