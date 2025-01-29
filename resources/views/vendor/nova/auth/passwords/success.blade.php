@extends('nova::auth.layout')

@section('content')
    @include('nova::auth.partials.header')

    <div class="bg-white shadow rounded-lg p-8 max-w-login mx-auto">
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-6">{{ __('Password Reset Successful') }}</h2>
            <p class="mb-6">{{ __('Your password has been successfully reset.') }}</p>
            <button onclick="window.close()" class="btn btn-default btn-primary hover:bg-primary-dark">
                {{ __('Close') }}
            </button>
        </div>
    </div>
@endsection
