<?php

namespace App\Observers;

use App\Models\Customer;
use App\Supports\Code;

class CustomerObserver
{
    public function creating(Customer $c): void
    {
        if (empty($c->code)) {
            $c->code = Code::customer();
        }
        $c->code = strtoupper(trim((string) $c->code));
    }
}
