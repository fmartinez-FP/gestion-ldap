{{-- resources/views/livewire/aplicaciones/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Aplicaciones')
@section('page-title', 'Aplicaciones registradas')
@section('content')
<div class="space-y-8">
  <livewire:aplicaciones.gestor-apps />
  <livewire:aplicaciones.grupos-globales />
</div>
@endsection
