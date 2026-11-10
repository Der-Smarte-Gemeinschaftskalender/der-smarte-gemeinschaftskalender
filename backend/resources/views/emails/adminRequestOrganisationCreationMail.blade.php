@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.common.greeting') }}</h1>
    <p>
        {{ __('emails.adminRequestOrganisationCreation.text', ['organisationName' => $requestOrganisationNamen]) }}
    </p>

    <a href="{{ $urlRequestedOrganisations }}" class="btn">{{ __('emails.common.reviewRequest') }}</a>

    <p>{{ __('emails.common.alternativeUrl') }}</p>
    <p>{{ $urlRequestedOrganisations }}</p>
</div>

@include('emails.partials.footer')
@endsection