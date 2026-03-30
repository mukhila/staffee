<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

  <!-- begin::GXON Meta Basic -->
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="robots" content="index, follow">
  <meta name="author" content="LayoutDrop">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'Staff Management') }}</title>

  <!-- begin::GXON Favicon Tags -->
  <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/apple-touch-icon.png') }}">
  <!-- end::GXON Favicon Tags -->

  <!-- begin::GXON Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
  <!-- end::GXON Google Fonts -->

  <!-- begin::GXON Required Stylesheet -->
  <link rel="stylesheet" href="{{ asset('assets/libs/flaticon/css/all/all.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/lucide/lucide.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/simplebar/simplebar.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/node-waves/waves.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap-select/css/bootstrap-select.min.css') }}">
  <!-- end::GXON Required Stylesheet -->

  <!-- begin::GXON CSS Stylesheet -->
  <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/datatables/datatables.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
  <!-- end::GXON CSS Stylesheet -->

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
</head>

<body>
  <div class="page-layout">

    @include('layouts.partials.header')

    @include('layouts.partials.sidebar')

    <!-- begin::GXON Sidebar right -->
    <div class="app-sidebar-end">
      <ul class="sidebar-list">
        <li>
          <a href="#">
            <div class="avatar avatar-sm bg-warning shadow-sharp-warning rounded-circle text-white mx-auto mb-2">
              <i class="fi fi-rr-to-do"></i>
            </div>
            <span class="text-dark">Task</span>
          </a>
        </li>
        <li>
          <a href="#">
            <div class="avatar avatar-sm bg-secondary shadow-sharp-secondary rounded-circle text-white mx-auto mb-2">
              <i class="fi fi-rr-interrogation"></i>
            </div>
            <span class="text-dark">Help</span>
          </a>
        </li>
        <li>
          <a href="#">
            <div class="avatar avatar-sm bg-info shadow-sharp-info rounded-circle text-white mx-auto mb-2">
              <i class="fi fi-rr-calendar"></i>
            </div>
            <span class="text-dark">Event</span>
          </a>
        </li>
        <li>
          <a href="#">
            <div class="avatar avatar-sm bg-gray shadow-sharp-gray rounded-circle text-white mx-auto mb-2">
              <i class="fi fi-rr-settings"></i>
            </div>
            <span class="text-dark">Settings</span>
          </a>
        </li>
      </ul>
    </div>
    <!-- end::GXON Sidebar right -->

    <main class="app-wrapper">
        {{ $slot }}
    </main>

    @include('layouts.partials.footer')

  </div>

  <!-- begin::GXON Page Scripts -->
  <script src="{{ asset('assets/libs/global/global.min.js') }}"></script>
  <script src="{{ asset('assets/libs/sortable/Sortable.min.js') }}"></script>
  <script src="{{ asset('assets/libs/chartjs/chart.js') }}"></script>
  <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/libs/datatables/datatables.min.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  <script src="{{ asset('assets/js/todolist.js') }}"></script>
  <script src="{{ asset('assets/js/appSettings.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
  <!-- end::GXON Page Scripts -->
  @stack('scripts')
</body>

</html>
