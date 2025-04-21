@extends('layouts.template')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3>{{ $page->title }}</h3>
            <div class="card-tools">
                <button onclick="modalAction('{{ url('/profile/import') }}')" class="btn btn-info">
                    <i class="fa fa-upload"></i> Ubah Foto Profil
                </button>
            </div>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <img src="{{ asset('profil/' . Auth::user()->username . '/foto.png') . '?v=' . time() }}"
                            alt="Foto Profil"
                            class="img-thumbnail shadow-sm rounded-circle"
                            style="max-width: 150px;">
                        </div>
                        <div class="col-md-9">
                            <div class="bg-light p-3 rounded border">
                                <h5 class="mb-3 text-primary">Informasi Pengguna</h5>
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <th style="width: 150px;">Username</th>
                                        <td>: {{ $user->username }}</td>
                                    </tr>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <td>: {{ $user->nama }}</td>
                                    </tr>
                                    <tr>
                                        <th>Level Pengguna</th>
                                        <td>: {{ $user->level->level_nama ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="myModal" class="modal fade animate shake" tabindex="-1" role="dialog"
             data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true">
        </div>
    </div>
@endsection

@push('js')
<script>
    function modalAction(url = '') {
        $('#myModal').load(url, function () {
            $('#myModal').modal('show');
        });
    }
</script>
@endpush
