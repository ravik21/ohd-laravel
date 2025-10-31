<?php

namespace App\Actions\OHD\Ticket;

use Illuminate\Support\Facades\DB;

use App\Models\OHD\Ticket;

use Carbon\Carbon;

class PaymentAction
{
    protected $dbConnection = 'ohdmysql';

    public function execute($ticketId)
    {
        $ticket     = Ticket::find($ticketId)->toArray();
        $customer   = $this->customer($ticketId);
        $address    = $this->address($ticketId);

        return $this->filterData($ticket, $customer, $address);
    }

    public function amount($ticketId)
    {
        return DB::connection($this->dbConnection)
            ->table('artime_repair_cost')
            ->where('ticket_id', $ticketId)
            ->sum('maximum_charge');
    }

    public function repairItems($ticketId)
    {
        return DB::connection($this->dbConnection)
            ->table('artime_repair_cost as rc')
            ->leftJoin('artime_users as us', 'rc.user_id', '=', 'us.user_id')
            ->where('rc.ticket_id', $ticketId)
            ->orderBy('rc.repair_cost_id')
            ->select(
                'rc.repair_cost_id',
                'rc.item',
                'rc.minimum_charge',
                'rc.maximum_charge',
                'rc.user_id',
                'us.user_id as user_ref_id',
                'rc.ticket_id',
                'rc.date',
                'us.user_name'
            )
            ->get()
            ->map(function ($item) {
                $item->date = $item->date
                    ? Carbon::parse($item->date)->format('d M Y')
                    : null;
                return $item;
            })->toArray();
    }

    protected function customer($ticketId)
    {
        $customer   = DB::connection($this->dbConnection)
            ->table('artime_tickets as at')
            ->join('artime_tickets_products_forms_values as atfv', function ($join) {
                $join->on('atfv.ticket_id', '=', 'at.ticket_id')
                    ->on('atfv.ticket_product_id', '=', 'at.ticket_product_id');
            })
            ->join('artime_tickets_products_form_fields as atff', function ($join) {
                $join->on('atff.ticket_field_id', '=', 'atfv.ticket_field_id')
                    ->on('atff.ticket_product_id', '=', 'at.ticket_product_id');
            })
            ->where('at.ticket_id', $ticketId)
            ->whereIn('atff.ticket_field_caption', ['Kite make', 'Kite model', 'Kite size'])
            ->select('at.ticket_product_id', 'at.ticket_id', 'atfv.*', 'atff.*')
            ->get();

        return $customer->pluck('ticket_field_value')->filter()->values()->toArray();
    }

    protected function address($ticketId)
    {
        $address = DB::connection($this->dbConnection)
            ->table('artime_tickets as at')
            ->join('artime_tickets_products_forms_values as atfv', function ($join) {
                $join->on('atfv.ticket_id', '=', 'at.ticket_id')
                    ->on('atfv.ticket_product_id', '=', 'at.ticket_product_id');
            })
            ->join('artime_tickets_products_form_fields as atff', function ($join) {
                $join->on('atff.ticket_field_id', '=', 'atfv.ticket_field_id')
                    ->on('atff.ticket_product_id', '=', 'at.ticket_product_id');
            })
            ->where('at.ticket_id', $ticketId)
            ->whereIn('atff.ticket_field_caption', [
                'Company or Name',
                'Attention',
                'Street',
                'Room/Floor/Address 2',
                'Department/Address 3',
                'City',
                'State',
                'Zip code',
                'Country',
            ])
            ->select('at.ticket_product_id', 'at.ticket_id', 'atfv.*', 'atff.*')
            ->get();

        return $address->pluck('ticket_field_value')->filter()->values()->toArray();
    }

    protected function filterData($ticket, $customer, $address)
    {
        $data = [
            'ticket_num' => $ticket['ticket_num'],
            'customer_name' => $ticket['customer_name'],
            'customer_email' => $ticket['customer_email'],
            'customer_phone' => $ticket['customer_phone'],
            'description' => $ticket['description'],
            'customer_info' => [
                'customer_name' => $ticket['customer_name'],
                'kite_make' => isset($customer[0]) ? $customer[0] : '',
                'kite_model' => isset($customer[1]) ? $customer[1] : '',
                'kite_size' => isset($customer[2]) ? $customer[2] : '',
            ],
            'shipping_address' => [
                'line1' => isset($address[0]) ? $address[0] : '',
                'line2' => isset($address[1]) ? $address[1] : '',
                'city' => isset($address[2]) ? $address[2] : '',
                'state' => isset($address[3]) ? $address[3] : '',
                'postal_code' => isset($address[4]) ? $address[4] : '',
                'country' => isset($address[5]) ? $address[5] : '',
            ],
            'billing_address' => [
                'line1' => isset($address[0]) ? $address[0] : '',
                'line2' => isset($address[1]) ? $address[1] : '',
                'city' => isset($address[2]) ? $address[2] : '',
                'state' => isset($address[3]) ? $address[3] : '',
                'postal_code' => isset($address[4]) ? $address[4] : '',
                'country' => isset($address[5]) ? $address[5] : '',
            ]
        ];

        return [
            'ticket_id' => $ticket['ticket_id'],
            'ticket_num' => $data['ticket_num'],
            'email' => $data['customer_email'],
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'address' => [
                'line1' => $data['billing_address']['line1'],
                'line2' => $data['billing_address']['line2'],
                'city' => $data['billing_address']['city'],
                'state' => $data['billing_address']['state'],
                'postal_code' => $data['billing_address']['postal_code'],
                'country' => $data['billing_address']['country'],
            ],
            'shipping' => [
                'address' => [
                    'line1' => $data['shipping_address']['line1'],
                    'line2' => $data['shipping_address']['line2'],
                    'city' => $data['shipping_address']['city'],
                    'state' => $data['shipping_address']['state'],
                    'postal_code' => $data['shipping_address']['postal_code'],
                    'country' => $data['shipping_address']['country'],
                ],
                'name' => $data['customer_name']
            ],
        ];
    }
}
