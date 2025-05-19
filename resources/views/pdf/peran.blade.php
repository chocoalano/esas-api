@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama peran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($peran as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->name }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
