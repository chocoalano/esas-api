@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Perusahaan</th>
                <th>Nama Departemen</th>
                <th>Nama Posisi</th>
                <th>Nama Level</th>
                <th>NIP</th>
                <th>Nama</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($user as $index => $a)
                <tr>
                    {{-- @if ($a->employee)
                    {{ dd($a->employee) }}
                    @endif --}}
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->company->name ?? '-' }}</td>
                    <td>{{ $a->employee->departement->name ?? '-' }}</td>
                    <td>{{ $a->employee->jobPosition->name ?? '-' }}</td>
                    <td>{{ $a->employee->jobLevel->name ?? '-' }}</td>
                    <td>{{ $a->nip ?? '-' }}</td>
                    <td>{{ $a->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
