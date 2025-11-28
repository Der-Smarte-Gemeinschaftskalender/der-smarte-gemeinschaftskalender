@extends('emails.partials.body')
@section('content')
@include('emails.partials.header')

<div class="content">
    <h2>Bitte bestätigen Sie Ihre E-Mail-Adresse</h2>

    <h4>
        Sie haben sich für Veranstaltungsbenachrichtigungen bei
        <span class="highlight">{{ env('APP_NAME') }}</span>
        angemeldet.
    </h4>

    <h4>
        Bitte <span class="highlight">bestätigen Sie Ihre E-Mail-Adresse</span>,
        damit wir Sie regelmäßig über neue Veranstaltungen informieren dürfen.
    </h4>

    <a href="{{ env('APP_URL') }}/exports/email/confirm/{{ $token }}"
        class="btn">E-Mail-Adresse bestätigen</a>

</div>

@include('emails.partials.footer')
@endsection
