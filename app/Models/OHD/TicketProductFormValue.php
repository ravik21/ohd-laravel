<?php

namespace App\Models\OHD;

use Illuminate\Database\Eloquent\Model;

class TicketProductFormValue extends Model
{
    protected $table = 'artime_tickets_products_forms_values';

    protected $connection = 'ohdmysql';

    protected $primaryKey = 'ticket_id';

    public $timestamps = false;

    public function field()
    {
        return $this->belongsTo(TicketProductFormField::class, 'ticket_field_id', 'ticket_field_id');
    }
}
