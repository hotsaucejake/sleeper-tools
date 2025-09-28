@extends('tools.shoulda-coulda-woulda')

@section('content')
    @if ($valid_league)
        <!-- Header with navigation when showing results -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard', ['league_id' => $league_id ?? '']) }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Shoulda Coulda Woulda Results</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-center">Strength of Schedule</h5>
                        <p class="card-text text-center">Ranked Toughest to Easiest</p>
                    </div>
                    <div class="card-body">
                        @foreach ($overall_losses as $roster => $loss)
                            <div class="progress mb-1" style="height: 36px">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                     role="progressbar"
                                     aria-valuenow="{{ $loss }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     style="width: {{ number_format(($loss / (max($overall_losses)) * 100), 1) }}%">
                                    <span class="fw-bold fs-6">{{ $managers[$roster]['name'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
        <div class="row">
            @foreach ($managers as $manager)
                <div class="col-md-4">
                    <div class="card mb-3">
                        <img src="{{ $manager['avatar'] }}" class="card-img-top" alt="{{ $manager['name'] }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $manager['name'] }}</h5>
                            <p class="card-text"><strong>Record:</strong> {{ $manager['win'] }} - {{ $manager['loss'] }}
                            </p>
                            {{-- <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p> --}}
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach ($manager['records'] as $record)
                                @if ($record['roster_id'] !== $manager['roster_id'])
                                    @if ($league->settings->league_average_match)
                                        <li class="list-group-item"><strong>{{ $record['name'] }}
                                                :</strong> {{ $record['win'] + ($managers[$manager['roster_id']]['win'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['win']) }}
                                            - {{ $record['loss'] + ($managers[$manager['roster_id']]['loss'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['loss']) }}
                                        </li>
                                    @else
                                        <li class="list-group-item"><strong>{{ $record['name'] }}
                                                :</strong> {{ $record['win'] }}
                                            - {{ $record['loss'] }}</li>
                                    @endif
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Share Section for results -->
        <div class="row mt-4">
            <div class="col">
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">Share this analysis!</h6>
                    <p class="mb-2">Copy this URL to share your league's Shoulda Coulda Woulda analysis:</p>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control"
                            value="{{ route('shoulda-coulda-woulda', ['league_id' => $league_id ?? '']) }}"
                            readonly
                            id="shareUrl"
                        >
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col">
                <div class="input-group mb-3 mx-auto" style="max-width: 500px;">
                    <input type="text" name="league_id" id="league_id" class="form-control"
                           placeholder="Sleeper League ID" aria-label="Sleeper League ID"
                           aria-describedby="button-addon2"
                           onchange="onLeagueChange()">
                    <a href="#" id="league_link" class="btn btn-outline-secondary" type="button"
                       id="button-addon2">Go</a>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('footer-scripts')
    <script>
        function onLeagueChange() {
            var league_id = document.getElementById('league_id')
            var league_link = document.getElementById('league_link')
            league_link.href = '/?league=' + league_id.value
        }

        function copyToClipboard() {
            const urlInput = document.getElementById('shareUrl');
            if (urlInput) {
                urlInput.select();
                urlInput.setSelectionRange(0, 99999); // For mobile devices
                navigator.clipboard.writeText(urlInput.value).then(function () {
                    // Show success feedback
                    const button = document.querySelector('button[onclick="copyToClipboard()"]');
                    const originalText = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-success');

                    setTimeout(function () {
                        button.textContent = originalText;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-secondary');
                    }, 2000);
                });
            }
        }
    </script>
@endsection
