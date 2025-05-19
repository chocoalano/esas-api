@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama perusahaan</th>
                <th>Nama departement</th>
                <th>Nama level</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($level as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->company->name }}</td>
                    <td>{{ $a->departement->name }}</td>
                    <td>{{ $a->name }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
