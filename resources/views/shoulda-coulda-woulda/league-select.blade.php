@extends('layouts.shoulda-coulda-woulda')

@section('content')
    <div class="row">
        <div class="col">
            <div class="input-group mb-3 mx-auto" style="max-width: 500px;">
                <input type="text" name="league_id" id="league_id" class="form-control" placeholder="Sleeper League ID" aria-label="Sleeper League ID" aria-describedby="button-addon2" onchange="onLeagueChange()">
                <a href="#" id="league_link" class="btn btn-outline-secondary" type="button" id="button-addon2">Go</a>
            </div>
        </div>
    </div>
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
