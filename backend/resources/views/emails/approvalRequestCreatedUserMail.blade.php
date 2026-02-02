@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Genehmigungsanfrage eingereicht</h1>
    <p>
        Ihre Anfrage für {{ $requestTypeName }} wurde erfolgreich eingereicht.
    </p>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>Anfrageart:</strong> {{ $requestTypeName }}</p>
        <p style="margin: 0;"><strong>Anfrage-ID:</strong> #{{ $requestId }}</p>
    </div>

    <p>
        Ein Administrator wird Ihre Anfrage überprüfen und Sie werden per E-Mail benachrichtigt, wenn die Anfrage genehmigt oder abgelehnt wird.
    </p>

    <p>
        <strong>Vielen Dank für Ihre Geduld!</strong>
    </p>
</div>

@include('emails.partials.footer')
@endsection
