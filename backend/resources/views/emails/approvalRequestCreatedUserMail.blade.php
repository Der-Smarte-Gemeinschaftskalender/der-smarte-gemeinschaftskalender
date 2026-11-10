@extends('emails.partials.body', ['title' => __('emails.approvalRequestCreated.heading')])

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.approvalRequestCreated.heading') }}</h1>

    {!! $mailBody !!}

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>{{ __('emails.common.requestType') }}</strong> {{ $requestTypeName }}</p>
        <p style="margin: 0;"><strong>{{ __('emails.common.requestId') }}</strong> #{{ $requestId }}</p>
    </div>

    <p>
        {{ __('emails.approvalRequestCreated.reviewInfo') }}
    </p>

    <p>
        <strong>{{ __('emails.approvalRequestCreated.thanks') }}</strong>
    </p>
</div>

@include('emails.partials.footer')
@endsection
