@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Moin</h1>
    <p>
        Sie wurden aus der folgenden Organisation "{{ $organisationName }}" entfernt.
    </p>

    <a href="{{ $urlMyOrganisations }}" class="btn">Meine Organisationen ansehen</a>

    <p>Alternativ können sie auch folgende URL im Browser besuchen:</p>
    <p>{{ $urlMyOrganisations }}</p>
</div>

@include('emails.partials.footer')
@endsection