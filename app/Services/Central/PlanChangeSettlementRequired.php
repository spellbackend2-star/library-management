<?php

namespace App\Services\Central;

use App\Models\CentralInvoice;
use RuntimeException;

class PlanChangeSettlementRequired extends RuntimeException
{
    public function __construct(public CentralInvoice $invoice)
    {
        parent::__construct(
            'Pay the outstanding balance on the current plan before changing plans.'
        );
    }
}
