<?php

declare(strict_types=1);

return [
    'list' => [
        'title' => 'Payments',
        'description' => 'Money-in ledger across invoices',
        'create' => 'New payment',
        'date' => 'Date',
        'invoice' => 'Invoice',
        'patient' => 'Patient',
        'amount' => 'Amount',
        'method' => 'Method',
        'reference' => 'Reference',
        'received_by' => 'Received by',
        'empty' => 'No payments found',
    ],
    'filter' => [
        'title' => 'Filters',
        'method' => 'Method',
        'from' => 'From date',
        'to' => 'To date',
        'search' => 'Reference or patient name',
        'apply' => 'Apply',
        'reset' => 'Reset',
        'all' => 'All',
    ],
    'form' => [
        'create_title' => 'New payment',
        'edit_title' => 'Edit payment #:id',
        'invoice' => 'Invoice',
        'amount' => 'Amount',
        'method' => 'Method',
        'paid_at' => 'Paid at',
        'reference' => 'Reference',
        'notes' => 'Notes',
        'save' => 'Save payment',
        'saved' => 'Payment saved; invoice status recalculated.',
    ],
];
