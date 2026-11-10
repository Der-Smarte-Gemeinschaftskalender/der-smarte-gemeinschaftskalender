@extends('emails.partials.body', ['title' => __('emails.subjects.notification')])
@section('content')
@include('emails.partials.header')
<div>
    <h1>{{ __('emails.notification.heading.' . $intervall) }}</h1>
    <p>
        {{ __('emails.notification.intro', ['appName' => config('app.name')]) }}
    </p>
    @foreach ($events as $key => $eventType)
    <h2>{{ __('emails.' . $key) }}</h2>
    @if (count($eventType['events']) == 0)
    {{ __('emails.notification.noEvents') }}
    @if (count($eventType['alternativeEvents']) != 0)
    {{ __('emails.notification.alternativeEvents') }}
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