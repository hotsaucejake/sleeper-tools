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
                                @for ($i = 1; $i <= $max_week; $i++)
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

            <!-- Award Tallies Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Award Tallies (Weeks 1-{{ $week }})</h5>
                    <p class="card-text text-muted mb-0">Total awards earned by each manager through week {{ $week }}</p>
                </div>
                <div class="card-body">
                    @if (!empty($award_tallies))
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Manager</th>
                                        <th class="text-center">💰<br><small>Money Shot</small></th>
                                        <th class="text-center">🍆<br><small>RJPA</small></th>
                                        <th class="text-center">🌮<br><small>Taco</small></th>
                                        <th class="text-center">🔥<br><small>Best Manager</small></th>
                                        <th class="text-center">🤔<br><small>Worst Manager</small></th>
                                        <th class="text-center">😂<br><small>Biggest Blowout</small></th>
                                        <th class="text-center">😱<br><small>Narrow Victory</small></th>
                                        <th class="text-center">🤓<br><small>Overachiever</small></th>
                                        <th class="text-center">💀<br><small>Below Expectation</small></th>
                                        <th class="text-center">⭐<br><small>Position Awards</small></th>
                                        <th class="text-center">👀<br><small>Benchwarmer</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($award_tallies as $rosterId => $tally)
                                        @php
                                            $positionAwards = ($tally['awards']['QB of the Week'] ?? 0) +
                                                            ($tally['awards']['RB of the Week'] ?? 0) +
                                                            ($tally['awards']['WR of the Week'] ?? 0) +
                                                            ($tally['awards']['TE of the Week'] ?? 0) +
                                                            ($tally['awards']['K of the Week'] ?? 0) +
                                                            ($tally['awards']['DEF of the Week'] ?? 0);

                                            $benchwarmerAwards = ($tally['awards']['QB Benchwarmer of the Week'] ?? 0) +
                                                               ($tally['awards']['RB Benchwarmer of the Week'] ?? 0) +
                                                               ($tally['awards']['WR Benchwarmer of the Week'] ?? 0) +
                                                               ($tally['awards']['TE Benchwarmer of the Week'] ?? 0);

                                            $total = ($tally['awards']['The Money Shot'] ?? 0) +
                                                   ($tally['awards']['The Taco'] ?? 0) +
                                                   ($tally['awards']['Best Manager'] ?? 0) +
                                                   ($tally['awards']['Worst Manager'] ?? 0) +
                                                   ($tally['awards']['Biggest Blowout'] ?? 0) +
                                                   ($tally['awards']['Narrow Victory'] ?? 0) +
                                                   ($tally['awards']['Overachiever'] ?? 0) +
                                                   ($tally['awards']['Below Expectation'] ?? 0) +
                                                   $positionAwards +
                                                   $benchwarmerAwards +
                                                   ($tally['awards']['The Ron Jeremy Performance Award'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $tally['manager']['name'] }}</td>
                                            <td class="text-center">{{ $tally['awards']['The Money Shot'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['The Ron Jeremy Performance Award'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['The Taco'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Best Manager'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Worst Manager'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Biggest Blowout'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Narrow Victory'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Overachiever'] ?? 0 }}</td>
                                            <td class="text-center">{{ $tally['awards']['Below Expectation'] ?? 0 }}</td>
                                            <td class="text-center">{{ $positionAwards }}</td>
                                            <td class="text-center">{{ $benchwarmerAwards }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No award data available.</p>
                    @endif
                </div>
            </div>

            @include('partials.share-url', [
                'title' => 'Share these awards!',
                'description' => "Copy this URL to share Week {$week} performance awards:",
                'url' => route('performance-awards', ['league_id' => $league_id ?? '', 'week' => $week])
            ])
        </div>
    </div>
</div>
@endsection
