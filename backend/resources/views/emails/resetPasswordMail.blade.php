@extends('emails.partials.body')
@section('content')
@include('emails.partials.header')

<div class="content">
    <h2>Passwort zurücksetzen</h2>

    {!! $mailBody !!}

    <a href="{{ env('APP_URL') }}/reset-password/{{ $resetToken }}"
        class="btn">Passwort zurücksetzen</a>

    <p>
        Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail bitte.
    </p>
</div>

@include('emails.partials.footer')
@endsection
