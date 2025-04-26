<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendeeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/attendee/register",
     *     summary="Register a new attendee",
     *     tags={"Attendee"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attendee registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You're registered successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="jane@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function register(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:attendee',
        ], [
            'email.required' => 'Email is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'You\'re already registered with given email. Please try with diffrent email.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create Attendee
        $user = Attendee::create([
            'name'     => $request->name,
            'email'    => $request->email,
        ]);

        // Return response
        return response()->json([
            'message' => 'You\'re registered successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/attendee/events",
     *     summary="List events available for attendee with filters",
     *     tags={"Attendee"},
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Search by event title",
     *         required=false,
     *         @OA\Schema(type="string", example="laravel")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="Filter by country (in, uk, usa)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"in", "uk", "usa"}, example="in")
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Start date filter (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-04-12")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="End date filter (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-04-15")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Events fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Event list fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Laravel Conference"),
     *                 @OA\Property(property="country", type="string", example="in"),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2025-04-12 09:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2025-04-12 17:00:00")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function events(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'country' => 'nullable|in:in,uk,usa',
            'start_time' => 'nullable|date_format:Y-m-d',
            'end_time' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $events = Events::getEventsByCriteria($request);

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
}
