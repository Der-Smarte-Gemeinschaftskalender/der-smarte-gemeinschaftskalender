@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Moin</h1>
    <p>
        Es wurde die Erstellung einer neuen Organisation mit dem Namen "{{ $requestOrganisationNamen }}" angefragt.
    </p>

    <a href="{{ $urlRequestedOrganisations }}" class="btn">Jetzt Anfrage prüfen</a>

    <p>Alternativ können sie auch folgende URL im Browser besuchen:</p>
    <p>{{ $urlRequestedOrganisations }}</p>
</div>

@include('emails.partials.footer')
@endsection