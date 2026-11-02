<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuideController extends Controller
{
    /**
     * Menampilkan panduan penggunaan aplikasi & diagram alur sistem HRIS.
     * Tersedia untuk seluruh role (Direktur, Manager, Karyawan) di semua departemen.
     */
    public function index()
    {
        $authUser = Auth::user();
        $employee = $authUser->employee;

        return view('guide.index', compact('authUser', 'employee'));
    }
}
