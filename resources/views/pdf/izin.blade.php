@extends('layouts.layouts-pdf')

@section('title', 'Laporan Kehadiran')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>No. Izin</th>
                <th>Jenis Izin</th>
                <th>NIP User</th>
                <th>Nama User</th>
                <th>Jadwal Izin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($izin as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->permit_numbers ?? '-' }}</td>
                    <td>{{ $a->permitType->type ?? '-' }}</td>
                    <td>{{ $a->user->nip ?? '-' }}</td>
                    <td>{{ $a->user->name ?? '-' }}</td>
                    <td>{{ $a->userTimeworkSchedule->work_day ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
