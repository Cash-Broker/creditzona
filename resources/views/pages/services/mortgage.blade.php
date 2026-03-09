@extends('layouts.app')

@section('title', 'Ипотечен кредит')

@section('content')
    <h1>Ипотечен кредит</h1>
    <p>Консултация и ориентация по процеса.</p>

    @include('partials.lead-form', ['serviceType' => 'mortgage'])
@endsection