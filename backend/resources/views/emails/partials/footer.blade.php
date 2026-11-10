<div class="footer">
    <img src="{{env('APP_URL')}}/logo.png"
        alt="Logo: Der Smarte Gemeinschaftskalender"
        style="max-width: 228px; width: 100%; margin-bottom: 15px;">

    <h4>
        {{ __('emails.footer.basedOn', ['appName' => config('app.name')]) }}
    </h4>

    <div class="footer-links">
        <a href="{{ env('APP_URL') }}/public/terms">{{ __('emails.footer.terms') }}</a>
        <a href="{{ env('APP_URL') }}/public/about">{{ __('emails.footer.imprint') }}</a>
        <a href="{{ env('APP_URL') }}/public/privacy">{{ __('emails.footer.privacy') }}</a>
    </div>
    @isset($unsubscribePath, $editPath)
    {{-- The translation is trusted, only the inserted links are escaped --}}
    <p>
        {!! __('emails.footer.unsubscribe', [
            'unsubscribeLink' => '<a href="' . e(env('APP_URL') . $unsubscribePath) . '">' . e(__('emails.footer.unsubscribeLinkText')) . '</a>',
            'editLink' => '<a href="' . e(env('APP_URL') . $editPath) . '">' . e(__('emails.footer.editLinkText')) . '</a>',
        ]) !!}
    </p>
    @endisset
</div>