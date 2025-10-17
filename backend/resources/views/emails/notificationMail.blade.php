@extends('emails.partials.body')
@section('content')
@include('emails.partials.header')
<div>
    <h1>Moin Ihre {{ __('emails.' . $intervall) }} Veranstaltungsübersicht</h1>
    <p>
        Hier finden Sie die aktuellen Veranstaltungen auf {{ env('APP_NAME') }} – passend zu
        Ihren Interessen.
    </p>
    @foreach ($events as $key => $eventType)
    <h2>{{ __('emails.' . $key) }}</h2>
    @if (count($eventType['events']) == 0)
    Es wurden keine Termine gefunden
    @if (count($eventType['alternativeEvents']) != 0)
    Wir haben alternative Termine rausgesucht, die für Sie interessant sein könnten:
    @foreach ($eventType['alternativeEvents'] as $event)
    @if (!$loop->first)
    @component('emails.partials.eventLink', ['event' => $event])
    @endcomponent
    @endif
    @endforeach
    @endif
    @else
    @foreach ($eventType['events'] as $event)
    @if (!$loop->first)
    @component('emails.partials.eventLink', ['event' => $event])
    @endcomponent
    @endif
    @endforeach
    @endif
    @endforeach

    <br><br>
    @component('emails.partials.footer', [
    'notificationId' => $notificationId,
    'unsubscribePath' => $unsubscribePath ?? null,
    'editPath' => $editPath ?? null,
    ])
    @endcomponent
</div>
@endsection