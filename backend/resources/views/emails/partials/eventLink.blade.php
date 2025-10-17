<a class="event-link" href="{{ env('APP_URL') }}/events/{{ $event['uuid'] }}">
    <div class="event">
        <div class="event-title">{{ $event['title'] ?? 'Veranstaltung' }}</div>
        <div class="event-date">
            📅 {{ $event['formattedBeginsOn'] ?? 'Datum wird noch bekannt gegeben' }}<br />
            👤 {{ $event['attributedTo']['name'] }}
        </div>
    </div>
</a>