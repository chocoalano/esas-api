@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Radius Absen</th>
                <th>Alamat lengkap</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($company as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->name }}</td>
                    <td>{{ $a->latitude }}</td>
                    <td>{{ $a->longitude }}</td>
                    <td>{{ $a->radius }}</td>
                    <td>{{ $a->full_address }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
