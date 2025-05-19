@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Perusahaan</th>
                <th>Nama Departemen</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Tgl</th>
                <th>Shift</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($jadwalkerja as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->user->company->name ?? '-' }}</td>
                    <td>{{ $a->employee->departement->name ?? '-' }}</td>
                    <td>{{ $a->user->nip ?? '-' }}</td>
                    <td>{{ $a->user->name ?? '-' }}</td>
                    <td>{{ $a->work_day ?? '-' }}</td>
                    <td>{{ $a->timework->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
