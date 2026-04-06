<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TournamentController extends Controller
{
    // Danh sách giải đấu
    public function index(Request $request)
    {
        try {
            $query = Tournament::with(['organizer', 'fieldCluster']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('format')) {
                $query->where('format', $request->format);
            }

            // Nếu có tham số my_tournaments=true, chỉ lấy giải của user đang login
            if ($request->has('my_tournaments') && $request->my_tournaments == 'true') {
                $query->where('organizer_id', Auth::id());
            }
            // Nếu không có tham số, hiển thị tất cả giải công khai (status != draft)
            elseif (!$request->has('my_tournaments')) {
                $user = Auth::user();
                // Admin xem tất cả
                if ($user->role->name_role !== 'admin') {
                    // User thường xem: giải công khai + giải của mình
                    $query->where(function($q) use ($user) {
                        $q->where('status', '!=', 'draft')
                          ->orWhere('organizer_id', $user->id);
                    });
                }
            }

            $tournaments = $query->orderBy('created_at', 'desc')->paginate(10);

            return ApiResponse::success('Danh sách giải đấu', $tournaments);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // Tạo giải mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'format' => 'required|in:round_robin,knockout,group_knockout',
                'field_type' => 'required|in:5v5,7v7,11v11',
                'max_teams' => 'required|integer|min:2',
                'min_teams' => 'required|integer|min:2',
                'registration_start' => 'required|date',
                'registration_end' => 'required|date|after:registration_start',
                'start_date' => 'required|date|after:registration_end',
                'end_date' => 'required|date|after:start_date',
                'field_cluster_id' => 'required|exists:field_clusters,id',
                'payment_method' => 'required|in:cash,transfer',
                // ✅ Thêm validation cho bookings (optional)
                'bookings' => 'nullable|array',
                'bookings.*.field_id' => 'required_with:bookings|exists:fields,id',
                'bookings.*.date' => 'required_with:bookings|date',
                'bookings.*.session_type' => 'required_with:bookings|in:morning,afternoon,evening,full_day',
            ]);

            // Validate max_teams > min_teams
            if ($validated['max_teams'] < $validated['min_teams']) {
                return ApiResponse::error('max_teams phải lớn hơn hoặc bằng min_teams', null, 400);
            }

            // Kiểm tra cụm sân có hỗ trợ loại sân yêu cầu không
            $fieldCompatibility = $this->checkFieldClusterCompatibility(
                $validated['field_cluster_id'],
                $validated['field_type']
            );
            if (!$fieldCompatibility['compatible']) {
                return ApiResponse::error($fieldCompatibility['message'], null, 400);
            }

            $userId = Auth::id();

            // Kiểm tra user đã có giải đang hoạt động chưa (draft, open, in_progress)
            $activeTournament = Tournament::where('organizer_id', $userId)
                ->whereIn('status', ['draft', 'open', 'in_progress'])
                ->first();

            if ($activeTournament) {
                return ApiResponse::error(
                    'Bạn đã có giải đấu "' . $activeTournament->name . '" đang hoạt động (trạng thái: ' . $activeTournament->status . '). Vui lòng hoàn thành hoặc hủy giải đấu này trước khi tạo giải mới.',
                    ['active_tournament_id' => $activeTournament->id, 'active_tournament_status' => $activeTournament->status],
                    400
                );
            }

            // Kiểm tra phương thức thanh toán
            $fieldCluster = \App\Models\FieldCluster::with('owner')->find($validated['field_cluster_id']);
            if (!$fieldCluster || !$fieldCluster->owner) {
                return ApiResponse::error('Cụm sân không có chủ sân', null, 400);
            }

            // Nếu chọn chuyển khoản, kiểm tra owner đã upload QR code chưa
            if ($validated['payment_method'] === 'transfer') {
                if (!$fieldCluster->owner->qr_code_image) {
                    return ApiResponse::error(
                        'Cụm sân này chưa cấu hình QR code chuyển khoản. Vui lòng chọn phương thức "Tiền mặt" hoặc chọn cụm sân khác.',
                        null,
                        400
                    );
                }
            }

            // ✅ Bắt đầu transaction để đảm bảo data consistency
            DB::beginTransaction();
            try {
                $validated['organizer_id'] = $userId;
                $validated['status'] = 'draft';
                $validated['payment_status'] = $validated['payment_method'] === 'cash' ? 'pending' : 'pending';

                $tournament = Tournament::create($validated);

                // ✅ Nếu có bookings, tự động book sân luôn
                $bookingsCreated = [];
                $totalPrice = 0;

                if (!empty($validated['bookings'])) {
                    foreach ($validated['bookings'] as $booking) {
                        $field = \App\Models\Field::findOrFail($booking['field_id']);

                        // Kiểm tra field thuộc cụm sân của giải
                        if ($field->field_cluster_id !== $tournament->field_cluster_id) {
                            throw new \Exception("Sân '{$field->name}' (ID: {$field->id}) không thuộc cụm sân đã chọn cho giải đấu");
                        }

                        // Kiểm tra sân available
                        if ($field->status !== 'available') {
                            throw new \Exception("Sân '{$field->name}' (ID: {$field->id}) không khả dụng (trạng thái: {$field->status})");
                        }

                        // Lấy giá
                        $dayOfWeek = Carbon::parse($booking['date'])->dayOfWeek;
                        $dayType = in_array($dayOfWeek, [0, 6]) ? 'weekend' : 'weekday';

                        $price = \App\Models\TournamentSessionPrice::where('field_cluster_id', $tournament->field_cluster_id)
                            ->where('field_type', $tournament->field_type)
                            ->where('day_type', $dayType)
                            ->where('session_type', $booking['session_type'])
                            ->first();

                        if (!$price) {
                            $clusterName = $field->fieldCluster->name ?? 'Cụm sân';
                            $sessionTypeVN = match($booking['session_type']) {
                                'morning' => 'Sáng (6h-12h)',
                                'afternoon' => 'Chiều (12h-18h)',
                                'evening' => 'Tối (18h-23h)',
                                'full_day' => 'Cả ngày (6h-23h)',
                                default => $booking['session_type']
                            };
                            $dayTypeVN = $dayType === 'weekday' ? 'Ngày thường' : 'Cuối tuần';

                            throw new \Exception(
                                "Cụm sân '{$clusterName}' chưa cấu hình giá theo buổi cho giải đấu. " .
                                "Loại sân: {$tournament->field_type}, Ngày: {$dayTypeVN}, Buổi: {$sessionTypeVN}. " .
                                "Vui lòng liên hệ chủ sân để cấu hình giá hoặc chọn cụm sân khác."
                            );
                        }

                        // Tính giờ từ session
                        [$startTime, $endTime] = $this->getSessionTimeRange($booking['session_type']);

                        // Kiểm tra trùng lịch
                        $conflict = \App\Models\Book::where('field_id', $booking['field_id'])
                            ->where('day_book', $booking['date'])
                            ->where(function ($q) use ($startTime, $endTime) {
                                $q->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($query) use ($startTime, $endTime) {
                                      $query->where('start_time', '<=', $startTime)
                                            ->where('end_time', '>=', $endTime);
                                  });
                            })
                            ->exists();

                        if ($conflict) {
                            throw new \Exception("Sân '{$field->name}' ngày {$booking['date']} buổi {$booking['session_type']} đã được đặt");
                        }

                        // Tạo booking
                        $book = \App\Models\Book::create([
                            'user_id' => $userId,
                            'tournament_id' => $tournament->id,
                            'booking_type' => 'tournament',
                            'field_id' => $booking['field_id'],
                            'day_book' => $booking['date'],
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'total_price' => $price->price_per_session,
                        ]);

                        $totalPrice += $price->price_per_session;
                        $bookingsCreated[] = $book;
                    }
                }

                DB::commit();

                // ✅ Tạo response tùy theo có book sân hay không
                $response = [
                    'tournament' => $tournament->load([
                        'organizer',
                        'fieldCluster.fields' => function($query) {
                            $query->orderBy('name');
                        }
                    ]),
                ];

                if (!empty($bookingsCreated)) {
                    $response['bookings'] = $bookingsCreated;
                    $response['total_bookings'] = count($bookingsCreated);
                    $response['total_price'] = $totalPrice;
                }

                // ✅ Thêm payment info
                $response['payment_info'] = [
                    'method' => $tournament->payment_method,
                    'status' => $tournament->payment_status,
                    'total_amount' => $totalPrice,
                ];

                // Nếu chọn chuyển khoản, thêm QR code URL
                if ($tournament->payment_method === 'transfer' && $fieldCluster->owner->qr_code_image) {
                    $response['payment_info']['qr_code_url'] = asset('storage/' . $fieldCluster->owner->qr_code_image);
                    $response['payment_info']['instruction'] = "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: " . number_format($totalPrice) . " VNĐ và ghi nội dung: BOOK-T{$tournament->id}";
                    $response['payment_info']['transfer_note'] = "BOOK-T{$tournament->id}";
                } elseif ($tournament->payment_method === 'cash') {
                    $response['payment_info']['instruction'] = "Vui lòng thanh toán tiền mặt khi đến sân. Số tiền: " . number_format($totalPrice) . " VNĐ";
                }

                $message = empty($bookingsCreated)
                    ? 'Tạo giải đấu thành công'
                    : 'Tạo giải đấu và đặt ' . count($bookingsCreated) . ' sân thành công';

                return ApiResponse::success($message, $response, 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // Xem chi tiết giải
    public function show(string $id)
    {
        try {
            $tournament = Tournament::with([
                'organizer',
                'fieldCluster',
                'schedules',
                'registrations.team.captain',
                'groups.standings.team',
                'rounds.matches'
            ])->findOrFail($id);

            return ApiResponse::success('Chi tiết giải đấu', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy giải đấu', null, 404);
        }
    }

    // PUBLIC: Danh sách giải đấu công khai (không cần đăng nhập)
    public function publicIndex(Request $request)
    {
        try {
            $query = Tournament::with(['organizer', 'fieldCluster'])
                ->whereNotIn('status', ['draft']); // Chỉ hiển thị giải đã publish

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('format')) {
                $query->where('format', $request->format);
            }

            if ($request->has('field_type')) {
                $query->where('field_type', $request->field_type);
            }

            $tournaments = $query->orderBy('created_at', 'desc')->paginate(10);

            // Thêm thông tin số đội đã đăng ký
            $tournaments->getCollection()->transform(function ($tournament) {
                $tournament->approved_teams_count = $tournament->approvedTeamsCount();
                $tournament->is_full = $tournament->isFull();
                $tournament->available_slots = $tournament->availableSlots();
                return $tournament;
            });

            return ApiResponse::success('Danh sách giải đấu công khai', $tournaments);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // PUBLIC: Chi tiết giải đấu công khai (không cần đăng nhập)
    public function publicShow(string $id)
    {
        try {
            $tournament = Tournament::with([
                'organizer',
                'fieldCluster',
                'schedules',
                'registrations' => function ($query) {
                    $query->where('registration_status', 'approved'); // Chỉ hiển thị đăng ký đã approve
                },
                'registrations.team.captain',
                'groups.standings.team',
                'rounds.matches'
            ])->whereNotIn('status', ['draft'])
              ->findOrFail($id);

            // Thêm thông tin số đội đã đăng ký
            $tournament->approved_teams_count = $tournament->approvedTeamsCount();
            $tournament->is_full = $tournament->isFull();
            $tournament->available_slots = $tournament->availableSlots();

            return ApiResponse::success('Chi tiết giải đấu', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy giải đấu', null, 404);
        }
    }

    // Cập nhật giải
    public function update(Request $request, string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền cập nhật', null, 403);
            }

            // Chỉ cho phép update khi status là draft hoặc open
            if (!in_array($tournament->status, ['draft', 'open'])) {
                return ApiResponse::error('Không thể cập nhật giải đấu đang diễn ra hoặc đã kết thúc', null, 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'format' => 'sometimes|in:round_robin,knockout,group_knockout',
                'field_type' => 'sometimes|in:5v5,7v7,11v11',
                'max_teams' => 'sometimes|integer|min:2',
                'min_teams' => 'sometimes|integer|min:2',
                'registration_start' => 'sometimes|date',
                'registration_end' => 'sometimes|date|after:registration_start',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'field_cluster_id' => 'sometimes|exists:field_clusters,id',
            ]);

            // Nếu có thay đổi field_type hoặc field_cluster_id thì kiểm tra tương thích
            $fieldType = $validated['field_type'] ?? $tournament->field_type;
            $fieldClusterId = $validated['field_cluster_id'] ?? $tournament->field_cluster_id;
            if (isset($validated['field_type']) || isset($validated['field_cluster_id'])) {
                $fieldCompatibility = $this->checkFieldClusterCompatibility($fieldClusterId, $fieldType);
                if (!$fieldCompatibility['compatible']) {
                    return ApiResponse::error($fieldCompatibility['message'], null, 400);
                }
            }

            $tournament->update($validated);

            return ApiResponse::success('Cập nhật thành công', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // Xóa giải
    public function destroy(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền xóa', null, 403);
            }

            if (!in_array($tournament->status, ['draft', 'cancelled'])) {
                return ApiResponse::error('Chỉ xóa được giải ở trạng thái draft hoặc cancelled', null, 400);
            }

            $tournament->delete();

            return ApiResponse::success('Xóa giải thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Kiểm tra cụm sân có hỗ trợ loại sân yêu cầu không.
     * Quy tắc ghép sân: 1 sân 7 = 2 sân 5 | 1 sân 11 = 2 sân 7 = 4 sân 5
     */
    private function checkFieldClusterCompatibility(int $fieldClusterId, string $fieldType): array
    {
        $fields = \App\Models\Field::where('field_cluster_id', $fieldClusterId)
            ->where('status', 'available')
            ->get()
            ->groupBy('field_type');

        $count5v5 = $fields->get('5v5', collect())->count();
        $count7v7 = $fields->get('7v7', collect())->count();
        $count11v11 = $fields->get('11v11', collect())->count();

        if ($fieldType === '5v5') {
            if ($count5v5 >= 1) {
                return ['compatible' => true, 'message' => 'OK'];
            }
            return [
                'compatible' => false,
                'message' => 'Cụm sân không có sân 5v5 nào available.',
            ];
        }

        if ($fieldType === '7v7') {
            if ($count7v7 >= 1) {
                return ['compatible' => true, 'message' => 'OK'];
            }
            // Ghép 2 sân 5v5 → 1 sân 7v7
            if ($count5v5 >= 2) {
                return ['compatible' => true, 'message' => 'OK (ghép từ sân 5v5)'];
            }
            return [
                'compatible' => false,
                'message' => 'Cụm sân không có sân 7v7 và không đủ sân 5v5 để ghép (cần ít nhất 2 sân 5v5).',
            ];
        }

        if ($fieldType === '11v11') {
            if ($count11v11 >= 1) {
                return ['compatible' => true, 'message' => 'OK'];
            }
            // Ghép 2 sân 7v7 → 1 sân 11v11
            if ($count7v7 >= 2) {
                return ['compatible' => true, 'message' => 'OK (ghép từ sân 7v7)'];
            }
            // Ghép 4 sân 5v5 → 1 sân 11v11
            if ($count5v5 >= 4) {
                return ['compatible' => true, 'message' => 'OK (ghép từ sân 5v5)'];
            }
            // Ghép kết hợp: 1 sân 7v7 + 2 sân 5v5 → 1 sân 11v11
            if ($count7v7 >= 1 && $count5v5 >= 2) {
                return ['compatible' => true, 'message' => 'OK (ghép kết hợp 7v7 + 5v5)'];
            }
            return [
                'compatible' => false,
                'message' => 'Cụm sân không đủ để tổ chức giải 11v11. Cần: 1 sân 11v11, hoặc 2 sân 7v7, hoặc 4 sân 5v5.',
            ];
        }

        return ['compatible' => false, 'message' => 'Loại sân không hợp lệ.'];
    }

    // Publish giải (mở đăng ký)
    public function publish(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền publish', null, 403);
            }

            if ($tournament->status !== 'draft') {
                return ApiResponse::error('Chỉ publish được giải ở trạng thái draft', null, 400);
            }

            $tournament->update(['status' => 'open']);

            return ApiResponse::success('Mở đăng ký thành công', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // GENERATE SCHEDULE — Tự động sinh lịch thi đấu (1 nút bấm)
    // POST /tournaments/{id}/generate-schedule
    // Body: {
    //   "start_date": "2026-04-10",
    //   "time_slots": ["08:00","10:00","14:00","16:00"],  // tuỳ chọn
    //   "field_ids": [1,2],                               // tuỳ chọn
    //   "num_groups": 2,                                  // chỉ dùng cho group_knockout
    //   "days_between_rounds": 3                          // tuỳ chọn
    // }
    // ================================================================
    public function generateSchedule(Request $request, string $id)
    {
        try {
            $tournament = Tournament::with([
                'registrations' => function ($q) {
                    $q->where('registration_status', 'approved')->with('team');
                }
            ])->findOrFail($id);

            $user = Auth::user();
            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền tạo lịch thi đấu', null, 403);
            }

            if (!in_array($tournament->status, ['open', 'in_progress'])) {
                return ApiResponse::error('Giải đấu phải ở trạng thái open hoặc in_progress để tạo lịch', null, 400);
            }

            if ($tournament->rounds()->count() > 0) {
                return ApiResponse::error(
                    'Lịch thi đấu đã được tạo. Gọi DELETE /tournaments/' . $id . '/schedule để xóa lịch cũ trước.',
                    400
                );
            }

            $validated = $request->validate([
                'start_date'         => 'required|date',
                'time_slots'         => 'nullable|array|min:1',
                'time_slots.*'       => 'date_format:H:i',
                'field_ids'          => 'nullable|array',
                'field_ids.*'        => 'exists:fields,id',
                'num_groups'         => 'nullable|integer|min:2|max:8',
                'days_between_rounds'=> 'nullable|integer|min:1|max:30',
            ]);

            // Lấy danh sách đội đã approved
            $teams = $tournament->registrations->pluck('team')->filter()->values();

            if ($teams->count() < 2) {
                return ApiResponse::error('Cần ít nhất 2 đội đã được duyệt để tạo lịch', null, 400);
            }

            // Lấy bookings của giải
            $bookings = \App\Models\Book::where('tournament_id', $id)
                ->where('booking_type', 'tournament')
                ->with('field')
                ->get();

            if ($bookings->isEmpty()) {
                return ApiResponse::error('Chưa book sân cho giải đấu. Vui lòng book sân trước khi tạo lịch.', null, 400);
            }

            // Lấy field_ids từ bookings (nếu không truyền vào)
            $fieldIds = $validated['field_ids'] ?? $bookings->pluck('field_id')->unique()->values()->toArray();

            if (empty($fieldIds)) {
                return ApiResponse::error('Không có sân nào để tạo lịch', null, 400);
            }

            // Tự động tạo time_slots từ bookings (nếu không truyền vào)
            if (!isset($validated['time_slots'])) {
                $timeSlots = $this->generateTimeSlotsFromBookings($bookings, $tournament->field_type);
            } else {
                $timeSlots = $validated['time_slots'];
            }

            $startDate        = Carbon::parse($validated['start_date']);
            $daysBetweenRounds = (int) ($validated['days_between_rounds'] ?? 3);
            $numGroups        = (int) ($validated['num_groups'] ?? 2);

            DB::beginTransaction();
            try {
                $result = match ($tournament->format) {
                    'knockout'       => $this->generateKnockout($tournament, $teams, $startDate, $timeSlots, $fieldIds, $daysBetweenRounds),
                    'round_robin'    => $this->generateRoundRobin($tournament, $teams, $startDate, $timeSlots, $fieldIds, $daysBetweenRounds),
                    'group_knockout' => $this->generateGroupKnockout($tournament, $teams, $startDate, $timeSlots, $fieldIds, $daysBetweenRounds, $numGroups),
                };

                $tournament->update(['status' => 'in_progress']);
                DB::commit();

                return ApiResponse::success('Tạo lịch thi đấu thành công', $result);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // ================================================================
    // GET BRACKET — Lấy dữ liệu lịch/bracket để FE render sơ đồ
    // GET /tournaments/{id}/bracket
    // ================================================================
    public function getBracket(string $id)
    {
        try {
            $tournament = Tournament::with([
                'registrations' => function ($q) {
                    $q->where('registration_status', 'approved')->with('team');
                }
            ])->findOrFail($id);

            // ========================================
            // LOGIC KIỂM TRA QUYỀN XEM LỊCH
            // ========================================
            $user = Auth::user();

            // Nếu lịch chưa được publish
            if (!$tournament->schedule_published) {
                // Bắt buộc phải đăng nhập
                if (!$user) {
                    return ApiResponse::error('Lịch thi đấu chưa được công bố', null, 403);
                }

                // Chỉ người tạo giải mới xem được
                if ($tournament->organizer_id !== $user->id) {
                    return ApiResponse::error('Lịch thi đấu chưa được công bố. Chỉ người tổ chức mới có thể xem trước.', null, 403);
                }
            }
            // Nếu đã publish hoặc là người tạo giải → cho phép xem
            // ========================================

            $approvedTeams = $tournament->registrations->pluck('team')->filter()->values()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

            $tournamentInfo = $tournament->only(['id', 'name', 'format', 'status', 'start_date', 'end_date', 'field_type', 'schedule_published']);

            if ($tournament->format === 'knockout') {
                $rounds = \App\Models\TournamentRound::with([
                    'matches' => function ($q) {
                        $q->orderBy('match_number')
                          ->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field',
                                  'homeFromMatch', 'awayFromMatch']);
                    }
                ])
                ->where('tournament_id', $id)
                ->orderBy('round_order')
                ->get();

                return ApiResponse::success('Bracket giải đấu', [
                    'format'      => 'knockout',
                    'tournament'  => $tournamentInfo,
                    'teams'       => $approvedTeams,
                    'rounds'      => $rounds,
                ]);
            }

            if ($tournament->format === 'round_robin') {
                $groups = \App\Models\TournamentGroup::with([
                    'standings' => function ($q) {
                        $q->orderBy('ranking')->with('team');
                    },
                    'matches' => function ($q) {
                        $q->orderBy('match_number')->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field']);
                    }
                ])
                ->where('tournament_id', $id)
                ->orderBy('group_name')
                ->get();

                return ApiResponse::success('Lịch thi đấu vòng tròn', [
                    'format'     => 'round_robin',
                    'tournament' => $tournamentInfo,
                    'teams'      => $approvedTeams,
                    'groups'     => $groups,
                ]);
            }

            if ($tournament->format === 'group_knockout') {
                $groups = \App\Models\TournamentGroup::with([
                    'standings' => function ($q) {
                        $q->orderBy('ranking')->with('team');
                    },
                    'matches' => function ($q) {
                        $q->orderBy('match_number')->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field']);
                    }
                ])
                ->where('tournament_id', $id)
                ->orderBy('group_name')
                ->get();

                $knockoutRounds = \App\Models\TournamentRound::with([
                    'matches' => function ($q) {
                        $q->orderBy('match_number')
                          ->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field',
                                  'homeFromMatch', 'awayFromMatch']);
                    }
                ])
                ->where('tournament_id', $id)
                ->where('round_type', '!=', 'group_stage')
                ->orderBy('round_order')
                ->get();

                return ApiResponse::success('Bracket giải đấu vòng bảng + loại trực tiếp', [
                    'format'          => 'group_knockout',
                    'tournament'      => $tournamentInfo,
                    'teams'           => $approvedTeams,
                    'groups'          => $groups,
                    'knockout_rounds' => $knockoutRounds,
                ]);
            }

            return ApiResponse::error('Format giải đấu không hợp lệ', null, 400);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // RESET SCHEDULE — Xóa toàn bộ lịch để tạo lại
    // DELETE /tournaments/{id}/schedule
    // ================================================================
    public function resetSchedule(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền xóa lịch thi đấu', null, 403);
            }

            $completedCount = \App\Models\TournamentMatch::where('tournament_id', $id)
                ->where('status', 'completed')
                ->count();

            if ($completedCount > 0) {
                return ApiResponse::error('Không thể xóa lịch khi đã có ' . $completedCount . ' trận hoàn thành', null, 400);
            }

            DB::beginTransaction();
            try {
                \App\Models\TournamentMatch::where('tournament_id', $id)->delete();

                $groupIds = \App\Models\TournamentGroup::where('tournament_id', $id)->pluck('id');
                \App\Models\TournamentGroupStanding::whereIn('tournament_group_id', $groupIds)->delete();
                \App\Models\TournamentGroup::where('tournament_id', $id)->delete();

                \App\Models\TournamentRound::where('tournament_id', $id)->delete();

                $tournament->update(['status' => 'open', 'schedule_published' => false]);
                DB::commit();

                return ApiResponse::success('Đã xóa toàn bộ lịch thi đấu. Giải đấu trở về trạng thái open.', null);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // PUBLISH SCHEDULE — Công bố lịch thi đấu cho tất cả user
    // POST /tournaments/{id}/publish-schedule
    // ================================================================
    public function publishSchedule(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $user = Auth::user();

            // Chỉ người tạo giải mới được publish
            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền công bố lịch thi đấu', null, 403);
            }

            // Kiểm tra đã có lịch thi đấu chưa
            $hasSchedule = \App\Models\TournamentMatch::where('tournament_id', $id)->exists();
            if (!$hasSchedule) {
                return ApiResponse::error('Chưa có lịch thi đấu để công bố. Vui lòng tạo lịch trước.', null, 400);
            }

            // Kiểm tra đã publish chưa
            if ($tournament->schedule_published) {
                return ApiResponse::error('Lịch thi đấu đã được công bố trước đó', null, 400);
            }

            $tournament->update(['schedule_published' => true]);

            return ApiResponse::success('Đã công bố lịch thi đấu thành công. Tất cả user giờ có thể xem lịch.', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // UNPUBLISH SCHEDULE — Ẩn lịch thi đấu (nếu cần sửa)
    // POST /tournaments/{id}/unpublish-schedule
    // ================================================================
    public function unpublishSchedule(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền ẩn lịch thi đấu', null, 403);
            }

            if (!$tournament->schedule_published) {
                return ApiResponse::error('Lịch thi đấu chưa được công bố', null, 400);
            }

            $tournament->update(['schedule_published' => false]);

            return ApiResponse::success('Đã ẩn lịch thi đấu. Chỉ bạn mới có thể xem.', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // PRIVATE — Sinh lịch Loại Trực Tiếp (Knockout)
    // Yêu cầu số đội là lũy thừa của 2: 2, 4, 8, 16...
    // ================================================================
    private function generateKnockout($tournament, $teams, Carbon $startDate, array $timeSlots, array $fieldIds, int $daysBetweenRounds): array
    {
        $teamList = $teams->shuffle()->values();
        $n = $teamList->count();

        // Bắt buộc phải là lũy thừa của 2 để bracket cân đối
        if ($n < 2 || ($n & ($n - 1)) !== 0) {
            throw new \Exception(
                "Số đội ($n) phải là lũy thừa của 2 (2, 4, 8, 16...) cho thể thức loại trực tiếp. " .
                "Hiện có $n đội đã duyệt."
            );
        }

        $numRounds   = (int) log($n, 2);
        $matchNumber = 1;
        $roundData   = []; // [$r => ['round' => ..., 'matches' => [...]]]

        // Tên vòng đấu tính từ cuối (vòng cuối = chung kết)
        $typesFromEnd = ['final', 'semi_final', 'quarter_final', 'round_of_16', 'round_of_32'];

        for ($r = 0; $r < $numRounds; $r++) {
            $matchesInRound = $n / (int) pow(2, $r + 1);
            $indexFromEnd   = $numRounds - 1 - $r;
            $roundType      = $typesFromEnd[min($indexFromEnd, count($typesFromEnd) - 1)];
            $roundName      = $this->getRoundNameForType($roundType);
            $roundStartDate = $startDate->copy()->addDays($r * $daysBetweenRounds);

            $round = \App\Models\TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => $roundName,
                'round_type'    => $roundType,
                'round_order'   => $r + 1,
                'start_date'    => $roundStartDate,
                'end_date'      => $roundStartDate->copy()->addDays(max(0, $daysBetweenRounds - 1)),
                'is_knockout'   => true,
            ]);

            $roundData[$r] = ['round' => $round, 'matches' => []];

            $slotIdx         = 0;
            $fieldIdx        = 0;
            $currentMatchDate = $roundStartDate->copy();

            for ($m = 0; $m < $matchesInRound; $m++) {
                $matchData = [
                    'tournament_id'      => $tournament->id,
                    'tournament_round_id'=> $round->id,
                    'field_id'           => $fieldIds[$fieldIdx % count($fieldIds)],
                    'match_date'         => $currentMatchDate->toDateString(),
                    'match_time'         => $timeSlots[$slotIdx % count($timeSlots)],
                    'match_number'       => $matchNumber++,
                    'status'             => 'scheduled',
                ];

                if ($r === 0) {
                    // Vòng đầu: gán đội thực vào
                    $matchData['home_team_id'] = $teamList[$m * 2]->id;
                    $matchData['away_team_id'] = $teamList[$m * 2 + 1]->id;
                } else {
                    // Vòng tiếp theo: TBD, liên kết tới trận vòng trước
                    $prevMatches = $roundData[$r - 1]['matches'];
                    $matchData['home_team_id']      = null;
                    $matchData['away_team_id']      = null;
                    $matchData['home_from_match_id']= $prevMatches[$m * 2]->id;
                    $matchData['away_from_match_id']= $prevMatches[$m * 2 + 1]->id;
                    $matchData['home_from_position']= 'Thắng trận ' . $prevMatches[$m * 2]->match_number;
                    $matchData['away_from_position']= 'Thắng trận ' . $prevMatches[$m * 2 + 1]->match_number;
                }

                $match = \App\Models\TournamentMatch::create($matchData);
                $roundData[$r]['matches'][] = $match;

                $slotIdx++;
                if ($slotIdx >= count($timeSlots)) {
                    $slotIdx = 0;
                    $currentMatchDate->addDay();
                }
                $fieldIdx++;
            }
        }

        // Tạo trận tranh hạng 3 (chạy song song với chung kết, cùng ngày)
        if ($numRounds >= 2) {
            $finalDate        = $startDate->copy()->addDays(($numRounds - 1) * $daysBetweenRounds);
            $semiFinalMatches = $roundData[$numRounds - 2]['matches']; // Các trận bán kết

            $thirdPlaceRound = \App\Models\TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => 'Tranh hạng 3',
                'round_type'    => 'third_place',
                'round_order'   => $numRounds, // Cùng thứ tự với chung kết
                'start_date'    => $finalDate,
                'end_date'      => $finalDate,
                'is_knockout'   => true,
            ]);

            \App\Models\TournamentMatch::create([
                'tournament_id'       => $tournament->id,
                'tournament_round_id' => $thirdPlaceRound->id,
                'home_team_id'        => null,
                'away_team_id'        => null,
                'home_from_position'  => isset($semiFinalMatches[0])
                    ? 'Thua trận ' . $semiFinalMatches[0]->match_number
                    : 'Thua bán kết 1',
                'away_from_position'  => isset($semiFinalMatches[1])
                    ? 'Thua trận ' . $semiFinalMatches[1]->match_number
                    : 'Thua bán kết 2',
                'field_id'            => $fieldIds[0],
                'match_date'          => $finalDate->toDateString(),
                'match_time'          => $timeSlots[count($timeSlots) > 1 ? 1 : 0],
                'match_number'        => $matchNumber,
                'status'              => 'scheduled',
            ]);
        }

        $totalMatches = array_sum(array_map(fn ($rd) => count($rd['matches']), $roundData));
        $totalMatches += ($numRounds >= 2 ? 1 : 0); // +1 trận hạng 3

        return [
            'format'         => 'knockout',
            'teams'          => $n,
            'rounds_created' => $numRounds + ($numRounds >= 2 ? 1 : 0),
            'matches_created'=> $totalMatches,
            'note'           => 'Trận Tranh hạng 3 cần gán đội thủ công qua API assign-team sau khi có kết quả bán kết.',
        ];
    }

    // ================================================================
    // PRIVATE — Sinh lịch Vòng Tròn (Round Robin)
    // Mỗi đội đá với mọi đội còn lại đúng 1 lần
    // ================================================================
    private function generateRoundRobin($tournament, $teams, Carbon $startDate, array $timeSlots, array $fieldIds, int $daysBetweenRounds): array
    {
        $teamList = $teams->shuffle()->values();
        $teamIds  = $teamList->pluck('id')->toArray();

        // Tạo bảng group duy nhất để theo dõi standings
        $group = \App\Models\TournamentGroup::create([
            'tournament_id' => $tournament->id,
            'group_name'    => 'Vòng bảng',
        ]);

        foreach ($teamList as $team) {
            \App\Models\TournamentGroupStanding::create([
                'tournament_group_id' => $group->id,
                'team_id'             => $team->id,
            ]);
        }

        $pairings = $this->generateRoundRobinPairings($teamIds);
        $totalMatchDays = count($pairings);

        $round = \App\Models\TournamentRound::create([
            'tournament_id' => $tournament->id,
            'round_name'    => 'Vòng bảng',
            'round_type'    => 'group_stage',
            'round_order'   => 1,
            'start_date'    => $startDate,
            'end_date'      => $startDate->copy()->addDays($totalMatchDays - 1),
            'is_knockout'   => false,
        ]);

        $matchNumber  = 1;
        $slotIdx      = 0;
        $fieldIdx     = 0;
        $currentDate  = $startDate->copy();

        foreach ($pairings as $roundPairs) {
            foreach ($roundPairs as [$homeId, $awayId]) {
                if (is_null($homeId) || is_null($awayId)) continue; // bỏ qua bye

                \App\Models\TournamentMatch::create([
                    'tournament_id'       => $tournament->id,
                    'tournament_round_id' => $round->id,
                    'tournament_group_id' => $group->id,
                    'home_team_id'        => $homeId,
                    'away_team_id'        => $awayId,
                    'field_id'            => $fieldIds[$fieldIdx % count($fieldIds)],
                    'match_date'          => $currentDate->toDateString(),
                    'match_time'          => $timeSlots[$slotIdx % count($timeSlots)],
                    'match_number'        => $matchNumber++,
                    'status'              => 'scheduled',
                ]);

                $slotIdx++;
                if ($slotIdx >= count($timeSlots)) {
                    $slotIdx = 0;
                    $currentDate->addDay();
                }
                $fieldIdx++;
            }
        }

        return [
            'format'         => 'round_robin',
            'teams'          => count($teamIds),
            'matches_created'=> $matchNumber - 1,
        ];
    }

    // ================================================================
    // PRIVATE — Sinh lịch Vòng Bảng + Loại Trực Tiếp (Group Knockout)
    // ================================================================
    private function generateGroupKnockout($tournament, $teams, Carbon $startDate, array $timeSlots, array $fieldIds, int $daysBetweenRounds, int $numGroups): array
    {
        $teamList    = $teams->shuffle()->values();
        $n           = $teamList->count();
        $groupLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        if ($numGroups > count($groupLabels)) {
            throw new \Exception('Số bảng tối đa là ' . count($groupLabels));
        }

        // Chia đội vào các bảng theo kiểu round-robin distribution
        $groups     = [];
        $groupTeams = array_fill(0, $numGroups, []);

        foreach ($teamList as $i => $team) {
            $groupTeams[$i % $numGroups][] = $team;
        }

        // Tạo vòng group stage
        $groupStageRound = \App\Models\TournamentRound::create([
            'tournament_id' => $tournament->id,
            'round_name'    => 'Vòng bảng',
            'round_type'    => 'group_stage',
            'round_order'   => 1,
            'start_date'    => $startDate,
            'end_date'      => null,
            'is_knockout'   => false,
        ]);

        $matchNumber = 1;
        $slotIdx     = 0;
        $fieldIdx    = 0;
        $currentDate = $startDate->copy();

        for ($g = 0; $g < $numGroups; $g++) {
            $group = \App\Models\TournamentGroup::create([
                'tournament_id' => $tournament->id,
                'group_name'    => 'Bảng ' . $groupLabels[$g],
            ]);
            $groups[] = $group;

            // Khởi tạo standings
            foreach ($groupTeams[$g] as $team) {
                \App\Models\TournamentGroupStanding::create([
                    'tournament_group_id' => $group->id,
                    'team_id'             => $team->id,
                ]);
            }

            // Sinh lịch vòng tròn nội bộ bảng
            $teamIdsInGroup = array_map(fn ($t) => $t->id, $groupTeams[$g]);
            $pairings       = $this->generateRoundRobinPairings($teamIdsInGroup);

            foreach ($pairings as $roundPairs) {
                foreach ($roundPairs as [$homeId, $awayId]) {
                    if (is_null($homeId) || is_null($awayId)) continue;

                    \App\Models\TournamentMatch::create([
                        'tournament_id'       => $tournament->id,
                        'tournament_round_id' => $groupStageRound->id,
                        'tournament_group_id' => $group->id,
                        'home_team_id'        => $homeId,
                        'away_team_id'        => $awayId,
                        'field_id'            => $fieldIds[$fieldIdx % count($fieldIds)],
                        'match_date'          => $currentDate->toDateString(),
                        'match_time'          => $timeSlots[$slotIdx % count($timeSlots)],
                        'match_number'        => $matchNumber++,
                        'status'              => 'scheduled',
                    ]);

                    $slotIdx++;
                    if ($slotIdx >= count($timeSlots)) {
                        $slotIdx = 0;
                        $currentDate->addDay();
                    }
                    $fieldIdx++;
                }
            }
        }

        // Tính ngày bắt đầu vòng knockout (sau khi group stage kết thúc)
        $knockoutStartDate = $currentDate->copy()->addDays($daysBetweenRounds);

        // Số đội vào vòng knockout = nhất mỗi bảng (mặc định 1 đội/bảng)
        $teamsAdvancing  = $numGroups; // top 1 mỗi bảng
        $knockoutSize    = 1;
        while ($knockoutSize < $teamsAdvancing) $knockoutSize *= 2;
        $knockoutRounds  = (int) log($knockoutSize, 2);

        $typesFromEnd  = ['final', 'semi_final', 'quarter_final', 'round_of_16', 'round_of_32'];
        $knockoutRoundData = [];

        for ($r = 0; $r < $knockoutRounds; $r++) {
            $matchesInRound = $knockoutSize / (int) pow(2, $r + 1);
            $indexFromEnd   = $knockoutRounds - 1 - $r;
            $roundType      = $typesFromEnd[min($indexFromEnd, count($typesFromEnd) - 1)];
            $roundName      = $this->getRoundNameForType($roundType);
            $roundStartDate = $knockoutStartDate->copy()->addDays($r * $daysBetweenRounds);

            $round = \App\Models\TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => $roundName,
                'round_type'    => $roundType,
                'round_order'   => $r + 2, // +2 vì group_stage là order 1
                'start_date'    => $roundStartDate,
                'end_date'      => $roundStartDate->copy()->addDays(max(0, $daysBetweenRounds - 1)),
                'is_knockout'   => true,
            ]);

            $knockoutRoundData[$r] = ['round' => $round, 'matches' => []];

            $slotIdx  = 0;
            $fieldIdx = 0;

            for ($m = 0; $m < $matchesInRound; $m++) {
                $matchData = [
                    'tournament_id'       => $tournament->id,
                    'tournament_round_id' => $round->id,
                    'home_team_id'        => null,
                    'away_team_id'        => null,
                    'field_id'            => $fieldIds[$fieldIdx % count($fieldIds)],
                    'match_date'          => $roundStartDate->toDateString(),
                    'match_time'          => $timeSlots[$slotIdx % count($timeSlots)],
                    'match_number'        => $matchNumber++,
                    'status'              => 'scheduled',
                ];

                if ($r === 0) {
                    // Vòng knockout đầu tiên: đội đến từ kết quả bảng
                    $homeGroupLabel = $groupLabels[$m * 2 % $numGroups] ?? '?';
                    $awayGroupLabel = $groupLabels[($m * 2 + 1) % $numGroups] ?? '?';
                    $matchData['home_from_position'] = 'Nhất bảng ' . $homeGroupLabel;
                    $matchData['away_from_position'] = 'Nhì bảng ' . $awayGroupLabel;
                } else {
                    $prevMatches = $knockoutRoundData[$r - 1]['matches'];
                    $matchData['home_from_match_id'] = $prevMatches[$m * 2]->id ?? null;
                    $matchData['away_from_match_id'] = $prevMatches[$m * 2 + 1]->id ?? null;
                    $matchData['home_from_position'] = isset($prevMatches[$m * 2])
                        ? 'Thắng trận ' . $prevMatches[$m * 2]->match_number : null;
                    $matchData['away_from_position'] = isset($prevMatches[$m * 2 + 1])
                        ? 'Thắng trận ' . $prevMatches[$m * 2 + 1]->match_number : null;
                }

                $match = \App\Models\TournamentMatch::create($matchData);
                $knockoutRoundData[$r]['matches'][] = $match;

                $slotIdx++;
                $fieldIdx++;
            }
        }

        $groupMatchCount   = $matchNumber - 1;
        $knockoutMatchCount = array_sum(array_map(fn ($rd) => count($rd['matches']), $knockoutRoundData));

        return [
            'format'               => 'group_knockout',
            'groups_created'       => $numGroups,
            'group_matches'        => $groupMatchCount,
            'knockout_rounds'      => $knockoutRounds,
            'knockout_matches'     => $knockoutMatchCount,
            'note'                 => 'Sau khi vòng bảng kết thúc, dùng API assign-team để gán đội nhất/nhì bảng vào vòng knockout.',
        ];
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * Sinh danh sách cặp đấu vòng tròn (Berger table / circle method)
     * Trả về mảng các vòng, mỗi vòng là mảng cặp [homeId, awayId]
     */
    private function generateRoundRobinPairings(array $teamIds): array
    {
        $n = count($teamIds);
        if ($n % 2 === 1) {
            $teamIds[] = null; // bye
            $n++;
        }

        $rounds   = [];
        $fixed    = $teamIds[0];
        $rotating = array_slice($teamIds, 1);
        $half     = $n / 2;

        for ($r = 0; $r < $n - 1; $r++) {
            $round   = [];
            $round[] = [$fixed, $rotating[$n - 2]]; // fixed đấu với cuối rotating

            for ($i = 0; $i < $half - 1; $i++) {
                $round[] = [$rotating[$i], $rotating[$n - 2 - $i - 1]];
            }

            $rounds[] = $round;
            array_push($rotating, array_shift($rotating)); // Rotate trái 1 bước
        }

        return $rounds;
    }

    private function getRoundNameForType(string $type): string
    {
        return match ($type) {
            'round_of_32' => 'Vòng 1/32',
            'round_of_16' => 'Vòng 1/16',
            'quarter_final'=> 'Tứ kết',
            'semi_final'  => 'Bán kết',
            'final'       => 'Chung kết',
            'third_place' => 'Tranh hạng 3',
            'group_stage' => 'Vòng bảng',
            default       => $type,
        };
    }

    // ================================================================
    // BOOK FIELDS — Thuê sân cho giải đấu
    // POST /tournaments/{id}/book-fields
    // ================================================================
    public function bookFields(Request $request, string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền book sân', null, 403);
            }

            $validated = $request->validate([
                'bookings' => 'required|array|min:1',
                'bookings.*.field_id' => 'required|exists:fields,id',
                'bookings.*.date' => 'required|date',
                'bookings.*.session_type' => 'required|in:morning,afternoon,evening,full_day',
            ]);

            DB::beginTransaction();
            try {
                $totalPrice = 0;
                $bookingsCreated = [];

                foreach ($validated['bookings'] as $booking) {
                    $field = \App\Models\Field::findOrFail($booking['field_id']);

                    // Kiểm tra field thuộc cụm sân của giải
                    if ($field->field_cluster_id !== $tournament->field_cluster_id) {
                        throw new \Exception("Sân {$field->id} không thuộc cụm sân của giải đấu");
                    }

                    // Kiểm tra sân available
                    if ($field->status !== 'available') {
                        throw new \Exception("Sân {$field->id} không khả dụng");
                    }

                    // Lấy giá
                    $dayOfWeek = Carbon::parse($booking['date'])->dayOfWeek;
                    $dayType = in_array($dayOfWeek, [0, 6]) ? 'weekend' : 'weekday';

                    $price = \App\Models\TournamentSessionPrice::where('field_cluster_id', $tournament->field_cluster_id)
                        ->where('field_type', $tournament->field_type)
                        ->where('day_type', $dayType)
                        ->where('session_type', $booking['session_type'])
                        ->first();

                    if (!$price) {
                        throw new \Exception("Chưa có giá cho {$tournament->field_type} - {$dayType} - {$booking['session_type']}");
                    }

                    // Tính giờ từ session
                    [$startTime, $endTime] = $this->getSessionTimeRange($booking['session_type']);

                    // Kiểm tra trùng lịch
                    $conflict = \App\Models\Book::where('field_id', $booking['field_id'])
                        ->where('day_book', $booking['date'])
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function ($query) use ($startTime, $endTime) {
                                  $query->where('start_time', '<=', $startTime)
                                        ->where('end_time', '>=', $endTime);
                              });
                        })
                        ->exists();

                    if ($conflict) {
                        throw new \Exception("Sân {$field->id} ngày {$booking['date']} buổi {$booking['session_type']} đã được đặt");
                    }

                    // Tạo booking
                    $book = \App\Models\Book::create([
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id,
                        'booking_type' => 'tournament',
                        'field_id' => $booking['field_id'],
                        'day_book' => $booking['date'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'total_price' => $price->price_per_session,
                    ]);

                    $totalPrice += $price->price_per_session;
                    $bookingsCreated[] = $book;
                }

                DB::commit();

                return ApiResponse::success('Book sân thành công', [
                    'bookings' => $bookingsCreated,
                    'total_bookings' => count($bookingsCreated),
                    'total_price' => $totalPrice,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // GET FIELD BOOKINGS — Xem sân đã book
    // GET /tournaments/{id}/field-bookings
    // ================================================================
    public function getFieldBookings(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $bookings = \App\Models\Book::with(['field.fieldCluster', 'user'])
                ->where('tournament_id', $id)
                ->where('booking_type', 'tournament')
                ->orderBy('day_book')
                ->orderBy('start_time')
                ->get();

            $totalPrice = $bookings->sum('total_price');

            return ApiResponse::success('Danh sách sân đã book', [
                'bookings' => $bookings,
                'total_bookings' => $bookings->count(),
                'total_price' => $totalPrice,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // DELETE FIELD BOOKING — Hủy booking sân
    // DELETE /tournaments/{id}/field-bookings/{booking_id}
    // ================================================================
    public function deleteFieldBooking(string $id, string $bookingId)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền hủy booking', null, 403);
            }

            $booking = \App\Models\Book::where('tournament_id', $id)
                ->where('id', $bookingId)
                ->firstOrFail();

            // Không cho hủy nếu đã generate schedule
            $hasMatches = \App\Models\TournamentMatch::where('tournament_id', $id)
                ->where('field_id', $booking->field_id)
                ->exists();

            if ($hasMatches) {
                return ApiResponse::error('Không thể hủy booking vì đã có lịch thi đấu trên sân này', null, 400);
            }

            $booking->delete();

            return ApiResponse::success('Hủy booking thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Helper: Tính giờ từ session type
    private function getSessionTimeRange(string $sessionType): array
    {
        return match ($sessionType) {
            'morning' => ['06:00', '12:00'],
            'afternoon' => ['12:00', '18:00'],
            'evening' => ['18:00', '23:00'],
            'full_day' => ['06:00', '23:00'],
        };
    }

    // Helper: Tạo time slots từ bookings
    private function generateTimeSlotsFromBookings($bookings, string $fieldType): array
    {
        // Thời lượng 1 trận (phút)
        $matchDuration = match ($fieldType) {
            '5v5' => 60,    // 60 phút
            '7v7' => 80,    // 80 phút
            '11v11' => 90,  // 90 phút
            default => 60,
        };

        $timeSlots = [];

        // Lấy các session type từ bookings
        $sessions = $bookings->groupBy(function ($booking) {
            return Carbon::parse($booking->start_time)->format('H:i');
        });

        foreach ($sessions as $startTime => $sessionBookings) {
            $booking = $sessionBookings->first();
            $start = Carbon::parse($booking->start_time);
            $end = Carbon::parse($booking->end_time);

            // Tạo các slot trong khoảng thời gian này
            $current = $start->copy();
            while ($current->copy()->addMinutes($matchDuration)->lte($end)) {
                $timeSlots[] = $current->format('H:i');
                $current->addMinutes($matchDuration + 15); // +15 phút nghỉ giữa các trận
            }
        }

        return array_unique($timeSlots);
    }

    // ================================================================
    // UPLOAD PAYMENT PROOF (BILL) - User upload ảnh bill chuyển khoản
    // ================================================================
    public function uploadPaymentProof(Request $request, string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);
            $currentUser = Auth::user();

            // Chỉ người tạo giải mới được upload bill
            if ($tournament->organizer_id !== $currentUser->id) {
                return ApiResponse::error('Bạn không có quyền upload bill cho giải này', null, 403);
            }

            // Chỉ giải chọn chuyển khoản mới cần upload bill
            if ($tournament->payment_method !== 'transfer') {
                return ApiResponse::error('Giải này thanh toán bằng tiền mặt, không cần upload bill', null, 400);
            }

            // Validate
            $validated = $request->validate([
                'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'], // max 5MB
            ]);

            // Xóa bill cũ nếu có
            if ($tournament->payment_proof) {
                $oldPath = public_path('storage/' . $tournament->payment_proof);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Upload bill mới
            $file = $request->file('payment_proof');
            $filename = 'tournament_' . $tournament->id . '_bill_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('payment_proofs', $filename, 'public');

            // Update tournament
            $tournament->payment_proof = $path;
            $tournament->save();

            return ApiResponse::success(
                'Upload bill thành công. Chờ chủ sân xác nhận thanh toán.',
                [
                    'tournament_id' => $tournament->id,
                    'payment_proof_url' => asset('storage/' . $path),
                    'payment_status' => $tournament->payment_status,
                ],
                200
            );

        } catch (\Exception $e) {
            return ApiResponse::error('Upload thất bại: ' . $e->getMessage(), null, 500);
        }
    }

    // ================================================================
    // CONFIRM PAYMENT - Owner xác nhận hoặc từ chối thanh toán
    // ================================================================
    public function confirmPayment(Request $request, string $id)
    {
        try {
            $tournament = Tournament::with('fieldCluster.owner')->findOrFail($id);
            $currentUser = Auth::user();

            // Kiểm tra quyền: Chỉ owner của cụm sân mới được confirm
            if (!$currentUser->ownerProfile) {
                return ApiResponse::error('Bạn không phải là chủ sân', null, 403);
            }

            $fieldCluster = $tournament->fieldCluster;
            if (!$fieldCluster || $fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                return ApiResponse::error('Bạn không phải chủ sân của giải đấu này', null, 403);
            }

            // Validate
            $validated = $request->validate([
                'action' => ['required', 'in:approve,reject'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            // Nếu là transfer, kiểm tra đã upload bill chưa
            if ($tournament->payment_method === 'transfer' && !$tournament->payment_proof && $validated['action'] === 'approve') {
                return ApiResponse::error('User chưa upload bill chuyển khoản. Không thể xác nhận thanh toán.', null, 400);
            }

            // Update payment status
            if ($validated['action'] === 'approve') {
                $tournament->payment_status = 'paid';
                $tournament->payment_date = now();
                $message = 'Xác nhận thanh toán thành công';
            } else {
                $tournament->payment_status = 'failed';
                $message = 'Đã từ chối thanh toán';
            }

            // Lưu note nếu có (có thể thêm field payment_note vào bảng tournaments)
            $tournament->save();

            return ApiResponse::success(
                $message,
                [
                    'tournament_id' => $tournament->id,
                    'payment_status' => $tournament->payment_status,
                    'payment_date' => $tournament->payment_date,
                    'note' => $validated['note'] ?? null,
                ],
                200
            );

        } catch (\Exception $e) {
            return ApiResponse::error('Xử lý thất bại: ' . $e->getMessage(), null, 500);
        }
    }
}
