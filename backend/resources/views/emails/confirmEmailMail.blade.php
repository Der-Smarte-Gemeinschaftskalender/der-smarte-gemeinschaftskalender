@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Moin</h1>
    <p>
        Sie haben auf {{ env('APP_NAME') }} ein Konto registriert, das mit dieser E-Mail-Adresse verknüpft ist. Sie sind
        nur einen Klick von der Aktivierung entfernt.

        Wenn Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail.
    </p>

    <a href="{{ env('APP_URL') }}/validate/{{ $verificationToken }}" class="btn">E-Mail-Adresse bestätigen</a>

    <p>Alternativ können sie auch folgende URL im Browser besuchen:</p>
    <p>{{ env('APP_URL') }}/validate/{{ $verificationToken }}</p>
</div>

@include('emails.partials.footer')
@endsection