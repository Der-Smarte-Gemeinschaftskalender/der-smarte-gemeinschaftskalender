<a class="event-link" href="{{ env('APP_URL') }}/events/{{ $event['uuid'] }}">
    <div class="event">
        <div class="event-title">{{ $event['title'] ?? __('emails.notification.eventFallbackTitle') }}</div>
        <div class="event-date">
            📅 {{ $event['formattedBeginsOn'] ?? __('emails.notification.dateTbd') }}<br />
            👤 {{ $event['attributedTo']['name'] }}
        </div>
    </div>
</a>