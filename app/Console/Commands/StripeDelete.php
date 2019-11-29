<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;

class StripeDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mass delete Stripe subscriptions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        //Replace this with your own key
        $key = env('STRIPE_SECRET', 'null');
    
        if(is_null($key)) {
            throw new \Exception("You need to set a Stripe key in your env file.");
        }

        Stripe::setApiKey($key);
    }

    

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = 0;
        $customers = Customer::all(['limit' => 20]);
        $hasMore = $customers->has_more;
        $offset = null;
        
        while($hasMore) {
            $customers = Customer::all(['limit' => 20, 'starting_after' => $offset]);
            $count = $count + $this->deleteSubs($customers);
            $hasMore = $customers->has_more;
            $last = last($customers->data);
            if(!empty($last)) {
                $offset = $last->id;
            }
        }
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
                    $this->info('Deleting ' . $subscription->id);
                } catch(\Exception $e) {
                    $this->error('Could not delete ' . $subscription->id . " " . $e->getMessage());
                    Log::error("Error deleting " . $subscription->id . " " . $e->getMessage());

                }
            }
        }

        return $i;
    }
}
