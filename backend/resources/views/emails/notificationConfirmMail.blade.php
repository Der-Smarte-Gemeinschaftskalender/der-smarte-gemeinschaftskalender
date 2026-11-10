@extends('emails.partials.body', ['title' => __('emails.notificationConfirm.heading')])
@section('content')
@include('emails.partials.header')

<div class="content">
    <h2>{{ __('emails.notificationConfirm.heading') }}</h2>

    {{-- The translations are trusted, only the inserted values are escaped --}}
    <h4>
        {!! __('emails.notificationConfirm.subscribed', [
            'appName' => '<span class="highlight">' . e(config('app.name')) . '</span>',
        ]) !!}
    </h4>

    <h4>
        {!! __('emails.notificationConfirm.confirmHint', [
            'confirm' => '<span class="highlight">' . e(__('emails.notificationConfirm.confirmHighlight')) . '</span>',
        ]) !!}
    </h4>

    <a href="{{ env('APP_URL') }}/exports/email/confirm/{{ $token }}"
        class="btn">{{ __('emails.common.confirmEmail') }}</a>

</div>

@include('emails.partials.footer')
@endsection
