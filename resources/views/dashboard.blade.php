@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Sleeper Tools Dashboard</h1>
                @include('partials.league-id-display', ['league_id' => $league_id])
            </div>

            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <h2 class="h4 mb-4">Available Analytics Tools</h2>

            <div class="row">
                <!-- Shoulda Coulda Woulda Tool -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-primary me-3">
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <h5 class="card-title mb-0">Shoulda Coulda Woulda</h5>
                            </div>
                            <p class="card-text">
                                Analyze how your team would perform with different schedules. See strength of schedule rankings and alternative win/loss records.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="{{ route('shoulda-coulda-woulda', ['league_id' => $league_id]) }}" class="btn btn-primary">
                                Analyze Schedule
                                <svg width="16" height="16" fill="currentColor" class="ms-1" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Performance Awards Tool -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-primary me-3">
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                                <h5 class="card-title mb-0">Performance Awards</h5>
                            </div>
                            <p class="card-text">
                                Weekly performance awards including best manager, biggest blowout, position leaders, and more fun categories.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="{{ route('performance-awards', ['league_id' => $league_id]) }}" class="btn btn-primary">
                                View Awards
                                <svg width="16" height="16" fill="currentColor" class="ms-1" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Placeholder for future tools -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 opacity-50">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-muted me-3">
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <h5 class="card-title mb-0 text-muted">Points Analysis</h5>
                            </div>
                            <p class="card-text text-muted">
                                Comprehensive scoring analysis, consistency metrics, and performance trends.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary disabled">Coming Soon</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 opacity-50">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-muted me-3">
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                    </svg>
                                </div>
                                <h5 class="card-title mb-0 text-muted">Draft Analysis</h5>
                            </div>
                            <p class="card-text text-muted">
                                Evaluate draft performance, pick value, and strategy effectiveness.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary disabled">Coming Soon</span>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.share-url', [
                'title' => 'Share Your Dashboard',
                'description' => "Copy this URL to share your league's analytics dashboard with others:",
                'url' => url()->current() . '?league_id=' . $league_id
            ])
        </div>
    </div>
</div>
@endsection
