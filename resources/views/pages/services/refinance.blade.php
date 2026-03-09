@extends('layouts.app')

@section('title', 'Рефинансиране')

@section('content')
    <h1>Рефинансиране</h1>
    <p>Събиране на информация и преглед на възможности.</p>

    @include('partials.lead-form', ['serviceType' => 'refinance'])
@endsection