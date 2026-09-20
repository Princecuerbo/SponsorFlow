@extends('layouts.app')

@section('title', 'Review Application')
@section('eyebrow', 'FASSG Office · Verification')
@section('page-title', 'Review Application: ' . $application->studentProfile->user->name)

@section('content')
    @include('fassg.applications._review', ['reviewContext' => 'verification'])
@endsection