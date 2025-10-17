@extends('emails.partials.body')

@section('content')
@include('emails.partials.header')

<div class="content">
    <h1>Moin</h1>
    <p>
        Ihr Benutzerkonto auf {{ env('APP_NAME') }} wurde gelöscht.
    </p>
</div>

@include('emails.partials.footer')
@endsection