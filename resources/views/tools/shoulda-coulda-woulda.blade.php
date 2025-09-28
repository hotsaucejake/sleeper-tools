@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Header with navigation -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard', ['league_id' => $league_id ?? '']) }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Shoulda Coulda Woulda</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Shoulda Coulda Woulda</h1>
                @include('partials.league-id-display', ['league_id' => $league_id ?? ''])
            </div>

            <!-- Strength of Schedule Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0 text-center">Strength of Schedule</h5>
                    <p class="card-text text-center text-muted mb-0">Ranked Toughest to Easiest</p>
                </div>
                <div class="card-body">
                    @foreach ($overall_losses as $roster => $loss)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold">{{ $managers[$roster]['name'] }}</span>
                                <small class="text-muted">{{ $loss }} losses</small>
                            </div>
                            <div class="progress" style="height: 25px">
                                <div class="progress-bar progress-bar-striped bg-info"
                                     role="progressbar"
                                     aria-valuenow="{{ $loss }}"
                                     aria-valuemin="0"
                                     aria-valuemax="{{ max($overall_losses) }}"
                                     style="width: {{ number_format(($loss / (max($overall_losses)) * 100), 1) }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Manager Analysis Cards -->
            <h4 class="mb-3">Team Analysis</h4>
            <div class="row">
                @foreach ($managers as $manager)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            @if($manager['avatar'])
                                <img src="{{ $manager['avatar'] }}" class="card-img-top" alt="{{ $manager['name'] }}" style="height: 200px; object-fit: cover;">
                            @endif
                            <div class="card-header">
                                <h5 class="card-title mb-1">{{ $manager['name'] }}</h5>
                                <p class="card-text mb-0">
                                    <strong>Actual Record:</strong>
                                    <span class="badge bg-primary">{{ $manager['win'] }} - {{ $manager['loss'] }}</span>
                                </p>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>vs. Team</th>
                                                <th class="text-center">Record</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($manager['records'] as $record)
                                                @if ($record['roster_id'] !== $manager['roster_id'])
                                                    <tr>
                                                        <td>{{ $record['name'] }}</td>
                                                        <td class="text-center">
                                                            @if ($league->settings->league_average_match ?? false)
                                                                <span class="badge bg-secondary">
                                                                    {{ $record['win'] + ($managers[$manager['roster_id']]['win'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['win']) }} -
                                                                    {{ $record['loss'] + ($managers[$manager['roster_id']]['loss'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['loss']) }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">
                                                                    {{ $record['win'] }} - {{ $record['loss'] }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @include('partials.share-url', [
                'title' => 'Share this analysis!',
                'description' => "Copy this URL to share your league's Shoulda Coulda Woulda analysis:",
                'url' => route('shoulda-coulda-woulda', ['league_id' => $league_id ?? ''])
            ])
        </div>
    </div>
</div>
@endsection
