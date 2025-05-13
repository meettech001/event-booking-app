<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Events;
use App\Repositories\EventRepository;
use App\Services\BookingService;
use Exception;
use Illuminate\Console\Scheduling\Event as SchedulingEvent;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    protected EventRepository $eventRepo;

    public function __construct(EventRepository $eventRepo)
    {
        $this->eventRepo = $eventRepo;
    }
    /**
     * @OA\Post(
     *     path="/api/events",
     *     summary="List all events for the authenticated user",
     *     tags={"Events"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of events with pagination",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Event list fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Conference"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2025-04-26 10:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2025-04-26 16:00:00"),
     *                     @OA\Property(property="location", type="string", example="New York"),
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No events found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No events found."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */

    public function list(Request $request)
    {
        $events = Events::where('user_id', $request->user()->id)->paginate(10);

        if ($events->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No events found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Event list fetched successfully',
            'data' => $events->items(), // Just the current page's data
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/event/create",
     *     summary="Create a new event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "start_time", "end_time", "capacity"},
     *             @OA\Property(property="title", type="string", example="Tech Summit 2025"),
     *             @OA\Property(property="description", type="string", example="Annual tech summit"),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-04-30 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-04-30 17:00:00"),
     *             @OA\Property(property="capacity", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Event created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Tech Summit 2025"),
     *                 @OA\Property(property="start_time", type="string", format="date-time"),
     *                 @OA\Property(property="end_time", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(@OA\Property(property="errors", type="object"))
     *     )
     * )
     */

    public function create(CreateEventRequest $request)
    {
        $event = $this->eventRepo->create($request);

        return response()->json([
            'message' => 'Event created successfully.',
            'data'    => $event
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/event/update/{id}",
     *     summary="Update an existing event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the event to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "start_time", "end_time"},
     *             @OA\Property(property="title", type="string", example="Updated Tech Summit"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-05-01 09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-05-01 16:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Event updated successfully"),
     *             @OA\Property(property="event", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Updated Tech Summit")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found or unauthorized",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Event not found or unauthorized"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(@OA\Property(property="errors", type="object"))
     *     )
     * )
     */

    public function update(UpdateEventRequest $request, $id)
    {
        $event = Events::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or unauthorized'], 404);
        }
        
        $this->eventRepo->update($event, $request);

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $event,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/event/remove/{id}",
     *     summary="Delete an event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the event to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event deleted successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Event deleted successfully"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found or unauthorized",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Event not found or unauthorized"))
     *     )
     * )
     */

    public function remove(Request $request, $id)
    {
        $event = Events::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or unauthorized'], 404);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/event/show-bookings",
     *     summary="Fetch bookings for an event",
     *     description="Retrieve all bookings for a given event by event_id.",
     *     operationId="showBookings",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_id"},
     *             @OA\Property(property="event_id", type="integer", example=1, description="ID of the event"),
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=201,
     *         description="Bookings fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bookings fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="attendee_id", type="integer", example=5),
     *                 @OA\Property(property="event_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-25T09:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-25T09:00:00Z"),
     *             ))
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or business logic error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="Event not found or unauthorized")
     *         )
     *     )
     * )
     */

    public function showBookings(Request $request, BookingService $bookingService)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'event_id'    => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $booking = $bookingService->getBookings($validator->validated(), $request->user());
        } catch (Exception $e) {
            return response()->json(['success' => false, 'errors' => $e->getMessage()], 422);
        }

        // Return response
        return response()->json([
            'message' => 'Bookings fetched successfully',
            'data' => $booking,
        ], 201);
    }
}
