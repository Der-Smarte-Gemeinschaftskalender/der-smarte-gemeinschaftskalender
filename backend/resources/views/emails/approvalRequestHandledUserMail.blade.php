@extends('emails.partials.body', ['title' => __('emails.approvalRequestHandled.heading')])

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>{{ __('emails.approvalRequestHandled.heading') }}</h1>

   {!! $mailBody !!}

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>{{ __('emails.approvalRequestHandled.status') }}</strong> <span style="color: @if($status === 'approved') #2ecc71 @elseif($status === 'rejected') #e74c3c @else #95a5a6 @endif; font-weight: bold;">
            @if($status === 'approved')
                {{ __('emails.approvalRequestHandled.statusApproved') }}
            @elseif($status === 'rejected')
                {{ __('emails.approvalRequestHandled.statusRejected') }}
            @else
                {{ ucfirst($status) }}
            @endif
        </span></p>
        @if($comment)
            <p style="margin: 0;"><strong>{{ __('emails.approvalRequestHandled.adminComment') }}</strong> {{ $comment }}</p>
        @endif
    </div>

    @if($status === 'approved')
        <p>{{ __('emails.approvalRequestHandled.approvedText') }}</p>
    @elseif($status === 'rejected')
        <p>{{ __('emails.approvalRequestHandled.rejectedText') }}</p>
    @endif
</div>

@include('emails.partials.footer')
@endsection
