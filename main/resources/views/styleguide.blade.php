@extends(Config::theme() . 'layout.master')

@section('content')
<main class="container py-4">
  <h1 class="mb-4">Styleguide</h1>

  <section class="mb-5">
    <h2 class="h4">Buttons</h2>
    @include('partials.ui.button', ['text' => 'Primary', 'variant' => 'primary'])
    @include('partials.ui.button', ['text' => 'Outline', 'variant' => 'outline', 'attributes' => ['class' => 'ms-2']])
  </section>

  <section class="mb-5">
    <h2 class="h4">Inputs</h2>
    @include('partials.ui.input', ['label' => 'Email', 'type' => 'email', 'name' => 'email', 'id' => 'sg-email', 'hint' => 'We’ll never share your email.', 'icon' => 'las la-envelope'])
    @include('partials.ui.input', ['label' => 'Password', 'type' => 'password', 'name' => 'password', 'id' => 'sg-password', 'icon' => 'las la-lock'])
  </section>

  <section>
    <h2 class="h4">Tokens</h2>
    <div class="p-3 rounded-md shadow-sm" style="background: var(--color-surface); color: var(--color-text)">
      Primary: <span style="color: var(--color-primary-500)">#</span>
    </div>
  </section>
</main>
@endsection
