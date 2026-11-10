@extends('emails.partials.body', ['title' => __('emails.resetPassword.heading')])
@section('content')
@include('emails.partials.header')

<div class="content">
    <h2>{{ __('emails.resetPassword.heading') }}</h2>

    {!! $mailBody !!}

    <a href="{{ env('APP_URL') }}/reset-password/{{ $resetToken }}"
        class="btn">{{ __('emails.resetPassword.button') }}</a>

    <p>
        {{ __('emails.resetPassword.ignoreHint') }}
    </p>
</div>

@include('emails.partials.footer')
@endsection
