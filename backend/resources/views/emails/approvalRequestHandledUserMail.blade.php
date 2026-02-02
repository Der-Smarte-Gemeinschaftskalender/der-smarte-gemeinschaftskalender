@extends('emails.partials.body', ['title' => 'Genehmigungsanfrage bearbeitet'])

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Ihre Genehmigungsanfrage wurde bearbeitet</h1>
    <p>
        Ihre Genehmigungsanfrage für {{ $requestTypeName }} wurde bearbeitet.
    </p>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>Status:</strong> <span style="color: @if($status === 'approved') #2ecc71 @elseif($status === 'rejected') #e74c3c @else #95a5a6 @endif; font-weight: bold;">
            @if($status === 'approved')
                Genehmigt
            @elseif($status === 'rejected')
                Abgelehnt
            @else
                {{ ucfirst($status) }}
            @endif
        </span></p>
        @if($comment)
            <p style="margin: 0;"><strong>Kommentar vom Admin:</strong> {{ $comment }}</p>
        @endif
    </div>

    @if($status === 'approved')
        <p>Ihre Anfrage wurde genehmigt und die Änderungen wurden vorgenommen.</p>
    @elseif($status === 'rejected')
        <p>Leider wurde Ihre Anfrage abgelehnt. Die Änderungen wurden nicht vorgenommen.</p>
    @endif
</div>

@include('emails.partials.footer')
@endsection
