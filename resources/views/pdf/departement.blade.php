@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama perusahaan</th>
                <th>Nama departement</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($departement as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->company->name }}</td>
                    <td>{{ $a->name }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
