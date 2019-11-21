<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public $stripe;

    public function __construct() {
        //Replace this with your own key
        $key = env('STRIPE_SECRET', 'null');
        
        if(is_null($key)) {
            throw new \Exception("You need to set a Stripe key in your env file.");
        }

        Stripe::setApiKey($key);

    }
    public function listAllCustomers(Request $request) {
        $offset = $request->query('offset');
        $customers = Customer::all(['limit' => 50, 'starting_after' => $offset]);

        return view('customers')->with(['customers' => $customers, 'last' => last($customers->data), 'has_more' => $customers->has_more]);
    }

    public function deleteAllSubscriptions(Request $request) {
        $count = 0;
        $customers = Customer::all(['limit' => 10]);
        $hasMore = $customers->has_more;
        $offset = null;
        while($hasMore) {
            $customers = Customer::all(['limit' => 10, 'starting_after' => $offset]);
            $count = $count + $this->deleteSubs($customers);
            $hasMore = $customers->has_more;
            $last = last($customers->data);
            if(!empty($last)) {
                $offset = $last->id;
            }
        }

        Session::flash('status', "Deleted " . $count . " subscriptions.");

        return redirect('/customers');
    }

    private function deleteSubs($customers) {
        $i = 0;
        foreach($customers as $customer) {
            foreach($customer->subscriptions as $subscription) {
                try {
                    $subscriptionObject = Subscription::retrieve($subscription->id);

                    //The cancelation will be effective immediately, issue a refund to the user for the unused time, and will cause an invoice to be issued
                    $subscriptionObject->delete(['invoice_now' => true, 'prorate' => true]);

                    $i++;
                } catch(\Exception $e) {
                    Log::error("Error deleting " . $subscription->id . " " . $e->getMessage());

                }
            }
        }

        return $i;
    }
}
