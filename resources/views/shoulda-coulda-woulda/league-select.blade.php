@extends('layouts.shoulda-coulda-woulda')

@section('content')
    @if ($valid_league)
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
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
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
                            <p class="card-text"><strong>Record:</strong> {{ $manager['win'] }} - {{ $manager['loss'] }}</p>
                            {{-- <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p> --}}
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach ($manager['records'] as $record)
                                @if ($record['roster_id'] !== $manager['roster_id'])
                                    @if ($league->settings->league_average_match)
                                        <li class="list-group-item"><strong>{{ $record['name'] }}:</strong> {{ $record['win'] + ($managers[$manager['roster_id']]['win'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['win']) }}
                                            - {{ $record['loss'] + ($managers[$manager['roster_id']]['loss'] - $managers[$manager['roster_id']]['records'][$manager['roster_id']]['loss']) }}</li>
                                    @else
                                        <li class="list-group-item"><strong>{{ $record['name'] }}:</strong> {{ $record['win'] }}
                                            - {{ $record['loss'] }}</li>
                                    @endif
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="row">
            <div class="col">
                <div class="input-group mb-3 mx-auto" style="max-width: 500px;">
                    <input type="text" name="league_id" id="league_id" class="form-control"
                        placeholder="Sleeper League ID" aria-label="Sleeper League ID" aria-describedby="button-addon2"
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
            league_link.href = '/shoulda-coulda-woulda?league=' + league_id.value
        }
    </script>
@endsection
