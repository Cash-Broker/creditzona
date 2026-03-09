@extends('layouts.app')

@section('title', 'Потребителски кредит')

@section('content')
    <h1>Потребителски кредит</h1>
    <p>Изпратете запитване и ще ви върнем обаждане за консултация.</p>

    @include('partials.lead-form', ['serviceType' => 'consumer'])
@endsection