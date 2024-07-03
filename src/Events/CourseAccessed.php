<?php

namespace Ctrlweb\BadgeFactor2\Events;

use App\Models\User;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CourseAccessed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public User $user, public Course $course, public ?Carbon $when = null)
    {
        if ($this->when == null)
        {
            $this->when = Carbon::now();
        }
    }
}
