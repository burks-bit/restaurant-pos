<?php

namespace Tests\Feature;

use App\Models\SalesLedger;
use App\Services\OrderService;
use Tests\TestCase;

class OrderServiceEntryTypeTest extends TestCase
{
    public function test_it_uses_reservation_deposit_when_reservation_fee_is_used_without_table_session(): void
    {
        $service = new OrderService();

        $entryType = $service->resolveSalesLedgerEntryType(5, null, 100);

        $this->assertSame(SalesLedger::TYPE_RESERVATION_DEPOSIT, $entryType);
    }

    public function test_it_uses_dine_in_payment_for_regular_dine_in_orders(): void
    {
        $service = new OrderService();

        $entryType = $service->resolveSalesLedgerEntryType(5, 10, 0);

        $this->assertSame(SalesLedger::TYPE_DINE_IN_PAYMENT, $entryType);
    }

    public function test_it_uses_dine_in_payment_when_reservation_fee_is_used_with_table_session(): void
    {
        $service = new OrderService();

        $entryType = $service->resolveSalesLedgerEntryType(5, 10, 100);

        $this->assertSame(SalesLedger::TYPE_DINE_IN_PAYMENT, $entryType);
    }
}
