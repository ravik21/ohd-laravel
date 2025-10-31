<?php

namespace App\Models\OHD;

use Illuminate\Database\Eloquent\Model;

class TicketProductFormField extends Model
{
    protected $table = 'artime_tickets_products_form_fields';

    protected $connection = 'ohdmysql';

    protected $primaryKey = 'ticket_product_id';

    public $timestamps = false;
}
