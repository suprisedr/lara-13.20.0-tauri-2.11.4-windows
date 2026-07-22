<?php

namespace App\Enums;

enum StockMovementAction: string
{
    /** Goods received from a supplier — increases quantity_on_hand. */
    case Receive = 'receive';

    /** Goods issued / sold — decreases quantity_on_hand. */
    case Issue = 'issue';

    /** Manual stock-count correction — quantity may be positive or negative. */
    case Adjust = 'adjust';

    /** Goods transferred between locations — recorded for audit; no net quantity change at item level. */
    case Transfer = 'transfer';

    /** IAS 2.9 write-down to net realisable value — no quantity change, records the value reduction. */
    case WriteDown = 'write_down';

    /** IAS 2.33 reversal of a previous write-down when NRV recovers — no quantity change. */
    case ReverseWriteDown = 'reverse_write_down';
}
