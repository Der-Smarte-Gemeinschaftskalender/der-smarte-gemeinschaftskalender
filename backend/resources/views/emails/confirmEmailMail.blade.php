@extends('emails.partials.body', ['title' => __('emails.common.confirmEmail')])

@section('content')
@include('emails.partials.header')

<div class="content">
    {!! $mailBody !!}

    <a href="{{ env('APP_URL') }}/validate/{{ $verificationToken }}" class="btn">{{ __('emails.common.confirmEmail') }}</a>

    <p>{{ __('emails.common.alternativeUrl') }}</p>
    <p>{{ env('APP_URL') }}/validate/{{ $verificationToken }}</p>
</div>

@include('emails.partials.footer')
@endsection