@extends('layouts.app')

@section('title', 'Изкупуване на задължения')

@section('content')
    <h1>Изкупуване на задължения</h1>
    <p>Описвате ситуацията и ви насочваме какво е реалистично.</p>

    @include('partials.lead-form', ['serviceType' => 'debt_buyout'])
@endsection