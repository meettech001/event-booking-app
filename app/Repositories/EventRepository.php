<?php
namespace App\Repositories;

use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Events;

class EventRepository
{
    public function create(CreateEventRequest $request): Events
    {
        $data = [
            'country'     => $request->country,
            'capacity'    => $request->capacity,
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description ?? null,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ];
        return Events::create($data);
    }

    public function update(Events $event, UpdateEventRequest $request) : void{

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
    public  function getEventsByCriteria($request): mixed
    {
        $query = Events::select("*");

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
?>