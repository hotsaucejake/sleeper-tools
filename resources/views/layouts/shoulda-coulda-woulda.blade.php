<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="hotsaucejake">
    <title>Laravel Tools</title>

    @vite(['resources/js/app.js'])

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        body {
            background-image: linear-gradient(180deg, #eee, #fff 100px, #fff);
        }

        .container {
            max-width: 960px;
        }

        .pricing-header {
            max-width: 700px;
        }
    </style>


    <!-- Custom styles for this template -->
    <link href="pricing.css" rel="stylesheet">
</head>

<body>

    <div class="container py-3">
        <header>
            <div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
                <a href="/" class="d-flex align-items-center text-dark text-decoration-none">
                    <span class="fs-4">Sleeper Tools</span>
                </a>

                <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
                    <a class="me-3 py-2 text-dark text-decoration-none" href="{{ route('shoulda-coulda-woulda.select-league') }}">Shoulda Coulda Woulda</a>
                </nav>
            </div>

            <div class="pricing-header p-3 pb-md-4 mx-auto text-center">
                <h1 class="display-4 fw-normal">Shoulda Woulda Coulda</h1>
                <p class="fs-5 text-muted">We all know you shoulda-coulda-woulda won it all this season if you had someone else's layup of a schedule. Use this to see what your overall record would have been in the regular season if you had a different schedule. </p>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="pt-4 my-md-5 pt-md-5 border-top">
            <div class="row">
                <div class="col-12 col-md">

                </div>
                <div class="col-6 col-md">

                </div>
                <div class="col-6 col-md">

                </div>
                <div class="col-6 col-md">

                </div>
            </div>
        </footer>
    </div>



</body>

</html>
