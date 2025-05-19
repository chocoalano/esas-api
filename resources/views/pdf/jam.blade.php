@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama perusahaan</th>
                <th>Nama departement</th>
                <th>Nama</th>
                <th>Jam masuk</th>
                <th>Jam pulang</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($jam as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->company->name }}</td>
                    <td>{{ $a->department->name }}</td>
                    <td>{{ $a->name }}</td>
                    <td>{{ $a->in }}</td>
                    <td>{{ $a->out }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
