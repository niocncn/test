<?php

namespace App\Console\Commands;

use App\Exports\CustomersParsingErrors;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class Customers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:customers';

    protected $errors = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate customers from CSV  to DB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getIso3CountriesArray()
    {
        $countries = Http::get('http://country.io/names.json')->json();
        $isoToIso3 = Http::get('http://country.io/iso3.json')->json();

        $resultArray = [];

        foreach (array_flip($countries) as $key => $country){
            $resultArray[$key] = $isoToIso3[$country];
        }

        return $resultArray;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isoCodes = $this->getIso3CountriesArray();

        $cusomersInputFile = file_get_contents(public_path('random.csv'));

        $strings = explode("\r\n",$cusomersInputFile);

        $keys = explode(',',$strings[0]);

        foreach (array_slice($strings,1) as $customerString){

            $keyedCustomerArray = array_combine($keys,explode(',',$customerString));

            $keyedCustomerArray['age'] = (int) filter_var($keyedCustomerArray['age'], FILTER_SANITIZE_NUMBER_INT);

            $validate = Validator::make($keyedCustomerArray, [
                'email' => 'required|email:rfc,dns|unique:App\Models\Customer,email',
                'age' => 'required|numeric|min:18|max:99'
            ]);

            $explName = explode(' ',$keyedCustomerArray['name']);

            $keyedCustomerArray['name'] = Arr::get($explName,'0','');
            $keyedCustomerArray['surname'] = Arr::get($explName,'1','');

            if($validate->fails()){
                $this->errors[$customerString] = $validate->errors()->messages();

                continue;
            }else{
                $loc = $keyedCustomerArray['location'];

                if(empty($loc) || ! isset($isoCodes[$loc])){
                    $keyedCustomerArray['location'] = 'Unknown';

                }else{
                    $keyedCustomerArray['country_code'] = $isoCodes[$loc];
                }

                (new Customer($keyedCustomerArray))->save();

            }
        }

        if(! empty($this->errors)){
            $fileName = 'CustomersReport-' . now()->format('c') . '.xls';
            (new CustomersParsingErrors($this->errors))->store($fileName,'public');
            $this->info('Done! Got some errors here.');
            $this->info('Look for the report in ./storage/app/public');
        }else{
            $this->info('Done! No Errors');
        }

        return 0;
    }
}
