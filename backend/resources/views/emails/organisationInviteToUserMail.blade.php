@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Moin</h1>
    <p>
        Sie haben eine Einladung für die Organisation "{{ $organisationName }}" bekommen.
    </p>

    <a href="{{ $urlMyOrganisations }}" class="btn">Einladung annehmen oder ablehnen</a>

    <p>Alternativ können sie auch folgende URL im Browser besuchen:</p>
    <p>{{ $urlMyOrganisations }}</p>
</div>

@include('emails.partials.footer')
@endsection