@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('Create Plan') }}</h4>
        <a href="{{ route('system.plans.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('system.plans.store') }}">
                @csrf
                @include('system.plans.partials.form', ['plan' => new \App\Models\Plan()])

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('system.plans.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save Plan') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
