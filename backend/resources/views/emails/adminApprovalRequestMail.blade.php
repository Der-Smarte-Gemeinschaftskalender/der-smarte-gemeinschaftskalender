@extends('emails.partials.body', ['title' => __('emails.adminApprovalRequest.heading')])

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.adminApprovalRequest.heading') }}</h1>
    <p>
        {{ __('emails.adminApprovalRequest.intro') }}
    </p>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>{{ __('emails.common.requestType') }}</strong> {{ $requestTypeName }}</p>
        <p style="margin: 0 0 10px 0;"><strong>{{ __('emails.adminApprovalRequest.requestedBy') }}</strong> {{ $approvalRequest->createdBy->mobilizon_name }} ({{ $approvalRequest->createdBy->email }})</p>
        <p style="margin: 0;"><strong>{{ __('emails.common.requestId') }}</strong> #{{ $approvalRequest->id }}</p>
    </div>

    <p>{{ __('emails.adminApprovalRequest.reviewHint') }}</p>

    <a href="{{ $approvalUrl }}" class="btn">{{ __('emails.common.reviewRequest') }}</a>

    <p>{{ __('emails.common.alternativeUrl') }}</p>
    <p>{{ $approvalUrl }}</p>
</div>

@include('emails.partials.footer')
@endsection
