@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Select Your League</h4>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted mb-4">
                        Enter your Sleeper League ID to access fantasy football analysis tools
                    </p>

                    <form method="GET" action="{{ route('home') }}">
                        <div class="mb-3">
                            <label for="league_id" class="form-label">League ID</label>
                            <div class="input-group">
                                <input type="text"
                                       name="league"
                                       id="league_id"
                                       class="form-control form-control-lg"
                                       placeholder="Enter your Sleeper League ID"
                                       aria-label="Sleeper League ID"
                                       required>
                                <button class="btn btn-primary btn-lg" type="submit">
                                    Analyze League
                                </button>
                            </div>
                            <div class="form-text">
                                You can find your League ID in the Sleeper app under League Settings
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
