@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.common.greeting') }}</h1>
    <p>
        {{ __('emails.userDeleted.text', ['appName' => config('app.name')]) }}
    </p>
</div>

@include('emails.partials.footer')
@endsection