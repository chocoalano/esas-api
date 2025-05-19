@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Jenis Izin</th>
                <th>Dibayar</th>
                <th>Persetujuan atasan</th>
                <th>Persetujuan manager</th>
                <th>Persetujuan HR</th>
                <th>File pendukung</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i=1;
            @endphp
            @foreach ($jenis_izin as $a)
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $a->type }}</td>
                    <td>{{ $a->is_payed === 1 ? 'ya' : 'tidak' }}</td>
                    <td>{{ $a->approval_line === 1 ? 'ya' : 'tidak' }}</td>
                    <td>{{ $a->approval_manager === 1 ? 'ya' : 'tidak' }}</td>
                    <td>{{ $a->approval_hr === 1 ? 'ya' : 'tidak' }}</td>
                    <td>{{ $a->with_file === 1 ? 'ya' : 'tidak' }}</td>
                </tr>
                @php
                    $i++;
                @endphp
            @endforeach
        </tbody>
    </table>
@endsection
