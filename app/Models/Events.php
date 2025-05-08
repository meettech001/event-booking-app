<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'country',
        'capacity',
    ];

    public function bookings()
    {
        return $this->hasMany(Bookings::class, 'event_id');
    }

    /**
     * Update event
     */
    public static function updateEvent($event, $request): void
    {
        if ($request->user_id) {
            $event->user_id = $request->user_id;
        }
        if ($request->title) {
            $event->title = $request->title;
        }
        if ($request->description) {
            $event->description = $request->description;
        }
        if ($request->start_time) {
            $event->start_time = $request->start_time;
        }
        if ($request->end_time) {
            $event->end_time = $request->end_time;
        }
        if ($request->country) {
            $event->country = $request->country;
        }
        if ($request->capacity) {
            $event->capacity = $request->capacity;
        }
        $event->save();
    }

    /**
     * Get events based on search filters
     */
    public static function getEventsByCriteria($request): mixed
    {
        $query = self::select("*");

        if ($request->title != "") {
            $query->whereLike("title", "%" . $request->title . "%");
        }
        if ($request->country != "") {
            $query->where("country", $request->country);
        }
        if ($request->start_time != "") {
            $query->whereDate("start_time", '>=', $request->start_time);
        }
        if ($request->end_time != "") {
            $query->whereDate("end_time", '<=', $request->end_time);
        }

        $records  = $query->paginate(10);

        return $records;
    }
}
