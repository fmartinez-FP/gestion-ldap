@extends('layouts.app')
@section('title', isset($uid) ? 'Editar usuario' : 'Nuevo usuario')
@section('page-title', isset($uid) ? 'Editar usuario — ' . $uid : 'Nuevo usuario')
@section('content')
<livewire:usuarios.form-usuario :uid="$uid ?? null" />
@endsection
