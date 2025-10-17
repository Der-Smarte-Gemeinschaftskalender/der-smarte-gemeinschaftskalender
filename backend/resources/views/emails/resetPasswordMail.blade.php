@extends('emails.partials.body')
@section('content')
@include('emails.partials.header')

<div class="content">
    <h2>Passwort zurücksetzen</h2>

    <h4>
        Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts bei
        <span class="highlight">{{ env('APP_NAME') }}</span>
        gestellt.
    </h4>

    <h4>
        Bitte klicken Sie auf den folgenden Button, um Ihr Passwort zurückzusetzen:
    </h4>

    <a href="{{ env('APP_URL') }}/reset-password/{{ $email }}/{{ $resetToken }}"
        class="btn">Passwort zurücksetzen</a>

    <h4>
        Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail bitte.
    </h4>

</div>

@include('emails.partials.footer')
@endsection