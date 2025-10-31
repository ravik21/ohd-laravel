<?php

namespace App\Models\OHD;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'artime_tickets';

    protected $connection = 'ohdmysql';

    protected $primaryKey = 'ticket_id';

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'creator_user_id', 'user_id');
    }

    public function formValues()
    {
        return $this->hasMany(TicketProductFormValue::class, 'ticket_id', 'ticket_id');
    }
}
