<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarApiController extends Controller
{
    /**
     * Get Calendar Schedules & Agendas for Authenticated User
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        $employee = $authUser->employee;

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $specificDate = $request->query('date');

        $schedulesQuery = CalendarSchedule::with(['user', 'creator'])
            ->where(function ($q) use ($authUser) {
                // 1. Jadwal milik sendiri (baik approved maupun pending)
                $q->where('user_id', $authUser->id);

                // 2. Jadwal seluruh perusahaan yang sudah disetujui
                $q->orWhere(function ($sub) {
                    $sub->where('is_company_wide', true)
                        ->where('approval_status', 'approved');
                });

                // 3. Otorisasi Direktur Utama
                if ($authUser->isDirektur()) {
                    $q->orWhere(function ($sub) {
                        $sub->where('approval_status', 'approved')
                            ->where(function ($cross) {
                                $cross->where('target_department', 'Executive')
                                      ->orWhere('target_departments', 'like', '%"Executive"%')
                                      ->orWhere('target_department', 'Direksi')
                                      ->orWhere('target_departments', 'like', '%"Direksi"%')
                                      ->orWhere('scope', 'direksi');
                            });
                    });
                }

                // 4. Otorisasi Sekretaris Direktur
                if ($authUser->isSekretaris()) {
                    $director = User::where('role', 'direktur')->first();
                    if ($director) {
                        $q->orWhere('user_id', $director->id);
                    }
                    $q->orWhere(function ($cross) {
                        $cross->where('target_department', 'Executive')
                              ->orWhere('target_departments', 'like', '%"Executive"%')
                              ->orWhere('target_department', 'Direksi')
                              ->orWhere('target_departments', 'like', '%"Direksi"%')
                              ->orWhere('scope', 'direksi');
                    });
                }

                // 5. Jadwal Departemen (karyawan & manager selain Direktur/Sekretaris)
                if (!$authUser->isDirektur() && !$authUser->isSekretaris() && $authUser->department) {
                    $q->orWhere(function ($sub) use ($authUser) {
                        $sub->where('scope', 'departemen')
                            ->where('department', $authUser->department);
                    });

                    // Antar departemen yang melibatkan departemen user
                    $q->orWhere(function ($sub) use ($authUser) {
                        $sub->where('scope', 'antar_departemen')
                            ->where(function ($cross) use ($authUser) {
                                $cross->where('department', $authUser->department)
                                      ->orWhere('target_department', $authUser->department)
                                      ->orWhere('target_departments', 'like', '%"' . $authUser->department . '"%');
                            });
                    });
                }
            });

        // Filter berdasarkan tanggal spesifik atau rentang bulan
        if ($specificDate) {
            $schedulesQuery->whereDate('date', $specificDate);
        } else {
            $schedulesQuery->whereYear('date', $year)
                ->whereMonth('date', $month);
        }

        $schedules = $schedulesQuery->orderBy('date', 'asc')
            ->orderBy('time_start', 'asc')
            ->get();

        $formatted = $schedules->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'date' => Carbon::parse($item->date)->toDateString(),
                'formatted_date' => Carbon::parse($item->date)->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_start' => $item->time_start ? substr($item->time_start, 0, 5) : null,
                'time_end' => $item->time_end ? substr($item->time_end, 0, 5) : null,
                'category' => $item->category ?? 'Agenda',
                'color' => $item->color ?? '#C81E1E',
                'is_company_wide' => (bool) $item->is_company_wide,
                'scope' => $item->scope ?? 'individu',
                'department' => $item->department,
                'approval_status' => $item->approval_status ?? 'approved',
                'creator_name' => $item->creator ? $item->creator->name : 'Sistem',
            ];
        });

        // Hitung ringkasan agenda hari ini
        $todayStr = Carbon::today()->toDateString();
        $todaySchedules = $formatted->filter(function ($item) use ($todayStr) {
            return $item['date'] === $todayStr;
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'total_schedules' => $formatted->count(),
                'today_schedules_count' => $todaySchedules->count(),
                'today_schedules' => $todaySchedules,
                'schedules' => $formatted,
            ],
        ]);
    }

    /**
     * Store New Schedule / Agenda from Mobile App
     */
    public function store(Request $request)
    {
        $authUser = $request->user();
        $employee = $authUser->employee;

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time_start' => 'nullable|string',
            'time_end' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:Meeting,Agenda,Tugas,Pelatihan,Penting,Lainnya',
            'scope' => 'nullable|string|in:individu,departemen,antar_departemen',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $scope = $request->input('scope', 'individu');
        $approvalStatus = 'approved';

        // Jika agenda departemen dibuat oleh staf non-manager, butuh persetujuan
        if ($scope === 'departemen' && !$authUser->isManager() && !$authUser->isDirektur()) {
            $approvalStatus = 'pending';
        }

        $schedule = CalendarSchedule::create([
            'user_id' => $authUser->id,
            'employee_id' => $employee?->id,
            'date' => $request->date,
            'title' => $request->title,
            'description' => $request->description,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'category' => $request->input('category', 'Agenda'),
            'color' => $this->getColorByCategory($request->input('category', 'Agenda')),
            'is_company_wide' => false,
            'department' => $authUser->department,
            'created_by' => $authUser->id,
            'scope' => $scope,
            'approval_status' => $approvalStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal agenda berhasil ditambahkan.',
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => Carbon::parse($schedule->date)->toDateString(),
                'category' => $schedule->category,
                'approval_status' => $schedule->approval_status,
            ],
        ], 201);
    }

    /**
     * Get Schedule Detail
     */
    public function show(Request $request, $id)
    {
        $authUser = $request->user();
        $schedule = CalendarSchedule::with(['user', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'description' => $schedule->description,
                'date' => Carbon::parse($schedule->date)->toDateString(),
                'formatted_date' => Carbon::parse($schedule->date)->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_start' => $schedule->time_start ? substr($schedule->time_start, 0, 5) : null,
                'time_end' => $schedule->time_end ? substr($schedule->time_end, 0, 5) : null,
                'category' => $schedule->category,
                'color' => $schedule->color,
                'scope' => $schedule->scope,
                'department' => $schedule->department,
                'approval_status' => $schedule->approval_status,
                'creator_name' => $schedule->creator ? $schedule->creator->name : 'Sistem',
                'created_at' => $schedule->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function getColorByCategory(string $category): string
    {
        return match ($category) {
            'Meeting' => '#C81E1E',      // Brand Red
            'Pelatihan' => '#881337',    // Burgundy Red
            'Tugas' => '#2563EB',        // Blue
            'Penting' => '#DC2626',      // Bright Red
            'Lainnya' => '#64748B',      // Slate / Silver Dark
            default => '#C81E1E',
        };
    }
}

