@extends('emails.partials.body', ['title' => 'Neue Genehmigungsanfrage'])

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Neue Genehmigungsanfrage</h1>
    <p>
        Eine neue Genehmigungsanfrage wurde eingereicht:
    </p>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>Anfrageart:</strong> {{ $requestTypeName }}</p>
        <p style="margin: 0 0 10px 0;"><strong>Angefordert von:</strong> {{ $approvalRequest->createdBy->mobilizon_name }} ({{ $approvalRequest->createdBy->email }})</p>
        <p style="margin: 0;"><strong>Anfrage-ID:</strong> #{{ $approvalRequest->id }}</p>
    </div>

    <p>Bitte überprüfen Sie die Anfrage und genehmigen oder lehnen Sie sie ab.</p>

    <a href="{{ $approvalUrl }}" class="btn">Jetzt Anfrage prüfen</a>

    <p>Alternativ können Sie auch folgende URL im Browser besuchen:</p>
    <p>{{ $approvalUrl }}</p>
</div>

@include('emails.partials.footer')
@endsection
