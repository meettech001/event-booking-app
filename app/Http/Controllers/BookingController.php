<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/booking/book-event",
     *     summary="Book an event for an attendee",
     *     tags={"Bookings"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"attendee_id", "event_id"},
     *             @OA\Property(property="attendee_id", type="integer", example=5),
     *             @OA\Property(property="event_id", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successfully booked the event",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You're booked event successfully"),
     *             @OA\Property(property="booking", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="attendee_id", type="integer", example=5),
     *                 @OA\Property(property="event_id", type="integer", example=10),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-26 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or business rule violation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="You have already booked this event")
     *         )
     *     )
     * )
     */

    public function bookEvent(BookingRequest $request, BookingService $bookingService)
    {

        try {
            $booking = $bookingService->book($request->validated());
        } catch (Exception $e) {
            return response()->json(['success' => false, 'errors' => $e->getMessage()], 422);
        }

        // Return response
        return response()->json([
            'message' => 'You\'re booked event successfully',
            'booking' => $booking,
        ], 201);
    }
}
