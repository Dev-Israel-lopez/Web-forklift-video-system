<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use App\Services\EventService;
use App\Http\Resources\EventResource;


class EventController extends Controller
{

    public function __construct(private EventService $service) {}

    /**
     * POST /api/events
     * Headers:
     *   Authorization: Bearer <token>
     *   X-User-Id: <id>
     */
    public function store(Request $request)
    {
        // Autenticaciï¿½n rï¿½pida por Bearer token + X-User-Id
        $authHeader = $request->header('Authorization', '');
        $userIdHeader = $request->header('X-User-Id');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Falta Authorization Bearer'], 401);
        }

        $token = substr($authHeader, 7);
        $user = User::where('id', $userIdHeader)->where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Token o usuario invï¿½lido'], 401);
        }

        // Valida payload
        $data = $request->validate([
            'timestamp'     => ['required', 'string', 'max:32'],
            'event'         => ['required', 'string', 'max:64'],
            'zone_idx'      => ['required', 'integer', 'min:0'],
            'level'         => ['required', 'string', 'max:16'], // red|yellow|green
            'save'          => ['required', 'boolean'],
            'filename'      => ['nullable', 'string', 'max:255'],
            'forklift_name' => ['nullable', 'string', 'max:128'],
            'confidence'    => ['nullable', 'numeric'],
            'meta'          => ['nullable', 'array'],
        ]);

        $event = Event::create([
            'user_id'       => $user->id,
            'timestamp_str' => $data['timestamp'],
            'event'         => $data['event'],
            'zone_idx'      => $data['zone_idx'],
            'level'         => $data['level'],
            'save'          => $data['save'],
            'filename'      => $data['filename'] ?? null,
            'forklift_name' => $data['forklift_name'] ?? null,
            'confidence'    => $data['confidence'] ?? null,
            'meta'          => $data['meta'] ?? null,
        ]);

        return response()->json([
            'message' => 'Evento registrado',
            'id' => $event->id,
        ], 201);
    }

       /**
     * GET /api/events
     * Query params opcionales:
     *  - limit (int, 1..100, default 100)
     *  - level (string)
     *  - save  (0|1)
     */
 /**
     * GET /api/events
     * Query params opcionales:
     *  - limit (int, 1..100, default 100)
     *  - level (string)   // critical|warning|normal o red|yellow|green
     *  - save  (0|1)
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'level' => 'sometimes|string',
            'save'  => 'sometimes|in:0,1',
        ]);

        [$events, $statsOverall, $statsFiltered] = $this->service->listLatestWithStats($validated);

        return response()->json([
            'data' => EventResource::collection($events),
            'meta' => [
                // totales globales en toda la tabla (útil para los recuadros del dashboard)
                'stats' => $statsOverall,
                // totales sólo del conjunto filtrado (por si usas ?level=&save=)
               // 'stats_in_result' => $statsFiltered,
            ],
        ]);
    }
}
