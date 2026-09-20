@extends('layouts.app')

@section('title', 'Review Application')
@section('eyebrow', 'FASSG Office · Applications')
@section('page-title', 'Review Application: ' . $application->studentProfile->user->name)

@section('content')
    @include('fassg.applications._review', ['reviewContext' => 'applications'])
@endsection