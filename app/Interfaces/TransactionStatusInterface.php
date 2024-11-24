<?php

namespace App\Interfaces;

interface TransactionStatusInterface
{
    const PROCESSING = "Pending";
    const SUCCESSFUL = "Successful";
    const DECLINED = "Declined";
}
