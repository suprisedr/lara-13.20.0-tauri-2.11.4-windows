<?php

namespace App\Enums;

enum InventoryType: string
{
    case RawMaterial = 'raw_material';
    case Wip = 'wip';
    case FinishedGoods = 'finished_goods';
    case Merchandise = 'merchandise';
    case Consumable = 'consumable';
}
