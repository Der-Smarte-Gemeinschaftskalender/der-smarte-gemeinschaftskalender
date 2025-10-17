<div class="footer">
    <img src="{{env('APP_URL')}}/logo.png"
        alt="Logo: Der Smarte Gemeinschaftskalender"
        style="max-width: 228px; width: 100%; margin-bottom: 15px;">

    <h4>
        {{ env('APP_NAME') }} basiert auf Mobilizon im Fediverse.
    </h4>

    <div class="footer-links">
        <a href="{{ env('APP_URL') }}/public/terms">Nutzungsbedingungen</a>
        <a href="{{ env('APP_URL') }}/public/about">Impressum</a>
        <a href="{{ env('APP_URL') }}/public/privacy">Datenschutz</a>
    </div>
    @isset($unsubscribePath, $editPath)
    <p>
        Wenn Sie keine Benachrichtigungen mehr erhalten möchten,
        können Sie sich jederzeit
        <a href="{{ env('APP_URL') }}{{ $unsubscribePath }}">
            hier abmelden
        </a>.
        Oder Ihre Einstellungen
        <a href="{{ env('APP_URL') }}{{ $editPath }}">
            hier bearbeiten
        </a>.
    </p>
    @endisset
</div>