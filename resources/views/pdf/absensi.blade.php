@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nip</th>
                <th>Nama</th>
                <th>Departemen</th>
                <th>Jam masuk</th>
                <th>Jam pulang</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($absensi as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->user->nip }}</td>
                    <td>{{ $a->user->name }}</td>
                    <td>{{ $a->user->employee?->departement?->name ?? '-' }}</td>
                    <td>{{ $a->time_in }}</td>
                    <td>{{ $a->time_out }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
