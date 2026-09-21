@extends('layouts.app')

@section('title', 'Review Application')

@section('content')
    @include('fassg.applications._review', ['reviewContext' => 'verification'])
@endsection