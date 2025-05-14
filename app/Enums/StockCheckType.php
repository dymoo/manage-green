<?php

namespace App\Enums;

// Basic Enum for Stock Check types
enum StockCheckType: string
{
    case CHECK_IN = 'check_in';
    case CHECK_OUT = 'check_out';
    // Add other types if needed, e.g., AUDIT, TRANSFER
} 