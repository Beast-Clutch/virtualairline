@inject('moduleSvc', 'App\Services\ModuleService')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>@yield('title') - {!! config('app.name') !!}</title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
          name='viewport'/>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet"/>

    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css"/>
    {{--<link href="https://use.fontawesome.com/releases/v5.0.2/css/all.css" rel="stylesheet">--}}

    <link href="{!! public_asset('/assets/frontend/css/bootstrap.min.css') !!}" rel="stylesheet"/>
    <link href="{!! public_asset('/assets/vendor/select2/dist/css/select2.min.css') !!}" rel="stylesheet"/>
    <link href="{!! public_asset('/assets/frontend/css/now-ui-kit.css') !!}" rel="stylesheet"/>
    <link href="{!! public_asset('/assets/frontend/css/styles.css') !!}" rel="stylesheet"/>
    <link href="{!! public_asset('/assets/vendor/leaflet/dist/leaflet.css') !!}" rel="stylesheet"/>

    <style>
        .font-large {
            font-size: 20px;
        }

        .font-medium {
            font-size: 18px;
        }
    </style>

    @yield('css')

    <script>
    @if (Auth::user())
        const PHPVMS_USER_API_KEY = "{!! Auth::user()->api_key !!}";
    @else
        const PHPVMS_USER_API_KEY = false;
    @endif
    </script>

</head>

<body>
<!-- Navbar -->
<nav class="navbar navbar-toggleable-md" style="background: #067ec1;">
    <div class="container" style="width: 85%!important;">
        <div class="navbar-translate">
            <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
                    data-target="#navigation" aria-controls="navigation-index" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
            </button>
            <p class="navbar-brand text-white" data-placement="bottom" target="_blank">
                <a href="{!! url('/') !!}">
                    <img src="{!! public_asset('/assets/frontend/img/logo_blue_bg.svg') !!}" width="135px" style=""/>
                </a>
            </p>
        </div>
        <div class="collapse navbar-collapse justify-content-end" id="navigation">
            <ul class="navbar-nav">

                @if(Auth::check())
                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/dashboard') !!}">
                            <i class="fa fa-tachometer"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link" href="{!! url('/livemap') !!}">
                        <i class="fa fa-globe"></i>
                        <p>Live Map</p>
                    </a>
                </li>

                @if(!Auth::check())
                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/login') !!}">
                            <i class="fa fa-sign-in"></i>
                            <p>Login</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/register') !!}">
                            <i class="fa fa-id-card-o"></i>
                            <p>Register</p>
                        </a>
                    </li>

                    {{-- Show the module links for being logged out --}}
                    @foreach($moduleSvc->getFrontendLinks($logged_in=false) as &$link)
                        <li class="nav-item">
                            <a class="nav-link" href="{!! url($link['url']) !!}">
                                <i class="{!! $link['icon'] !!}"></i>
                                <p>{!! $link['title'] !!}</p>
                            </a>
                        </li>
                    @endforeach

                @else

                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/flights') !!}">
                            <i class="fa fa-plane"></i>
                            <p>Flights</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/pireps') !!}">
                            <i class="fa fa-cloud-upload"></i>
                            <p>PIREPs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/profile') !!}">
                            <i class="fa fa-user-circle-o"></i>
                            <p>Profile</p>
                        </a>
                    </li>

                    @role('admin')
                        <li class="nav-item">
                            <a class="nav-link" href="{!! url('/admin') !!}">
                                <i class="fa fa-circle-o-notch"></i>
                                <p>Admin</p>
                            </a>
                        </li>
                    @endrole

                    {{-- Show the module links for being logged out --}}
                    @foreach($moduleSvc->getFrontendLinks($logged_in=true) as &$link)
                        <li class="nav-item">
                            <a class="nav-link" href="{!! url($link['url']) !!}">
                                <i class="{!! $link['icon'] !!}"></i>
                                <p>{!! $link['title'] !!}</p>
                            </a>
                        </li>
                    @endforeach

                    <li class="nav-item">
                        <a class="nav-link" href="{!! url('/logout') !!}">
                            <i class="fa fa-external-link-square"></i>
                            <p>Log Out</p>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->
<div class="clearfix" style="height: 25px;"></div>
<div class="wrapper">
    <div class="clear"></div>
    <div class="container-fluid" style="width: 85%!important;">
        @include('layouts.default.flash.message')
        @yield('content')
    </div>
    <div class="clearfix" style="height: 200px;"></div>
</div>

<script src="{!! public_asset('/assets/vendor/lodash/dist/lodash.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/vendor/jquery/dist/jquery.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/vendor/bootstrap/bootstrap.min.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/vendor/select2/dist/js/select2.js') !!}"></script>
{{--<script src="{!! public_asset('/assets/frontend/js/plugins/bootstrap-switch.js') !!}"></script>
<script src="{!! public_asset('/assets/frontend/js/plugins/nouislider.min.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/frontend/js/plugins/bootstrap-datepicker.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/frontend/js/now-ui-kit.js') !!}" type="text/javascript"></script>
<script src="{!! public_asset('/assets/vendor/leaflet/dist/leaflet.js') !!}"></script>
<script src="{!! public_asset('/assets/vendor/leaflet-plugins/leaflet.geodesic.js') !!}?v={!! time() !!}"></script>
<script src="{!! public_asset('/assets/vendor/leaflet-plugins/leaflet.rotatedMarker.js') !!}?v={!! time() !!}"></script>
<script src="{!! public_asset('/assets/vendor/leaflet-ajax/dist/leaflet.ajax.min.js') !!}?v={!! time() !!}"></script>--}}
<script src="{!! public_asset('/assets/system/js/system-deps.js') !!}?v={!! time() !!}"></script>
<script src="{!! public_asset('/assets/system/js/system.js') !!}?v={!! time() !!}"></script>

<script>
$(document).ready(function () {
    $(".select2").select2();
});
</script>

@yield('scripts')

</body>
</html>
