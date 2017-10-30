<?php

namespace Igniter\Flame\Currency\Console;

use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from an online source';

    /**
     * Currency instance
     *
     * @var \Igniter\Flame\Currency\Currency
     */
    protected $currency;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->currency = app('currency');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get Settings
        $defaultCurrency = $this->currency->config('default');

        $this->updateFromYahoo($defaultCurrency);
    }

    protected function updateFromYahoo($defaultCurrency)
    {
        $this->comment('Updating currency exchange rates from Finance Yahoo...');

        $data = [];

        // Get all currencies
        foreach ($this->currency->getDriver()->all() as $code => $value) {
            $data[] = "{$defaultCurrency}{$code}=X";
        }

        // Ask Yahoo for exchange rate
        if ($data) {
            $content = $this->request('http://download.finance.yahoo.com/d/quotes.csv?s='.implode(',', $data).'&f=sl1&e=.csv');

            $lines = explode("\n", trim($content));

            // Update each rate
            foreach ($lines as $line) {
                $code = substr($line, 4, 3);
                $value = substr($line, 11, 6) * 1.00;

                if ($value) {
                    $this->currency->getDriver()->update($code, [
                        'currency_rate' => $value,
                    ]);
                }
            }

            // Clear old cache
            $this->call('currency:cleanup');
        }

        $this->info('Complete');
    }

    protected function request($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_MAXCONNECTS, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}