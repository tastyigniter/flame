<?php

namespace Igniter\Flame\Currency\Middleware;

use Closure;
use Illuminate\Http\Request;

class CurrencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Don't redirect the console
        if (app()->runningInConsole()) {
            return $next($request);
        }

        // Check for a user defined currency
        if (($currency = $this->getUserCurrency($request)) === null) {
            $currency = $this->getDefaultCurrency();
        }

        // Set user currency
        if ($currency !== currency()->getUserCurrency())
            $this->setUserCurrency($currency, $request);

        return $next($request);
    }

    /**
     * Get the user selected currency.
     *
     * @param Request $request
     *
     * @return string|null
     */
    protected function getUserCurrency(Request $request)
    {
        // Check request for currency
        $currency = $request->get('currency');
        if ($currency && currency()->isActive($currency) === true) {
            return $currency;
        }

        // Get currency from session
        $currency = $request->getSession()->get('igniter.currency');
        if ($currency && currency()->isActive($currency) === true) {
            return $currency;
        }

        return null;
    }

    /**
     * Get the application default currency.
     *
     * @return string
     */
    protected function getDefaultCurrency()
    {
        return currency()->config('default');
    }

    /**
     * Set the user currency.
     *
     * @param string $currency
     * @param Request $request
     *
     * @return string
     */
    private function setUserCurrency($currency, $request)
    {
        // Set user selection globally
        currency()->setUserCurrency($currency = strtoupper($currency));

        // Save it for later too!
        $request->getSession()->put(['igniter.currency' => $currency]);

        return $currency;
    }
}
