<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class CustomersParsingErrors implements FromView
{

    use Exportable;

    public $errors;

    public function __construct($errors = [])
    {
        $this->errors = $errors;
    }

    public function view(): View
    {
        return view('exports.customers-import-errors', [
            'errors' => $this->errors
        ]);
    }
}
