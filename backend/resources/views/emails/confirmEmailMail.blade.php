@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    {!! $mailBody !!}

    <a href="{{ env('APP_URL') }}/validate/{{ $verificationToken }}" class="btn">E-Mail-Adresse bestätigen</a>

    <p>Alternativ können sie auch folgende URL im Browser besuchen:</p>
    <p>{{ env('APP_URL') }}/validate/{{ $verificationToken }}</p>
</div>

@include('emails.partials.footer')
@endsection