@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.common.greeting') }}</h1>

    {!! $mailBody !!}

    <a href="{{ $urlMyOrganisations }}" class="btn">{{ __('emails.organisationRemoved.button') }}</a>

    <p>{{ __('emails.common.alternativeUrl') }}</p>
    <p>{{ $urlMyOrganisations }}</p>
</div>

@include('emails.partials.footer')
@endsection