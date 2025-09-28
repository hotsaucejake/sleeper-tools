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
                    <li class="breadcrumb-item active" aria-current="page">Performance Awards</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Performance Awards - Week {{ $week }}</h1>
                @include('partials.league-id-display', ['league_id' => $league_id ?? ''])
            </div>

            <!-- Week Selector -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('performance-awards') }}" class="row g-3 align-items-center">
                        <input type="hidden" name="league_id" value="{{ $league_id }}">
                        <div class="col-auto">
                            <label for="week" class="form-label">Select Week:</label>
                        </div>
                        <div class="col-auto">
                            <select name="week" id="week" class="form-select" onchange="this.form.submit()">
                                @for ($i = 1; $i <= ($current_week->toInt() ?? 18); $i++)
                                    <option value="{{ $i }}" {{ $week == $i ? 'selected' : '' }}>
                                        Week {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Awards Grid -->
            <div class="row">
                @foreach ($awards as $award)
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100">
                            @if ($award->playerInfo && $award->playerInfo['avatar'])
                                <div class="position-relative">
                                    <img src="{{ $award->playerInfo['avatar'] }}"
                                         class="card-img-top player-avatar"
                                         alt="{{ $award->playerInfo['name'] }}"
                                         style="height: 200px; object-fit: cover; object-position: top;"
                                         crossorigin="anonymous"
                                         loading="lazy"
                                         onerror="this.parentElement.style.display='none'; this.parentElement.nextElementSibling.querySelector('.no-avatar-emoji').style.display='block';">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-dark fs-6">{{ $award->emoji }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="card-body text-center">
                                @if (!($award->playerInfo && $award->playerInfo['avatar']))
                                    <div class="display-1 mb-3">{{ $award->emoji }}</div>
                                @else
                                    <div class="display-1 mb-3 no-avatar-emoji" style="display: none;">{{ $award->emoji }}</div>
                                @endif

                                <h5 class="card-title text-primary">{{ $award->title }}</h5>
                                <h6 class="card-subtitle mb-2 text-muted">{{ $award->managerName }}</h6>

                                @if ($award->playerInfo)
                                    <div class="mb-2">
                                        <h6 class="fw-bold text-dark mb-1">{{ $award->playerInfo['name'] }}</h6>
                                        <small class="text-muted">
                                            {{ $award->playerInfo['position'] }}
                                            @if ($award->playerInfo['team'])
                                                - {{ strtoupper($award->playerInfo['team']) }}
                                            @endif
                                        </small>
                                    </div>
                                @endif

                                <p class="card-text">{{ $award->description }}</p>

                                @if ($award->value > 0)
                                    <div class="mt-3">
                                        <span class="badge bg-secondary fs-6">
                                            @if (str_contains($award->title, 'Manager') || str_contains($award->title, 'Blowout') || str_contains($award->title, 'Victory'))
                                                {{ number_format($award->value, 1) }}%
                                            @else
                                                {{ number_format($award->value, 2) }} pts
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if ($award->secondaryManagerName)
                                    <div class="mt-2">
                                        <small class="text-muted">vs {{ $award->secondaryManagerName }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (empty($awards))
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            <h5 class="alert-heading">No Awards Available</h5>
                            <p class="mb-0">No performance data is available for Week {{ $week }}. This might be because the week hasn't been played yet or there's no matchup data available.</p>
                        </div>
                    </div>
                </div>
            @endif

            @include('partials.share-url', [
                'title' => 'Share these awards!',
                'description' => "Copy this URL to share Week {$week} performance awards:",
                'url' => route('performance-awards', ['league_id' => $league_id ?? '', 'week' => $week])
            ])
        </div>
    </div>
</div>
@endsection